<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Contact Groups (like Google Contacts labels) ────────────────────────
        Schema::create('contact_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('color', 20)->default('#4285f4');
            $table->timestamps();
            $table->index('user_id');
        });

        // ── Contacts ────────────────────────────────────────────────────────────
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('company')->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('avatar')->nullable();
            $table->boolean('is_starred')->default(false);

            // Contact fields (JSON arrays for multi-value)
            $table->json('emails')->nullable();     // [{email, type, is_primary}]
            $table->json('phones')->nullable();     // [{number, type}]
            $table->json('addresses')->nullable();  // [{street, city, state, country, zip, type}]
            $table->json('websites')->nullable();   // [{url, type}]
            $table->json('social_profiles')->nullable(); // [{platform, handle}]

            $table->date('birthday')->nullable();
            $table->text('notes')->nullable();

            // Link to YGXone user (if the contact is also a platform user)
            $table->unsignedBigInteger('linked_user_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'is_starred']);
            $table->index('user_id');
        });

        // ── Contact <-> Group pivot ─────────────────────────────────────────────
        Schema::create('contact_group_pivot', function (Blueprint $table) {
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->foreignId('group_id')->constrained('contact_groups')->onDelete('cascade');
            $table->primary(['contact_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_group_pivot');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('contact_groups');
    }
};
