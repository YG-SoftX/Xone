<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('browser_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->index();
            $table->text('value')->nullable();
            $table->string('label')->nullable();
            $table->string('group')->default('general'); // general, search_engines, presets, features
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_settings');
    }
};
