<?php

namespace App\Listeners;

use App\Events\EcosystemEventReceived;
use App\Services\UserPaymentSyncService;

class SyncUserPaymentStatus
{
    /**
     * @var array<int, string>
     */
    private const CLEARING_EVENTS = [
        'TuitionPaid',
        'PaymentCompleted',
        'PaymentPaid',
        'PaymentStatusChanged',
    ];

    public function __construct(private UserPaymentSyncService $syncService) {}

    public function handle(EcosystemEventReceived $event): void
    {
        $eventData = $event->event;
        $sourceModule = strtolower((string) ($eventData['source_module'] ?? ''));

        if (! in_array($sourceModule, ['assesspay', 'assess_pay'], true)) {
            return;
        }

        $eventName = (string) ($eventData['name'] ?? '');
        $payload = $eventData['payload'] ?? [];

        if (! in_array($eventName, self::CLEARING_EVENTS, true)) {
            return;
        }

        if ($eventName !== 'TuitionPaid' && ! $this->isCompletedPayment($payload)) {
            return;
        }

        $this->syncService->markTuitionPaid($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isCompletedPayment(array $payload): bool
    {
        $status = strtolower((string) (
            $payload['status']
            ?? $payload['payment_status']
            ?? $payload['tuition_status']
            ?? ''
        ));

        return in_array($status, ['paid', 'completed', 'complete'], true)
            || filled($payload['paid_at'] ?? null)
            || filled($payload['completed_at'] ?? null);
    }
}
