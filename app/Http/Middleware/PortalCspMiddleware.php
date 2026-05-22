<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;

class PortalCspMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        Vite::useCspNonce();

        $response = $next($request);

        $moduleUrls = collect(config('deoris_events.modules', []))
            ->pluck('url')
            ->filter()
            ->toArray();
        
        $portal = rtrim((string) config('app.url', 'https://deoris.test'), '/');

        $frameSrc = implode(' ', array_merge(["'self'"], $moduleUrls));
        $connectSrc = implode(' ', array_filter(array_merge(
            ["'self'", $portal],
            $moduleUrls,
            [config('services.auth.url')]
        )));

        $csp = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "script-src 'self' 'nonce-" . Vite::cspNonce() . "' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "style-src-elem 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "img-src 'self' data: https:",
            "font-src 'self' https://fonts.gstatic.com",
            "connect-src " . $connectSrc,
            "frame-src " . $frameSrc,
            "frame-ancestors 'self'",
            "object-src 'none'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
