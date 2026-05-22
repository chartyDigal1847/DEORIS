<?php

namespace App\Listeners;

use App\Events\EcosystemEventReceived;
use App\Jobs\ProcessEcosystemEvent;

class QueueEcosystemEvent
{
    public function handle(EcosystemEventReceived $event): void
    {
        ProcessEcosystemEvent::dispatch($event->event);
    }
}
