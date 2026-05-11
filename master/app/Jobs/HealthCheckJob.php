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
 * HealthCheckJob
 * 
 * Performs health checks on all registered services in the YG ecosystem.
 * Runs every minute via scheduler to ensure real-time monitoring.
 */
class HealthCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 2;

    protected ?string $serviceSlug;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $serviceSlug = null)
    {
        $this->serviceSlug = $serviceSlug; // null = check all services
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $services = $this->serviceSlug
            ? [AppModule::where('slug', $this->serviceSlug)->first()]
            : AppModule::where('is_active', true)->get();

        foreach ($services as $service) {
            if (!$service) continue;

            try {
                $healthUrl = rtrim($service->url, '/') . '/api/health';
                
                $response = Http::timeout(10)->get($healthUrl);
                
                if ($response->successful()) {
                    $healthData = $response->json();
                    
                    $service->update([
                        'status' => 'healthy',
                        'last_health_check' => now(),
                        'health_data' => $healthData,
                        'consecutive_failures' => 0,
                    ]);

                    Log::info("Service healthy: {$service->name}", [
                        'slug' => $service->slug,
                        'version' => $healthData['version'] ?? 'unknown',
                        'uptime' => $healthData['uptime'] ?? 'unknown',
                    ]);
                } else {
                    $this->markServiceDegraded($service, "HTTP {$response->status()}");
                }

            } catch (\Exception $e) {
                $this->markServiceUnhealthy($service, $e->getMessage());
            }
        }
    }

    /**
     * Mark service as degraded.
     */
    protected function markServiceDegraded(AppModule $service, string $reason): void
    {
        $failures = ($service->consecutive_failures ?? 0) + 1;
        
        $service->update([
            'status' => 'degraded',
            'last_health_check' => now(),
            'health_error' => $reason,
            'consecutive_failures' => $failures,
        ]);

        Log::warning("Service degraded: {$service->name}", [
            'slug' => $service->slug,
            'reason' => $reason,
            'failures' => $failures,
        ]);
    }

    /**
     * Mark service as unhealthy.
     */
    protected function markServiceUnhealthy(AppModule $service, string $error): void
    {
        $failures = ($service->consecutive_failures ?? 0) + 1;
        
        $service->update([
            'status' => 'unhealthy',
            'last_health_check' => now(),
            'health_error' => $error,
            'consecutive_failures' => $failures,
        ]);

        Log::error("Service unhealthy: {$service->name}", [
            'slug' => $service->slug,
            'error' => $error,
            'failures' => $failures,
        ]);

        // Trigger alert after 3 consecutive failures
        if ($failures >= 3) {
            $this->triggerAlert($service, $error);
        }
    }

    /**
     * Trigger critical alert for service failure.
     */
    protected function triggerAlert(AppModule $service, string $error): void
    {
        // Implementation would create alert record and send notification
        Log::critical("ALERT: Service {$service->name} has failed {$service->consecutive_failures} times", [
            'error' => $error,
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('HealthCheckJob failed', [
            'error' => $exception->getMessage(),
            'service_slug' => $this->serviceSlug,
        ]);
    }
}
