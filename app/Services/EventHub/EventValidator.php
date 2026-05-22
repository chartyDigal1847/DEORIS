<?php

namespace App\Services\EventHub;

use Deoris\Integration\DTO\EcosystemEvent;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class EventValidator
{
    public function __construct(private readonly TrustedModuleRegistry $modules)
    {
    }

    public function validate(EcosystemEvent $event): void
    {
        $validator = Validator::make($event->toArray(), [
            'id' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:120', 'in:'.implode(',', config('deoris_events.allowed_events', []))],
            'source_module' => ['required', 'string', 'max:80'],
            'payload' => ['required', 'array'],
            'occurred_at' => ['required', 'date'],
            'correlation_id' => ['required', 'string', 'max:120'],
            'schema_version' => ['required', 'string', 'max:20'],
        ]);

        $validator->after(function ($validator) use ($event): void {
            if (! $this->modules->isTrusted($event->sourceModule)) {
                $validator->errors()->add('source_module', 'Unknown or untrusted source module.');
            }

            match ($event->name) {
                'ApplicationSubmitted' => $this->requireAny($validator, $event, ['student_email', 'user_id', 'student_number']),
                'ApplicationStatusChanged',
                'AdmissionApproved',
                'AdmissionRejected',
                'ExamAssigned',
                'ExamCompleted',
                'ExamScoreReleased' => $this->requireAny($validator, $event, ['student_email', 'user_id', 'student_number']),
                'StudentEnrolled' => $this->requireAny($validator, $event, ['user_id', 'student_email', 'student_number']),
                'TuitionPaid' => $this->requireAny($validator, $event, ['user_id', 'student_email', 'student_number']),
                'GradeReleased' => $this->requireAny($validator, $event, ['user_id', 'student_email', 'student_number']),
                'MedicalApproved' => $this->requireAny($validator, $event, ['user_id', 'student_email', 'student_number']),
                'LibraryPenaltyAdded' => $this->requireAny($validator, $event, ['user_id', 'student_email', 'student_number']),
                'ClearanceUpdated' => $this->requireAny($validator, $event, ['user_id', 'student_email', 'student_number']),
                default => null,
            };
        });

        $validator->validate();
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function requireAny($validator, EcosystemEvent $event, array $keys): void
    {
        foreach ($keys as $key) {
            if (filled($event->payload[$key] ?? null)) {
                return;
            }
        }

        $validator->errors()->add('payload', 'Payload must include one of: '.implode(', ', $keys).'.');
    }
}
