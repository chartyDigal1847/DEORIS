<?php

namespace Deoris\Integration\Support;

final class Signature
{
    public static function sign(string $body, string $secret, int $timestamp, string $nonce): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$nonce.'.'.$body, $secret);
    }

    public static function verify(string $body, string $secret, int $timestamp, string $nonce, string $signature): bool
    {
        return hash_equals(self::sign($body, $secret, $timestamp, $nonce), $signature);
    }
}
