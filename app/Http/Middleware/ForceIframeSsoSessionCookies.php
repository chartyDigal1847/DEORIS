<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class ForceIframeSsoSessionCookies
{
    private const PORTAL_SESSION_COOKIE = '__Host-deoris_identity_session';

    /**
     * Previous cookie names/domains that can collide with the corrected
     * host-only SSO cookie in real browsers.
     *
     * @var array<int, string>
     */
    private const LEGACY_SESSION_COOKIES = [
        'deoris_identity_session',
        'deoris_portal_session',
        'laravel_session',
    ];

    /**
     * Sanctum's stateful middleware intentionally defaults session.same_site to
     * "lax" for normal SPAs. DEORIS modules fetch back to the portal from HTTPS
     * iframes, so the portal session cookie must remain SameSite=None; Secure.
     *
     * The cookie is host-only and __Host-prefixed so module subdomains cannot
     * overwrite it with their own Set-Cookie headers during fast iframe switches.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ── Pin ALL critical config values from .env ─────────────────────────
        $appKey = $this->readEnvValue(base_path('.env'), 'APP_KEY');
        if ($appKey) {
            config(['app.key' => $appKey]);
        }

        $this->migrateLegacySessionCookie($request);

        config([
            'app.env'              => env('APP_ENV', 'local'),
            'session.driver'       => env('SESSION_DRIVER', 'database'),
            'session.cookie'       => self::PORTAL_SESSION_COOKIE,
            'session.domain'       => null,
            'session.path'         => '/',
            'session.http_only'    => true,
            'session.same_site'    => 'none',
            'session.secure'       => true,
            'broadcasting.default' => env('BROADCAST_CONNECTION', 'reverb'),
        ]);
        $response = $next($request);

        foreach (self::LEGACY_SESSION_COOKIES as $cookieName) {
            $response->headers->setCookie(Cookie::forget($cookieName, '/'));
            $response->headers->setCookie(Cookie::forget($cookieName, '/', '.deoris.test'));
        }

        return $response;
    }

    private function migrateLegacySessionCookie(Request $request): void
    {
        if ($request->cookies->has(self::PORTAL_SESSION_COOKIE)) {
            return;
        }

        foreach (self::LEGACY_SESSION_COOKIES as $cookieName) {
            $legacyValue = $request->cookies->get($cookieName);

            if (is_string($legacyValue) && $legacyValue !== '') {
                $request->cookies->set(self::PORTAL_SESSION_COOKIE, $legacyValue);
                return;
            }
        }
    }

    private function readEnvValue(string $envFile, string $key): ?string
    {
        if (! is_readable($envFile)) return null;
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if ($line === '' || $line[0] === '#') continue;
            $eq = strpos($line, '=');
            if ($eq === false) continue;
            if (trim(substr($line, 0, $eq)) !== $key) continue;
            $val = trim(substr($line, $eq + 1));
            if (strlen($val) >= 2 && $val[0] === '"'  && $val[-1] === '"')  $val = substr($val, 1, -1);
            if (strlen($val) >= 2 && $val[0] === "'"  && $val[-1] === "'")  $val = substr($val, 1, -1);
            return $val;
        }
        return null;
    }
}
