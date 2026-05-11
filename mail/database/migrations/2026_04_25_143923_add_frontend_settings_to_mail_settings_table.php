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
            $table->string('hero_title')->default('Sovereign Communication for the Modern Age')->after('brand_name');
            $table->text('hero_subtitle')->nullable()->after('hero_title');
            $table->string('cta_text')->default('Enter Mailbox')->after('hero_subtitle');
            $table->boolean('show_landing_page')->default(true)->after('cta_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mail_settings', function (Blueprint $table) {
            $table->dropColumn(['hero_title', 'hero_subtitle', 'cta_text', 'show_landing_page']);
        });
    }
};
