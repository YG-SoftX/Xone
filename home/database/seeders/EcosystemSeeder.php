<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * EcosystemSeeder — seeds default data for the ecosystem services.
 *
 * Populates:
 *   - app_modules  (default YG ecosystem services)
 *   - themes       (a default light theme for the home module)
 *
 * Run via: php artisan db:seed --class=Database\\Seeders\\EcosystemSeeder
 */
class EcosystemSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAppModules();
        $this->seedDefaultTheme();
    }

    private function seedAppModules(): void
    {
        if (!Schema::hasTable('app_modules')) {
            return;
        }

        $existing = DB::table('app_modules')->count();
        if ($existing > 0) {
            return; // Already seeded
        }

        $modules = [
            ['name' => 'Mail',     'slug' => 'mail',     'icon' => 'fas fa-envelope',     'is_active' => true,  'is_core' => true,  'base_url' => null],
            ['name' => 'Drive',    'slug' => 'drive',    'icon' => 'fas fa-cloud',        'is_active' => true,  'is_core' => true,  'base_url' => null],
            ['name' => 'DocX',     'slug' => 'docx',     'icon' => 'fas fa-file-alt',     'is_active' => true,  'is_core' => true,  'base_url' => null],
            ['name' => 'Xcel',     'slug' => 'xcel',     'icon' => 'fas fa-table',        'is_active' => true,  'is_core' => false, 'base_url' => null],
            ['name' => 'Chat',     'slug' => 'chat',     'icon' => 'fas fa-comment',      'is_active' => true,  'is_core' => false, 'base_url' => null],
            ['name' => 'Meet',     'slug' => 'meet',     'icon' => 'fas fa-video',        'is_active' => true,  'is_core' => false, 'base_url' => null],
            ['name' => 'Calendar', 'slug' => 'calendar', 'icon' => 'fas fa-calendar-alt', 'is_active' => true,  'is_core' => false, 'base_url' => null],
            ['name' => 'Contacts', 'slug' => 'contacts', 'icon' => 'fas fa-address-book', 'is_active' => true,  'is_core' => false, 'base_url' => null],
            ['name' => 'Notes',    'slug' => 'notes',    'icon' => 'fas fa-sticky-note',  'is_active' => true,  'is_core' => false, 'base_url' => null],
            ['name' => 'Developer','slug' => 'developer','icon' => 'fas fa-code',         'is_active' => true,  'is_core' => false, 'base_url' => null],
            ['name' => 'Pay',      'slug' => 'pay',      'icon' => 'fas fa-credit-card',  'is_active' => true,  'is_core' => false, 'base_url' => null],
            ['name' => 'Account',  'slug' => 'account',  'icon' => 'fas fa-user-shield',  'is_active' => true,  'is_core' => true,  'base_url' => null],
            ['name' => 'YGXONE',   'slug' => 'yg-xone',  'icon' => 'fas fa-globe',        'is_active' => true,  'is_core' => true,  'base_url' => 'https://ygxone.com'],
        ];

        $now = now();
        foreach ($modules as $module) {
            DB::table('app_modules')->insert(array_merge($module, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $this->command?->info('✅ Seeded ' . count($modules) . ' app modules.');
    }

    private function seedDefaultTheme(): void
    {
        if (!Schema::hasTable('themes')) {
            return;
        }

        $existing = DB::table('themes')->count();
        if ($existing > 0) {
            return; // Already seeded
        }

        DB::table('themes')->insert([
            'service'            => 'yg-xone',
            'name'               => 'Default Light',
            'is_active'          => true,
            'colors'             => json_encode([
                'primary'   => '#2563eb',
                'secondary' => '#7c3aed',
                'accent'    => '#f59e0b',
                'background'=> '#ffffff',
                'surface'   => '#f8fafc',
                'text'      => '#0f172a',
                'text-dim'  => '#64748b',
                'border'    => '#e2e8f0',
                'success'   => '#22c55e',
                'danger'    => '#ef4444',
            ]),
            'fonts'              => json_encode([
                'heading_font' => 'Outfit',
                'body_font'    => 'Inter',
            ]),
            'logos'              => json_encode([]),
            'settings'           => json_encode([
                'border_radius'    => 12,
                'shadow_style'     => 'modern',
                'powered_by_text'  => 'Powered by',
                'copyright'        => 'YGXONE Sovereign Intelligence Platform',
            ]),
            'background_gradient' => null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->command?->info('✅ Seeded default theme.');
    }
}
