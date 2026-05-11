<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for YG Meet module (Video Conferencing)
     */
    public function up(): void
    {
        // Meetings
        Schema::create('meet_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Host/organizer
            $table->string('meeting_code')->unique(); // e.g., abc-defg-hij
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('scheduled_at');
            $table->integer('duration_minutes')->default(60);
            $table->enum('status', ['scheduled', 'active', 'completed', 'cancelled'])->default('scheduled');
            $table->string('recording_url')->nullable();
            $table->bigInteger('recording_size_bytes')->nullable();
            $table->integer('max_participants')->default(100);
            $table->boolean('require_password')->default(false);
            $table->string('password_hash')->nullable();
            $table->json('settings')->nullable(); // Screen share, chat, recording options
            $table->timestamps();
            
            $table->index('meeting_code');
            $table->index(['user_id', 'scheduled_at']);
        });

        // Meeting participants
        Schema::create('meet_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meet_meetings')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('email')->nullable();
            $table->string('display_name');
            $table->enum('role', ['host', 'presenter', 'participant'])->default('participant');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->boolean('video_enabled')->default(false);
            $table->timestamps();
            
            $table->index('meeting_id');
        });

        // Meeting chat messages
        Schema::create('meet_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meet_meetings')->onDelete('cascade');
            $table->foreignId('participant_id')->constrained('meet_participants')->onDelete('cascade');
            $table->text('message');
            $table->boolean('is_system_message')->default(false);
            $table->timestamps();
            
            $table->index('meeting_id');
        });

        // Meeting recordings
        Schema::create('meet_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meet_meetings')->onDelete('cascade');
            $table->string('recording_url');
            $table->bigInteger('size_bytes');
            $table->integer('duration_seconds');
            $table->string('format'); // mp4, webm
            $table->boolean('is_transcribed')->default(false);
            $table->text('transcript')->nullable();
            $table->timestamps();
            
            $table->index('meeting_id');
        });

        // Calendar events (for scheduling)
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->string('timezone')->default('UTC');
            $table->boolean('all_day')->default(false);
            $table->string('location')->nullable();
            $table->string('meeting_url')->nullable();
            $table->json('attendees')->nullable(); // Array of emails/user_ids
            $table->json('reminders')->nullable(); // Notification settings
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // daily, weekly, monthly
            $table->timestamps();
            
            $table->index(['user_id', 'start_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('meet_recordings');
        Schema::dropIfExists('meet_chat_messages');
        Schema::dropIfExists('meet_participants');
        Schema::dropIfExists('meet_meetings');
    }
};
