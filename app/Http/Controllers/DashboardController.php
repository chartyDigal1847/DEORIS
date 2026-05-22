<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ModuleRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ModuleRegistry $modules
    ) {
    }

    /**
     * Redirect /dashboard → /homepage (kept for legacy compatibility).
     */
    public function redirect(Request $request): RedirectResponse
    {
        return match ($request->user()->role) {
            User::ROLE_ADMIN => redirect()->route('admin.dashboard'),
            default          => redirect()->route('homepage'),
        };
    }

    /**
     * Main portal homepage — role-aware dashboard shell.
     */
    public function homepage(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $electionActive = (bool) config('deoris_events.election_active', false);

        return view('homepage', [
            'moduleLinks'    => $this->modules->links(),
            'selectedModule' => 'dashboard',
            'visibleModules' => $user->visibleModules($electionActive),
            'electionActive' => $electionActive,
        ]);
    }

    /**
     * Admin-only dashboard view.
     */
    public function admin(): View
    {
        return view('dashboards.admin');
    }
}
