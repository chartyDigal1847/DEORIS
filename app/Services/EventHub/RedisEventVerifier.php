<?php

namespace App\Services\EventHub;

use Deoris\Integration\DTO\EcosystemEvent;
use Deoris\Integration\Support\SignedEventEnvelope;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

final class RedisEventVerifier
{
    public function __construct(
        private readonly TrustedModuleRegistry $modules,
    ) {
    }

    public function verifyAndUnwrap(string $message): EcosystemEvent
    {
        $event = SignedEventEnvelope::unwrap(
            $message,
            fn (string $module): ?string => $this->modules->secretFor($module),
        );

        $window = (int) config('deoris_events.replay_window_seconds', 300);

        /** @var array<string, mixed> $envelope */
        $envelope = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        $timestamp = (int) ($envelope['timestamp'] ?? 0);
        $nonce = (string) ($envelope['nonce'] ?? '');
        $module = (string) ($envelope['module'] ?? $event->sourceModule);

        if (abs(time() - $timestamp) > $window) {
            throw new InvalidArgumentException('Redis event timestamp is outside the replay window.');
        }

        $nonceKey = "deoris:event-nonce:{$module}:{$nonce}";
        if (! Cache::add($nonceKey, true, $window)) {
            throw new InvalidArgumentException('Duplicate Redis event nonce rejected.');
        }

        if ($event->sourceModule !== $module) {
            throw new InvalidArgumentException('Event source_module does not match signed module.');
        }

        return $event;
    }
}
