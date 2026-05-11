<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('social_accounts')) {
            Schema::create('social_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('provider_name'); // google, github, etc
                $table->string('provider_id');
                $table->string('provider_email')->nullable();
                $table->string('provider_avatar')->nullable();
                $table->timestamps();

                $table->unique(['provider_name', 'provider_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
