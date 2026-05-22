<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Centralized service registry for the DEORIS SOA ecosystem.
 *
 * Each row represents one independent Laravel service/module.
 * The portal uses this table for:
 *   - API gateway routing
 *   - Health monitoring
 *   - Role-based module visibility
 *   - Service discovery
 */
class ServiceRegistry extends Model
{
    protected $table = 'service_registry';

    public const STATUS_ACTIVE      = 'active';
    public const STATUS_INACTIVE    = 'inactive';
    public const STATUS_DEGRADED    = 'degraded';
    public const STATUS_MAINTENANCE = 'maintenance';

    protected $fillable = [
        'service_key',
        'label',
        'url',
        'api_version',
        'status',
        'allowed_roles',
        'environment_config',
        'health_check_url',
        'last_health_check_at',
        'health_ok',
    ];

    protected function casts(): array
    {
        return [
            'allowed_roles'        => 'array',
            'environment_config'   => 'array',
            'last_health_check_at' => 'datetime',
            'health_ok'            => 'boolean',
        ];
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForRole(Builder $query, string $role): Builder
    {
        return $query->where(function (Builder $q) use ($role): void {
            $q->whereNull('allowed_roles')
              ->orWhereJsonContains('allowed_roles', $role)
              ->orWhereJsonContains('allowed_roles', 'all');
        });
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Use service_key as the route model binding key so routes can use
     * /api/v1/services/entryease instead of /api/v1/services/1
     */
    public function getRouteKeyName(): string
    {
        return 'service_key';
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function allowsRole(string $role): bool
    {
        if (empty($this->allowed_roles)) {
            return true;
        }

        return in_array($role, $this->allowed_roles, true)
            || in_array('all', $this->allowed_roles, true);
    }

    /**
     * Build the full API URL for a given path on this service.
     */
    public function apiUrl(string $path = ''): string
    {
        $base = rtrim($this->url, '/').'/api/'.$this->api_version;

        return $path !== '' ? $base.'/'.ltrim($path, '/') : $base;
    }
}
