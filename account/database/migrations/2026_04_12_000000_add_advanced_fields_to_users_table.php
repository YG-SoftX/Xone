<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active')->after('password');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('user')->after('status');
            }
            if (!Schema::hasColumn('users', 'web_app_activity')) {
                $table->boolean('web_app_activity')->default(true);
            }
            if (!Schema::hasColumn('users', 'timeline_history')) {
                $table->boolean('timeline_history')->default(true);
            }
            if (!Schema::hasColumn('users', 'device_access_logs')) {
                $table->boolean('device_access_logs')->default(true);
            }
            if (!Schema::hasColumn('users', 'yg_pay_ledger')) {
                $table->boolean('yg_pay_ledger')->default(true);
            }
            if (!Schema::hasColumn('users', 'personalized_ads')) {
                $table->boolean('personalized_ads')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'role',
                'web_app_activity',
                'timeline_history',
                'device_access_logs',
                'yg_pay_ledger',
                'personalized_ads',
            ]);
        });
    }
};
