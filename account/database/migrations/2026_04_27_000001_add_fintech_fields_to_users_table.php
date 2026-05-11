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
            if (!Schema::hasColumn('users', 'firstname')) {
                $table->string('firstname')->nullable();
            }
            if (!Schema::hasColumn('users', 'lastname')) {
                $table->string('lastname')->nullable();
            }
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique();
            }
            if (!Schema::hasColumn('users', 'mobile_code')) {
                $table->string('mobile_code')->nullable();
            }
            if (!Schema::hasColumn('users', 'mobile')) {
                $table->string('mobile')->nullable();
            }
            if (!Schema::hasColumn('users', 'full_mobile')) {
                $table->string('full_mobile')->nullable()->unique();
            }
            if (!Schema::hasColumn('users', 'refferal_user_id')) {
                $table->unsignedBigInteger('refferal_user_id')->nullable();
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->boolean('status')->default(true);
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable();
            }
            if (!Schema::hasColumn('users', 'kyc_verified')) {
                $table->boolean('kyc_verified')->default(false);
            }
            if (!Schema::hasColumn('users', 'two_factor_status')) {
                $table->boolean('two_factor_status')->default(false);
            }
            if (!Schema::hasColumn('users', 'two_factor_secret')) {
                $table->string('two_factor_secret')->nullable();
            }
            if (!Schema::hasColumn('users', 'device_id')) {
                $table->string('device_id')->nullable();
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
                'firstname', 'lastname', 'username', 'mobile_code', 'mobile', 
                'full_mobile', 'refferal_user_id', 'status', 'address', 
                'kyc_verified', 'two_factor_status', 'two_factor_secret', 'device_id'
            ]);
        });
    }
};
