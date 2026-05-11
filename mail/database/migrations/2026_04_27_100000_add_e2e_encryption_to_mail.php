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
        // Add E2E encryption fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->text('public_key')->nullable()->after('password')->comment('RSA public key for E2E encryption');
            $table->text('private_key')->nullable()->after('public_key')->comment('Encrypted RSA private key');
            $table->boolean('encryption_enabled')->default(false)->after('private_key')->comment('Whether E2E encryption is enabled');
        });

        // Add E2E encryption fields to mails table
        Schema::table('mails', function (Blueprint $table) {
            $table->boolean('is_encrypted')->default(false)->after('read')->comment('Whether email is E2E encrypted');
            $table->text('encrypted_body')->nullable()->after('body')->comment('Encrypted email body (JSON for hybrid encryption)');
            $table->text('encrypted_subject')->nullable()->after('subject')->comment('Encrypted email subject');
            $table->string('encryption_algorithm')->nullable()->after('encrypted_subject')->comment('Encryption algorithm used (e.g., aes-256-cbc)');
            $table->string('encryption_key_id')->nullable()->after('encryption_algorithm')->comment('Reference to encryption key version');
            $table->text('sender_signature')->nullable()->after('encryption_key_id')->comment('Digital signature from sender');
            $table->string('recipient_public_key_id')->nullable()->after('sender_signature')->comment('ID of recipient public key used');
            
            // Indexes for performance
            $table->index('is_encrypted');
            $table->index('encryption_key_id');
        });

        // Create table for email encryption key history
        Schema::create('email_encryption_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('key_version')->comment('Version identifier for key rotation');
            $table->text('public_key')->comment('Public key at this version');
            $table->timestamp('activated_at')->comment('When this key became active');
            $table->timestamp('deactivated_at')->nullable()->comment('When this key was rotated out');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index('key_version');
        });

        // Create table for encrypted attachments
        Schema::table('attachments', function (Blueprint $table) {
            $table->boolean('is_encrypted')->default(false)->after('file_size')->comment('Whether attachment is encrypted');
            $table->text('encryption_metadata')->nullable()->after('is_encrypted')->comment('JSON metadata for decryption (key, IV, etc.)');
            $table->index('is_encrypted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mails', function (Blueprint $table) {
            $table->dropIndex(['is_encrypted']);
            $table->dropIndex(['encryption_key_id']);
            $table->dropColumn([
                'is_encrypted',
                'encrypted_body',
                'encrypted_subject',
                'encryption_algorithm',
                'encryption_key_id',
                'sender_signature',
                'recipient_public_key_id',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['public_key', 'private_key', 'encryption_enabled']);
        });

        Schema::dropIfExists('email_encryption_keys');

        Schema::table('attachments', function (Blueprint $table) {
            $table->dropIndex(['is_encrypted']);
            $table->dropColumn(['is_encrypted', 'encryption_metadata']);
        });
    }
};
