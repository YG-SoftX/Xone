<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for YG Mail module integration with unified platform
     */
    public function up(): void
    {
        // Mailboxes (already exists in YG Mail, adding integration fields)
        if (!Schema::hasTable('mail_mailboxes')) {
            Schema::create('mail_mailboxes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('email')->unique();
                $table->string('display_name')->nullable();
                $table->bigInteger('storage_used_bytes')->default(0);
                $table->bigInteger('storage_quota_bytes')->default(5368709120); // 5GB
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index('user_id');
            });
        }

        // Email messages
        if (!Schema::hasTable('mail_messages')) {
            Schema::create('mail_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mailbox_id')->constrained('mail_mailboxes')->onDelete('cascade');
                $table->string('message_id')->unique();
                $table->string('from_email');
                $table->string('from_name')->nullable();
                $table->json('to_emails');
                $table->json('cc_emails')->nullable();
                $table->json('bcc_emails')->nullable();
                $table->string('subject');
                $table->text('body_plain')->nullable();
                $table->longText('body_html')->nullable();
                $table->string('folder')->default('inbox');
                $table->boolean('is_read')->default(false);
                $table->boolean('is_starred')->default(false);
                $table->boolean('has_attachments')->default(false);
                $table->timestamp('received_at');
                $table->timestamps();
                
                $table->index(['mailbox_id', 'folder', 'received_at']);
                $table->index(['mailbox_id', 'is_read']);
            });
        }

        // Email attachments
        if (!Schema::hasTable('mail_attachments')) {
            Schema::create('mail_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_id')->constrained('mail_messages')->onDelete('cascade');
                $table->string('filename');
                $table->string('mime_type');
                $table->bigInteger('size_bytes');
                $table->string('storage_path');
                $table->string('content_id')->nullable();
                $table->timestamps();
            });
        }

        // Email folders/labels
        if (!Schema::hasTable('mail_folders')) {
            Schema::create('mail_folders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mailbox_id')->constrained('mail_mailboxes')->onDelete('cascade');
                $table->string('name');
                $table->string('color')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->timestamps();
                
                $table->unique(['mailbox_id', 'name']);
            });
        }

        // Custom domains for business email
        if (!Schema::hasTable('custom_domains')) {
            Schema::create('custom_domains', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('domain')->unique();
                $table->string('verification_token')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->boolean('mx_configured')->default(false);
                $table->boolean('spf_configured')->default(false);
                $table->boolean('dkim_configured')->default(false);
                $table->boolean('dmarc_configured')->default(false);
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
                
                $table->index('user_id');
            });
        }

        // Email aliases
        if (!Schema::hasTable('email_aliases')) {
            Schema::create('email_aliases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mailbox_id')->constrained('mail_mailboxes')->onDelete('cascade');
                $table->string('alias_email')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index('mailbox_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_aliases');
        Schema::dropIfExists('custom_domains');
        Schema::dropIfExists('mail_folders');
        Schema::dropIfExists('mail_attachments');
        Schema::dropIfExists('mail_messages');
        Schema::dropIfExists('mail_mailboxes');
    }
};
