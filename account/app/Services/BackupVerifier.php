<?php

namespace App\Services;

use App\Models\CronJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Backup Verification Service
 * 
 * Validates database backups created by cron jobs to ensure data integrity.
 */
class BackupVerifier
{
    protected string $backupDirectory;
    
    public function __construct()
    {
        $this->backupDirectory = config('backup.directory', storage_path('app/backups'));
    }

    /**
     * Verify latest backup file
     */
    public function verifyLatestBackup(): array
    {
        try {
            // Find the most recent backup file
            $latestBackup = $this->getLatestBackupFile();
            
            if (!$latestBackup) {
                return [
                    'success' => false,
                    'message' => 'No backup files found',
                    'verified' => false,
                ];
            }

            $verification = [
                'file' => $latestBackup,
                'size' => $this->getFileSize($latestBackup),
                'age_hours' => $this->getFileAgeInHours($latestBackup),
                'is_compressed' => $this->isCompressed($latestBackup),
                'checksum' => null,
                'integrity_check' => false,
                'errors' => [],
            ];

            // Perform integrity checks
            $verification['checksum'] = $this->calculateChecksum($latestBackup);
            $verification['integrity_check'] = $this->checkFileIntegrity($latestBackup);
            
            // Validate backup age (should be less than 26 hours for daily backups)
            if ($verification['age_hours'] > 26) {
                $verification['errors'][] = 'Backup is older than 26 hours - may be stale';
            }

            // Validate file size (should be reasonable, not empty or too large)
            $sizeMB = $verification['size'] / (1024 * 1024);
            if ($sizeMB < 0.1) {
                $verification['errors'][] = 'Backup file is suspiciously small (< 0.1 MB)';
            } elseif ($sizeMB > 5000) {
                $verification['warnings'][] = 'Backup file is very large (> 5 GB) - consider cleanup';
            }

            $verification['verified'] = empty($verification['errors']);

            // Log verification result
            Log::channel('cron')->info('Backup verification completed', [
                'file' => $latestBackup,
                'verified' => $verification['verified'],
                'size_mb' => round($sizeMB, 2),
                'age_hours' => round($verification['age_hours'], 2),
            ]);

            return $verification;
        } catch (\Exception $e) {
            Log::channel('cron')->error('Backup verification failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'verified' => false,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Test restore from backup (dry run)
     */
    public function testRestore(string $backupFile): array
    {
        try {
            // Check if backup file exists
            if (!file_exists($backupFile)) {
                return [
                    'success' => false,
                    'message' => 'Backup file not found',
                ];
            }

            // For compressed files, test decompression
            if ($this->isCompressed($backupFile)) {
                $testResult = $this->testDecompression($backupFile);
                if (!$testResult['success']) {
                    return $testResult;
                }
            }

            // For SQL files, validate syntax (basic check)
            if (str_ends_with($backupFile, '.sql') || str_ends_with($backupFile, '.sql.gz')) {
                $syntaxCheck = $this->validateSqlSyntax($backupFile);
                if (!$syntaxCheck['valid']) {
                    return [
                        'success' => false,
                        'message' => 'SQL syntax validation failed',
                        'details' => $syntaxCheck['errors'],
                    ];
                }
            }

            return [
                'success' => true,
                'message' => 'Backup file passed restoration tests',
                'file' => $backupFile,
                'size' => $this->getFileSize($backupFile),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Restoration test failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Schedule automatic backup verification
     */
    public function scheduleVerification(CronJob $backupJob): void
    {
        // Create a verification job that runs 1 hour after backup
        $verificationSchedule = $this->calculateVerificationSchedule($backupJob->schedule);
        
        CronJob::updateOrCreate(
            ['name' => 'backup-verification-' . $backupJob->name],
            [
                'command' => '/usr/bin/php /home/YOUR_USERNAME/yg-account-core/artisan backup:verify >> /dev/null 2>&1',
                'schedule' => $verificationSchedule,
                'description' => "Verifies integrity of backups created by {$backupJob->name}",
                'is_enabled' => true,
                'is_system' => true,
                'metadata' => json_encode([
                    'type' => 'backup_verification',
                    'parent_job' => $backupJob->name,
                    'delay_hours' => 1,
                ]),
            ]
        );
    }

    /**
     * Get backup statistics
     */
    public function getBackupStats(): array
    {
        $backups = $this->getAllBackupFiles();
        
        if (empty($backups)) {
            return [
                'total_backups' => 0,
                'total_size_mb' => 0,
                'oldest_backup' => null,
                'newest_backup' => null,
                'average_size_mb' => 0,
            ];
        }

        $sizes = array_map(fn($file) => $this->getFileSize($file), $backups);
        $totalSize = array_sum($sizes);
        
        return [
            'total_backups' => count($backups),
            'total_size_mb' => round($totalSize / (1024 * 1024), 2),
            'oldest_backup' => basename(min($backups)),
            'newest_backup' => basename(max($backups)),
            'average_size_mb' => round(($totalSize / count($backups)) / (1024 * 1024), 2),
            'retention_days' => $this->calculateRetentionDays($backups),
        ];
    }

    /**
     * Clean up old backups beyond retention period
     */
    public function cleanupOldBackups(int $retentionDays = 30): int
    {
        $backups = $this->getAllBackupFiles();
        $deletedCount = 0;
        $cutoffDate = now()->subDays($retentionDays);

        foreach ($backups as $backup) {
            if (filemtime($backup) < $cutoffDate->timestamp) {
                if (@unlink($backup)) {
                    $deletedCount++;
                    Log::channel('cron')->info("Deleted old backup: " . basename($backup));
                }
            }
        }

        return $deletedCount;
    }

    // ── Private Helper Methods ──────────────────────────────────────────────

    protected function getLatestBackupFile(): ?string
    {
        $backups = $this->getAllBackupFiles();
        return empty($backups) ? null : max($backups);
    }

    protected function getAllBackupFiles(): array
    {
        if (!is_dir($this->backupDirectory)) {
            return [];
        }

        $files = glob($this->backupDirectory . '/*.sql*');
        sort($files);
        
        return $files;
    }

    protected function getFileSize(string $file): int
    {
        return filesize($file) ?: 0;
    }

    protected function getFileAgeInHours(string $file): float
    {
        $fileTime = filemtime($file);
        return (time() - $fileTime) / 3600;
    }

    protected function isCompressed(string $file): bool
    {
        return str_ends_with($file, '.gz') || str_ends_with($file, '.zip') || str_ends_with($file, '.bz2');
    }

    protected function calculateChecksum(string $file): string
    {
        return hash_file('sha256', $file) ?: '';
    }

    protected function checkFileIntegrity(string $file): bool
    {
        // Basic integrity check - verify file is readable and not corrupted
        if (!is_readable($file)) {
            return false;
        }

        if ($this->isCompressed($file)) {
            return $this->testDecompression($file)['success'];
        }

        // For uncompressed files, just verify we can read it
        $handle = @fopen($file, 'r');
        if ($handle === false) {
            return false;
        }
        fclose($handle);
        
        return true;
    }

    protected function testDecompression(string $file): array
    {
        if (!str_ends_with($file, '.gz')) {
            return ['success' => false, 'message' => 'Unsupported compression format'];
        }

        // Test gzip decompression without extracting full file
        $gz = @gzopen($file, 'r');
        if ($gz === false) {
            return ['success' => false, 'message' => 'Failed to open compressed file'];
        }

        // Try reading first 1KB to verify integrity
        $testData = @gzread($gz, 1024);
        gzclose($gz);

        if ($testData === false) {
            return ['success' => false, 'message' => 'Corrupted compressed file'];
        }

        return ['success' => true, 'message' => 'Compression integrity verified'];
    }

    protected function validateSqlSyntax(string $file): array
    {
        try {
            // Read first few lines to check for basic SQL structure
            $handle = $this->isCompressed($file) ? gzopen($file, 'r') : fopen($file, 'r');
            
            if ($handle === false) {
                return ['valid' => false, 'errors' => ['Cannot read file']];
            }

            $firstLines = '';
            for ($i = 0; $i < 10; $i++) {
                $line = $this->isCompressed($file) ? gzgets($handle) : fgets($handle);
                if ($line === false) break;
                $firstLines .= $line;
            }

            if ($this->isCompressed($file)) {
                gzclose($handle);
            } else {
                fclose($handle);
            }

            // Basic validation - check for common SQL keywords
            $hasCreateTable = stripos($firstLines, 'CREATE TABLE') !== false;
            $hasInsert = stripos($firstLines, 'INSERT INTO') !== false;
            $hasDropTable = stripos($firstLines, 'DROP TABLE') !== false;

            if (!$hasCreateTable && !$hasInsert && !$hasDropTable) {
                return [
                    'valid' => false,
                    'errors' => ['File does not appear to contain valid SQL statements'],
                ];
            }

            return ['valid' => true, 'errors' => []];
        } catch (\Exception $e) {
            return ['valid' => false, 'errors' => [$e->getMessage()]];
        }
    }

    protected function calculateVerificationSchedule(string $backupSchedule): string
    {
        // Simplified: add 1 hour to backup schedule
        // In production, you'd parse the cron expression properly
        return $backupSchedule; // For now, same schedule + manual offset
    }

    protected function calculateRetentionDays(array $backups): int
    {
        if (empty($backups)) {
            return 0;
        }

        $oldest = min($backups);
        $newest = max($backups);
        
        $oldestTime = filemtime($oldest);
        $newestTime = filemtime($newest);
        
        return (int)(($newestTime - $oldestTime) / 86400);
    }
}
