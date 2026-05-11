<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->nullable();
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable();
            }
            if (!Schema::hasColumn('users', 'birthday')) {
                $table->date('birthday')->nullable();
            }
            if (!Schema::hasColumn('users', 'gender')) {
                $table->string('gender')->nullable();
            }
            if (!Schema::hasColumn('users', 'recovery_email')) {
                $table->string('recovery_email')->nullable();
            }
            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false);
            }
            if (!Schema::hasColumn('users', 'kyc_status')) {
                $table->string('kyc_status')->default('pending');
            }
            if (!Schema::hasColumn('users', 'security_logs')) {
                $table->json('security_logs')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'phone',
                'avatar',
                'birthday',
                'gender',
                'recovery_email',
                'two_factor_enabled',
                'kyc_status',
                'security_logs'
            ]);
        });
    }
};
