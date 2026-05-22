<?php

namespace App\Jobs;

use App\Events\EcosystemEventProcessed;
use App\Events\PortalNotificationCreated;
use App\Models\EventLog;
use App\Models\PortalNotification;
use App\Services\EventHub\EventLogService;
use App\Services\EventHub\EventValidator;
use App\Services\EventHub\NotificationFactory;
use Deoris\Integration\DTO\EcosystemEvent;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessEcosystemEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 15;

    /**
     * @param  array<string, mixed>  $event
     */
    public function __construct(public array $event)
    {
        $this->onQueue(config('deoris_events.event_queue', 'events'));
    }

    public function handle(EventValidator $validator, EventLogService $logs, NotificationFactory $notifications): void
    {
        $event = EcosystemEvent::fromArray($this->event);

        if ($logs->isProcessed($event->id)) {
            return;
        }

        $claimed = EventLog::query()
            ->where('event_id', $event->id)
            ->where('status', EventLog::STATUS_RECEIVED)
            ->update(['status' => EventLog::STATUS_PROCESSING]);

        if ($claimed === 0) {
            return;
        }

        $validator->validate($event);

        foreach ($notifications->recipients($event) as $recipient) {
            $content = $notifications->content($event);

            $notification = PortalNotification::create([
                'id' => (string) Str::uuid(),
                'notifiable_type' => $recipient::class,
                'notifiable_id' => $recipient->id,
                'source_module' => $event->sourceModule,
                'event_name' => $event->name,
                'type' => $content['type'],
                'title' => $content['title'],
                'body' => $content['body'],
                'data' => $event->payload,
                'action_url' => $content['action_url'],
                'correlation_id' => $event->correlationId,
            ]);

            try {
                PortalNotificationCreated::dispatch(
                    $notification,
                    PortalNotification::query()
                        ->where('notifiable_type', $recipient::class)
                        ->where('notifiable_id', $recipient->id)
                        ->unread()
                        ->count(),
                );
                $notification->update(['broadcast_at' => now()]);
            } catch (BroadcastException $e) {
                Log::warning('[ProcessEcosystemEvent] Broadcast unavailable — notification saved but not pushed.', [
                    'notification_id' => $notification->id,
                    'event' => $event->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $logs->processed($event->id);

        $eventLog = EventLog::query()->where('event_id', $event->id)->first();
        if ($eventLog) {
            EcosystemEventProcessed::dispatch($eventLog);
        }
    }

    public function failed(Throwable $exception): void
    {
        app(EventLogService::class)->failed((string) ($this->event['id'] ?? ''), $exception);
    }
}
