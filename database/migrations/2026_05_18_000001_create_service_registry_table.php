<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_registry', function (Blueprint $table): void {
            $table->id();
            $table->string('service_key', 80)->unique();
            $table->string('label', 120);
            $table->string('url', 512);
            $table->string('api_version', 20)->default('v1');
            $table->enum('status', ['active', 'inactive', 'degraded', 'maintenance'])->default('active')->index();
            $table->json('allowed_roles')->nullable();
            $table->json('environment_config')->nullable();
            $table->string('health_check_url', 512)->nullable();
            $table->timestamp('last_health_check_at')->nullable();
            $table->boolean('health_ok')->default(true);
            $table->timestamps();

            $table->index(['status', 'service_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_registry');
    }
};
