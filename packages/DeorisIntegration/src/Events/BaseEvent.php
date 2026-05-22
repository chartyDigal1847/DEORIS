<?php

namespace Deoris\Integration\Events;

use Deoris\Integration\Contracts\EcosystemEventContract;
use Deoris\Integration\DTO\EcosystemEvent;

abstract readonly class BaseEvent implements EcosystemEventContract
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $sourceModule,
        public array $payload,
        public ?string $correlationId = null,
    ) {
    }

    abstract public function name(): string;

    public function toEcosystemEvent(): EcosystemEvent
    {
        return EcosystemEvent::make(
            name: $this->name(),
            sourceModule: $this->sourceModule,
            payload: $this->payload,
            correlationId: $this->correlationId,
        );
    }
}
