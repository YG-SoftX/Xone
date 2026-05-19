<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the `themes` table used by HomeThemeService to dynamically
     * generate CSS variables, logos, fonts, and design settings for the
     * home module UI. Managed from the master panel.
     */
    public function up(): void
    {
        if (Schema::hasTable('themes')) {
            return;
        }

        Schema::create('themes', function (Blueprint $table) {
            $table->id();

            // Which service this theme applies to (e.g. 'home', 'yg-xone', 'mail')
            $table->string('service', 50)->index();

            // Human-readable theme name (e.g. 'Default Light', 'Midnight Dark')
            $table->string('name', 100);

            // Active flag — only one theme per service should be active at a time
            $table->boolean('is_active')->default(true);

            // JSON payloads for flexible design attributes
            $table->json('colors')->nullable()->comment(
                'Color palette: {"primary": "#2563eb", "secondary": "#7c3aed", ...}'
            );
            $table->json('fonts')->nullable()->comment(
                'Font family config: {"heading_font": "Outfit", "body_font": "Inter"}'
            );
            $table->json('logos')->nullable()->comment(
                'Logo URLs: {"header_logo": "/storage/logo.png", "favicon": "/storage/favicon.ico"}'
            );
            $table->json('settings')->nullable()->comment(
                'Design settings: {"border_radius": 12, "shadow_style": "modern", ...}'
            );

            // Optional background gradient (e.g. "linear-gradient(135deg, #667eea 0%, #764ba2 100%)")
            $table->text('background_gradient')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
