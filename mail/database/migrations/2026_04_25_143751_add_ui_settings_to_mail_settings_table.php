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
        Schema::table('mail_settings', function (Blueprint $table) {
            $table->string('primary_color')->default('#9B1B30')->after('vacation_end');
            $table->string('sidebar_type')->default('glass')->after('primary_color');
            $table->string('brand_name')->default('YGXONE Mail')->after('sidebar_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mail_settings', function (Blueprint $table) {
            $table->dropColumn(['primary_color', 'sidebar_type', 'brand_name']);
        });
    }
};
