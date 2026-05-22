<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->json('data');
                $table->string('source_module', 80)->nullable();
                $table->string('event_name', 120)->nullable();
                $table->string('title')->nullable();
                $table->text('body')->nullable();
                $table->string('action_url')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('broadcast_at')->nullable();
                $table->string('correlation_id', 120)->nullable();
                $table->timestamps();

                $table->index(['source_module', 'event_name']);
                $table->index('correlation_id');
            });

            return;
        }

        Schema::table('notifications', function (Blueprint $table): void {
            if (! Schema::hasColumn('notifications', 'source_module')) {
                $table->string('source_module', 80)->nullable()->index()->after('data');
            }
            if (! Schema::hasColumn('notifications', 'event_name')) {
                $table->string('event_name', 120)->nullable()->after('source_module');
            }
            if (! Schema::hasColumn('notifications', 'title')) {
                $table->string('title')->nullable()->after('event_name');
            }
            if (! Schema::hasColumn('notifications', 'body')) {
                $table->text('body')->nullable()->after('title');
            }
            if (! Schema::hasColumn('notifications', 'action_url')) {
                $table->string('action_url')->nullable()->after('body');
            }
            if (! Schema::hasColumn('notifications', 'broadcast_at')) {
                $table->timestamp('broadcast_at')->nullable()->after('read_at');
            }
            if (! Schema::hasColumn('notifications', 'correlation_id')) {
                $table->string('correlation_id', 120)->nullable()->index()->after('broadcast_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table): void {
            foreach (['event_name', 'title', 'body', 'action_url', 'correlation_id'] as $column) {
                if (Schema::hasColumn('notifications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
