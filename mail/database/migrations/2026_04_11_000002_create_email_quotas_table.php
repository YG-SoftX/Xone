<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('daily_sent')->default(0);
            $table->integer('daily_limit')->default(100);
            $table->date('date');
            $table->unique(['user_id', 'date']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_quotas');
    }
};
