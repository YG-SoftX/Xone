<?php

namespace App\Console\Commands;

use App\Services\YgMasterService;
use Illuminate\Console\Command;

class RegisterWithYgMaster extends Command
{
    protected $signature = 'yg-master:register';
    protected $description = 'Register this YG Console instance with YG Master';

    protected YgMasterService $ygMasterService;

    public function __construct(YgMasterService $ygMasterService)
    {
        parent::__construct();
        $this->ygMasterService = $ygMasterService;
    }

    public function handle(): int
    {
        $this->info('Registering YG Console with YG Master...');

        $result = $this->ygMasterService->registerConsole();

        if ($result['success']) {
            $this->info('✓ Successfully registered with YG Master');
            $this->line('Service ID: ' . ($result['data']['service_id'] ?? 'N/A'));
            
            return Command::SUCCESS;
        }

        $this->error('✗ Registration failed: ' . $result['error']);
        
        return Command::FAILURE;
    }
}
