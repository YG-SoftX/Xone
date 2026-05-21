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
        Schema::create('universal_footer_items', function (Blueprint $table) {
            $table->id();
            $table->string('service_key')->index(); // 'mail', 'drive', 'docx', etc. or 'global' for all services
            $table->enum('position', ['footer'])->default('footer');
            $table->string('label'); // Display text
            $table->string('url'); // Link URL
            $table->string('icon')->nullable(); // Font Awesome icon class
            $table->integer('order')->default(0); // Sort order
            $table->boolean('is_active')->default(true);
            $table->boolean('is_external')->default(false); // Open in new tab
            $table->json('metadata')->nullable(); // Additional config (color, tooltip, etc.)
            $table->timestamps();
            
            // Index for fast queries
            $table->index(['service_key', 'is_active', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('universal_footer_items');
    }
};
