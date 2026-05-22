<?php

namespace Deoris\Integration\Contracts;

use Deoris\Integration\DTO\EcosystemEvent;
use Deoris\Integration\Events\BaseEvent;

interface EventPublisherInterface
{
    public function publishHttp(BaseEvent|EcosystemEvent $event): void;

    public function publishRedis(BaseEvent|EcosystemEvent $event): void;
}
