<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the old role enum (admin, hr, student) with the full DEORIS role set.
 *
 * Because MySQL does not support ALTER COLUMN on ENUM directly in all versions,
 * we use a raw SQL MODIFY COLUMN statement. SQLite (used in tests) does not
 * enforce enum constraints, so we skip the DDL there.
 */
return new class extends Migration
{
    /** New allowed role values. */
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

    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $values = implode(
                ', ',
                array_map(fn (string $r) => "'{$r}'", self::ROLES)
            );

            DB::statement(
                "ALTER TABLE users MODIFY COLUMN role ENUM({$values}) NOT NULL DEFAULT 'student'"
            );
        }
        // SQLite: no DDL change needed — it stores any string value.
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE users MODIFY COLUMN role ENUM('admin','hr','student') NOT NULL DEFAULT 'student'"
            );
        }
    }
};
