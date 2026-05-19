<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * EcosystemService — reads ecosystem app data from the shared `app_modules`
 * database table with a fallback to the master ecosystem config.
 *
 * This is the bridge that makes the home UI fully controllable from the master
 * panel. Admins toggle visibility, reorder apps, and update URLs in the master
 * panel, and those changes are reflected here instantly (with cache TTL).
 */
class EcosystemService
{
    /**
     * Cache key prefix for ecosystem data.
     */
    private const CACHE_PREFIX = 'yg_ecosystem_';

    /**
     * Cache TTL in seconds (default 15 minutes).
     */
    private const CACHE_TTL = 900;

    /**
     * Get all active ecosystem apps formatted for the home UI.
     *
     * @return array<int, array> Sorted list of active app modules.
     */
    public function getActiveApps(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'active_apps', self::CACHE_TTL, function () {
            return $this->fetchActiveApps();
        });
    }

    /**
     * Get all ecosystem apps (including inactive) for admin views.
     *
     * @return array<int, array> Full list of app modules with status.
     */
    public function getAllApps(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'all_apps', self::CACHE_TTL, function () {
            return $this->fetchAllApps();
        });
    }

    /**
     * Get a single app module by slug.
     */
    public function getApp(string $slug): ?array
    {
        $apps = $this->getAllApps();
        foreach ($apps as $app) {
            if ($app['slug'] === $slug) {
                return $app;
            }
        }
        return null;
    }

    /**
     * Get ecosystem health summary for the admin dashboard.
     */
    public function getHealthSummary(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'health_summary', 300, function () {
            $apps = $this->getAllApps();
            $total = count($apps);
            $active = 0;

            foreach ($apps as $app) {
                if ($app['is_active']) {
                    $active++;
                }
            }

            return [
                'total'        => $total,
                'active'       => $active,
                'inactive'     => $total - $active,
                'last_synced'  => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Clear the ecosystem cache (called after master panel updates).
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'active_apps');
        Cache::forget(self::CACHE_PREFIX . 'all_apps');
        Cache::forget(self::CACHE_PREFIX . 'health_summary');
    }

    /**
     * Get app icon HTML (FontAwesome or emoji fallback).
     */
    public function getAppIcon(string $icon, string $class = ''): string
    {
        // If it's already an emoji, return as-is
        if (preg_match('/[\x{1F000}-\x{1FFFF}]/u', $icon)) {
            return '<span class="' . e($class) . '">' . $icon . '</span>';
        }

        // If it's a heroicon or FontAwesome class
        return '<i class="' . e($icon) . ' ' . e($class) . '"></i>';
    }

    /**
     * Get standard ecosystem service definitions with icons, colors, descriptions.
     */
    public function getServiceDefinitions(): array
    {
        return [
            'mail'     => ['label' => 'Mail',     'icon' => 'fas fa-envelope',      'color' => '#ef4444', 'description' => 'Sovereign Email'],
            'drive'    => ['label' => 'Drive',    'icon' => 'fas fa-cloud',         'color' => '#22c55e', 'description' => 'Encrypted Storage'],
            'docx'     => ['label' => 'DocX',     'icon' => 'fas fa-file-alt',      'color' => '#3b82f6', 'description' => 'Documents & Editing'],
            'xcel'     => ['label' => 'Xcel',     'icon' => 'fas fa-table',         'color' => '#10b981', 'description' => 'Spreadsheets & Data'],
            'chat'     => ['label' => 'Chat',     'icon' => 'fas fa-comment',       'color' => '#8b5cf6', 'description' => 'Team Messaging'],
            'meet'     => ['label' => 'Meet',     'icon' => 'fas fa-video',         'color' => '#f59e0b', 'description' => 'Video Conferencing'],
            'calendar' => ['label' => 'Calendar', 'icon' => 'fas fa-calendar-alt',  'color' => '#ec4899', 'description' => 'Scheduling'],
            'contacts' => ['label' => 'Contacts', 'icon' => 'fas fa-address-book',  'color' => '#14b8a6', 'description' => 'Contact Management'],
            'notes'    => ['label' => 'Notes',    'icon' => 'fas fa-sticky-note',   'color' => '#f97316', 'description' => 'Note Taking'],
            'developer'=> ['label' => 'Developer','icon' => 'fas fa-code',          'color' => '#6366f1', 'description' => 'API Platform'],
            'pay'      => ['label' => 'Pay',      'icon' => 'fas fa-credit-card',   'color' => '#06b6d4', 'description' => 'Payments & Billing'],
            'account'  => ['label' => 'Account',  'icon' => 'fas fa-user-shield',   'color' => '#64748b', 'description' => 'Identity & Security'],
        ];
    }

    /**
     * Get ecosystem stats from the shared database.
     */
    public function getEcosystemStats(): array
    {
        try {
            return [
                'total_apps'      => DB::table('app_modules')->count(),
                'active_apps'     => DB::table('app_modules')->where('is_active', true)->count(),
                'total_users'     => DB::table('users')->count(),
                'active_today'    => DB::table('search_training_data')
                    ->whereDate('created_at', today())
                    ->distinct('user_id')
                    ->count('user_id'),
            ];
        } catch (\Exception $e) {
            Log::error('EcosystemService::getEcosystemStats failed', ['error' => $e->getMessage()]);
            return [
                'total_apps'   => 0,
                'active_apps'  => 0,
                'total_users'  => 0,
                'active_today' => 0,
            ];
        }
    }

    // ── Private Helpers ─────────────────────────────────────────────────────────────

    /**
     * Fetch active apps from the shared app_modules table.
     * Falls back to the master ecosystem config if the table is missing.
     */
    private function fetchActiveApps(): array
    {
        try {
            $modules = DB::table('app_modules')
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            if ($modules->isEmpty()) {
                return $this->getDefaultApps(true);
            }

            return $modules->map(function ($module) {
                return $this->formatModule($module, true);
            })->values()->toArray();
        } catch (\Exception $e) {
            Log::warning('app_modules table not accessible, using defaults', ['error' => $e->getMessage()]);
            return $this->getDefaultApps(true);
        }
    }

    /**
     * Fetch ALL apps from the shared app_modules table (including inactive).
     */
    private function fetchAllApps(): array
    {
        try {
            $modules = DB::table('app_modules')
                ->orderBy('id')
                ->get();

            if ($modules->isEmpty()) {
                return $this->getDefaultApps(false);
            }

            return $modules->map(function ($module) {
                return $this->formatModule($module, false);
            })->values()->toArray();
        } catch (\Exception $e) {
            Log::warning('app_modules table not accessible, using defaults', ['error' => $e->getMessage()]);
            return $this->getDefaultApps(false);
        }
    }

    /**
     * Format a database module row into a consistent array.
     */
    private function formatModule($module, bool $activeOnly): array
    {
        $definitions = $this->getServiceDefinitions();
        $slug = $module->slug ?? 'unknown';
        $def = $definitions[$slug] ?? ['label' => $module->name, 'icon' => 'fas fa-cube', 'color' => '#6b7280', 'description' => $module->name];

        $url = $module->base_url;
        if (!$url) {
            $url = match ($slug) {
                'yg-xone', 'home'  => config('app.url', 'https://ygxone.com'),
                'yg-account', 'account' => config('services.yg_account.url', 'https://account.ygxone.com'),
                'yg-mail', 'mail'      => config('services.yg_mail.url', 'https://mail.ygxone.com'),
                'yg-drive', 'drive'    => config('services.yg_drive.url', 'https://drive.ygxone.com'),
                'yg-docx', 'docx'      => config('services.yg_docx.url', 'https://docs.ygxone.com'),
                'yg-calendar', 'calendar' => config('services.yg_calendar.url', 'https://calendar.ygxone.com'),
                'yg-contacts', 'contacts' => config('services.yg_contacts.url', 'https://contacts.ygxone.com'),
                default => "https://{$slug}.ygxone.com",
            };
        }

        return [
            'id'          => $module->id ?? null,
            'name'        => $module->name ?? $def['label'],
            'slug'        => $slug,
            'icon'        => $module->icon ?? $def['icon'],
            'icon_color'  => $def['color'],
            'description' => $def['description'],
            'url'         => $url,
            'is_active'   => (bool) ($module->is_active ?? true),
            'is_core'     => (bool) ($module->is_core ?? false),
        ];
    }

    /**
     * Default apps when the app_modules table isn't available yet.
     */
    private function getDefaultApps(bool $activeOnly): array
    {
        $definitions = $this->getServiceDefinitions();
        $apps = [];

        $defaultModules = [
            ['slug' => 'mail',     'name' => 'Mail',     'is_active' => true,  'is_core' => true],
            ['slug' => 'drive',    'name' => 'Drive',    'is_active' => true,  'is_core' => true],
            ['slug' => 'docx',     'name' => 'DocX',     'is_active' => true,  'is_core' => true],
            ['slug' => 'xcel',     'name' => 'Xcel',     'is_active' => true,  'is_core' => false],
            ['slug' => 'chat',     'name' => 'Chat',     'is_active' => true,  'is_core' => false],
            ['slug' => 'meet',     'name' => 'Meet',     'is_active' => true,  'is_core' => false],
            ['slug' => 'calendar', 'name' => 'Calendar', 'is_active' => true,  'is_core' => false],
            ['slug' => 'contacts', 'name' => 'Contacts', 'is_active' => true,  'is_core' => false],
            ['slug' => 'notes',    'name' => 'Notes',    'is_active' => true,  'is_core' => false],
            ['slug' => 'developer','name' => 'Developer','is_active' => true,  'is_core' => false],
            ['slug' => 'pay',      'name' => 'Pay',      'is_active' => true,  'is_core' => false],
            ['slug' => 'account',  'name' => 'Account',  'is_active' => true,  'is_core' => true],
        ];

        foreach ($defaultModules as $mod) {
            if ($activeOnly && !$mod['is_active']) {
                continue;
            }
            $def = $definitions[$mod['slug']] ?? ['label' => $mod['name'], 'icon' => 'fas fa-cube', 'color' => '#6b7280', 'description' => $mod['name']];
            $url = match ($mod['slug']) {
                'mail'      => config('services.yg_mail.url', 'https://mail.ygxone.com'),
                'drive'     => config('services.yg_drive.url', 'https://drive.ygxone.com'),
                'docx'      => config('services.yg_docx.url', 'https://docs.ygxone.com'),
                'account'   => config('services.yg_account.url', 'https://account.ygxone.com'),
                default     => "https://{$mod['slug']}.ygxone.com",
            };

            $apps[] = [
                'id'          => null,
                'name'        => $mod['name'],
                'slug'        => $mod['slug'],
                'icon'        => $def['icon'],
                'icon_color'  => $def['color'],
                'description' => $def['description'],
                'url'         => $url,
                'is_active'   => $mod['is_active'],
                'is_core'     => $mod['is_core'],
            ];
        }

        return $apps;
    }
}
