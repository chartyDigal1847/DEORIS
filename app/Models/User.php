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
    public const ROLE_NURSE            = 'nurse';
    public const ROLE_ELECTION_OFFICER = 'election_officer';
    public const ROLE_CANDIDATE        = 'candidate';

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
            self::ROLE_NURSE,
            self::ROLE_ELECTION_OFFICER,
            self::ROLE_CANDIDATE,
        ];
    }

    // ── Convenience role-check helpers ──────────────────────────────────────

    public function isAdmin(): bool            { return $this->role === self::ROLE_ADMIN; }
    public function isStudent(): bool          { return $this->role === self::ROLE_STUDENT; }
    public function isInstructor(): bool       { return $this->role === self::ROLE_INSTRUCTOR; }
    public function isCashier(): bool          { return $this->role === self::ROLE_CASHIER; }
    public function isLibrarian(): bool        { return $this->role === self::ROLE_LIBRARIAN; }
    public function isAdmissionOfficer(): bool { return $this->role === self::ROLE_ADMISSION_OFFICER; }
    public function isNurse(): bool            { return $this->role === self::ROLE_NURSE; }
    public function isElectionOfficer(): bool  { return $this->role === self::ROLE_ELECTION_OFFICER; }
    public function isCandidate(): bool        { return $this->role === self::ROLE_CANDIDATE; }

    /**
     * CareerConnect is restricted to faculty (instructors) only — not students.
     */
    public function canAccessCareerConnect(): bool
    {
        return $this->hasRole(self::ROLE_INSTRUCTOR);
    }

    /**
     * Returns the list of module keys this user is allowed to see and access.
     *
     * Student flow:
     *   Step 1 (pending)   → profile, entryease only
     *   Step 3 (approved)  → + enrollease (entryease hidden)
     *   Step 4 (enrolled)  → + enrollease visible
     *   Step 5 (cleared)   → + gradetrack, assesspay, librarysys, taskflow,
     *                          meditrack, votesys (if election active)
     *   CareerConnect      → NEVER for students
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

            // ── Cashier: payments only ───────────────────────────────────────
            self::ROLE_CASHIER => ['assesspay'],

            // ── Librarian: library only ──────────────────────────────────────
            self::ROLE_LIBRARIAN => ['librarysys'],

            // ── Admission Officer: admissions + enrollment ───────────────────
            self::ROLE_ADMISSION_OFFICER => ['entryease', 'enrollease', 'clearcheck'],

            // ── Nurse: medical records only ──────────────────────────────────
            self::ROLE_NURSE => ['meditrack'],

            // ── Election Officer: VoteSys + ClearCheck ───────────────────────
            self::ROLE_ELECTION_OFFICER => ['votesys', 'clearcheck'],

            // ── Candidate: VoteSys only (when election active) ───────────────
            self::ROLE_CANDIDATE => $electionActive ? ['votesys'] : [],

            // ── Student: progressive unlock ──────────────────────────────────
            self::ROLE_STUDENT => $this->studentVisibleModules($electionActive),

            default => [],
        };
    }

    /**
     * Resolves the progressive module list for a student based on their
     * admission_status, enrollment_status, and clearcheck_passed flag.
     *
     * Step 1 — registered, admission pending  → EntryEase only (take entrance exam)
     * Step 2 — same as step 1 (exam submitted, still pending review) → EntryEase
     * Step 3 — admission approved, not yet enrolled → EnrollEase only
     * Step 4 — enrolled, clearcheck not passed → EnrollEase + AssessPay + ClearCheck
     * Step 5 — enrolled + clearcheck passed → full access (no CareerConnect)
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

        // Step 4 — enrolled but clearcheck not passed
        if (! $this->clearcheck_passed) {
            return ['enrollease', 'assesspay', 'clearcheck'];
        }

        // Step 5 — fully cleared: unlock all student modules
        $modules = [
            'enrollease', 'gradetrack', 'assesspay',
            'librarysys', 'taskflow', 'meditrack', 'clearcheck',
        ];

        if ($electionActive) {
            $modules[] = 'votesys';
        }

        // CareerConnect is NEVER shown to students.
        return $modules;
    }

    /**
     * Whether this user can access a specific module key.
     */
    public function canAccessModule(string $moduleKey, bool $electionActive = false): bool
    {
        return in_array($moduleKey, $this->visibleModules($electionActive), true);
    }
}
