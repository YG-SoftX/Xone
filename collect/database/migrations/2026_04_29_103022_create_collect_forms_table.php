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
        Schema::create('collect_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('collect_projects')->cascadeOnDelete();
            $table->string('title');
            $table->json('schema')->nullable();
            $table->json('settings')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collect_forms');
    }
};
