<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorAuthenticationIsConfirmed
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasEnabledTwoFactorAuthentication()) {
            if ($request->expectsJson()) {
                abort(403, 'Two-factor authentication is required.');
            }

            return redirect()
                ->route('profile.show')
                ->with('flash.banner', 'Two-factor authentication is required before you can continue.')
                ->with('flash.bannerStyle', 'danger');
        }

        return $next($request);
    }
}
