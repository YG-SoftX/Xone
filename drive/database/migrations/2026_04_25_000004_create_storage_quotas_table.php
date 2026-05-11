<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->bigInteger('quota_bytes')->default(15368709120); // 15 GB default
            $table->string('plan')->default('free');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_quotas');
    }
};
