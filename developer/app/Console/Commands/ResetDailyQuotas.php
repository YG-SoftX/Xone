<?php

namespace App\Console\Commands;

use App\Services\QuotaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResetDailyQuotas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotas:reset-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset daily API quota counters at midnight';

    protected $quotaService;

    /**
     * Create a new command instance.
     */
    public function __construct(QuotaService $quotaService)
    {
        parent::__construct();
        $this->quotaService = $quotaService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Resetting daily API quota counters...');
        
        try {
            $resetCount = $this->quotaService->resetDailyCounters();
            
            $this->info("Successfully reset daily counters for {$resetCount} projects");
            
            Log::info("Daily quota counters reset", [
                'projects_reset' => $resetCount,
                'timestamp' => now()->toDateTimeString(),
            ]);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to reset quotas: {$e->getMessage()}");
            
            Log::error("Daily quota reset failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }
}
