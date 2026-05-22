<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ModuleRegistry;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Centralized API Gateway.
 *
 * Routes authenticated portal requests to independent module services.
 * The portal is orchestration-only — it forwards requests and aggregates
 * responses without containing any business logic.
 *
 * Security:
 *   - Authentication enforced before forwarding (Sanctum session or token)
 *   - Role-based module access checked before forwarding
 *   - Per-user rate limiting (prevents abuse of downstream services)
 *   - Request sanitization (strips internal headers before forwarding)
 *   - Correlation ID injected for distributed tracing
 *   - Timeout enforced (4 s) to prevent slow-service cascades
 */
class ApiGatewayController extends Controller
{
    /**
     * Headers that must never be forwarded to downstream services.
     * These are portal-internal or could be spoofed by the client.
     */
    private const STRIP_HEADERS = [
        'host',
        'authorization',
        'cookie',
        'x-csrf-token',
        'x-xsrf-token',
        'x-forwarded-for',
        'x-forwarded-host',
        'x-forwarded-proto',
        'x-real-ip',
        'x-deoris-module',
        'x-deoris-signature',
        'x-deoris-timestamp',
        'x-deoris-nonce',
    ];

    public function __construct(private readonly ModuleRegistry $modules)
    {
    }

    /**
     * Forward a portal API request to the target module service.
     *
     * Route: /api/v1/gateway/{module}/{path?}
     *
     * The portal injects:
     *   X-Portal-User-Id    — authenticated user ID
     *   X-Portal-User-Role  — authenticated user role
     *   X-Portal-User-Email — authenticated user email
     *   X-Correlation-Id    — distributed tracing ID
     *   Authorization       — Bearer token from module's search_token config
     */
    public function forward(Request $request, string $module, string $path = ''): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // ── Rate limiting: 120 req/min per user across all gateway calls ────
        $rateLimitKey = 'gateway:'.$user->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 120)) {
            return $this->gatewayError('rate_limit_exceeded', 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        // ── Module access check ──────────────────────────────────────────────
        $electionActive = (bool) config('deoris_events.election_active', false);
        if (! $user->canAccessModule($module, $electionActive)) {
            return $this->gatewayError('module_access_denied', 403);
        }

        // ── Resolve module URL ───────────────────────────────────────────────
        $moduleConfig = $this->modules->get($module);
        if (! $moduleConfig) {
            return $this->gatewayError('module_not_found', 404);
        }

        $moduleUrl = env($moduleConfig['env'], $moduleConfig['url']);
        if (! $moduleUrl) {
            return $this->gatewayError('module_unavailable', 503);
        }

        // ── Build target URL ─────────────────────────────────────────────────
        $targetUrl = rtrim($moduleUrl, '/').'/api/v1/'.ltrim($path, '/');
        $correlationId = (string) Str::uuid();

        // ── Build forwarded headers ──────────────────────────────────────────
        $forwardHeaders = $this->buildForwardHeaders($request, $user, $correlationId);

        // ── Forward request ──────────────────────────────────────────────────
        try {
            $method   = strtolower($request->method());
            $response = Http::withHeaders($forwardHeaders)
                ->acceptJson()
                ->timeout(4)
                ->withQueryParameters($request->query())
                ->{$method}($targetUrl, in_array($method, ['post', 'put', 'patch']) ? $request->all() : []);

            return response()->json(
                $response->json() ?? [],
                $response->status(),
                ['X-Correlation-Id' => $correlationId],
            );
        } catch (ConnectionException $e) {
            Log::warning('API gateway connection failed', [
                'module'         => $module,
                'target_url'     => $targetUrl,
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
            ]);

            return $this->gatewayError('service_unavailable', 503, $correlationId);
        } catch (\Throwable $e) {
            Log::error('API gateway unexpected error', [
                'module'         => $module,
                'target_url'     => $targetUrl,
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
            ]);

            return $this->gatewayError('gateway_error', 502, $correlationId);
        }
    }

    /**
     * Build the headers to forward to the downstream service.
     * Strips sensitive portal-internal headers and injects identity context.
     */
    private function buildForwardHeaders(Request $request, User $user, string $correlationId): array
    {
        // Start with safe subset of incoming headers
        $headers = collect($request->headers->all())
            ->mapWithKeys(fn ($values, $key) => [$key => implode(', ', $values)])
            ->reject(fn ($_, $key) => in_array(strtolower($key), self::STRIP_HEADERS, true))
            ->all();

        // Inject portal identity context (modules trust these headers from the portal)
        $headers['X-Portal-User-Id']    = (string) $user->id;
        $headers['X-Portal-User-Role']  = $user->role;
        $headers['X-Portal-User-Email'] = $user->email;
        $headers['X-Correlation-Id']    = $correlationId;
        $headers['X-Forwarded-By']      = 'deoris-portal';

        return $headers;
    }

    private function gatewayError(string $error, int $status, ?string $correlationId = null): JsonResponse
    {
        $payload = ['success' => false, 'error' => $error];

        if ($correlationId) {
            $payload['correlation_id'] = $correlationId;
        }

        return response()->json($payload, $status);
    }
}
