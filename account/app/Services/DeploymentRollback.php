<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

/**
 * Automated Deployment Rollback Service
 * 
 * Implements safe rollback mechanisms for failed deployments, including:
 * - Database migration rollback
 * - Code deployment rollback (git-based)
 * - Configuration rollback
 * - Asset compilation rollback
 */
class DeploymentRollback
{
    protected ?string $backupDirectory;
    protected ?string $gitRepositoryPath;

    public function __construct()
    {
        $this->backupDirectory = config('deployment.backup_directory', storage_path('app/deployments'));
        $this->gitRepositoryPath = base_path();
    }

    /**
     * Create deployment backup before changes
     */
    public function createBackup(string $deploymentId): array
    {
        try {
            $backupPath = "{$this->backupDirectory}/{$deploymentId}";
            
            // Create backup directory
            File::makeDirectory($backupPath, 0755, true);

            $backup = [
                'id' => $deploymentId,
                'timestamp' => now()->toIso8601String(),
                'git_commit' => $this->getCurrentGitCommit(),
                'database_version' => $this->getCurrentDatabaseVersion(),
                'config_snapshot' => $this->snapshotConfig(),
                'env_backup' => $this->backupEnvFile($backupPath),
            ];

            // Save backup metadata
            file_put_contents(
                "{$backupPath}/metadata.json",
                json_encode($backup, JSON_PRETTY_PRINT)
            );

            Log::channel('cron')->info("Deployment backup created", [
                'deployment_id' => $deploymentId,
                'path' => $backupPath,
            ]);

            return [
                'success' => true,
                'backup_id' => $deploymentId,
                'path' => $backupPath,
                'metadata' => $backup,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to create deployment backup: {$e->getMessage()}");
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Rollback to previous deployment
     */
    public function rollback(string $deploymentId = null): array
    {
        try {
            // If no deployment ID specified, rollback to last known good state
            if (!$deploymentId) {
                $deploymentId = $this->getLastStableDeployment();
                
                if (!$deploymentId) {
                    return [
                        'success' => false,
                        'message' => 'No stable deployment found for rollback',
                    ];
                }
            }

            $backupPath = "{$this->backupDirectory}/{$deploymentId}";
            
            if (!File::exists($backupPath)) {
                return [
                    'success' => false,
                    'message' => "Backup not found: {$deploymentId}",
                ];
            }

            Log::warning("Starting deployment rollback", [
                'deployment_id' => $deploymentId,
            ]);

            $results = [];

            // Step 1: Rollback database migrations
            $results['database'] = $this->rollbackDatabase($deploymentId);

            // Step 2: Rollback code to previous git commit
            $results['code'] = $this->rollbackCode($deploymentId);

            // Step 3: Restore configuration
            $results['config'] = $this->restoreConfig($deploymentId);

            // Step 4: Clear caches
            $results['cache'] = $this->clearCaches();

            // Step 5: Verify rollback
            $results['verification'] = $this->verifyRollback($deploymentId);

            $overallSuccess = collect($results)->every(fn($result) => $result['success'] ?? false);

            if ($overallSuccess) {
                Log::info("Deployment rollback completed successfully", [
                    'deployment_id' => $deploymentId,
                ]);

                return [
                    'success' => true,
                    'deployment_id' => $deploymentId,
                    'results' => $results,
                    'message' => 'Rollback completed successfully',
                ];
            } else {
                Log::error("Rollback completed with errors", [
                    'deployment_id' => $deploymentId,
                    'results' => $results,
                ]);

                return [
                    'success' => false,
                    'deployment_id' => $deploymentId,
                    'results' => $results,
                    'message' => 'Rollback completed with errors - manual intervention may be required',
                ];
            }
        } catch (\Exception $e) {
            Log::error("Rollback failed: {$e->getMessage()}");
            
            return [
                'success' => false,
                'message' => "Rollback failed: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Rollback database migrations
     */
    protected function rollbackDatabase(string $deploymentId): array
    {
        try {
            $backupPath = "{$this->backupDirectory}/{$deploymentId}";
            $metadata = json_decode(file_get_contents("{$backupPath}/metadata.json"), true);
            
            $targetMigration = $metadata['database_version'] ?? null;
            
            if (!$targetMigration) {
                return ['success' => false, 'message' => 'No database version recorded'];
            }

            // Rollback migrations using Artisan
            Artisan::call('migrate:rollback', [
                '--step' => 1,
                '--force' => true,
            ]);

            $output = Artisan::output();

            Log::info("Database rollback executed", [
                'output' => $output,
            ]);

            return [
                'success' => true,
                'message' => 'Database rolled back',
                'output' => $output,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Database rollback failed: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Rollback code to previous git commit
     */
    protected function rollbackCode(string $deploymentId): array
    {
        try {
            $backupPath = "{$this->backupDirectory}/{$deploymentId}";
            $metadata = json_decode(file_get_contents("{$backupPath}/metadata.json"), true);
            
            $targetCommit = $metadata['git_commit'] ?? null;
            
            if (!$targetCommit) {
                return ['success' => false, 'message' => 'No git commit recorded'];
            }

            // Checkout specific commit
            $command = "cd {$this->gitRepositoryPath} && git checkout {$targetCommit} 2>&1";
            $output = [];
            $returnCode = 0;
            
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                return [
                    'success' => false,
                    'message' => 'Git checkout failed',
                    'output' => implode("\n", $output),
                ];
            }

            // Install dependencies
            exec("cd {$this->gitRepositoryPath} && composer install --no-dev --optimize-autoloader 2>&1", $composerOutput);

            Log::info("Code rollback executed", [
                'commit' => $targetCommit,
                'git_output' => implode("\n", $output),
                'composer_output' => implode("\n", $composerOutput),
            ]);

            return [
                'success' => true,
                'message' => "Code rolled back to commit {$targetCommit}",
                'commit' => $targetCommit,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Code rollback failed: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Restore configuration files
     */
    protected function restoreConfig(string $deploymentId): array
    {
        try {
            $backupPath = "{$this->backupDirectory}/{$deploymentId}";
            
            // Restore .env file
            $envBackup = "{$backupPath}/.env.backup";
            
            if (File::exists($envBackup)) {
                File::copy($envBackup, base_path('.env'));
                
                // Clear config cache
                Artisan::call('config:clear');
                Artisan::call('cache:clear');
            }

            return [
                'success' => true,
                'message' => 'Configuration restored',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Config restore failed: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Clear all caches after rollback
     */
    protected function clearCaches(): array
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('event:clear');

            return [
                'success' => true,
                'message' => 'All caches cleared',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Cache clearing failed: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Verify rollback was successful
     */
    protected function verifyRollback(string $deploymentId): array
    {
        try {
            // Check if application is accessible
            $response = Http::timeout(5)->get(url('/'));
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Application is accessible',
                    'status_code' => $response->status(),
                ];
            }

            return [
                'success' => false,
                'message' => "Application returned status {$response->status()}",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Verification failed: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Get list of available rollbacks
     */
    public function getAvailableRollbacks(): array
    {
        if (!File::exists($this->backupDirectory)) {
            return [];
        }

        $rollbacks = [];
        $directories = File::directories($this->backupDirectory);

        foreach ($directories as $dir) {
            $metadataFile = "{$dir}/metadata.json";
            
            if (File::exists($metadataFile)) {
                $metadata = json_decode(File::get($metadataFile), true);
                $rollbacks[] = [
                    'deployment_id' => basename($dir),
                    'timestamp' => $metadata['timestamp'] ?? null,
                    'git_commit' => $metadata['git_commit'] ?? null,
                    'database_version' => $metadata['database_version'] ?? null,
                ];
            }
        }

        // Sort by timestamp descending
        usort($rollbacks, fn($a, $b) => strtotime($b['timestamp']) <=> strtotime($a['timestamp']));

        return $rollbacks;
    }

    /**
     * Mark deployment as stable (for future rollbacks)
     */
    public function markAsStable(string $deploymentId): bool
    {
        try {
            $backupPath = "{$this->backupDirectory}/{$deploymentId}";
            $metadataFile = "{$backupPath}/metadata.json";
            
            if (!File::exists($metadataFile)) {
                return false;
            }

            $metadata = json_decode(File::get($metadataFile), true);
            $metadata['stable'] = true;
            $metadata['verified_at'] = now()->toIso8601String();
            
            File::put($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));

            Log::info("Deployment marked as stable", [
                'deployment_id' => $deploymentId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to mark deployment as stable: {$e->getMessage()}");
            return false;
        }
    }

    // ── Private Helper Methods ──────────────────────────────────────────────

    protected function getCurrentGitCommit(): ?string
    {
        try {
            $output = [];
            exec("cd {$this->gitRepositoryPath} && git rev-parse HEAD 2>&1", $output);
            
            return $output[0] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getCurrentDatabaseVersion(): ?string
    {
        try {
            // Get last migration batch
            $lastBatch = \DB::table('migrations')
                ->orderBy('batch', 'desc')
                ->value('migration');
            
            return $lastBatch;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function snapshotConfig(): array
    {
        return [
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'db_connection' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_driver' => config('queue.default'),
        ];
    }

    protected function backupEnvFile(string $backupPath): bool
    {
        try {
            $envFile = base_path('.env');
            
            if (File::exists($envFile)) {
                File::copy($envFile, "{$backupPath}/.env.backup");
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function getLastStableDeployment(): ?string
    {
        $rollbacks = $this->getAvailableRollbacks();
        
        foreach ($rollbacks as $rollback) {
            $metadataFile = "{$this->backupDirectory}/{$rollback['deployment_id']}/metadata.json";
            
            if (File::exists($metadataFile)) {
                $metadata = json_decode(File::get($metadataFile), true);
                
                if ($metadata['stable'] ?? false) {
                    return $rollback['deployment_id'];
                }
            }
        }

        return null;
    }
}
