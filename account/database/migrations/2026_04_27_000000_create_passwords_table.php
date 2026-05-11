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
        Schema::create('passwords', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('user_id')->constrained()->onDelete('cascade');
            $blueprint->string('site_name');
            $blueprint->string('site_url')->nullable();
            $blueprint->string('username');
            $blueprint->text('encrypted_password'); // Using text for encrypted blob
            $blueprint->text('notes')->nullable();
            $blueprint->string('category')->default('other'); // social, finance, work, etc.
            $blueprint->timestamp('last_used_at')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passwords');
    }
};
