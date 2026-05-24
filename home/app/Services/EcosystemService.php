<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http as HttpFacade;
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
     * List of ecosystem services with metadata and health status.
     */
    private array $services = [
        'mail' => [
            'name' => 'Mail',
            'description' => 'Secure email service',
            'url' => 'https://mail.ygxone.com',
            'icon' => 'fas fa-envelope',
            'icon_color' => '#3b82f6',
            'enabled' => true,
        ],
        'drive' => [
            'name' => 'Drive',
            'description' => 'Cloud storage & file sharing',
            'url' => 'https://drive.ygxone.com',
            'icon' => 'fas fa-cloud',
            'icon_color' => '#10b981',
            'enabled' => true,
        ],
        'docx' => [
            'name' => 'DocX',
            'description' => 'Document editor & processor',
            'url' => 'https://docx.ygxone.com',
            'icon' => 'fas fa-file-word',
            'icon_color' => '#ef4444',
            'enabled' => true,
        ],
        'calendar' => [
            'name' => 'Calendar',
            'description' => 'Schedule & appointment manager',
            'url' => 'https://calendar.ygxone.com',
            'icon' => 'fas fa-calendar',
            'icon_color' => '#8b5cf6',
            'enabled' => true,
        ],
        'chat' => [
            'name' => 'Chat',
            'description' => 'Secure messaging',
            'url' => 'https://chat.ygxone.com',
            'icon' => 'fas fa-comments',
            'icon_color' => '#f59e0b',
            'enabled' => true,
        ],
        'contacts' => [
            'name' => 'Contacts',
            'description' => 'Contact management',
            'url' => 'https://contacts.ygxone.com',
            'icon' => 'fas fa-address-book',
            'icon_color' => '#ec4899',
            'enabled' => true,
        ],
        'notes' => [
            'name' => 'Notes',
            'description' => 'Quick notes & reminders',
            'url' => 'https://notes.ygxone.com',
            'icon' => 'fas fa-sticky-note',
            'icon_color' => '#8b5cf6',
            'enabled' => true,
        ],
        'xcel' => [
            'name' => 'Xcel',
            'description' => 'Spreadsheet application',
            'url' => 'https://xcel.ygxone.com',
            'icon' => 'fas fa-table',
            'icon_color' => '#22c55e',
            'enabled' => true,
        ],
        'ai' => [
            'name' => 'AI Assistant',
            'description' => 'Intelligent assistant',
            'url' => 'https://ai.ygxone.com',
            'icon' => 'fas fa-robot',
            'icon_color' => '#f97316',
            'enabled' => true,
        ],
        'developer' => [
            'name' => 'Developer',
            'description' => 'API & developer tools',
            'url' => 'https://developer.ygxone.com',
            'icon' => 'fas fa-code',
            'icon_color' => '#6366f1',
            'enabled' => true,
        ],
        'collect' => [
            'name' => 'Collect',
            'description' => 'Data collection forms',
            'url' => 'https://collect.ygxone.com',
            'icon' => 'fas fa-database',
            'icon_color' => '#14b8a6',
            'enabled' => true,
        ],
        'support' => [
            'name' => 'Support',
            'description' => 'Help & support center',
            'url' => 'https://support.ygxone.com',
            'icon' => 'fas fa-life-ring',
            'icon_color' => '#8b5cf6',
            'enabled' => true,
        ],
    ];

    /**
     * Get all active ecosystem apps formatted for the home UI.
     *
     * @return array<int, array> Sorted list of active app modules.
     */
    public function getActiveApps(): array
    {
        // Check if we should skip health checks (for development or when subdomains aren't ready)
        $skipHealthChecks = config('app.skip_ecosystem_health_checks', false);
        
        $activeApps = [];

        foreach ($this->services as $key => $service) {
            if ($service['enabled']) {
                $isHealthy = true;
                
                // Only perform health checks if not skipped
                if (!$skipHealthChecks) {
                    $isHealthy = Cache::remember("ecosystem_service_health_{$key}", 300, function () use ($key, $service) {
                        return $this->checkServiceHealth($key, $service['url']);
                    });
                }

                if ($isHealthy) {
                    $activeApps[] = $service;
                }
            }
        }

        return $activeApps;
    }

    /**
     * Get all ecosystem services.
     */
    public function getAllServices(): array
    {
        return $this->services;
    }

    /**
     * Get a specific service by name.
     */
    public function getService(string $serviceName): ?array
    {
        return $this->services[$serviceName] ?? null;
    }

    /**
     * Check if a service is healthy.
     */
    private function checkServiceHealth(string $key, string $url): bool
    {
        try {
            // Validate URL format
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                Log::warning("Invalid URL format for {$key} at {$url}");
                return false;
            }
            
            // Check if Http facade is available
            if (!class_exists('\Illuminate\Support\Facades\Http')) {
                // Fallback to simple cURL check if Http facade is not available
                return $this->checkServiceHealthWithCurl($url);
            }
            
            // Try to reach the service's health endpoint with shorter timeout
            $healthUrl = rtrim($url, '/') . '/up';
            $response = HttpFacade::timeout(2)->connectTimeout(1)->get($healthUrl);

            return $response->successful();
        } catch (\Exception $e) {
            Log::debug("Service health check failed for {$key} at {$url}", [
                'error' => $e->getMessage()
            ]);

            // If the /up endpoint fails, try the base URL with shorter timeout
            try {
                $response = HttpFacade::timeout(2)->connectTimeout(1)->get($url);
                return $response->successful();
            } catch (\Exception $e2) {
                Log::debug("Fallback health check also failed for {$key} at {$url}", [
                    'error' => $e2->getMessage()
                ]);
                return false;
            }
        }
    }

    /**
     * Fallback method to check service health using cURL when Http facade is not available
     */
    private function checkServiceHealthWithCurl(string $url): bool
    {
        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Log::warning("Invalid URL format for cURL health check: {$url}");
            return false;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);        // Total timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); // Connection timeout
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'YGXONE-Ecosystem-Health-Check/1.0');
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Log errors for debugging but don't fail completely
        if ($error) {
            Log::debug("cURL error for {$url}: {$error}");
            return false;
        }
        
        return $httpCode >= 200 && $httpCode < 400;
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
     * Internal method to fetch all apps from either database or config
     */
    private function fetchAllApps(): array
    {
        // First, try to get from the database table
        $dbApps = [];
        try {
            $dbApps = DB::table('app_modules')->get()->toArray();
        } catch (\Exception $e) {
            // If database table doesn't exist, fall back to config
            Log::info('app_modules table not found, using service config', ['error' => $e->getMessage()]);
        }
        
        // Convert DB apps to our standard format
        $formattedDbApps = [];
        foreach ($dbApps as $app) {
            $app = (array)$app;
            $formattedDbApps[] = [
                'name' => $app['name'] ?? 'Unknown App',
                'description' => $app['description'] ?? 'No description',
                'url' => $app['url'] ?? '#',
                'icon' => $app['icon'] ?? 'fas fa-puzzle-piece',
                'icon_color' => $app['icon_color'] ?? '#6b7280',
                'enabled' => $app['is_active'] ?? true,
                'slug' => $app['slug'] ?? strtolower(str_replace(' ', '_', $app['name'] ?? 'unknown_app'))
            ];
        }
        
        // If no DB apps, fall back to the built-in services array
        if (empty($formattedDbApps)) {
            $formattedDbApps = array_map(function($key, $service) {
                $service['slug'] = $key;
                return $service;
            }, array_keys($this->services), $this->services);
        }
        
        return $formattedDbApps;
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
}