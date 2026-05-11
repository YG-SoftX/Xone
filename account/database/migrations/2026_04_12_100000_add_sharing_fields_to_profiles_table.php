<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->boolean('share_profile')->default(false)->after('bio');
            $table->boolean('share_activity')->default(false)->after('share_profile');
            $table->boolean('allow_sharing_requests')->default(true)->after('share_activity');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['share_profile', 'share_activity', 'allow_sharing_requests']);
        });
    }
};
