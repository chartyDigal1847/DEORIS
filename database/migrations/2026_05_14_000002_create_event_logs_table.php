<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('event_logs')) {
            Schema::table('event_logs', function (Blueprint $table): void {
                if (! Schema::hasColumn('event_logs', 'event_id')) {
                    $table->string('event_id', 120)->nullable()->unique()->after('id');
                }
                if (! Schema::hasColumn('event_logs', 'error')) {
                    $table->text('error')->nullable()->after('status');
                }
            });

            return;
        }

        Schema::create('event_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id', 120)->unique();
            $table->string('event_name', 120);
            $table->string('source_module', 80);
            $table->string('correlation_id', 120)->nullable();
            $table->json('payload');
            $table->string('status', 40)->default('received');
            $table->text('error')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['source_module', 'event_name']);
            $table->index(['status', 'created_at']);
            $table->index('correlation_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('event_logs')) {
            return;
        }

        Schema::table('event_logs', function (Blueprint $table): void {
            foreach (['event_id', 'error'] as $column) {
                if (Schema::hasColumn('event_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
