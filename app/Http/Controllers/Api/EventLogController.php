<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use App\Models\User;

class EventLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Only admins can access the event log API.
        abort_unless($request->user()->hasRole(User::ROLE_ADMIN), 403);

        $status = trim((string) $request->query('status', ''));
        $module = trim((string) $request->query('module', ''));

        $logs = EventLog::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($module !== '', fn ($query) => $query->where('source_module', $module))
            ->latest()
            ->paginate((int) $request->integer('per_page', 25));

        return response()->json($logs);
    }
}
