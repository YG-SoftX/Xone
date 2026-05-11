<?php

namespace App\Console\Commands;

use App\Services\EventService;
use Illuminate\Console\Command;

class CleanupEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old service events (older than 24 hours)';

    protected $eventService;

    /**
     * Create a new command instance.
     */
    public function __construct(EventService $eventService)
    {
        parent::__construct();
        $this->eventService = $eventService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning up old events...');
        
        $deleted = $this->eventService->cleanupOldEvents();
        
        $this->info("Deleted {$deleted} old events.");
        
        return self::SUCCESS;
    }
}
