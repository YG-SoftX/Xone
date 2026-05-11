<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for YG Notes service
     */
    public function up(): void
    {
        // Notes table
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('color')->default('white'); // white, yellow, green, blue, red, purple, orange
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->json('labels')->nullable(); // Array of label strings
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_pinned']);
            $table->index(['user_id', 'is_archived']);
            $table->index(['user_id', 'is_deleted']);
        });

        // Note checklists (for todo items)
        Schema::create('note_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained()->onDelete('cascade');
            $table->string('item_text');
            $table->boolean('is_completed')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index('note_id');
        });

        // Note collaborators (for sharing)
        Schema::create('note_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('permission')->default('view'); // view, edit
            $table->timestamps();
            
            $table->unique(['note_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note_collaborators');
        Schema::dropIfExists('note_checklists');
        Schema::dropIfExists('notes');
    }
};
