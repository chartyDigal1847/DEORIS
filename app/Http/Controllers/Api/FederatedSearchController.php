<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Search\FederatedSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FederatedSearchController extends Controller
{
    public function __invoke(Request $request, FederatedSearchService $search): JsonResponse
    {
        $validated = $request->validate([
            'q'     => ['required', 'string', 'min:2', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $electionActive = (bool) config('deoris_events.election_active', false);

        // Only search modules the authenticated user is allowed to access.
        $allowedModules = $user->visibleModules($electionActive);

        return response()->json(
            $search->search($validated['q'], (int) ($validated['limit'] ?? 8), $allowedModules),
        );
    }
}
