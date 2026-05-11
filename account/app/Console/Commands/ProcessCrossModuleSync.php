<?php

namespace App\Console\Commands;

use App\Services\CrossModuleSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessCrossModuleSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:process {--limit=100 : Maximum events to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending cross-module synchronization events';

    protected $syncService;

    /**
     * Create a new command instance.
     */
    public function __construct(CrossModuleSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting cross-module sync processing...');
        
        try {
            $processed = $this->syncService->processPendingSyncs();
            
            $this->info("Successfully processed {$processed} sync events");
            
            Log::info("Cross-module sync completed", [
                'processed_count' => $processed,
                'timestamp' => now()->toDateTimeString(),
            ]);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Sync processing failed: {$e->getMessage()}");
            
            Log::error("Cross-module sync failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }
}
