<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drive_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('drive_folders')->nullOnDelete();
            $table->string('name');
            $table->string('color', 7)->nullable()->default('#4a86e8');
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_trashed')->default(false);
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'parent_id']);
            $table->index(['user_id', 'is_trashed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_folders');
    }
};
