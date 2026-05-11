<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DeploymentRollback;

class RollbackDeployment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deployment:rollback 
                            {deployment_id? : Specific deployment ID to rollback to}
                            {--list : List available rollbacks}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback deployment to previous stable version';

    protected DeploymentRollback $rollbackService;

    public function __construct(DeploymentRollback $rollbackService)
    {
        parent::__construct();
        $this->rollbackService = $rollbackService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // List available rollbacks
        if ($this->option('list')) {
            $this->listAvailableRollbacks();
            return Command::SUCCESS;
        }

        $deploymentId = $this->argument('deployment_id');

        // If no deployment ID specified, find last stable
        if (!$deploymentId) {
            $this->warn('No deployment ID specified. Rolling back to last stable deployment...');
        }

        // Confirmation prompt
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  This will rollback your deployment. Continue?')) {
                $this->info('Rollback cancelled.');
                return Command::FAILURE;
            }
        }

        $this->info('🔄 Starting deployment rollback...');
        $this->newLine();

        $result = $this->rollbackService->rollback($deploymentId);

        if ($result['success']) {
            $this->info('✅ Rollback completed successfully!');
            $this->newLine();
            
            $this->info("Deployment ID: {$result['deployment_id']}");
            $this->newLine();

            // Display results for each step
            $this->info('Rollback Results:');
            foreach ($result['results'] as $step => $stepResult) {
                $status = $stepResult['success'] ? '✅' : '❌';
                $this->line("  {$status} {$step}: {$stepResult['message']}");
            }
        } else {
            $this->error('❌ Rollback failed!');
            $this->newLine();
            $this->error($result['message']);
            
            if (isset($result['results'])) {
                $this->newLine();
                $this->warn('Partial Results:');
                foreach ($result['results'] as $step => $stepResult) {
                    $status = $stepResult['success'] ? '✅' : '❌';
                    $this->line("  {$status} {$step}: {$stepResult['message']}");
                }
            }
            
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function listAvailableRollbacks(): void
    {
        $rollbacks = $this->rollbackService->getAvailableRollbacks();

        if (empty($rollbacks)) {
            $this->info('No deployment backups found.');
            return;
        }

        $this->info('📦 Available Deployment Rollbacks');
        $this->newLine();

        $tableData = [];
        foreach ($rollbacks as $rollback) {
            $tableData[] = [
                $rollback['deployment_id'],
                $rollback['timestamp'],
                substr($rollback['git_commit'] ?? 'N/A', 0, 8),
                $rollback['database_version'] ?? 'N/A',
            ];
        }

        $this->table(
            ['Deployment ID', 'Timestamp', 'Git Commit', 'Database Version'],
            $tableData
        );

        $this->newLine();
        $this->info('To rollback, run: php artisan deployment:rollback <deployment_id>');
    }
}
