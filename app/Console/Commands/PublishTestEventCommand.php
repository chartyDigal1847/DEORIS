<?php

namespace App\Console\Commands;

use App\Models\User;
use Deoris\Integration\EventPublisher;
use Deoris\Integration\Events\StudentEnrolled;
use Illuminate\Console\Command;

class PublishTestEventCommand extends Command
{
    protected $signature = 'deoris:events:publish-test
                            {--user= : Portal user id to notify}
                            {--email= : Student email to match}
                            {--channel=http : Delivery channel: http or redis}';

    protected $description = 'Publish a signed StudentEnrolled test event into the portal event hub.';

    public function handle(): int
    {
        $user = $this->resolveUser();

        $event = new StudentEnrolled('EnrollEase', [
            'user_id' => $user?->id,
            'student_email' => $user?->email ?? $this->option('email'),
            'student_name' => $user?->name ?? 'Test Student',
            'program' => 'BS Information Technology',
        ]);

        $secret = (string) config('deoris_events.modules.EnrollEase.secret', '');
        if ($secret === '') {
            $this->error('Set ENROLLEASE_EVENT_SECRET in .env before publishing test events.');

            return self::FAILURE;
        }

        $publisher = new EventPublisher(
            portalUrl: (string) config('app.url'),
            moduleSecret: $secret,
            redisChannel: (string) config('deoris_events.redis_channel', 'deoris.events'),
        );

        $channel = strtolower((string) $this->option('channel'));

        if ($channel === 'redis') {
            $publisher->publishRedis($event);
        } else {
            $publisher->publishHttp($event);
        }

        $ecosystem = $event->toEcosystemEvent();

        $this->info("Published StudentEnrolled via {$channel}.");
        $this->line("Event ID: {$ecosystem->id}");
        $this->line("Correlation ID: {$ecosystem->correlationId}");

        return self::SUCCESS;
    }

    private function resolveUser(): ?User
    {
        if ($this->option('user')) {
            return User::query()->find($this->option('user'));
        }

        if ($email = $this->option('email')) {
            return User::query()->where('email', $email)->first();
        }

        return User::query()->where('role', User::ROLE_STUDENT)->first()
            ?? User::query()->where('role', User::ROLE_ADMIN)->first();
    }
}
