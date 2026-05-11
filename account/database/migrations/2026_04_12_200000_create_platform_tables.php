<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Platform feature toggles table
        Schema::create('platform_features', function (Blueprint $table) {
            $table->id();
            $table->string('feature_key')->unique(); // wallet, nfc, kyc, drive, mail, meet, chat, pay, docx
            $table->string('feature_name');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_public')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        // YG Services table
        Schema::create('yg_services', function (Blueprint $table) {
            $table->id();
            $table->string('service_key')->unique(); // mail, drive, meet, chat, pay, docx, master
            $table->string('service_name');
            $table->string('url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_maintenance')->default(false);
            $table->string('status')->default('operational'); // operational, degraded, down, maintenance
            $table->integer('port')->nullable();
            $table->string('health_check_url')->nullable();
            $table->timestamp('last_health_check')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // NFC Tokens table
        Schema::create('nfc_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token_uid')->unique(); // NFC UID
            $table->string('device_name')->nullable();
            $table->string('device_type')->nullable(); // phone, card, ring, wristband
            $table->boolean('is_active')->default(true);
            $table->decimal('daily_limit', 12, 2)->default(1000);
            $table->decimal('transaction_limit', 12, 2)->default(100);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        // NFC Transactions table
        Schema::create('nfc_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nfc_token_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('transaction_type'); // payment, transfer, reload
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status'); // pending, completed, failed, declined
            $table->string('merchant')->nullable();
            $table->string('merchant_id')->nullable();
            $table->string('reference')->unique();
            $table->text('notes')->nullable();
            $table->string('nfc_reader_location')->nullable();
            $table->timestamps();
        });

        // Invoices table
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('recipient_email')->nullable();
            $table->string('recipient_name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('draft'); // draft, sent, viewed, paid, overdue, cancelled, refunded
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->json('line_items')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();
        });

        // SMTP Accounts table (for companies/users who want SMTP service)
        Schema::create('smtp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('domain'); // company domain
            $table->string('email_address'); // SMTP email
            $table->string('display_name');
            $table->string('smtp_host');
            $table->integer('smtp_port')->default(587);
            $table->string('smtp_username');
            $table->string('smtp_password_encrypted'); // encrypted
            $table->string('encryption')->default('tls'); // tls, ssl, none
            $table->integer('daily_limit')->default(500);
            $table->integer('emails_sent_today')->default(0);
            $table->date('last_reset_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->json('dns_records')->nullable(); // SPF, DKIM, DMARC
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // Email Templates table
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('subject');
            $table->text('body'); // HTML body
            $table->boolean('is_html')->default(true);
            $table->string('category')->default('custom'); // welcome, notification, marketing, custom
            $table->json('variables')->nullable(); // available template variables
            $table->timestamps();
        });

        // Device Management table - Enhanced with fingerprinting and tracking
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Basic Device Info
            $table->string('device_name');
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('os')->nullable();
            $table->string('os_version')->nullable();
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('device_id')->nullable(); // unique device identifier
            
            // Hardware Identifiers (Mobile Devices)
            $table->string('imei')->nullable()->index(); // International Mobile Equipment Identity
            $table->string('android_id')->nullable()->index(); // Android Device ID
            $table->string('idfa')->nullable()->index(); // iOS Advertising Identifier
            $table->string('mac_address_hash')->nullable(); // Hashed MAC address for privacy
            
            // Geolocation Tracking
            $table->decimal('latitude', 10, 7)->nullable(); // GPS latitude
            $table->decimal('longitude', 10, 7)->nullable(); // GPS longitude
            $table->integer('location_accuracy')->nullable(); // GPS accuracy in meters
            $table->json('location_history')->nullable(); // Recent location history (last 10)
            $table->string('country_code', 2)->nullable()->index(); // ISO country code
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            
            // Device Fingerprinting
            $table->string('canvas_fingerprint')->nullable()->index(); // Canvas rendering hash
            $table->string('webgl_fingerprint')->nullable()->index(); // WebGL renderer hash
            $table->string('fonts_hash')->nullable(); // Installed fonts hash
            $table->string('screen_resolution')->nullable(); // e.g., "1920x1080"
            $table->integer('color_depth')->nullable(); // Screen color depth
            $table->integer('pixel_ratio')->nullable(); // Device pixel ratio
            $table->string('timezone')->nullable(); // User's timezone
            $table->string('language', 10)->nullable(); // Browser language
            $table->json('hardware_concurrency')->nullable(); // CPU cores
            $table->integer('device_memory')->nullable(); // RAM in GB
            $table->boolean('touch_support')->nullable(); // Touch screen capability
            
            // Security & Risk Assessment
            $table->integer('device_reputation_score')->default(50); // 0-100 trust score
            $table->json('risk_flags')->nullable(); // Array of detected risks
            $table->integer('login_count')->default(0); // Total logins from this device
            $table->integer('failed_login_attempts')->default(0); // Failed attempts
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_location_update')->nullable();
            
            // Session Management
            $table->boolean('is_trusted')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'is_blocked']);
            $table->index(['device_reputation_score']);
            $table->index(['created_at']);
        });
        
        // Device Activity Logs - Track all device interactions
        Schema::create('device_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('user_devices')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('activity_type')->index(); // login, logout, action, location_update
            $table->string('ip_address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['user_id', 'occurred_at']);
            $table->index(['activity_type', 'occurred_at']);
        });
        
        // Cross-Account Device Links - Detect account farming
        Schema::create('device_account_links', function (Blueprint $table) {
            $table->id();
            $table->string('device_fingerprint')->index(); // Combined fingerprint hash
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained('user_devices')->onDelete('set null');
            $table->string('ip_address')->nullable();
            $table->integer('account_count')->default(1); // Number of accounts on this device
            $table->json('account_ids')->nullable(); // Array of user IDs
            $table->boolean('is_suspicious')->default(false); // Flagged by fraud detection
            $table->string('suspicion_reason')->nullable();
            $table->timestamp('first_detected_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
            
            $table->unique(['device_fingerprint', 'user_id']);
            $table->index(['is_suspicious']);
        });
        
        // Fraud Detection Alerts
        Schema::create('fraud_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('device_id')->nullable()->constrained('user_devices')->onDelete('set null');
            $table->string('alert_type')->index(); // impossible_travel, velocity_check, account_farming, etc.
            $table->string('severity')->default('medium'); // low, medium, high, critical
            $table->text('description');
            $table->json('evidence')->nullable(); // Supporting data
            $table->string('status')->default('open'); // open, investigating, resolved, false_positive
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'severity']);
            $table->index(['created_at']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('smtp_accounts');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('nfc_transactions');
        Schema::dropIfExists('nfc_tokens');
        Schema::dropIfExists('yg_services');
        Schema::dropIfExists('platform_features');
    }
};
