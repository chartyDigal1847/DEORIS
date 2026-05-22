<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ╔════════════════════════════════════════════════════════════════════════╗
 * ║ SECURITY CRITICAL: SSO SECURITY HEADERS                               ║
 * ║                                                                        ║
 * ║ Adds defense-in-depth headers to prevent:                             ║
 * ║  • SSO tokens being cached in HTTP caches or CDNs                     ║
 * ║  • Token leakage via Referer header to external sites                 ║
 * ║  • Token storage in browser cache (must come from memory only)        ║
 * ║  • Man-in-the-middle downgrades from HTTPS to HTTP                    ║
 * ║  • Framing attacks (clickjacking)                                     ║
 * ║                                                                        ║
 * ║ Applied to all SSO endpoints (/api/v1/sso/*)                          ║
 * ╚════════════════════════════════════════════════════════════════════════╝
 */
class EnforceSsoSecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // ── Cache-Control: Do not cache SSO tokens in HTTP caches ──────────────
        // Prevents intermediaries (CDNs, proxies, browsers) from caching tokens.
        // 'private' means only browser cache (if at all).
        // 'no-store' means don't store this response anywhere.
        // 'must-revalidate' means always check server (don't use stale cache).
        $response->headers->set('Cache-Control', 'private, no-store, must-revalidate, no-cache');

        // ── Pragma: Legacy HTTP/1.0 cache control (for old intermediaries) ──────
        $response->headers->set('Pragma', 'no-cache');

        // ── Expires: Legacy cache expiry (set to past date = don't cache) ──────
        $response->headers->set('Expires', '0');

        // ── Referrer-Policy: Do not send token-bearing URL as Referer to external sites ──
        // 'strict-origin-when-cross-origin' means:
        //   - Same-site: send full Referer
        //   - Cross-site: send only origin (no path/query with potential token)
        // 'no-referrer' would work too but breaks legitimate analytics.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // ── X-Content-Type-Options: Prevent MIME sniffing ────────────────────────
        // Forces browser to respect Content-Type. Prevents a response served as
        // text/plain or HTML from being executed as JavaScript.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // ── X-Frame-Options: Prevent clickjacking (legacy, CSP frame-ancestors is modern) ──
        // 'SAMEORIGIN' means this response can only be framed by same-origin pages.
        // An SSO endpoint should never be framed anyway, but defense-in-depth.
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // ── Strict-Transport-Security: Enforce HTTPS (prevent downgrade to HTTP) ──
        // 'max-age=31536000' = 1 year; after first HTTPS response, always use HTTPS.
        // 'includeSubDomains' means all subdomains must use HTTPS too.
        // 'preload' submits this domain to HSTS preload list (browser hardcodes HTTPS).
        $response->headers->set(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains; preload'
        );

        // ── X-XSS-Protection: Legacy XSS filter (modern browsers ignore this) ──────
        // 'mode=block' tells older IE/browsers to block the response if XSS detected.
        // Modern browsers use CSP instead, but legacy clients may still benefit.
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // ── X-Permitted-Cross-Domain-Policies: Prevent Adobe Flash crossdomain access ──
        // 'none' means no crossdomain.xml policy. Flash is deprecated but old systems
        // may still check this header. Prevents tokens from being stolen by Flash.
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // ── Permissions-Policy: Disable dangerous browser features ────────────────
        // Prevents scripts from accessing:
        //   - camera, microphone, geolocation (user privacy)
        //   - payment API, USB (security)
        // This is a modern replacement for Feature-Policy.
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
        );

        return $response;
    }
}
