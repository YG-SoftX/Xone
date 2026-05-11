<?php

namespace App\Console\Commands;

use App\Services\UnifiedNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup {--days=30 : Delete notifications older than this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old notifications to free database space';

    protected $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(UnifiedNotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days');
        
        $this->info("Cleaning up notifications older than {$days} days...");
        
        try {
            $deleted = $this->notificationService->cleanupOldNotifications();
            
            $this->info("Successfully deleted {$deleted} old notifications");
            
            Log::info("Notification cleanup completed", [
                'deleted_count' => $deleted,
                'older_than_days' => $days,
                'timestamp' => now()->toDateTimeString(),
            ]);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Cleanup failed: {$e->getMessage()}");
            
            Log::error("Notification cleanup failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }
}
