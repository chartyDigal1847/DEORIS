<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckEnrollmentSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enrollment:check-sync {email? : Email of user to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check enrollment sync status from EnrollEase to DEORIS for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        if (!$email) {
            // Show all users with enrollment sync data
            $users = User::whereNotNull('enrollease_enrollment_id')
                ->orderByDesc('enrollment_status_synced_at')
                ->get();

            if ($users->isEmpty()) {
                $this->info('No users with enrollment sync data found.');
                return;
            }

            $this->info("Found {$users->count()} users with enrollment sync data:\n");

            foreach ($users as $user) {
                $this->displayUserSyncStatus($user);
            }
        } else {
            $user = User::where('email', $email)->first();

            if (!$user) {
                $this->error("User with email '{$email}' not found.");
                return;
            }

            $this->displayUserSyncStatus($user);
        }
    }

    private function displayUserSyncStatus(User $user): void
    {
        $this->line('');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("User: {$user->name} ({$user->email})");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->info('Enrollment Status Information:');
        $this->line("  ID: {$user->id}");
        $this->line("  Enrollment Status: <fg=cyan>{$user->enrollment_status}</>");
        $this->line("  Admission Status: <fg=cyan>{$user->admission_status}</>");

        if ($user->enrollease_enrollment_id) {
            $this->info('EnrollEase Reference:');
            $this->line("  EnrollEase Enrollment ID: <fg=yellow>{$user->enrollease_enrollment_id}</>");
            $this->line("  Last Synced At: <fg=yellow>" . ($user->enrollment_status_synced_at?->format('Y-m-d H:i:s') ?? 'N/A') . "</>");
            $this->line("  Previous Status: <fg=yellow>" . ($user->previous_enrollment_status ?? 'N/A') . "</>");
        } else {
            $this->warn('  No EnrollEase sync data');
        }

        $this->line('');
    }
}
