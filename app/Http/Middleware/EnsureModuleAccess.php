<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access to a portal module route if the authenticated user does not
 * have that module in their visibleModules() list.
 *
 * Usage in routes:
 *   ->middleware('module.access:entryease')
 *
 * The election_active flag is read from the ELECTION_ACTIVE env variable so
 * it can be toggled without a code deploy.
 */
class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthenticated.');
        }

        $electionActive = (bool) config('deoris_events.election_active', false);

        if (! $user->canAccessModule($moduleKey, $electionActive)) {
            // Return a clean 403 JSON for API requests, redirect for web.
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have access to this module.'], 403);
            }

            return redirect()->route('homepage')->with(
                'error',
                'You do not have permission to access that module.'
            );
        }

        return $next($request);
    }
}
