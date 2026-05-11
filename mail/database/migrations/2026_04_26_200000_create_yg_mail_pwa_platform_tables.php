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
        // Custom Domains Table - Business email domain management
        Schema::create('custom_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('domain')->unique(); // e.g., company.com
            $table->string('verification_token')->nullable(); // DNS verification token
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            
            // DNS Records Status
            $table->boolean('mx_configured')->default(false);
            $table->boolean('spf_configured')->default(false);
            $table->boolean('dkim_configured')->default(false);
            $table->boolean('dmarc_configured')->default(false);
            
            // Email Routing
            $table->boolean('catch_all_enabled')->default(false);
            $table->string('catch_all_email')->nullable(); // forward to this email
            
            // Security
            $table->boolean('tls_enforced')->default(true);
            $table->boolean('spam_filtering')->default(true);
            $table->boolean('virus_scanning')->default(true);
            
            // Limits
            $table->integer('max_mailboxes')->default(10); // max email accounts
            $table->integer('max_storage_gb')->default(5); // per mailbox storage
            $table->integer('daily_send_limit')->default(500); // emails per day
            
            // Status
            $table->enum('status', ['pending', 'active', 'suspended', 'expired'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'status']);
            $table->index('domain');
        });

        // Mailboxes Table - Individual email accounts within domains
        Schema::create('mailboxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('custom_domains')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('local_part'); // e.g., "john" from john@company.com
            $table->string('full_email')->unique(); // john@company.com
            $table->string('display_name')->nullable();
            
            // Mailbox Settings
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_reply_enabled')->default(false);
            $table->text('auto_reply_message')->nullable();
            $table->timestamp('auto_reply_starts_at')->nullable();
            $table->timestamp('auto_reply_ends_at')->nullable();
            
            // Storage & Quotas
            $table->bigInteger('storage_used_bytes')->default(0);
            $table->bigInteger('storage_quota_bytes')->default(5368709120); // 5GB default
            
            // Forwarding
            $table->boolean('forwarding_enabled')->default(false);
            $table->string('forward_to_email')->nullable();
            $table->boolean('keep_copy')->default(true); // keep copy when forwarding
            
            // Spam & Security
            $table->integer('spam_score_threshold')->default(5); // 1-10, lower = stricter
            $table->boolean('quarantine_spam')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['domain_id', 'local_part']);
            $table->index('full_email');
        });

        // Email Aliases Table - Alternative addresses pointing to mailboxes
        Schema::create('email_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailbox_id')->constrained()->onDelete('cascade');
            $table->string('alias_email')->unique(); // alias@company.com
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('alias_email');
        });

        // Email Rules Table - Auto-filtering and organization
        Schema::create('email_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailbox_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('priority')->default(0); // higher = processed first
            $table->boolean('is_active')->default(true);
            
            // Conditions (JSON)
            $table->json('conditions'); // {from, to, subject_contains, has_attachment}
            
            // Actions (JSON)
            $table->json('actions'); // {move_to_folder, mark_as_read, forward, label, delete}
            
            $table->timestamps();
            
            $table->index(['mailbox_id', 'priority']);
        });

        // Email Folders/Labels Table
        Schema::create('email_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailbox_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('color')->nullable(); // hex color for labels
            $table->integer('parent_id')->nullable(); // for nested folders
            $table->integer('message_count')->default(0);
            $table->integer('unread_count')->default(0);
            $table->timestamps();
            
            $table->unique(['mailbox_id', 'name']);
        });

        // Push Notification Tokens Table
        Schema::create('push_notification_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token'); // FCM or Web Push token
            $table->enum('platform', ['web', 'android', 'ios', 'desktop'])->default('web');
            $table->string('browser')->nullable();
            $table->string('device_info')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'platform']);
            $table->unique('token');
        });

        // Real-Time Events Log (for WebSocket sync)
        Schema::create('realtime_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailbox_id')->constrained()->onDelete('cascade');
            $table->string('event_type'); // new_email, email_deleted, folder_updated, etc.
            $table->json('payload'); // event data
            $table->boolean('delivered')->default(false);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            
            $table->index(['mailbox_id', 'created_at']);
            $table->index('event_type');
        });

        // Email Templates Table - Reusable templates
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_html')->default(true);
            $table->boolean('is_public')->default(false); // share with team
            $table->timestamps();
            
            $table->index('user_id');
        });

        // Email Signatures Table
        Schema::create('email_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailbox_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('signature_html');
            $table->boolean('is_default')->default(false);
            $table->boolean('auto_append')->default(true); // append to new emails
            $table->timestamps();
            
            $table->index('mailbox_id');
        });

        // Spam Quarantine Table
        Schema::create('spam_quarantine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailbox_id')->constrained()->onDelete('cascade');
            $table->string('message_id')->unique();
            $table->string('from_email');
            $table->string('subject');
            $table->text('preview');
            $table->integer('spam_score');
            $table->json('spam_headers'); // SPF, DKIM, DMARC results
            $table->enum('action', ['quarantined', 'released', 'deleted', 'marked_safe'])->default('quarantined');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            
            $table->index(['mailbox_id', 'action']);
            $table->index('created_at');
        });

        // Email Activity Log (Audit Trail)
        Schema::create('email_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailbox_id')->constrained()->onDelete('cascade');
            $table->string('action'); // sent, received, deleted, moved, forwarded
            $table->string('message_id')->nullable();
            $table->json('metadata'); // additional details
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['mailbox_id', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_activity_logs');
        Schema::dropIfExists('spam_quarantine');
        Schema::dropIfExists('email_signatures');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('realtime_events');
        Schema::dropIfExists('push_notification_tokens');
        Schema::dropIfExists('email_folders');
        Schema::dropIfExists('email_rules');
        Schema::dropIfExists('email_aliases');
        Schema::dropIfExists('mailboxes');
        Schema::dropIfExists('custom_domains');
    }
};
