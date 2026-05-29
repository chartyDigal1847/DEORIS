<?php

namespace App\Listeners;

use App\Events\EcosystemEventReceived;
use App\Services\UserAdmissionSyncService;

/**
 * Syncs admission status from EntryEase into DEORIS users (event-driven only).
 */
class SyncUserAdmissionStatus
{
    public function __construct(private UserAdmissionSyncService $syncService) {}

    public function handle(EcosystemEventReceived $event): void
    {
        $eventData = $event->event;
        $sourceModule = strtolower((string) ($eventData['source_module'] ?? ''));

        if (! in_array($sourceModule, ['entryease', 'entry_ease'], true)) {
            return;
        }

        $eventName = $eventData['name'] ?? null;
        $payload = $eventData['payload'] ?? [];

        match ($eventName) {
            'AdmissionApproved',
            'AdmissionRejected',
            'ApplicationStatusChanged',
            'ApplicationSubmitted' => $this->syncService->syncFromPayload($payload),
            default => null,
        };
    }
}
