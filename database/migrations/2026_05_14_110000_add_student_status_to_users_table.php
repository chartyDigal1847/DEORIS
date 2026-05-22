<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds student progression columns to the users table.
 *
 * admission_status   — pending | approved | rejected
 * enrollment_status  — not_enrolled | enrolled
 * clearcheck_passed  — whether ClearCheck has been fully cleared
 * election_active    — system-wide flag: is an election currently running?
 *                      (stored per-user so admin can toggle per student if needed)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('admission_status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('role');

            $table->enum('enrollment_status', ['not_enrolled', 'enrolled'])
                ->default('not_enrolled')
                ->after('admission_status');

            $table->boolean('clearcheck_passed')
                ->default(false)
                ->after('enrollment_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['admission_status', 'enrollment_status', 'clearcheck_passed']);
        });
    }
};
