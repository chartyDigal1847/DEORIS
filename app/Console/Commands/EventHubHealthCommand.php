<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class EventHubHealthCommand extends Command
{
    protected $signature = 'deoris:events:health';

    protected $description = 'Check Redis connectivity for the DEORIS event hub.';

    public function handle(): int
    {
        $pong = Redis::connection()->ping();

        $this->info('Redis default connection: '.$pong);
        $this->info('Redis event channel: '.config('deoris_events.redis_channel'));
        $this->info('Queue connection: '.config('queue.default'));
        $this->info('Broadcast connection: '.config('broadcasting.default'));

        return self::SUCCESS;
    }
}
