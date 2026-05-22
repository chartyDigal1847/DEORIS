<?php

namespace Deoris\Integration;

use Closure;
use Deoris\Integration\DTO\EcosystemEvent;
use Illuminate\Support\Facades\Redis;

final readonly class EventSubscriber
{
    public function __construct(private string $redisChannel = 'deoris.events')
    {
    }

    /**
     * @param  Closure(EcosystemEvent): void  $handler
     */
    public function listen(Closure $handler): void
    {
        Redis::connection('pubsub')->subscribe([$this->redisChannel], function (string $message) use ($handler): void {
            $handler(EcosystemEvent::fromArray(json_decode($message, true, flags: JSON_THROW_ON_ERROR)));
        });
    }
}
