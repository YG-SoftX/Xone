<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupVerifier;
use App\Models\CronJob;
use Illuminate\Support\Facades\Mail;
use App\Mail\CronJobFailedNotification;

class VerifyBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:verify 
                            {--test-restore : Test restore from latest backup}
                            {--cleanup : Remove old backups beyond retention period}
                            {--retention-days=30 : Number of days to retain backups}
                            {--notify : Send email notification on failure}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify integrity of database backups and send alerts if verification fails';

    protected BackupVerifier $verifier;

    public function __construct(BackupVerifier $verifier)
    {
        parent::__construct();
        $this->verifier = $verifier;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Starting backup verification...');
        $this->newLine();

        // Step 1: Verify latest backup
        $this->info('Step 1: Verifying latest backup integrity...');
        $verification = $this->verifier->verifyLatestBackup();

        if (!$verification['success']) {
            $this->error('❌ Verification failed: ' . $verification['message']);
            
            if ($this->option('notify')) {
                $this->sendFailureNotification($verification);
            }
            
            return Command::FAILURE;
        }

        // Display verification results
        $this->displayVerificationResults($verification);

        if (!$verification['verified']) {
            $this->warn('⚠️  Backup has warnings:');
            foreach ($verification['errors'] as $error) {
                $this->line("   - {$error}");
            }
            
            if ($this->option('notify')) {
                $this->sendFailureNotification($verification);
            }
            
            return Command::FAILURE;
        }

        $this->info('✅ Backup verification passed!');
        $this->newLine();

        // Step 2: Test restore (optional)
        if ($this->option('test-restore')) {
            $this->info('Step 2: Testing restore from backup...');
            $latestBackup = $this->getLatestBackupFile();
            
            if ($latestBackup) {
                $testResult = $this->verifier->testRestore($latestBackup);
                
                if ($testResult['success']) {
                    $this->info('✅ Restore test passed!');
                } else {
                    $this->error('❌ Restore test failed: ' . $testResult['message']);
                    
                    if (!empty($testResult['details'])) {
                        foreach ($testResult['details'] as $detail) {
                            $this->line("   - {$detail}");
                        }
                    }
                    
                    return Command::FAILURE;
                }
            } else {
                $this->warn('⚠️  No backup file found for restore test');
            }
            
            $this->newLine();
        }

        // Step 3: Cleanup old backups (optional)
        if ($this->option('cleanup')) {
            $retentionDays = (int)$this->option('retention-days');
            $this->info("Step 3: Cleaning up backups older than {$retentionDays} days...");
            
            $deletedCount = $this->verifier->cleanupOldBackups($retentionDays);
            
            if ($deletedCount > 0) {
                $this->info("✅ Deleted {$deletedCount} old backup(s)");
            } else {
                $this->info('ℹ️  No old backups to delete');
            }
            
            $this->newLine();
        }

        // Step 4: Display backup statistics
        $this->info('Step 4: Backup Statistics');
        $stats = $this->verifier->getBackupStats();
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Backups', $stats['total_backups']],
                ['Total Size', "{$stats['total_size_mb']} MB"],
                ['Average Size', "{$stats['average_size_mb']} MB"],
                ['Retention Period', "{$stats['retention_days']} days"],
                ['Oldest Backup', $stats['oldest_backup'] ?? 'N/A'],
                ['Newest Backup', $stats['newest_backup'] ?? 'N/A'],
            ]
        );

        $this->newLine();
        $this->info('🎉 Backup verification completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Display verification results in formatted table
     */
    protected function displayVerificationResults(array $verification): void
    {
        $this->table(
            ['Property', 'Value'],
            [
                ['File', basename($verification['file'])],
                ['Size', number_format($verification['size'] / (1024 * 1024), 2) . ' MB'],
                ['Age', round($verification['age_hours'], 2) . ' hours'],
                ['Compressed', $verification['is_compressed'] ? 'Yes' : 'No'],
                ['Checksum', substr($verification['checksum'], 0, 16) . '...'],
                ['Integrity Check', $verification['integrity_check'] ? '✅ Passed' : '❌ Failed'],
            ]
        );

        if (!empty($verification['warnings'])) {
            $this->warn('Warnings:');
            foreach ($verification['warnings'] as $warning) {
                $this->line("   ⚠️  {$warning}");
            }
            $this->newLine();
        }
    }

    /**
     * Send failure notification via email
     */
    protected function sendFailureNotification(array $verification): void
    {
        try {
            // Create a mock CronJob for the notification
            $backupJob = CronJob::where('name', 'database-backup')->first();
            
            if ($backupJob) {
                $errorMessage = implode("\n", $verification['errors'] ?? ['Backup verification failed']);
                
                Mail::to(config('mail.admin_email', 'admin@ygxone.com'))
                    ->send(new CronJobFailedNotification(
                        $backupJob,
                        $errorMessage,
                        1
                    ));
                
                $this->info('📧 Failure notification sent to admin');
            }
        } catch (\Exception $e) {
            $this->error('Failed to send notification: ' . $e->getMessage());
        }
    }

    /**
     * Get latest backup file path
     */
    protected function getLatestBackupFile(): ?string
    {
        $backupDir = config('backup.directory', storage_path('app/backups'));
        
        if (!is_dir($backupDir)) {
            return null;
        }

        $files = glob($backupDir . '/*.sql*');
        
        return empty($files) ? null : max($files);
    }
}
