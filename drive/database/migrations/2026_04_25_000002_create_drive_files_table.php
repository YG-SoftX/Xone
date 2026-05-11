<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drive_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('drive_folders')->nullOnDelete();
            $table->string('name');
            $table->string('original_name');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->string('mime_type')->nullable();
            $table->bigInteger('size')->default(0);
            $table->string('extension', 20)->nullable();
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_trashed')->default(false);
            $table->text('description')->nullable();
            $table->string('shared_link', 64)->nullable()->unique();
            $table->timestamp('shared_link_expires_at')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'folder_id']);
            $table->index(['user_id', 'is_trashed']);
            $table->index(['user_id', 'is_starred']);
            $table->index('mime_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_files');
    }
};
