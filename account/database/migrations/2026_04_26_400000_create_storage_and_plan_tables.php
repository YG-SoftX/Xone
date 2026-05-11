<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── User storage quotas ───────────────────────────────────────────────
        if (!Schema::hasTable('storage_quotas')) {
            Schema::create('storage_quotas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
                $table->bigInteger('quota_bytes')->default(15 * 1024 ** 3);  // 15 GB default
                $table->bigInteger('used_bytes')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_quotas');
    }
};
