<?php

namespace App\Jobs;

use App\Models\AppModule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

/**
 * DeployServiceJob
 * 
 * Deploys updates to a specific service in the YG ecosystem.
 * Executes git pull, migrations, cache rebuild, and queue restart.
 */
class DeployServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes for deployment
    public $tries = 1; // No retries for deployment

    protected int $serviceId;
    protected array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(int $serviceId, array $options = [])
    {
        $this->serviceId = $serviceId;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $service = AppModule::findOrFail($this->serviceId);
        
        Log::info("Starting deployment for service: {$service->name}", [
            'slug' => $service->slug,
            'options' => $this->options,
        ]);

        try {
            // Step 1: Git Pull
            if ($this->options['git_pull'] ?? true) {
                $this->executeGitPull($service);
            }

            // Step 2: Install Dependencies
            if ($this->options['composer_install'] ?? true) {
                $this->executeComposerInstall($service);
            }

            // Step 3: Run Migrations
            if ($this->options['migrate'] ?? true) {
                $this->runMigrations($service);
            }

            // Step 4: Clear & Rebuild Caches
            if ($this->options['rebuild_cache'] ?? true) {
                $this->rebuildCaches($service);
            }

            // Step 5: Restart Queue Workers
            if ($this->options['restart_queue'] ?? true) {
                $this->restartQueueWorkers($service);
            }

            // Update service status
            $service->update([
                'status' => 'healthy',
                'last_deployed_at' => now(),
                'deploy_version' => $this->getCurrentVersion($service),
            ]);

            Log::info("Deployment completed successfully: {$service->name}", [
                'slug' => $service->slug,
                'version' => $service->deploy_version,
            ]);

        } catch (\Exception $e) {
            Log::error("Deployment failed: {$service->name}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $service->update([
                'status' => 'unhealthy',
                'health_error' => "Deployment failed: {$e->getMessage()}",
            ]);

            throw $e;
        }
    }

    /**
     * Execute git pull on service directory.
     */
    protected function executeGitPull(AppModule $service): void
    {
        $servicePath = $service->installation_path ?? base_path("../{$service->slug}");
        
        if (!is_dir($servicePath)) {
            throw new \Exception("Service directory not found: {$servicePath}");
        }

        chdir($servicePath);
        $output = [];
        $returnCode = 0;
        
        exec('git pull origin main 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception("Git pull failed: " . implode("\n", $output));
        }

        Log::info("Git pull completed", [
            'service' => $service->slug,
            'output' => $output,
        ]);
    }

    /**
     * Run composer install.
     */
    protected function executeComposerInstall(AppModule $service): void
    {
        $servicePath = $service->installation_path ?? base_path("../{$service->slug}");
        
        chdir($servicePath);
        $output = [];
        $returnCode = 0;
        
        exec('composer install --no-dev --optimize-autoloader 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception("Composer install failed: " . implode("\n", $output));
        }
    }

    /**
     * Run database migrations.
     */
    protected function runMigrations(AppModule $service): void
    {
        Artisan::call('migrate', [
            '--force' => true,
            '--path' => $service->migration_path ?? null,
        ]);

        Log::info("Migrations completed", [
            'service' => $service->slug,
            'output' => Artisan::output(),
        ]);
    }

    /**
     * Clear and rebuild caches.
     */
    protected function rebuildCaches(AppModule $service): void
    {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('optimize');

        Log::info("Caches rebuilt", [
            'service' => $service->slug,
        ]);
    }

    /**
     * Restart queue workers.
     */
    protected function restartQueueWorkers(AppModule $service): void
    {
        Artisan::call('queue:restart');

        Log::info("Queue workers restarted", [
            'service' => $service->slug,
        ]);
    }

    /**
     * Get current version from git.
     */
    protected function getCurrentVersion(AppModule $service): string
    {
        $servicePath = $service->installation_path ?? base_path("../{$service->slug}");
        
        if (!is_dir($servicePath)) {
            return 'unknown';
        }

        chdir($servicePath);
        $output = [];
        exec('git rev-parse --short HEAD 2>&1', $output);
        
        return $output[0] ?? 'unknown';
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('DeployServiceJob permanently failed', [
            'service_id' => $this->serviceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
