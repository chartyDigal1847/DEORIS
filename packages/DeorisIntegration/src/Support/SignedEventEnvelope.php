<?php

namespace Deoris\Integration\Support;

use Deoris\Integration\DTO\EcosystemEvent;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;

final class SignedEventEnvelope
{
    /**
     * @return array{module: string, timestamp: int, nonce: string, signature: string, body: string, event: array<string, mixed>}
     */
    public static function wrap(EcosystemEvent $event, string $secret): array
    {
        $body = json_encode($event->toArray(), JSON_THROW_ON_ERROR);
        $timestamp = time();
        $nonce = (string) Str::uuid();

        return [
            'module' => $event->sourceModule,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'signature' => Signature::sign($body, $secret, $timestamp, $nonce),
            'body' => $body,
            'event' => $event->toArray(),
        ];
    }

    /**
     * @param  callable(string): (?string)  $secretResolver
     *
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public static function unwrap(string $message, callable $secretResolver): EcosystemEvent
    {
        /** @var array<string, mixed> $envelope */
        $envelope = json_decode($message, true, 512, JSON_THROW_ON_ERROR);

        if (! isset($envelope['event'], $envelope['module'], $envelope['timestamp'], $envelope['nonce'], $envelope['signature'], $envelope['body'])) {
            throw new InvalidArgumentException('Redis event envelope is missing required signed fields.');
        }

        $module = (string) $envelope['module'];
        $secret = $secretResolver($module);

        if (! is_string($secret) || $secret === '') {
            throw new InvalidArgumentException("Untrusted module [{$module}] for Redis event.");
        }

        $timestamp = (int) $envelope['timestamp'];
        $nonce = (string) $envelope['nonce'];
        $signature = (string) $envelope['signature'];
        $body = (string) $envelope['body'];

        if (! Signature::verify($body, $secret, $timestamp, $nonce, $signature)) {
            throw new InvalidArgumentException('Invalid Redis event signature.');
        }

        /** @var array<string, mixed> $eventData */
        $eventData = is_array($envelope['event']) ? $envelope['event'] : json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        return EcosystemEvent::fromArray($eventData);
    }

    public static function encode(EcosystemEvent $event, string $secret): string
    {
        return json_encode(self::wrap($event, $secret), JSON_THROW_ON_ERROR);
    }
}
