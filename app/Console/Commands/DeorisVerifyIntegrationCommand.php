<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DeorisVerifyIntegrationCommand extends Command
{
    protected $signature = 'deoris:verify-integration';

    protected $description = 'Verify DEORIS portal is ready to receive EntryEase ecosystem events';

    public function handle(): int
    {
        $this->info('DEORIS Portal — integration verification');
        $this->newLine();

        $allowed = config('deoris_events.allowed_events', []);
        $required = [
            'ApplicationSubmitted',
            'ApplicationStatusChanged',
            'AdmissionApproved',
            'AdmissionRejected',
            'ExamAssigned',
            'ExamCompleted',
            'ExamScoreReleased',
        ];

        foreach ($required as $event) {
            $ok = in_array($event, $allowed, true);
            $this->line(sprintf('  [%s] allowed_events: %s', $ok ? 'OK' : '!!', $event));
        }

        $secret = config('deoris_events.modules.EntryEase.secret');
        $this->line(sprintf('  [%s] ENTRYEASE_EVENT_SECRET', filled($secret) ? 'OK' : '!!'));

        $officers = User::query()->whereIn('role', [User::ROLE_ADMISSION_OFFICER, User::ROLE_ADMIN])->count();
        $this->line(sprintf('  [%s] Admission staff users in DB (%d)', $officers > 0 ? 'OK' : '!!', $officers));

        $this->newLine();
        $this->line('Ingest URL: '.url('/api/events'));
        $this->line('Redis channel: '.config('deoris_events.redis_channel'));

        if (! filled($secret)) {
            $this->error('Set ENTRYEASE_EVENT_SECRET in .env');

            return self::FAILURE;
        }

        $this->info('Portal is configured for EntryEase notifications.');

        return self::SUCCESS;
    }
}
