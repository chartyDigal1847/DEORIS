<?php

use App\Http\Controllers\Api\EventLogController;
use App\Http\Controllers\Api\FederatedSearchController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\DashboardController;
use App\Models\User;
use App\Services\ModuleRegistry;
use Illuminate\Support\Facades\Route;

// ===========================================================================
// PORTAL DOMAIN — deoris.test
// ===========================================================================
Route::domain('deoris.test')->group(function () {

    // ── Public ──────────────────────────────────────────────────────────────

    // Landing page — guests see marketing; authenticated users stay in portal.
    // Some module logos point at "/", so do not make that feel like logout.
    Route::get('/', function () {
        return auth()->check()
            ? redirect()->route('homepage')
            : view('landing');
    })->name('landing');

    // Login redirect — authenticated users go straight to homepage.
    Route::get('/login-redirect', function () {
        return auth()->check()
            ? redirect()->route('homepage')
            : redirect()->route('login');
    })->name('login.redirect');

    // ── Authenticated portal shell ───────────────────────────────────────────

    Route::middleware(['auth', config('jetstream.auth_session'), 'verified'])
        ->group(function () {

            Route::get('/dashboard', fn () => redirect()->route('homepage'))
                ->name('dashboard');

            Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
                ->middleware('role:' . User::ROLE_ADMIN)
                ->name('admin.dashboard');

            // Main portal homepage — passes user-specific module visibility.
            Route::get('/homepage', [DashboardController::class, 'homepage'])
                ->name('homepage');

            // Portal JSON (web session auth — reliable for same-origin fetch from the shell).
            Route::prefix('portal')->group(function (): void {
                Route::get('/notifications', [NotificationController::class, 'index']);
                Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
                Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
                Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
                Route::get('/search', FederatedSearchController::class);
                Route::get('/event-logs', [EventLogController::class, 'index'])
                    ->middleware('role:' . User::ROLE_ADMIN);
            });

            // Module portal routes — each is guarded by module.access middleware.
            foreach (app(ModuleRegistry::class)->all() as $path => $module) {
                Route::get("/{$path}", function () use ($path) {
                    /** @var \App\Models\User $user */
                    $user = auth()->user();
                    $electionActive = (bool) config('deoris_events.election_active', false);

                    return view('homepage', [
                        'moduleLinks'    => app(ModuleRegistry::class)->links(),
                        'selectedModule' => $path,
                        'visibleModules' => $user->visibleModules($electionActive),
                        'electionActive' => $electionActive,
                    ]);
                })->middleware("module.access:{$path}");
            }

            // Legacy uppercase URLs → clean lowercase equivalents.
            foreach (app(ModuleRegistry::class)->all() as $path => $module) {
                Route::get('/' . $module['legacy'], fn () => redirect("/{$path}"));
            }
        });
});

// ===========================================================================
// MODULE SUBDOMAINS — {module}.deoris.test
//
// Each module is a completely separate Laravel application. This portal only
// serves:
//   - https://deoris.test/module-bridge.js
//   - https://deoris.test/api/sso/token
//   - https://deoris.test/api/sso/check
// ===========================================================================
