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
        Schema::create('universal_navigation_items', function (Blueprint $table) {
            $table->id();
            $table->string('service_key')->index(); // account, pay, mail, ai, etc.
            $table->string('position')->default('header'); // header, footer, sidebar
            $table->string('label');
            $table->string('url');
            $table->string('icon')->nullable(); // FontAwesome icon class
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_external')->default(false);
            $table->json('metadata')->nullable(); // For future flexibility (e.g., specific roles)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('universal_navigation_items');
    }
};
