<?php

namespace Tests\Feature;

use App\Jobs\ProcessEcosystemEvent;
use App\Listeners\SyncUserPaymentStatus;
use App\Models\EventLog;
use App\Models\User;
use App\Services\EventHub\TrustedModuleRegistry;
use App\Services\UserPaymentSyncService;
use Deoris\Integration\DTO\EcosystemEvent;
use Deoris\Integration\Support\Signature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class EventHubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'deoris_events.modules.EnrollEase.secret' => 'test-enrollease-secret',
        ]);
    }

    public function test_signed_event_ingest_is_accepted(): void
    {
        Bus::fake();

        $event = EcosystemEvent::make('StudentEnrolled', 'EnrollEase', [
            'user_id' => 1,
            'student_name' => 'Jane Student',
            'program' => 'BSIT',
        ]);

        $body = json_encode($event->toArray());
        $timestamp = time();
        $nonce = (string) Str::uuid();

        $response = $this->postJson('/api/events', $event->toArray(), [
            'X-DEORIS-Module' => 'EnrollEase',
            'X-DEORIS-Timestamp' => (string) $timestamp,
            'X-DEORIS-Nonce' => $nonce,
            'X-DEORIS-Signature' => Signature::sign($body, 'test-enrollease-secret', $timestamp, $nonce),
        ]);

        $response->assertAccepted();
        Bus::assertDispatched(ProcessEcosystemEvent::class);
        $this->assertDatabaseHas('event_logs', [
            'event_id' => $event->id,
            'status' => EventLog::STATUS_RECEIVED,
        ]);
    }

    public function test_duplicate_nonce_is_rejected(): void
    {
        $event = EcosystemEvent::make('StudentEnrolled', 'EnrollEase', ['user_id' => 1]);
        $body = json_encode($event->toArray());
        $timestamp = time();
        $nonce = (string) Str::uuid();
        $headers = [
            'X-DEORIS-Module' => 'EnrollEase',
            'X-DEORIS-Timestamp' => (string) $timestamp,
            'X-DEORIS-Nonce' => $nonce,
            'X-DEORIS-Signature' => Signature::sign($body, 'test-enrollease-secret', $timestamp, $nonce),
        ];

        Cache::flush();
        $this->postJson('/api/events', $event->toArray(), $headers)->assertAccepted();
        $this->postJson('/api/events', $event->toArray(), $headers)->assertStatus(409);
    }

    public function test_source_module_must_match_signed_header_module(): void
    {
        $event = EcosystemEvent::make('StudentEnrolled', 'EntryEase', ['user_id' => 1]);
        $body = json_encode($event->toArray());
        $timestamp = time();
        $nonce = (string) Str::uuid();

        $this->postJson('/api/events', $event->toArray(), [
            'X-DEORIS-Module' => 'EnrollEase',
            'X-DEORIS-Timestamp' => (string) $timestamp,
            'X-DEORIS-Nonce' => $nonce,
            'X-DEORIS-Signature' => Signature::sign($body, 'test-enrollease-secret', $timestamp, $nonce),
        ])->assertAccepted();

        $this->assertDatabaseHas('event_logs', [
            'event_id' => $event->id,
            'source_module' => 'EnrollEase',
        ]);
    }

    public function test_notification_factory_does_not_notify_all_users_without_identifiers(): void
    {
        User::factory()->count(3)->create(['role' => User::ROLE_STUDENT]);
        User::factory()->create(['role' => User::ROLE_ADMIN]);

        $factory = app(\App\Services\EventHub\NotificationFactory::class);
        $event = EcosystemEvent::make('StudentEnrolled', 'EnrollEase', []);

        $recipients = $factory->recipients($event);

        $this->assertCount(1, $recipients);
        $this->assertTrue($recipients->every(fn (User $user) => $user->role === User::ROLE_ADMIN));
    }

    public function test_trusted_module_registry_requires_secret(): void
    {
        $registry = app(TrustedModuleRegistry::class);

        $this->assertTrue($registry->isTrusted('EnrollEase'));
        $this->assertFalse($registry->isTrusted('UnknownModule'));
    }

    public function test_enrolled_student_does_not_see_clearcheck_until_fully_cleared(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'admission_status' => User::ADMISSION_APPROVED,
            'enrollment_status' => User::ENROLLMENT_ENROLLED,
            'clearcheck_passed' => false,
        ]);

        $this->assertSame(['enrollease', 'assesspay'], $user->visibleModules());

        $user->forceFill(['clearcheck_passed' => true]);

        $this->assertContains('clearcheck', $user->visibleModules());
    }

    public function test_tuition_paid_fully_clears_approved_enrolled_student(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'admission_status' => User::ADMISSION_APPROVED,
            'enrollment_status' => User::ENROLLMENT_ENROLLED,
            'clearcheck_passed' => false,
        ]);

        $synced = app(UserPaymentSyncService::class)->markTuitionPaid([
            'user_id' => $user->id,
            'student_email' => $user->email,
        ]);

        $this->assertTrue($synced);
        $this->assertTrue($user->fresh()->clearcheck_passed);
    }

    public function test_tuition_paid_does_not_clear_student_before_enrollment(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'admission_status' => User::ADMISSION_APPROVED,
            'enrollment_status' => User::ENROLLMENT_NOT_ENROLLED,
            'clearcheck_passed' => false,
        ]);

        $synced = app(UserPaymentSyncService::class)->markTuitionPaid([
            'user_id' => $user->id,
        ]);

        $this->assertFalse($synced);
        $this->assertFalse($user->fresh()->clearcheck_passed);
    }

    public function test_assesspay_completed_event_fully_clears_student(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'admission_status' => User::ADMISSION_APPROVED,
            'enrollment_status' => User::ENROLLMENT_ENROLLED,
            'clearcheck_passed' => false,
        ]);

        app(SyncUserPaymentStatus::class)->handle(new \App\Events\EcosystemEventReceived([
            'name' => 'PaymentCompleted',
            'source_module' => 'AssessPay',
            'payload' => [
                'user_id' => $user->id,
                'status' => 'completed',
            ],
        ]));

        $this->assertTrue($user->fresh()->clearcheck_passed);
    }

    public function test_assesspay_pending_event_does_not_clear_student(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'admission_status' => User::ADMISSION_APPROVED,
            'enrollment_status' => User::ENROLLMENT_ENROLLED,
            'clearcheck_passed' => false,
        ]);

        app(SyncUserPaymentStatus::class)->handle(new \App\Events\EcosystemEventReceived([
            'name' => 'PaymentStatusChanged',
            'source_module' => 'AssessPay',
            'payload' => [
                'user_id' => $user->id,
                'status' => 'pending',
            ],
        ]));

        $this->assertFalse($user->fresh()->clearcheck_passed);
    }
}
