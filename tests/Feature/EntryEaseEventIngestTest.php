<?php

namespace Tests\Feature;

use App\Jobs\ProcessEcosystemEvent;
use App\Models\User;
use App\Services\EventHub\EventLogService;
use Deoris\Integration\DTO\EcosystemEvent;
use Deoris\Integration\Support\Signature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class EntryEaseEventIngestTest extends TestCase
{
    use RefreshDatabase;

    public function test_entryease_application_submitted_is_accepted_via_signed_http_ingest(): void
    {
        Queue::fake();
        config(['deoris_events.modules.EntryEase.secret' => 'test-entryease-secret']);

        $event = EcosystemEvent::make(
            name: 'ApplicationSubmitted',
            sourceModule: 'EntryEase',
            payload: [
                'student_email' => 'applicant@deoris.test',
                'student_name' => 'Maria Santos',
                'applicant_id' => 42,
                'grade_level' => 'Grade 7',
                'status' => 'Pending',
            ],
        );

        // postJson encodes with json_encode($data, 0) — same output as JSON_THROW_ON_ERROR.
        // We sign the same byte string the portal will receive via $request->getContent().
        $payload   = $event->toArray();
        $body      = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = time();
        $nonce     = (string) Str::uuid();
        $signature = Signature::sign($body, 'test-entryease-secret', $timestamp, $nonce);

        $response = $this->postJson(
            '/api/events',
            $payload,
            [
                'X-DEORIS-Module'    => 'EntryEase',
                'X-DEORIS-Timestamp' => (string) $timestamp,
                'X-DEORIS-Nonce'     => $nonce,
                'X-DEORIS-Signature' => $signature,
            ],
        );

        $response->assertAccepted();
        Queue::assertPushed(ProcessEcosystemEvent::class, function ($job) {
            return ($job->event['name'] ?? '') === 'ApplicationSubmitted'
                && ($job->event['source_module'] ?? '') === 'EntryEase';
        });
    }

    public function test_application_submitted_creates_notification_for_admission_officer(): void
    {
        $officer = User::factory()->create([
            'role' => User::ROLE_ADMISSION_OFFICER,
            'email' => 'officer@deoris.test',
        ]);

        config(['deoris_events.modules.EntryEase.secret' => 'test-entryease-secret']);

        $event = EcosystemEvent::make(
            name: 'ApplicationSubmitted',
            sourceModule: 'EntryEase',
            payload: [
                'student_email' => 'applicant@deoris.test',
                'student_name' => 'Maria Santos',
                'applicant_id' => 42,
                'grade_level' => 'Grade 7',
                'status' => 'Pending',
            ],
        );

        app(EventLogService::class)->received($event);

        (new ProcessEcosystemEvent($event->toArray()))->handle(
            app(\App\Services\EventHub\EventValidator::class),
            app(EventLogService::class),
            app(\App\Services\EventHub\NotificationFactory::class),
        );

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $officer->id,
            'event_name' => 'ApplicationSubmitted',
            'source_module' => 'EntryEase',
        ]);
    }
}
