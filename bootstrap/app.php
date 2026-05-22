<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureTwoFactorAuthenticationIsConfirmed;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\ForceIframeSsoSessionCookies;
use App\Http\Middleware\PortalCspMiddleware;
use App\Http\Middleware\EnforceSsoSecurityHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        channels: __DIR__.'/../routes/channels.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'             => EnsureUserHasRole::class,
            'module.access'    => EnsureModuleAccess::class,
            'two-factor.confirmed' => EnsureTwoFactorAuthenticationIsConfirmed::class,
            'module.signature' => \App\Http\Middleware\VerifyModuleSignature::class,
            'sso.secure'       => EnforceSsoSecurityHeaders::class,
        ]);

        // Iframe SSO is an API handshake, not a web form submission. These
        // routes return JSON only and use Sanctum session or single-use bearer
        // tokens, so requiring a Blade CSRF token would create iframe 419s.
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // DEORIS iframe SSO cannot rely on Sanctum's conditional frontend
        // detector because some iframe/browser combinations omit Origin or
        // Referer. Start the API session unconditionally so /api/sso/check and
        // /api/sso/token always see the portal's encrypted session cookie.
        $middleware->appendToGroup('api', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            ForceIframeSsoSessionCookies::class,
        ]);

        $middleware->appendToGroup('web', [
            ForceIframeSsoSessionCookies::class,
            PortalCspMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Always return JSON for API routes and requests that expect it.
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e): bool {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
