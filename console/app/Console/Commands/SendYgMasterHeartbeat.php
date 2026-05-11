<?php

namespace App\Console\Commands;

use App\Services\YgMasterService;
use Illuminate\Console\Command;

class SendYgMasterHeartbeat extends Command
{
    protected $signature = 'yg-master:heartbeat';
    protected $description = 'Send heartbeat to YG Master for monitoring';

    protected YgMasterService $ygMasterService;

    public function __construct(YgMasterService $ygMasterService)
    {
        parent::__construct();
        $this->ygMasterService = $ygMasterService;
    }

    public function handle(): int
    {
        $this->info('Sending heartbeat to YG Master...');

        $result = $this->ygMasterService->sendHeartbeat();

        if ($result['success']) {
            $this->info('✓ Heartbeat sent successfully');
            
            return Command::SUCCESS;
        }

        $this->warn('⚠ Heartbeat failed: ' . $result['error']);
        
        return Command::FAILURE;
    }
}
