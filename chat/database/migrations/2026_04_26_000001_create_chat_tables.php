<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Spaces (rooms / group chats / DM threads) ──────────────────────────
        Schema::create('chat_spaces', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->string('avatar')->nullable();
            $table->enum('type', ['dm', 'group', 'space'])->default('space');
            $table->boolean('is_external')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // ── Space Members ──────────────────────────────────────────────────────
        Schema::create('chat_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('chat_spaces')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['owner', 'member', 'guest'])->default('member');
            $table->timestamp('last_read_at')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->timestamps();
            $table->unique(['space_id', 'user_id']);
            $table->index('user_id');
        });

        // ── Messages ───────────────────────────────────────────────────────────
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('chat_spaces')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reply_to_id')->nullable()->constrained('chat_messages')->onDelete('set null');
            $table->text('body')->nullable();
            $table->enum('type', ['text', 'file', 'system', 'meeting_invite'])->default('text');
            $table->json('attachments')->nullable();        // [{name, path, size, mime}]
            $table->json('reactions')->nullable();          // {emoji: [user_ids...]}
            $table->boolean('is_edited')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            $table->index(['space_id', 'created_at']);
            $table->index('user_id');
        });

        // ── Message Read Receipts (for DMs) ───────────────────────────────────
        Schema::create('chat_read_receipts', function (Blueprint $table) {
            $table->foreignId('message_id')->constrained('chat_messages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('read_at')->useCurrent();
            $table->primary(['message_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_read_receipts');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_members');
        Schema::dropIfExists('chat_spaces');
    }
};
