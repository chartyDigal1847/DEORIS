<?php

namespace App\Console\Commands;

use App\Models\ServiceRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Polls each registered service's health_check_url and updates the
 * service_registry table with the result.
 *
 * Schedule this command every minute via the scheduler:
 *   $schedule->command('deoris:services:health-check')->everyMinute()->withoutOverlapping();
 */
class ServiceHealthCheckCommand extends Command
{
    protected $signature = 'deoris:services:health-check
                            {--service= : Check a specific service key only}';

    protected $description = 'Poll all registered service health endpoints and update the service registry.';

    public function handle(): int
    {
        $query = ServiceRegistry::query()
            ->whereNotNull('health_check_url')
            ->where('status', '!=', ServiceRegistry::STATUS_INACTIVE);

        if ($key = $this->option('service')) {
            $query->where('service_key', $key);
        }

        $services = $query->get();

        if ($services->isEmpty()) {
            $this->info('No services with health check URLs found.');
            return self::SUCCESS;
        }

        $this->info("Checking health for {$services->count()} service(s)...");

        foreach ($services as $service) {
            $this->checkService($service);
        }

        return self::SUCCESS;
    }

    private function checkService(ServiceRegistry $service): void
    {
        try {
            $response = Http::timeout(3)->get($service->health_check_url);
            $healthy  = $response->successful();

            $service->update([
                'health_ok'            => $healthy,
                'last_health_check_at' => now(),
                'status'               => $healthy
                    ? ServiceRegistry::STATUS_ACTIVE
                    : ServiceRegistry::STATUS_DEGRADED,
            ]);

            $icon = $healthy ? '✓' : '✗';
            $this->line("  [{$icon}] {$service->label} ({$service->service_key}) — HTTP {$response->status()}");
        } catch (\Throwable $e) {
            $service->update([
                'health_ok'            => false,
                'last_health_check_at' => now(),
                'status'               => ServiceRegistry::STATUS_DEGRADED,
            ]);

            $this->line("  [✗] {$service->label} ({$service->service_key}) — {$e->getMessage()}");
        }
    }
}
