<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Calendars (each user can have multiple calendars) ──────────────────
        Schema::create('calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('color', 20)->default('#4285f4');      // Google-style colour
            $table->string('description')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_shared')->default(false);
            $table->enum('type', ['personal', 'work', 'birthdays', 'holidays', 'other'])->default('personal');
            $table->string('timezone', 100)->default('UTC');
            $table->timestamps();
            $table->index('user_id');
        });

        // ── Events ─────────────────────────────────────────────────────────────
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('meet_link')->nullable();           // YG Meet integration
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('all_day')->default(false);
            $table->string('color', 20)->nullable();           // override calendar colour
            $table->enum('status', ['confirmed', 'tentative', 'cancelled'])->default('confirmed');
            $table->enum('visibility', ['public', 'private', 'internal'])->default('public');

            // Recurrence
            $table->string('recurrence_rule')->nullable();     // RRULE (daily/weekly/monthly/yearly)
            $table->dateTime('recurrence_until')->nullable();
            $table->foreignId('parent_event_id')->nullable()->constrained('calendar_events')->onDelete('set null');

            // Reminders (JSON: [{minutes: 30, method: 'email'}, {minutes: 10, method: 'popup'}])
            $table->json('reminders')->nullable();

            $table->timestamps();
            $table->index(['user_id', 'starts_at']);
            $table->index(['calendar_id', 'starts_at']);
        });

        // ── Event Attendees ────────────────────────────────────────────────────
        Schema::create('event_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('calendar_events')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable();   // null = external guest
            $table->string('email');
            $table->string('name')->nullable();
            $table->enum('response', ['pending', 'accepted', 'declined', 'tentative'])->default('pending');
            $table->boolean('is_organizer')->default(false);
            $table->boolean('is_optional')->default(false);
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'email']);
        });

        // ── Calendar Shares ─────────────────────────────────────────────────────
        Schema::create('calendar_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('email');
            $table->enum('permission', ['view', 'edit', 'manage'])->default('view');
            $table->timestamps();
            $table->unique(['calendar_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_shares');
        Schema::dropIfExists('event_attendees');
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('calendars');
    }
};
