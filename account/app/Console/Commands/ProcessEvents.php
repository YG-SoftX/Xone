<?php

namespace App\Console\Commands;

use App\Services\EventService;
use Illuminate\Console\Command;

class ProcessEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending service events for real-time sync';

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
        $this->info('Processing pending events...');
        
        $processed = $this->eventService->processPendingEvents();
        
        $this->info("Processed {$processed} events.");
        
        return self::SUCCESS;
    }
}
