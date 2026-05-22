<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRegistry;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Centralized Service Registry API.
 *
 * Provides service discovery for the DEORIS SOA ecosystem.
 * Read access is available to all authenticated users (role-filtered).
 * Write access is restricted to admins.
 */
class ServiceRegistryController extends Controller
{
    /**
     * List all services visible to the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $services = ServiceRegistry::query()
            ->active()
            ->forRole($user->role)
            ->orderBy('label')
            ->get()
            ->map(fn (ServiceRegistry $s) => $this->serialize($s));

        return response()->json(['data' => $services]);
    }

    /**
     * Show a single service entry (admin only — includes full config).
     */
    public function show(Request $request, ServiceRegistry $service): JsonResponse
    {
        abort_unless($request->user()->hasRole(User::ROLE_ADMIN), 403);

        return response()->json(['data' => $this->serializeFull($service)]);
    }

    /**
     * Register or update a service entry (admin only).
     */
    public function upsert(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole(User::ROLE_ADMIN), 403);

        $validated = $request->validate([
            'service_key'        => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_-]+$/'],
            'label'              => ['required', 'string', 'max:120'],
            'url'                => ['required', 'url', 'max:512'],
            'api_version'        => ['nullable', 'string', 'max:20'],
            'status'             => ['nullable', Rule::in([
                ServiceRegistry::STATUS_ACTIVE,
                ServiceRegistry::STATUS_INACTIVE,
                ServiceRegistry::STATUS_DEGRADED,
                ServiceRegistry::STATUS_MAINTENANCE,
            ])],
            'allowed_roles'      => ['nullable', 'array'],
            'allowed_roles.*'    => ['string', Rule::in(User::roles())],
            'environment_config' => ['nullable', 'array'],
            'health_check_url'   => ['nullable', 'url', 'max:512'],
        ]);

        $service = ServiceRegistry::updateOrCreate(
            ['service_key' => $validated['service_key']],
            array_filter($validated, fn ($v) => $v !== null),
        );

        return response()->json(['data' => $this->serializeFull($service)], $service->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Update service status (admin only — used by health checks).
     */
    public function updateStatus(Request $request, ServiceRegistry $service): JsonResponse
    {
        abort_unless($request->user()->hasRole(User::ROLE_ADMIN), 403);

        $validated = $request->validate([
            'status'   => ['required', Rule::in([
                ServiceRegistry::STATUS_ACTIVE,
                ServiceRegistry::STATUS_INACTIVE,
                ServiceRegistry::STATUS_DEGRADED,
                ServiceRegistry::STATUS_MAINTENANCE,
            ])],
            'health_ok' => ['nullable', 'boolean'],
        ]);

        $service->update([
            'status'               => $validated['status'],
            'health_ok'            => $validated['health_ok'] ?? ($validated['status'] === ServiceRegistry::STATUS_ACTIVE),
            'last_health_check_at' => now(),
        ]);

        return response()->json(['data' => $this->serializeFull($service)]);
    }

    /**
     * Delete a service entry (admin only).
     */
    public function destroy(Request $request, ServiceRegistry $service): JsonResponse
    {
        abort_unless($request->user()->hasRole(User::ROLE_ADMIN), 403);

        $service->delete();

        return response()->json(['deleted' => true]);
    }

    // ── Serializers ──────────────────────────────────────────────────────────

    private function serialize(ServiceRegistry $s): array
    {
        return [
            'service_key' => $s->service_key,
            'label'       => $s->label,
            'url'         => $s->url,
            'api_version' => $s->api_version,
            'status'      => $s->status,
            'health_ok'   => $s->health_ok,
        ];
    }

    private function serializeFull(ServiceRegistry $s): array
    {
        return array_merge($this->serialize($s), [
            'allowed_roles'        => $s->allowed_roles,
            'environment_config'   => $s->environment_config,
            'health_check_url'     => $s->health_check_url,
            'last_health_check_at' => $s->last_health_check_at?->toAtomString(),
            'created_at'           => $s->created_at?->toAtomString(),
            'updated_at'           => $s->updated_at?->toAtomString(),
        ]);
    }
}
