<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PortalNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = PortalNotification::query()
            ->where('notifiable_type', $request->user()::class)
            ->where('notifiable_id', $request->user()->id)
            ->latest()
            ->limit((int) $request->integer('limit', 15))
            ->get();

        return response()->json([
            'unread_count' => $this->unreadFor($request),
            'data' => $notifications,
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->unreadFor($request),
        ]);
    }

    public function markRead(Request $request, PortalNotification $notification): JsonResponse
    {
        abort_unless((int) $notification->notifiable_id === (int) $request->user()->id, 403);

        $notification->forceFill(['read_at' => now()])->save();

        return $this->unreadCount($request);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        PortalNotification::query()
            ->where('notifiable_type', $request->user()::class)
            ->where('notifiable_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }

    private function unreadFor(Request $request): int
    {
        return PortalNotification::query()
            ->where('notifiable_type', $request->user()::class)
            ->where('notifiable_id', $request->user()->id)
            ->unread()
            ->count();
    }
}
