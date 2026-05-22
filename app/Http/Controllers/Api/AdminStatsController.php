<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\PortalNotification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-only portal statistics API.
 *
 * Returns live counts for the admin dashboard stat cards.
 * Results are cached for 60 seconds to avoid hammering the DB on every
 * dashboard refresh.
 *
 * The portal is orchestration-only — these stats are portal-database counts
 * (users, event logs, notifications). Module-specific business stats
 * (e.g. tuition totals, grade averages) belong to each module's own API.
 */
class AdminStatsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole(User::ROLE_ADMIN), 403);

        $stats = Cache::remember('deoris:admin-stats', 60, function (): array {
            return [
                'total_students'     => User::query()->where('role', User::ROLE_STUDENT)->count(),
                'total_instructors'  => User::query()->where('role', User::ROLE_INSTRUCTOR)->count(),
                'pending_admissions' => User::query()
                    ->where('role', User::ROLE_STUDENT)
                    ->where('admission_status', User::ADMISSION_PENDING)
                    ->count(),
                'cleared_students'   => User::query()
                    ->where('role', User::ROLE_STUDENT)
                    ->where('clearcheck_passed', true)
                    ->count(),
                'enrolled_students'  => User::query()
                    ->where('role', User::ROLE_STUDENT)
                    ->where('enrollment_status', User::ENROLLMENT_ENROLLED)
                    ->count(),
                'events_today'       => EventLog::query()
                    ->whereDate('received_at', today())
                    ->count(),
                'events_failed'      => EventLog::query()
                    ->where('status', EventLog::STATUS_FAILED)
                    ->count(),
                'unread_notifications' => PortalNotification::query()
                    ->whereNull('read_at')
                    ->count(),
                'total_users'        => User::query()->count(),
            ];
        });

        return response()->json(['data' => $stats]);
    }
}
