<?php

namespace App\Console\Commands;

use App\Events\EcosystemEventReceived;
use App\Services\EventHub\EventLogService;
use App\Services\EventHub\EventValidator;
use App\Services\EventHub\RedisEventVerifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Throwable;

class ListenForModuleEvents extends Command
{
    protected $signature = 'deoris:events:listen {--channel= : Redis channel override}';

    protected $description = 'Subscribe to the shared Redis Pub/Sub channel and queue module events for portal processing.';

    public function handle(EventValidator $validator, EventLogService $logs, RedisEventVerifier $verifier): int
    {
        $channel = $this->option('channel') ?: config('deoris_events.redis_channel', 'deoris.events');

        $this->info("Listening for DEORIS module events on Redis channel [{$channel}].");

        Redis::connection('pubsub')->subscribe([$channel], function (string $message) use ($validator, $logs, $verifier): void {
            try {
                $event = $verifier->verifyAndUnwrap($message);
                $validator->validate($event);
                $logs->received($event);

                EcosystemEventReceived::dispatch($event->toArray());
                $this->line("Queued {$event->name} from {$event->sourceModule} ({$event->id}).");
            } catch (Throwable $exception) {
                report($exception);
                $this->error('Rejected Redis event: '.$exception->getMessage());
            }
        });

        return self::SUCCESS;
    }
}
