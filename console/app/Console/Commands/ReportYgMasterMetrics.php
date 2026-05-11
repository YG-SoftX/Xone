<?php

namespace App\Console\Commands;

use App\Services\YgMasterService;
use Illuminate\Console\Command;

class ReportYgMasterMetrics extends Command
{
    protected $signature = 'yg-master:report-metrics';
    protected $description = 'Report comprehensive metrics to YG Master';

    protected YgMasterService $ygMasterService;

    public function __construct(YgMasterService $ygMasterService)
    {
        parent::__construct();
        $this->ygMasterService = $ygMasterService;
    }

    public function handle(): int
    {
        $this->info('Reporting metrics to YG Master...');

        $result = $this->ygMasterService->reportMetrics();

        if ($result['success']) {
            $this->info('✓ Metrics reported successfully');
            
            return Command::SUCCESS;
        }

        $this->warn('⚠ Metrics reporting failed: ' . $result['error']);
        
        return Command::FAILURE;
    }
}
