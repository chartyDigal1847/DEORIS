<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ROLES = [
        'admin',
        'student',
        'instructor',
        'cashier',
        'librarian',
        'admission_officer',
        'career_officer',
        'nurse',
        'election_officer',
    ];

    private const LEGACY_ROLES = [
        'admin',
        'student',
        'instructor',
        'cashier',
        'librarian',
        'admission_officer',
        'nurse',
        'election_officer',
        'candidate',
    ];

    public function up(): void
    {
        DB::table('users')
            ->where('role', 'candidate')
            ->update(['role' => 'student']);

        $this->modifyRoleEnum(self::ROLES);
    }

    public function down(): void
    {
        $this->modifyRoleEnum(self::LEGACY_ROLES);
    }

    /**
     * @param  list<string>  $roles
     */
    private function modifyRoleEnum(array $roles): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $values = implode(', ', array_map(fn (string $role) => "'{$role}'", $roles));

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM({$values}) NOT NULL DEFAULT 'student'");
    }
};
