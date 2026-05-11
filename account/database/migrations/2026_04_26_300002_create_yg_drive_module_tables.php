<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for YG Drive module
     */
    public function up(): void
    {
        // Drive folders
        if (!Schema::hasTable('drive_folders')) {
            Schema::create('drive_folders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('path')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'parent_id']);
            });
        }

        // Drive files
        if (!Schema::hasTable('drive_files')) {
            Schema::create('drive_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('folder_id')->nullable()->constrained('drive_folders')->onDelete('set null');
                $table->string('name');
                $table->string('mime_type');
                $table->bigInteger('size_bytes');
                $table->string('storage_path');
                $table->string('hash')->nullable();
                $table->boolean('is_public')->default(false);
                $table->timestamps();
                
                $table->index(['user_id', 'folder_id']);
                $table->index(['user_id', 'created_at']);
            });
        }

        // File shares
        if (!Schema::hasTable('drive_shares')) {
            Schema::create('drive_shares', function (Blueprint $table) {
                $table->id();
                $table->foreignId('file_id')->constrained('drive_files')->onDelete('cascade');
                $table->foreignId('shared_with_user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->string('shared_with_email')->nullable();
                $table->enum('permission', ['view', 'edit', 'comment'])->default('view');
                $table->timestamps();
                
                $table->index(['file_id', 'shared_with_user_id']);
            });
        }

        // File versions
        if (!Schema::hasTable('drive_file_versions')) {
            Schema::create('drive_file_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('file_id')->constrained('drive_files')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('version_number');
                $table->string('storage_path');
                $table->bigInteger('size_bytes');
                $table->text('change_summary')->nullable();
                $table->timestamps();
                
                $table->index('file_id');
            });
        }

        // Trash
        if (!Schema::hasTable('drive_trash')) {
            Schema::create('drive_trash', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('item_type'); // file or folder
                $table->unsignedBigInteger('item_id');
                $table->timestamp('deleted_at');
                $table->timestamp('expires_at');
                $table->timestamps();
                
                $table->index(['user_id', 'expires_at']);
            });
        }

        // Starred items
        if (!Schema::hasTable('drive_starred')) {
            Schema::create('drive_starred', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('item_type'); // file or folder
                $table->unsignedBigInteger('item_id');
                $table->timestamps();
                
                $table->unique(['user_id', 'item_type', 'item_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drive_starred');
        Schema::dropIfExists('drive_trash');
        Schema::dropIfExists('drive_file_versions');
        Schema::dropIfExists('drive_shares');
        Schema::dropIfExists('drive_files');
        Schema::dropIfExists('drive_folders');
    }
};
