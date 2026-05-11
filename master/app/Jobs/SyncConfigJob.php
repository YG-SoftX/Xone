<?php

namespace App\Jobs;

use App\Models\AppModule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SyncConfigJob
 * 
 * Synchronizes configuration across all YG ecosystem services.
 * Ensures consistent settings for shared resources (API keys, URLs, etc.).
 */
class SyncConfigJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;
    public $backoff = [30, 60, 120];

    protected string $configKey;
    protected mixed $configValue;
    protected ?string $targetService;

    /**
     * Create a new job instance.
     */
    public function __construct(string $configKey, mixed $configValue, ?string $targetService = null)
    {
        $this->configKey = $configKey;
        $this->configValue = $configValue;
        $this->targetService = $targetService; // null = sync to all services
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting configuration sync", [
            'key' => $this->configKey,
            'target_service' => $this->targetService ?? 'all',
        ]);

        try {
            $services = $this->targetService
                ? [AppModule::where('slug', $this->targetService)->first()]
                : AppModule::where('is_active', true)->get();

            foreach ($services as $service) {
                if (!$service) continue;

                $this->syncToService($service);
            }

            Log::info("Configuration sync completed successfully", [
                'key' => $this->configKey,
                'services_updated' => count($services),
            ]);

        } catch (\Exception $e) {
            Log::error("Configuration sync failed", [
                'key' => $this->configKey,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync configuration to a specific service.
     */
    protected function syncToService(AppModule $service): void
    {
        $syncUrl = rtrim($service->url, '/') . '/api/admin/config/sync';
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $service->api_key,
            'Content-Type' => 'application/json',
            'X-Sync-Source' => 'yg-master',
        ])->timeout(30)->post($syncUrl, [
            'key' => $this->configKey,
            'value' => $this->configValue,
            'timestamp' => now()->toIso8601String(),
        ]);

        if ($response->successful()) {
            Log::info("Config synced to service", [
                'service' => $service->slug,
                'key' => $this->configKey,
            ]);
        } else {
            throw new \Exception(
                "Failed to sync config to {$service->slug}: HTTP {$response->status()}"
            );
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical("SyncConfigJob permanently failed", [
            'key' => $this->configKey,
            'target_service' => $this->targetService ?? 'all',
            'error' => $exception->getMessage(),
        ]);
    }
}
