<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_photo_path')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('active'); // active, suspended, pending
            $table->string('role')->default('user'); // admin, super_admin, user, developer
            $table->string('timezone')->default('UTC');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->json('metadata')->nullable(); // For flexible history/logs context
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_photo_path',
                'phone',
                'status',
                'role',
                'timezone',
                'last_login_at',
                'last_login_ip',
                'metadata'
            ]);
        });
    }
};
