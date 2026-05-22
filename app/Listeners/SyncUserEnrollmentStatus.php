<?php

namespace App\Listeners;

use App\Events\EcosystemEventReceived;
use App\Services\UserEnrollmentSyncService;
use Illuminate\Support\Facades\Log;

/**
 * SyncUserEnrollmentStatus
 * 
 * Listens to EcosystemEventReceived and syncs enrollment status changes from EnrollEase
 * to the DEORIS User database.
 */
class SyncUserEnrollmentStatus
{
    public function __construct(private UserEnrollmentSyncService $syncService) {}

    /**
     * Handle the event.
     */
    public function handle(EcosystemEventReceived $event): void
    {
        $eventData = $event->event;
        $eventName = $eventData['name'] ?? null;
        $sourceModule = $eventData['source_module'] ?? null;

        // Only process events from EnrollEase
        if ($sourceModule !== 'EnrollEase') {
            return;
        }

        $payload = $eventData['payload'] ?? [];

        Log::debug('[SyncUserEnrollmentStatus] Processing ecosystem event', [
            'event_name' => $eventName,
            'source_module' => $sourceModule,
            'event_id' => $eventData['id'] ?? null,
        ]);

        match ($eventName) {
            'StudentEnrolled'      => $this->syncService->enrollStudent($payload),
            'EnrollmentApproved'   => $this->syncService->approveEnrollment($payload),
            'EnrollmentRejected'   => $this->syncService->rejectEnrollment($payload),
            default                => null,
        };
    }
}
