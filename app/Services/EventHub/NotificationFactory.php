<?php

namespace App\Services\EventHub;

use App\Models\User;
use Deoris\Integration\DTO\EcosystemEvent;
use Illuminate\Support\Collection;

final class NotificationFactory
{
    /**
     * @return Collection<int, User>
     */
    public function recipients(EcosystemEvent $event): Collection
    {
        return match ($event->name) {
            'ApplicationSubmitted' => $this->admissionStaff(),
            default => $this->recipientsFromPayload($event),
        };
    }

    /**
     * @return Collection<int, User>
     */
    private function admissionStaff(): Collection
    {
        return User::query()
            ->whereIn('role', [User::ROLE_ADMISSION_OFFICER, User::ROLE_ADMIN])
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function recipientsFromPayload(EcosystemEvent $event): Collection
    {
        $payload = $event->payload;
        $userId = $payload['user_id'] ?? null;
        $studentEmail = $payload['student_email'] ?? null;
        $email = $payload['email'] ?? null;
        $studentNumber = $payload['student_number'] ?? null;

        $hasIdentifier = filled($userId) || filled($studentEmail) || filled($email) || filled($studentNumber);

        if (! $hasIdentifier) {
            return $this->admissionStaff();
        }

        return User::query()
            ->where(function ($query) use ($userId, $studentEmail, $email, $studentNumber): void {
                if (filled($userId)) {
                    $query->orWhere('id', $userId);
                }
                if (filled($studentEmail)) {
                    $query->orWhere('email', $studentEmail);
                }
                if (filled($email)) {
                    $query->orWhere('email', $email);
                }
                if (filled($studentNumber)) {
                    $query->orWhere('student_number', $studentNumber);
                }
            })
            ->get();
    }

    /**
     * @return array{title: string, body: string, action_url: string|null, type: string}
     */
    public function content(EcosystemEvent $event): array
    {
        $payload = $event->payload;
        $student = $payload['student_name'] ?? 'A student';
        $entryEaseUrl = '/entryease';

        return match ($event->name) {
            'ApplicationSubmitted' => [
                'title' => 'New admission application',
                'body' => "{$student} submitted a Grade 7 application.",
                'action_url' => $entryEaseUrl,
                'type' => 'admission',
            ],
            'ApplicationStatusChanged' => [
                'title' => 'Application status updated',
                'body' => "{$student}'s application is now ".($payload['new_status'] ?? 'updated').'.',
                'action_url' => $entryEaseUrl,
                'type' => 'admission',
            ],
            'AdmissionApproved' => [
                'title' => 'Admission approved',
                'body' => "{$student}'s application has been approved.",
                'action_url' => $entryEaseUrl,
                'type' => 'admission',
            ],
            'AdmissionRejected' => [
                'title' => 'Admission decision',
                'body' => "{$student}'s application was not approved.",
                'action_url' => $entryEaseUrl,
                'type' => 'admission',
            ],
            'ExamAssigned' => [
                'title' => 'Exam schedule assigned',
                'body' => "{$student} was assigned to ".($payload['schedule_title'] ?? 'an exam schedule').'.',
                'action_url' => $entryEaseUrl,
                'type' => 'admission',
            ],
            'ExamCompleted' => [
                'title' => 'Exam submitted',
                'body' => "{$student} submitted their entrance exam.",
                'action_url' => $entryEaseUrl,
                'type' => 'admission',
            ],
            'ExamScoreReleased' => [
                'title' => 'Exam results available',
                'body' => "Results are available for {$student}".(isset($payload['percentage']) ? ' ('.$payload['percentage'].'%)' : '').'.',
                'action_url' => $entryEaseUrl,
                'type' => 'admission',
            ],
            'StudentEnrolled' => [
                'title' => 'Student enrollment completed',
                'body' => ($payload['student_name'] ?? 'A student').' was enrolled in '.($payload['program'] ?? 'a program').'.',
                'action_url' => '/enrollease',
                'type' => 'enrollment',
            ],
            'EnrollmentApproved' => [
                'title' => 'Enrollment approved',
                'body' => ($payload['student_name'] ?? 'A student')."'s enrollment application has been approved.",
                'action_url' => '/enrollease',
                'type' => 'enrollment',
            ],
            'EnrollmentRejected' => [
                'title' => 'Enrollment not approved',
                'body' => ($payload['student_name'] ?? 'A student')."'s enrollment application was not approved.",
                'action_url' => '/enrollease',
                'type' => 'enrollment',
            ],
            'EnrollmentCancelled' => [
                'title' => 'Enrollment cancelled',
                'body' => ($payload['student_name'] ?? 'A student')."'s enrollment application was cancelled.",
                'action_url' => '/enrollease',
                'type' => 'enrollment',
            ],
            'TuitionPaid' => [
                'title' => 'Tuition payment received',
                'body' => 'Payment of '.($payload['amount'] ?? 'tuition').' was posted.',
                'action_url' => '/assesspay',
                'type' => 'finance',
            ],
            'PaymentCompleted', 'PaymentPaid', 'PaymentStatusChanged' => [
                'title' => 'Payment status updated',
                'body' => 'AssessPay marked the payment as '.($payload['status'] ?? $payload['payment_status'] ?? 'completed').'.',
                'action_url' => '/assesspay',
                'type' => 'finance',
            ],
            'GradeReleased' => [
                'title' => 'Grade released',
                'body' => 'A grade was released for '.($payload['course'] ?? 'your course').'.',
                'action_url' => '/gradetrack',
                'type' => 'academic',
            ],
            'MedicalApproved' => [
                'title' => 'Medical approval updated',
                'body' => 'Medical clearance was approved.',
                'action_url' => '/meditrack',
                'type' => 'medical',
            ],
            'LibraryPenaltyAdded' => [
                'title' => 'Library penalty added',
                'body' => 'A library penalty was added to the account.',
                'action_url' => '/librarysys',
                'type' => 'library',
            ],
            'ClearanceUpdated' => [
                'title' => 'Clearance status updated',
                'body' => 'Clearance is now '.($payload['status'] ?? 'updated').'.',
                'action_url' => '/clearcheck',
                'type' => 'clearance',
            ],
            default => [
                'title' => $event->name,
                'body' => 'A module event was received from '.$event->sourceModule.'.',
                'action_url' => null,
                'type' => 'module_event',
            ],
        };
    }
}
