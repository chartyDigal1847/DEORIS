<?php

namespace App\Providers;

use Deoris\Integration\Contracts\EventPublisherInterface;
use Deoris\Integration\EventPublisher;
use App\Events\EcosystemEventReceived;
use App\Listeners\QueueEcosystemEvent;
use App\Listeners\SyncUserEnrollmentStatus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EventPublisherInterface::class, function () {
            $module = (string) env('DEORIS_MODULE_NAME', 'Portal');
            $secret = (string) env('DEORIS_MODULE_EVENT_SECRET', env('APP_KEY'));

            return new EventPublisher(
                portalUrl: (string) config('app.url'),
                moduleSecret: $secret,
                redisChannel: (string) config('deoris_events.redis_channel', 'deoris.events'),
            );
        });

        $this->app->alias(EventPublisherInterface::class, EventPublisher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(EcosystemEventReceived::class, QueueEcosystemEvent::class);
        Event::listen(EcosystemEventReceived::class, SyncUserEnrollmentStatus::class);
    }
}
