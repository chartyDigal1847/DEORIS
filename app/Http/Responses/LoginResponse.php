<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * After a successful login, always send the user to the portal home.
     *
     * Modules are loaded as iframes inside the portal shell — they never
     * own the top-level navigation.  Redirecting back to a module origin
     * after login is therefore unnecessary and would break the shell layout.
     *
     * The module's module-bridge.js will automatically retry REQUEST_SSO
     * once the portal shell reloads with an active session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        return redirect()->intended(config('fortify.home', '/homepage'));
    }
}
