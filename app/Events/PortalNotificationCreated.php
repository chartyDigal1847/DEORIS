<?php

namespace App\Events;

use App\Models\PortalNotification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PortalNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public PortalNotification $notification, public int $unreadCount)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.'.$this->notification->notifiable_id.'.notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'notification' => [
                'id' => $this->notification->id,
                'title' => $this->notification->title,
                'body' => $this->notification->body,
                'type' => $this->notification->type,
                'source_module' => $this->notification->source_module,
                'event_name' => $this->notification->event_name,
                'action_url' => $this->notification->action_url,
                'created_at' => $this->notification->created_at?->toAtomString(),
            ],
            'unread_count' => $this->unreadCount,
        ];
    }
}
