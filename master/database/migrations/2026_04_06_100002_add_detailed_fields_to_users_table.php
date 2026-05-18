<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable();
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active'); // active, suspended, pending
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('user'); // admin, super_admin, user, developer
            }
            if (!Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone')->default('UTC');
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip')->nullable();
            }
            if (!Schema::hasColumn('users', 'metadata')) {
                $table->json('metadata')->nullable(); // For flexible history/logs context
            }
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
