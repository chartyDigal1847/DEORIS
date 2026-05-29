<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    // ── Role constants ───────────────────────────────────────────────────────
    public const ROLE_ADMIN            = 'admin';
    public const ROLE_STUDENT          = 'student';
    public const ROLE_INSTRUCTOR       = 'instructor';
    public const ROLE_CASHIER          = 'cashier';
    public const ROLE_LIBRARIAN        = 'librarian';
    public const ROLE_ADMISSION_OFFICER = 'admission_officer';
    public const ROLE_CAREER_OFFICER   = 'career_officer';
    public const ROLE_NURSE            = 'nurse';
    public const ROLE_ELECTION_OFFICER = 'election_officer';

    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    // ── Admission / enrollment status constants ──────────────────────────────
    public const ADMISSION_PENDING      = 'pending';
    public const ADMISSION_UNDER_REVIEW = 'under_review';
    public const ADMISSION_APPROVED     = 'approved';
    public const ADMISSION_REJECTED     = 'rejected';

    public const ENROLLMENT_NOT_ENROLLED = 'not_enrolled';
    public const ENROLLMENT_ENROLLED     = 'enrolled';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'student_number',
        'password',
        'role',
        'admission_status',
        'enrollment_status',
        'enrollease_enrollment_id',
        'enrollment_status_synced_at',
        'previous_enrollment_status',
        'clearcheck_passed',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'enrollment_status_synced_at' => 'datetime',
            'password'           => 'hashed',
            'clearcheck_passed'  => 'boolean',
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * All valid role values.
     *
     * @return array<int, string>
     */
    public static function roles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_STUDENT,
            self::ROLE_INSTRUCTOR,
            self::ROLE_CASHIER,
            self::ROLE_LIBRARIAN,
            self::ROLE_ADMISSION_OFFICER,
            self::ROLE_CAREER_OFFICER,
            self::ROLE_NURSE,
            self::ROLE_ELECTION_OFFICER,
        ];
    }

    // ── Convenience role-check helpers ──────────────────────────────────────

    public function isAdmin(): bool            { return $this->role === self::ROLE_ADMIN; }
    public function isStudent(): bool          { return $this->role === self::ROLE_STUDENT; }
    public function isInstructor(): bool       { return $this->role === self::ROLE_INSTRUCTOR; }
    public function isCashier(): bool          { return $this->role === self::ROLE_CASHIER; }
    public function isLibrarian(): bool        { return $this->role === self::ROLE_LIBRARIAN; }
    public function isAdmissionOfficer(): bool { return $this->role === self::ROLE_ADMISSION_OFFICER; }
    public function isCareerOfficer(): bool    { return $this->role === self::ROLE_CAREER_OFFICER; }
    public function isNurse(): bool            { return $this->role === self::ROLE_NURSE; }
    public function isElectionOfficer(): bool  { return $this->role === self::ROLE_ELECTION_OFFICER; }

    /**
     * CareerConnect is accessible to staff and students who finished the
     * EntryEase → EnrollEase → AssessPay onboarding pipeline.
     */
    public function canAccessCareerConnect(): bool
    {
        if ($this->isStudent()) {
            return $this->studentHasCompletedOnboardingPipeline();
        }

        return $this->hasRole(
            self::ROLE_ADMIN,
            self::ROLE_INSTRUCTOR,
            self::ROLE_ADMISSION_OFFICER,
            self::ROLE_CAREER_OFFICER,
            self::ROLE_LIBRARIAN,
            self::ROLE_CASHIER,
        );
    }

    /**
     * Returns the list of module keys this user is allowed to see and access.
     *
     * Student flow:
     *   Step 1 (pending)   → profile, entryease only
     *   Step 3 (approved)  → + enrollease (entryease hidden)
     *   Step 4 (enrolled)  → + enrollease, assesspay
     *   Step 5 (pipeline complete) → all student modules including CareerConnect
     *       (after EntryEase approval, EnrollEase enrollment, AssessPay tuition)
     *
     * Staff roles get their relevant modules immediately.
     *
     * @param  bool  $electionActive  Whether a VoteSys election is currently running.
     * @return array<int, string>
     */
    public function visibleModules(bool $electionActive = false): array
    {
        return match ($this->role) {

            // ── Admin: everything ────────────────────────────────────────────
            self::ROLE_ADMIN => [
                'entryease', 'enrollease', 'gradetrack', 'assesspay',
                'librarysys', 'taskflow', 'careerconnect', 'meditrack',
                'votesys', 'clearcheck',
            ],

            // ── Instructor: grades + career + tasks ──────────────────────────
            self::ROLE_INSTRUCTOR => [
                'gradetrack', 'taskflow', 'careerconnect',
            ],

            // ── Cashier: payments + career ───────────────────────────────────
            self::ROLE_CASHIER => ['assesspay', 'careerconnect'],

            // ── Librarian: library + career ──────────────────────────────────
            self::ROLE_LIBRARIAN => ['librarysys', 'careerconnect'],

            // ── Admission Officer: admissions + enrollment + career comms ────
            self::ROLE_ADMISSION_OFFICER => ['entryease', 'enrollease', 'clearcheck', 'careerconnect'],

            // ── Career Officer: career services and opportunities ────────────
            self::ROLE_CAREER_OFFICER => ['careerconnect'],

            // ── Nurse: medical records only ──────────────────────────────────
            self::ROLE_NURSE => ['meditrack'],

            // ── Election Officer: VoteSys + ClearCheck ───────────────────────
            self::ROLE_ELECTION_OFFICER => ['votesys', 'clearcheck'],

            // ── Student: progressive unlock ──────────────────────────────────
            self::ROLE_STUDENT => $this->studentVisibleModules($electionActive),

            default => [],
        };
    }

    /**
     * Resolves the progressive module list for a student based on the
     * EntryEase → EnrollEase → AssessPay pipeline.
     *
     * Step 1 — admission pending / under review → EntryEase only
     * Step 2 — admission approved, not enrolled → EnrollEase only
     * Step 3 — enrolled, tuition not yet paid (AssessPay) → EnrollEase + AssessPay
     * Step 4 — approved + enrolled + tuition paid → all student modules (incl. CareerConnect)
     *
     * @return array<int, string>
     */
    private function studentVisibleModules(bool $electionActive): array
    {
        // Rejected — no modules
        if ($this->admission_status === self::ADMISSION_REJECTED) {
            return [];
        }

        // Steps 1 & 2 — pending admission (before or after entrance exam)
        // Step 2b — under review (registrar is reviewing the application)
        if (in_array($this->admission_status, [self::ADMISSION_PENDING, self::ADMISSION_UNDER_REVIEW], true)) {
            return ['entryease'];
        }

        // Step 3 — approved but not yet enrolled
        if ($this->enrollment_status === self::ENROLLMENT_NOT_ENROLLED) {
            return ['enrollease'];
        }

        // Step 3 — enrolled, still need to complete AssessPay
        if (! $this->studentHasCompletedOnboardingPipeline()) {
            return ['enrollease', 'assesspay'];
        }

        // Step 4 — onboarding pipeline complete: unlock all student modules
        $modules = [
            'enrollease', 'gradetrack', 'assesspay',
            'librarysys', 'taskflow', 'meditrack', 'clearcheck',
            'votesys', 'careerconnect',
        ];

        return $modules;
    }

    /**
     * True when the student has completed EntryEase (approved), EnrollEase
     * (enrolled), and AssessPay (tuition paid). The portal stores the paid step
     * on clearcheck_passed — that flag is set by AssessPay events, not by the
     * ClearCheck module.
     */
    private function studentHasCompletedOnboardingPipeline(): bool
    {
        return $this->admission_status === self::ADMISSION_APPROVED
            && $this->enrollment_status === self::ENROLLMENT_ENROLLED
            && (bool) $this->clearcheck_passed;
    }

    /**
     * Whether this user can access a specific module key.
     */
    public function canAccessModule(string $moduleKey, bool $electionActive = false): bool
    {
        return in_array($moduleKey, $this->visibleModules($electionActive), true);
    }
}
