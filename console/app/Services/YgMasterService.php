<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class YgMasterService
{
    protected string $masterUrl;
    protected string $apiKey;
    protected int $timeout;

    public function __construct()
    {
        $this->masterUrl = config('services.yg_master.url', 'https://master.ygxone.com');
        $this->apiKey = config('services.yg_master.api_key');
        $this->timeout = config('services.yg_master.timeout', 10);
    }

    /**
     * Register this console instance with YG Master
     */
    public function registerConsole(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'X-Service-Type' => 'yg-console',
            ])->timeout($this->timeout)->post($this->masterUrl . '/api/v1/services/register', [
                'service_name' => 'YG Console',
                'service_type' => 'developer_platform',
                'service_url' => config('app.url'),
                'version' => config('app.version', '1.0.0'),
                'health_check_url' => route('health.check'),
                'metadata' => [
                    'features' => [
                        'project_management',
                        'api_keys',
                        'oauth_apps',
                        'billing',
                        'ai_services',
                        'webhooks',
                        'play_store',
                    ],
                    'region' => config('app.region', 'global'),
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Cache registration details
                Cache::put('yg_master.registration', $data, now()->addDays(30));
                
                Log::info('YG Console registered with YG Master', [
                    'service_id' => $data['service_id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => 'Registration failed: ' . $response->body(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to register with YG Master', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send heartbeat/health status to YG Master
     */
    public function sendHeartbeat(): array
    {
        try {
            $stats = $this->getSystemStats();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout($this->timeout)->post($this->masterUrl . '/api/v1/services/heartbeat', [
                'service_id' => Cache::get('yg_master.registration.service_id'),
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'metrics' => [
                    'active_projects' => $stats['active_projects'],
                    'total_api_calls_today' => $stats['api_calls_today'],
                    'active_subscriptions' => $stats['active_subscriptions'],
                    'queue_size' => $stats['queue_size'],
                    'cache_hit_rate' => $stats['cache_hit_rate'],
                    'avg_response_time_ms' => $stats['avg_response_time'],
                ],
                'uptime_seconds' => $this->getUptime(),
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Heartbeat failed: ' . $response->body(),
            ];

        } catch (\Exception $e) {
            Log::warning('Failed to send heartbeat to YG Master', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Report metrics to YG Master
     */
    public function reportMetrics(): array
    {
        try {
            $metrics = [
                'projects' => [
                    'total' => \App\Models\Project::count(),
                    'active' => \App\Models\Project::where('status', 'active')->count(),
                    'created_today' => \App\Models\Project::whereDate('created_at', today())->count(),
                ],
                'api_keys' => [
                    'total' => \App\Models\ApiKey::count(),
                    'active' => \App\Models\ApiKey::active()->count(),
                    'revoked_today' => \App\Models\ApiKey::whereDate('updated_at', today())->whereNull('expires_at')->count(),
                ],
                'billing' => [
                    'total_revenue_today' => \App\Models\BillingInvoice::whereDate('created_at', today())->where('status', 'paid')->sum('amount'),
                    'pending_invoices' => \App\Models\BillingInvoice::where('status', 'pending')->count(),
                    'active_subscriptions' => \App\Models\Subscription::where('status', 'active')->count(),
                ],
                'ai_usage' => [
                    'total_tokens_today' => \App\Models\AiUsageLog::whereDate('created_at', today())->sum(\DB::raw('prompt_tokens + completion_tokens')),
                    'total_cost_today' => \App\Models\AiUsageLog::whereDate('created_at', today())->sum('cost'),
                    'requests_today' => \App\Models\AiUsageLog::whereDate('created_at', today())->count(),
                ],
                'webhooks' => [
                    'total_endpoints' => \App\Models\WebhookEndpoint::count(),
                    'active_endpoints' => \App\Models\WebhookEndpoint::where('active', true)->count(),
                    'deliveries_today' => \App\Models\WebhookDelivery::whereDate('created_at', today())->count(),
                    'failed_deliveries_today' => \App\Models\WebhookDelivery::whereDate('created_at', today())->whereNotNull('status_code')->where('status_code', '>=', 400)->count(),
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout($this->timeout)->post($this->masterUrl . '/api/v1/services/metrics', [
                'service_id' => Cache::get('yg_master.registration.service_id'),
                'timestamp' => now()->toISOString(),
                'metrics' => $metrics,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Metrics reporting failed: ' . $response->body(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to report metrics to YG Master', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch configuration from YG Master
     */
    public function fetchConfiguration(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout($this->timeout)->get($this->masterUrl . '/api/v1/services/config', [
                'service_id' => Cache::get('yg_master.registration.service_id'),
            ]);

            if ($response->successful()) {
                $config = $response->json();
                
                // Cache configuration for 1 hour
                Cache::put('yg_master.config', $config, now()->addHour());
                
                return [
                    'success' => true,
                    'data' => $config,
                ];
            }

            return [
                'success' => false,
                'error' => 'Configuration fetch failed: ' . $response->body(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to fetch configuration from YG Master', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Receive deployment command from YG Master
     */
    public function handleDeploymentCommand(array $command): array
    {
        try {
            Log::info('Received deployment command from YG Master', [
                'command' => $command,
            ]);

            $action = $command['action'] ?? null;

            switch ($action) {
                case 'restart':
                    return $this->handleRestart();
                
                case 'clear_cache':
                    return $this->handleClearCache();
                
                case 'run_migrations':
                    return $this->handleMigrations();
                
                case 'update_config':
                    return $this->handleConfigUpdate($command['config'] ?? []);
                
                default:
                    return [
                        'success' => false,
                        'error' => 'Unknown command: ' . $action,
                    ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to handle deployment command', [
                'error' => $e->getMessage(),
                'command' => $command,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get system statistics
     */
    protected function getSystemStats(): array
    {
        return [
            'active_projects' => \App\Models\Project::where('status', 'active')->count(),
            'api_calls_today' => \App\Models\AiUsageLog::whereDate('created_at', today())->count(),
            'active_subscriptions' => \App\Models\Subscription::where('status', 'active')->count(),
            'queue_size' => \Illuminate\Support\Facades\Queue::size(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'avg_response_time' => $this->getAvgResponseTime(),
        ];
    }

    /**
     * Get service uptime in seconds
     */
    protected function getUptime(): int
    {
        $startTime = Cache::get('service_start_time', time());
        return time() - $startTime;
    }

    /**
     * Get cache hit rate percentage
     */
    protected function getCacheHitRate(): float
    {
        // Simplified - in production, track this properly
        return 85.5;
    }

    /**
     * Get average response time in ms
     */
    protected function getAvgResponseTime(): float
    {
        // Simplified - in production, track this properly
        return 120.5;
    }

    /**
     * Handle restart command
     */
    protected function handleRestart(): array
    {
        Log::warning('Service restart requested by YG Master');
        
        // In production, this would trigger a graceful restart
        // For now, just acknowledge the command
        
        return [
            'success' => true,
            'message' => 'Restart command received',
        ];
    }

    /**
     * Handle clear cache command
     */
    protected function handleClearCache(): array
    {
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');

        Log::info('Cache cleared by YG Master command');

        return [
            'success' => true,
            'message' => 'Cache cleared successfully',
        ];
    }

    /**
     * Handle migrations command
     */
    protected function handleMigrations(): array
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', [
                '--force' => true,
            ]);

            Log::info('Migrations run by YG Master command');

            return [
                'success' => true,
                'message' => 'Migrations completed successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Migration failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle configuration update
     */
    protected function handleConfigUpdate(array $newConfig): array
    {
        // Update local configuration
        foreach ($newConfig as $key => $value) {
            config([$key => $value]);
        }

        Log::info('Configuration updated by YG Master', [
            'keys' => array_keys($newConfig),
        ]);

        return [
            'success' => true,
            'message' => 'Configuration updated',
        ];
    }
}
