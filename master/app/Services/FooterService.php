<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FooterService
{
    protected string $masterApiUrl;

    public function __construct()
    {
        // Get master API URL from config or environment
        $this->masterApiUrl = rtrim(config('services.master_api.url', env('MASTER_API_URL', 'http://localhost:8001')), '/');
    }

    /**
     * Fetch footer items for a specific service
     * 
     * @param string $serviceKey Service identifier (e.g., 'mail', 'drive')
     * @return array
     */
    public function getFooterItems(string $serviceKey = 'global'): array
    {
        $cacheKey = "external_footer.{$serviceKey}";

        return Cache::remember($cacheKey, 3600, function () use ($serviceKey) {
            try {
                $response = Http::timeout(5)->get("{$this->masterApiUrl}/api/footer/{$serviceKey}");

                if ($response->successful()) {
                    return $response->json('items', []);
                }

                \Log::warning('Failed to fetch footer items from master', [
                    'service' => $serviceKey,
                    'status' => $response->status(),
                ]);

                return $this->getDefaultFooterItems();
            } catch (\Exception $e) {
                \Log::error('Error fetching footer items', [
                    'service' => $serviceKey,
                    'error' => $e->getMessage(),
                ]);

                return $this->getDefaultFooterItems();
            }
        });
    }

    /**
     * Get default footer items as fallback
     * 
     * @return array
     */
    protected function getDefaultFooterItems(): array
    {
        return [
            [
                'label' => 'Privacy Policy',
                'url' => '/privacy',
                'icon' => 'fas fa-shield-alt',
                'target' => '_self',
                'rel' => '',
            ],
            [
                'label' => 'Terms of Service',
                'url' => '/terms',
                'icon' => 'fas fa-file-contract',
                'target' => '_self',
                'rel' => '',
            ],
            [
                'label' => 'Support',
                'url' => '/support',
                'icon' => 'fas fa-life-ring',
                'target' => '_self',
                'rel' => '',
            ],
        ];
    }

    /**
     * Clear cached footer items
     * 
     * @param string|null $serviceKey Specific service or null for all
     * @return void
     */
    public function clearCache(?string $serviceKey = null): void
    {
        if ($serviceKey) {
            Cache::forget("external_footer.{$serviceKey}");
        } else {
            // Clear all service caches
            $services = ['global', 'mail', 'drive', 'docx', 'chat', 'calendar',
                        'contacts', 'notes', 'xcel', 'meet', 'pay', 'ai',
                        'account', 'appstore', 'home'];

            foreach ($services as $service) {
                Cache::forget("external_footer.{$service}");
            }
        }
    }
}
