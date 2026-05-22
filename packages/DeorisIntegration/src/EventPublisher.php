<?php

namespace Deoris\Integration;

use Deoris\Integration\Contracts\EventPublisherInterface;
use Deoris\Integration\DTO\EcosystemEvent;
use Deoris\Integration\Events\BaseEvent;
use Deoris\Integration\Support\Signature;
use Deoris\Integration\Support\SignedEventEnvelope;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

final readonly class EventPublisher implements EventPublisherInterface
{
    public function __construct(
        private string $portalUrl,
        private string $moduleSecret,
        private string $redisChannel = 'deoris.events',
    ) {
    }

    public function publishHttp(BaseEvent|EcosystemEvent $event): void
    {
        $ecosystemEvent = $event instanceof BaseEvent ? $event->toEcosystemEvent() : $event;
        $body = json_encode($ecosystemEvent->toArray(), JSON_THROW_ON_ERROR);
        $timestamp = time();
        $nonce = (string) Str::uuid();

        Http::withHeaders([
            'X-DEORIS-Module' => $ecosystemEvent->sourceModule,
            'X-DEORIS-Timestamp' => (string) $timestamp,
            'X-DEORIS-Nonce' => $nonce,
            'X-DEORIS-Signature' => Signature::sign($body, $this->moduleSecret, $timestamp, $nonce),
        ])->withBody($body, 'application/json')
            ->timeout(5)
            ->retry(2, 200)
            ->post(rtrim($this->portalUrl, '/').'/api/v1/events');
    }

    public function publishRedis(BaseEvent|EcosystemEvent $event): void
    {
        $ecosystemEvent = $event instanceof BaseEvent ? $event->toEcosystemEvent() : $event;

        Redis::connection('pubsub')->publish(
            $this->redisChannel,
            SignedEventEnvelope::encode($ecosystemEvent, $this->moduleSecret),
        );
    }
}
