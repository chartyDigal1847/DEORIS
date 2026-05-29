<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Syncs admission status from EntryEase events into DEORIS users.
 */
class UserAdmissionSyncService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function syncFromPayload(array $payload): bool
    {
        try {
            $email = $payload['student_email'] ?? $payload['email'] ?? null;
            $studentId = $payload['student_id'] ?? $payload['user_id'] ?? null;
            $admissionStatus = $payload['admission_status'] ?? null;

            if (! $admissionStatus) {
                $admissionStatus = $this->mapApplicantStatus($payload['new_status'] ?? null);
            }

            if (! $admissionStatus) {
                return false;
            }

            $user = $this->findUser($email, $studentId);

            if (! $user) {
                Log::warning('[UserAdmissionSync] User not found', [
                    'email' => $email,
                    'student_id' => $studentId,
                ]);

                return false;
            }

            $user->update([
                'admission_status' => $admissionStatus,
            ]);

            Log::info('[UserAdmissionSync] Admission status updated', [
                'user_id' => $user->id,
                'admission_status' => $admissionStatus,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('[UserAdmissionSync] Failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return false;
        }
    }

    private function mapApplicantStatus(?string $status): ?string
    {
        return match ($status) {
            'Approved' => User::ADMISSION_APPROVED,
            'Rejected' => User::ADMISSION_REJECTED,
            'Under Review' => User::ADMISSION_UNDER_REVIEW,
            'Pending' => User::ADMISSION_PENDING,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function findUser(?string $email, mixed $studentId): ?User
    {
        if ($studentId) {
            $user = User::query()->find($studentId);
            if ($user) {
                return $user;
            }
        }

        if ($email) {
            return User::query()->where('email', $email)->first();
        }

        return null;
    }
}
