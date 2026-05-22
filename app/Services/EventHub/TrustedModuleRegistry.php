<?php

namespace App\Services\EventHub;

final class TrustedModuleRegistry
{
    /**
     * @return array<string, mixed>|null
     */
    public function find(string $module): ?array
    {
        $modules = config('deoris_events.modules', []);

        return $modules[$module] ?? null;
    }

    public function secretFor(string $module): ?string
    {
        $secret = $this->find($module)['secret'] ?? null;

        return is_string($secret) && $secret !== '' ? $secret : null;
    }

    public function isTrusted(string $module): bool
    {
        return $this->secretFor($module) !== null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function modules(): array
    {
        return config('deoris_events.modules', []);
    }
}
