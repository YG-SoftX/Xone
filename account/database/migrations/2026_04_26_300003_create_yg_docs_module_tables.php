<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for YG Docs module (Productivity Suite)
     */
    public function up(): void
    {
        // Documents
        if (!Schema::hasTable('docs_documents')) {
            Schema::create('docs_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                if (Schema::hasTable('drive_folders')) {
                    $table->foreignId('folder_id')->nullable()->constrained('drive_folders')->onDelete('set null');
                } else {
                    $table->unsignedBigInteger('folder_id')->nullable();
                }
                $table->string('title');
                $table->enum('type', ['document', 'spreadsheet', 'presentation'])->default('document');
                $table->longText('content')->nullable();
                $table->json('metadata')->nullable();
                $table->string('template_id')->nullable();
                $table->boolean('is_public')->default(false);
                $table->timestamp('last_edited_at')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'type']);
                $table->index(['user_id', 'updated_at']);
            });
        }

        // Document collaborators
        if (!Schema::hasTable('docs_collaborators')) {
            Schema::create('docs_collaborators', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('docs_documents')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->enum('permission', ['view', 'comment', 'edit', 'owner'])->default('view');
                $table->timestamp('last_accessed_at')->nullable();
                $table->timestamps();
                
                $table->unique(['document_id', 'user_id']);
            });
        }

        // Document comments
        if (!Schema::hasTable('docs_comments')) {
            Schema::create('docs_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('docs_documents')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->unsignedBigInteger('parent_comment_id')->nullable();
                $table->text('content');
                $table->json('position')->nullable();
                $table->boolean('is_resolved')->default(false);
                $table->timestamps();
                
                $table->index('document_id');
            });
        }

        // Document revisions
        if (!Schema::hasTable('docs_revisions')) {
            Schema::create('docs_revisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('docs_documents')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('revision_number');
                $table->longText('content_snapshot');
                $table->text('change_summary')->nullable();
                $table->timestamps();
                
                $table->index('document_id');
            });
        }

        // Document templates
        if (!Schema::hasTable('docs_templates')) {
            Schema::create('docs_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->enum('type', ['document', 'spreadsheet', 'presentation']);
                $table->text('description')->nullable();
                $table->longText('content');
                $table->json('metadata')->nullable();
                $table->boolean('is_public')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                
                $table->index('type');
            });
        }

        // Real-time editing sessions
        if (!Schema::hasTable('docs_editing_sessions')) {
            Schema::create('docs_editing_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('docs_documents')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamp('started_at');
                $table->timestamp('last_activity_at');
                $table->timestamp('ended_at')->nullable();
                $table->json('cursor_position')->nullable();
                
                $table->index(['document_id', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('docs_editing_sessions');
        Schema::dropIfExists('docs_templates');
        Schema::dropIfExists('docs_revisions');
        Schema::dropIfExists('docs_comments');
        Schema::dropIfExists('docs_collaborators');
        Schema::dropIfExists('docs_documents');
    }
};
