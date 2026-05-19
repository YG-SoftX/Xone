<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->string('category')->default('other'); // billing, technical, account, feature, other
            $table->string('priority')->default('medium'); // low, medium, high
            $table->string('status')->default('open');     // open, pending, resolved, closed
            $table->json('messages')->nullable();           // [{role, body, created_at}]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
