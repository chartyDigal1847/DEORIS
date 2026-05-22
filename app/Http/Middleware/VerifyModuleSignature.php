<?php

namespace App\Http\Middleware;

use App\Services\EventHub\TrustedModuleRegistry;
use Closure;
use Deoris\Integration\Support\Signature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class VerifyModuleSignature
{
    public function __construct(private readonly TrustedModuleRegistry $modules)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $module = (string) $request->header('X-DEORIS-Module');
        $timestamp = (int) $request->header('X-DEORIS-Timestamp');
        $nonce = (string) $request->header('X-DEORIS-Nonce');
        $signature = (string) $request->header('X-DEORIS-Signature');
        $secret = $this->modules->secretFor($module);
        $window = (int) config('deoris_events.replay_window_seconds', 300);

        if (! $secret || ! $timestamp || $nonce === '' || $signature === '') {
            return response()->json(['message' => 'Missing or untrusted DEORIS event signature.'], 401);
        }

        if (abs(time() - $timestamp) > $window) {
            return response()->json(['message' => 'Event signature timestamp is outside the replay window.'], 401);
        }

        $nonceKey = "deoris:event-nonce:{$module}:{$nonce}";
        if (! Cache::add($nonceKey, true, $window)) {
            return response()->json(['message' => 'Duplicate event nonce rejected.'], 409);
        }

        if (! Signature::verify($request->getContent(), $secret, $timestamp, $nonce, $signature)) {
            return response()->json(['message' => 'Invalid DEORIS event signature.'], 401);
        }

        return $next($request);
    }
}
