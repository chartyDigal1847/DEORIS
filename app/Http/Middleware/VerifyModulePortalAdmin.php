<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After module.signature: require X-Portal-User-Id of a DEORIS admin.
 */
class VerifyModulePortalAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $portalUserId = $request->header('X-Portal-User-Id');

        if (! $portalUserId) {
            return response()->json(['message' => 'X-Portal-User-Id header is required.'], 401);
        }

        $user = User::query()->find($portalUserId);

        if (! $user || ! $user->isAdmin()) {
            return response()->json(['message' => 'Portal admin privileges required.'], 403);
        }

        $request->attributes->set('portal_actor', $user);

        return $next($request);
    }
}
