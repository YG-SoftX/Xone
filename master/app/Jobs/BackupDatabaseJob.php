<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * BackupDatabaseJob
 * 
 * Creates automated database backups for all YG ecosystem services.
 * Stores backups with timestamp and uploads to cloud storage.
 */
class BackupDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes for large databases
    public $tries = 1;

    protected ?string $serviceName;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $serviceName = null)
    {
        $this->serviceName = $serviceName; // null = backup all services
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting database backup", ['service' => $this->serviceName ?? 'all']);

        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupDir = storage_path("app/backups/{$timestamp}");
            
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            if ($this->serviceName) {
                // Backup specific service
                $this->backupService($this->serviceName, $backupDir, $timestamp);
            } else {
                // Backup all services
                $services = \App\Models\AppModule::where('is_active', true)->get();
                
                foreach ($services as $service) {
                    $this->backupService($service->slug, $backupDir, $timestamp);
                }
            }

            // Compress backup directory
            $zipFile = $this->compressBackup($backupDir, $timestamp);

            // Upload to cloud storage (S3, etc.)
            $this->uploadToCloudStorage($zipFile, $timestamp);

            // Cleanup old backups (keep last 30 days)
            $this->cleanupOldBackups();

            Log::info("Database backup completed successfully", [
                'service' => $this->serviceName ?? 'all',
                'file' => $zipFile,
            ]);

        } catch (\Exception $e) {
            Log::error("Database backup failed", [
                'service' => $this->serviceName ?? 'all',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Backup a specific service database.
     */
    protected function backupService(string $serviceSlug, string $backupDir, string $timestamp): void
    {
        $config = config("database.connections.{$serviceSlug}");
        
        if (!$config) {
            Log::warning("No database config found for service: {$serviceSlug}");
            return;
        }

        $filename = "{$serviceSlug}_{$timestamp}.sql";
        $filepath = "{$backupDir}/{$filename}";

        // Build mysqldump command
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s 2>&1',
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['host']),
            escapeshellarg($config['database']),
            escapeshellarg($filepath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception("Backup failed for {$serviceSlug}: " . implode("\n", $output));
        }

        Log::info("Service backup completed", [
            'service' => $serviceSlug,
            'file' => $filename,
            'size' => File::size($filepath),
        ]);
    }

    /**
     * Compress backup directory into ZIP file.
     */
    protected function compressBackup(string $backupDir, string $timestamp): string
    {
        $zipFile = storage_path("app/backups/yg-master_backup_{$timestamp}.zip");
        
        $zip = new \ZipArchive();
        
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception("Cannot create ZIP archive");
        }

        $files = File::allFiles($backupDir);
        
        foreach ($files as $file) {
            $zip->addFile($file->getRealPath(), $file->getFilename());
        }

        $zip->close();

        // Remove uncompressed directory
        File::deleteDirectory($backupDir);

        return $zipFile;
    }

    /**
     * Upload backup to cloud storage.
     */
    protected function uploadToCloudStorage(string $zipFile, string $timestamp): void
    {
        try {
            $disk = Storage::disk('s3'); // or other cloud storage
            
            $key = "backups/yg-master/{$timestamp}.zip";
            
            $disk->put($key, File::get($zipFile), 'public');

            Log::info("Backup uploaded to cloud storage", [
                'key' => $key,
                'url' => $disk->url($key),
            ]);

        } catch (\Exception $e) {
            Log::warning("Cloud upload failed, keeping local backup", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cleanup old backups (keep last 30 days).
     */
    protected function cleanupOldBackups(): void
    {
        $backupPath = storage_path('app/backups');
        
        if (!File::exists($backupPath)) {
            return;
        }

        $cutoff = now()->subDays(30);
        
        $oldBackups = File::files($backupPath);
        
        foreach ($oldBackups as $backup) {
            if ($backup->getMTime() < $cutoff->timestamp) {
                File::delete($backup->getPathname());
                
                Log::info("Deleted old backup", [
                    'file' => $backup->getFilename(),
                ]);
            }
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical("BackupDatabaseJob permanently failed", [
            'service' => $this->serviceName ?? 'all',
            'error' => $exception->getMessage(),
        ]);
    }
}
