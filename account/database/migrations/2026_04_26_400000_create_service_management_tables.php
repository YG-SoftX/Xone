<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for admin service management
     */
    public function up(): void
    {
        // Service configurations table
        if (!Schema::hasTable('service_configurations')) {
            Schema::create('service_configurations', function (Blueprint $table) {
                $table->id();
                $table->string('service_key')->unique(); // mail, drive, docs, xcel, meet, forms, pay, ai
                $table->string('service_name'); // YG Mail, YG Drive, etc.
                $table->text('description')->nullable();
                $table->boolean('is_enabled')->default(true);
                $table->boolean('is_maintenance_mode')->default(false);
                $table->text('maintenance_message')->nullable();
                $table->json('settings')->nullable(); // Service-specific settings
                $table->timestamp('enabled_at')->nullable();
                $table->timestamp('disabled_at')->nullable();
                $table->foreignId('last_modified_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                
                $table->index('is_enabled');
            });
        }

        // Service usage statistics
        if (!Schema::hasTable('service_usage_stats')) {
            Schema::create('service_usage_stats', function (Blueprint $table) {
                $table->id();
                $table->string('service_key');
                $table->date('date');
                $table->integer('active_users')->default(0);
                $table->integer('total_actions')->default(0);
                $table->bigInteger('storage_used_bytes')->default(0);
                $table->integer('api_calls')->default(0);
                $table->float('avg_response_time_ms')->nullable();
                $table->integer('error_count')->default(0);
                $table->timestamps();
                
                $table->unique(['service_key', 'date']);
                $table->index('date');
            });
        }

        // Service access logs (audit trail)
        if (!Schema::hasTable('service_access_logs')) {
            Schema::create('service_access_logs', function (Blueprint $table) {
                $table->id();
                $table->string('service_key');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->string('action'); // enabled, disabled, maintenance_on, maintenance_off
                $table->text('reason')->nullable();
                $table->string('ip_address')->nullable();
                $table->foreignId('admin_user_id')->constrained('users')->onDelete('cascade');
                $table->timestamps();
                
                $table->index(['service_key', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_access_logs');
        Schema::dropIfExists('service_usage_stats');
        Schema::dropIfExists('service_configurations');
    }
};
