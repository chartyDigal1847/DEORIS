<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Applies AssessPay tuition events to the portal's student progression flag.
 */
class UserPaymentSyncService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function markTuitionPaid(array $payload): bool
    {
        try {
            $email = $payload['student_email'] ?? $payload['email'] ?? null;
            $studentId = $payload['user_id'] ?? $payload['student_id'] ?? null;
            $studentNumber = $payload['student_number'] ?? null;

            $user = $this->findUser($email, $studentId, $studentNumber);

            if (! $user) {
                Log::warning('[UserPaymentSync] TuitionPaid: User not found', [
                    'email' => $email,
                    'user_id' => $studentId,
                    'student_number' => $studentNumber,
                ]);

                return false;
            }

            if (! $this->isReadyForFullClearance($user)) {
                Log::info('[UserPaymentSync] TuitionPaid: User not ready for full clearance', [
                    'user_id' => $user->id,
                    'admission_status' => $user->admission_status,
                    'enrollment_status' => $user->enrollment_status,
                ]);

                return false;
            }

            if (! $user->clearcheck_passed) {
                $user->update(['clearcheck_passed' => true]);
            }

            Log::info('[UserPaymentSync] TuitionPaid: Student fully cleared', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('[UserPaymentSync] TuitionPaid failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return false;
        }
    }

    private function isReadyForFullClearance(User $user): bool
    {
        return $user->admission_status === User::ADMISSION_APPROVED
            && $user->enrollment_status === User::ENROLLMENT_ENROLLED;
    }

    private function findUser(?string $email, mixed $studentId, ?string $studentNumber): ?User
    {
        if ($studentId) {
            $user = User::query()->find($studentId);

            if ($user) {
                return $user;
            }
        }

        if ($email) {
            $user = User::query()->where('email', $email)->first();

            if ($user) {
                return $user;
            }
        }

        if ($studentNumber) {
            return User::query()->where('student_number', $studentNumber)->first();
        }

        return null;
    }
}
