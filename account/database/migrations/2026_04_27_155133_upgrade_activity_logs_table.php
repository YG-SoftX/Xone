<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('service')->default('Account')->after('type');
            $table->string('device')->nullable()->after('ip_address');
            $table->json('metadata')->nullable()->after('device');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn(['service', 'device', 'metadata']);
        });
    }
};
