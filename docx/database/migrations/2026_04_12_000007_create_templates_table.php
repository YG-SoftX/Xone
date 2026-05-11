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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->json('content_json')->nullable();
            $table->string('category')->default('business'); // business, education, personal, resume, letter, report
            $table->boolean('is_public')->default(false);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index(['category', 'is_public']);
            $table->index(['user_id', 'created_at']);
            $table->index('is_system');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
