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
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('service'); // mail, calendar, drive, docs, etc.
                $table->string('type'); // new_email, event_reminder, file_shared, etc.
                $table->string('title');
                $table->text('message')->nullable();
                $table->string('action_url')->nullable();
                $table->string('icon')->default('bell');
                $table->string('priority')->default('normal'); // low, normal, high, urgent
                $table->boolean('is_read')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                // Indexes for performance
                $table->index(['user_id', 'is_read']);
                $table->index(['user_id', 'created_at']);
                $table->index('service');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
