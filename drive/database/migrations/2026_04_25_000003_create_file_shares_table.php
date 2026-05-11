<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drive_file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shared_by_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shared_with_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('shared_with_email')->nullable();
            $table->enum('permission', ['view', 'comment', 'edit'])->default('view');
            $table->timestamp('expires_at')->nullable();
            $table->string('token', 64)->nullable()->unique();
            $table->boolean('is_link_share')->default(false);
            $table->timestamps();

            $table->index(['drive_file_id', 'shared_with_id']);
            $table->index(['shared_by_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_shares');
    }
};
