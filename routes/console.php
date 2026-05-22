<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── DEORIS scheduled tasks ────────────────────────────────────────────────────

// Poll all registered service health endpoints every minute.
// Updates service_registry.status and health_ok for the admin dashboard.
Schedule::command('deoris:services:health-check')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Flush the admin stats cache every 5 minutes so the dashboard stays fresh
// even when no requests are coming in.
Schedule::call(function () {
    \Illuminate\Support\Facades\Cache::forget('deoris:admin-stats');
})->everyFiveMinutes()->name('flush-admin-stats-cache');
