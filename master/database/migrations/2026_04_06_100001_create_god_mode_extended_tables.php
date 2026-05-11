<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('action');
            $table->string('endpoint')->nullable();
            $table->text('payload')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('device_id')->unique();
            $table->string('model')->nullable();
            $table->string('os_version')->nullable();
            $table->boolean('is_secured')->default(true);
            $table->boolean('remote_wipe_pending')->default(false);
            $table->timestamps();
        });

        Schema::create('payment_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('provider')->default('fonepay');
            $table->string('account_reference');
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_profiles');
        Schema::dropIfExists('mobile_devices');
        Schema::dropIfExists('audit_logs');
    }
};
