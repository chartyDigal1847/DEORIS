<?php

namespace Deoris\Integration;

use Deoris\Integration\Contracts\EventPublisherInterface;
use Illuminate\Support\ServiceProvider;

class DeorisIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EventPublisherInterface::class, EventPublisher::class);
    }
}
