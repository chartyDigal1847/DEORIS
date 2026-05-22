<?php

namespace App\Services\EventHub;

use App\Models\EventLog;
use Deoris\Integration\DTO\EcosystemEvent;
use Throwable;

final class EventLogService
{
    public function received(EcosystemEvent $event): EventLog
    {
        return EventLog::updateOrCreate(
            ['event_id' => $event->id],
            [
                'event_name' => $event->name,
                'source_module' => $event->sourceModule,
                'correlation_id' => $event->correlationId,
                'payload' => $event->payload,
                'status' => EventLog::STATUS_RECEIVED,
                'error' => null,
                'received_at' => now(),
            ],
        );
    }

    public function isProcessed(string $eventId): bool
    {
        return EventLog::query()
            ->where('event_id', $eventId)
            ->where('status', EventLog::STATUS_PROCESSED)
            ->exists();
    }

    public function processing(string $eventId): void
    {
        EventLog::where('event_id', $eventId)->update(['status' => EventLog::STATUS_PROCESSING]);
    }

    public function processed(string $eventId): void
    {
        EventLog::where('event_id', $eventId)->update([
            'status' => EventLog::STATUS_PROCESSED,
            'processed_at' => now(),
            'error' => null,
        ]);
    }

    public function failed(string $eventId, Throwable|string $error): void
    {
        EventLog::where('event_id', $eventId)->update([
            'status' => EventLog::STATUS_FAILED,
            'processed_at' => now(),
            'error' => is_string($error) ? $error : $error->getMessage(),
        ]);
    }
}
