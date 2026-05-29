<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Expands the admission_status ENUM on the users table to include 'under_review'.
 *
 * Before: pending | approved | rejected
 * After:  pending | under_review | approved | rejected
 *
 * EntryEase sets admission_status = 'under_review' when a registrar marks an
 * application as "Under Review". Without this value in the DEORIS enum, MySQL
 * silently rejects the update and the status never syncs to the portal.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE users
            MODIFY COLUMN admission_status
            ENUM('pending', 'under_review', 'approved', 'rejected')
            NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        // First reset any under_review rows back to pending so the column shrink doesn't fail
        DB::statement("UPDATE users SET admission_status = 'pending' WHERE admission_status = 'under_review'");

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE users
            MODIFY COLUMN admission_status
            ENUM('pending', 'approved', 'rejected')
            NOT NULL DEFAULT 'pending'
        ");
    }
};
