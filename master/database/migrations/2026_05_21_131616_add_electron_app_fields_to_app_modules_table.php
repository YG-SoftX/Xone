<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_modules', function (Blueprint $table) {
            $table->string('type')->default('laravel')->after('slug'); // laravel, static, electron
            $table->string('version')->nullable()->after('icon');
            $table->string('platform')->nullable()->after('version');
            $table->string('arch')->nullable()->after('platform');
            $table->string('status')->default('inactive')->after('is_core'); // inactive, registered, online, offline
            $table->json('config')->nullable()->after('base_url');
            $table->json('health_data')->nullable()->after('config');
            $table->timestamp('installed_at')->nullable()->after('health_data');
            $table->timestamp('last_seen')->nullable()->after('installed_at');
            $table->timestamp('last_health_check')->nullable()->after('last_seen');
        });
    }

    public function down(): void
    {
        Schema::table('app_modules', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'version',
                'platform',
                'arch',
                'status',
                'config',
                'health_data',
                'installed_at',
                'last_seen',
                'last_health_check'
            ]);
        });
    }
};