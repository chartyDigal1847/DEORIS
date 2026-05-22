<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Reference to the enrollment record from EnrollEase
            $table->unsignedBigInteger('enrollease_enrollment_id')->nullable()->after('enrollment_status')
                ->comment('Enrollment ID from EnrollEase system');
            
            // Timestamp of when enrollment status was last synced from EnrollEase
            $table->timestamp('enrollment_status_synced_at')->nullable()->after('enrollease_enrollment_id')
                ->comment('When enrollment status was last synced from EnrollEase');
            
            // Track the previous status for reference
            $table->string('previous_enrollment_status')->nullable()->after('enrollment_status_synced_at')
                ->comment('Previous enrollment status for reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['enrollease_enrollment_id', 'enrollment_status_synced_at', 'previous_enrollment_status']);
        });
    }
};
