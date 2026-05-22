<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class ForceIframeSsoSessionCookies
{
    /**
     * Previous cookie names/domains that can collide with the corrected
     * .deoris.test SSO cookie in real browsers.
     *
     * @var array<int, string>
     */
    private const LEGACY_SESSION_COOKIES = [
        'deoris_portal_session',
        'laravel_session',
    ];

    /**
     * Sanctum's stateful middleware intentionally defaults session.same_site to
     * "lax" for normal SPAs. DEORIS embeds first-party modules in HTTPS iframes,
     * so the portal session cookie must remain SameSite=None; Secure.
     */
    public function handle(Request $request, Closure $next): Response
    {
        config([
            'session.domain' => config('session.domain') ?: '.deoris.test',
            'session.http_only' => true,
            'session.same_site' => 'none',
            'session.secure' => true,
        ]);

        $response = $next($request);

        foreach (self::LEGACY_SESSION_COOKIES as $cookieName) {
            $response->headers->setCookie(Cookie::forget($cookieName, '/'));
            $response->headers->setCookie(Cookie::forget($cookieName, '/', '.deoris.test'));
        }

        return $response;
    }
}
