<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations for unified YG Platform (Google-style architecture)
     */
    public function up(): void
    {
        // ==========================================
        // CORE AUTHENTICATION & USER MANAGEMENT
        // ==========================================
        
        // User profiles (extended information)
        if (!Schema::hasTable('user_profiles')) {
            Schema::create('user_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('company')->nullable();
                $table->string('job_title')->nullable();
                $table->text('bio')->nullable();
                $table->string('website')->nullable();
                $table->json('social_links')->nullable();
                $table->timestamps();
            });
        }

        // Device tracking (from existing device intelligence system)
        if (!Schema::hasTable('user_devices')) {
            Schema::create('user_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('device_fingerprint')->index();
                $table->string('device_name')->nullable();
                $table->string('platform')->nullable(); // Windows, macOS, Android, iOS
                $table->string('browser')->nullable();
                $table->string('os_version')->nullable();
                $table->string('screen_resolution')->nullable();
                $table->string('timezone')->nullable();
                $table->string('language')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->integer('location_accuracy')->nullable();
                $table->string('imei')->nullable();
                $table->string('android_id')->nullable();
                $table->string('idfa')->nullable();
                $table->string('mac_address_hash')->nullable();
                $table->string('canvas_hash')->nullable();
                $table->string('webgl_hash')->nullable();
                $table->string('fonts_hash')->nullable();
                $table->integer('device_score')->default(50); // 0-100 risk score
                $table->boolean('is_trusted')->default(false);
                $table->boolean('is_suspicious')->default(false);
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
                
                $table->unique(['user_id', 'device_fingerprint']);
                $table->index('device_fingerprint');
            });
        }

        // Device activity logs
        if (!Schema::hasTable('device_activity_logs')) {
            Schema::create('device_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('device_id')->constrained('user_devices')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('activity_type'); // login, logout, action
                $table->string('ip_address')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();
                
                $table->index(['user_id', 'occurred_at']);
                $table->index('activity_type');
            });
        }

        // Cross-account device links
        if (!Schema::hasTable('device_account_links')) {
            Schema::create('device_account_links', function (Blueprint $table) {
                $table->id();
                $table->string('device_fingerprint')->index();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('device_id')->constrained('user_devices')->onDelete('cascade');
                $table->string('ip_address')->nullable();
                $table->integer('account_count')->default(1);
                $table->json('account_ids')->nullable();
                $table->boolean('is_suspicious')->default(false);
                $table->string('suspicion_reason')->nullable();
                $table->timestamps();
                
                $table->index(['device_fingerprint', 'user_id']);
            });
        }

        // Fraud alerts
        if (!Schema::hasTable('fraud_alerts')) {
            Schema::create('fraud_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('device_id')->nullable()->constrained('user_devices')->onDelete('set null');
                $table->string('alert_type'); // velocity_check, impossible_travel, concurrent_sessions
                $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
                $table->text('description');
                $table->json('evidence')->nullable();
                $table->enum('status', ['pending', 'reviewed', 'resolved', 'false_positive'])->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'status']);
                $table->index('alert_type');
            });
        }

        // ==========================================
        // SERVICE EVENTS (Real-time sync via polling)
        // ==========================================
        
        if (!Schema::hasTable('service_events')) {
            Schema::create('service_events', function (Blueprint $table) {
                $table->id();
                $table->string('service'); // mail, drive, docs, pay, meet
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('mailbox_id')->nullable()->index();
                $table->string('event_type'); // new_email, file_uploaded, etc.
                $table->json('payload');
                $table->boolean('processed')->default(false)->index();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'created_at']);
                $table->index(['mailbox_id', 'created_at']);
            });
        }

        // ==========================================
        // SHARED STORAGE QUOTA SYSTEM
        // ==========================================
        
        if (!Schema::hasTable('storage_quotas')) {
            Schema::create('storage_quotas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->bigInteger('total_quota_bytes')->default(10737418240); // 10GB default
                $table->bigInteger('mail_used_bytes')->default(0);
                $table->bigInteger('drive_used_bytes')->default(0);
                $table->bigInteger('docs_used_bytes')->default(0);
                $table->bigInteger('meet_used_bytes')->default(0);
                $table->timestamp('last_updated_at')->nullable();
                $table->timestamps();
                
                $table->unique('user_id');
            });
        }

        // ==========================================
        // CENTRALIZED NOTIFICATIONS
        // ==========================================
        
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                
                $table->index(['notifiable_id', 'notifiable_type', 'read_at']);
            });
        }

        // ==========================================
        // ACTIVITY LOGS (Unified feed)
        // ==========================================
        
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('service'); // mail, drive, docs, pay
                $table->string('action'); // sent_email, uploaded_file, etc.
                $table->unsignedBigInteger('item_id')->nullable();
                $table->string('item_type')->nullable();
                $table->json('metadata')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'created_at']);
                $table->index(['service', 'created_at']);
            });
        }

        // ==========================================
        // SEARCH INDEX CACHE
        // ==========================================
        
        if (!Schema::hasTable('search_index')) {
            Schema::create('search_index', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('service'); // mail, drive, docs
                $table->unsignedBigInteger('item_id');
                $table->string('item_type'); // message, file, document
                $table->string('title');
                $table->text('content')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('indexed_at');
                $table->timestamps();
                
                $table->index(['user_id', 'service']);
                if (DB::getDriverName() !== 'sqlite') {
                    $table->fullText(['title', 'content']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_index');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('storage_quotas');
        Schema::dropIfExists('service_events');
        Schema::dropIfExists('fraud_alerts');
        Schema::dropIfExists('device_account_links');
        Schema::dropIfExists('device_activity_logs');
        Schema::dropIfExists('user_devices');
        Schema::dropIfExists('user_profiles');
    }
};
