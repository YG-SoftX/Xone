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
        if (!Schema::hasTable('folders')) {
            Schema::create('folders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('parent_id')->nullable()->constrained('folders')->onDelete('cascade');
                $table->string('name');
                $table->string('color')->nullable();
                $table->string('icon')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'parent_id']);
                $table->index('created_at');
            });
        }

        if (!Schema::hasTable('spreadsheets')) {
            Schema::create('spreadsheets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('folder_id')->nullable()->constrained('folders')->onDelete('set null');
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('thumbnail')->nullable();
                $table->string('status')->default('draft'); // draft, published, archived
                $table->unsignedBigInteger('default_sheet_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spreadsheets');
    }
};
