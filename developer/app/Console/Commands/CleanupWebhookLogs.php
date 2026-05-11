<?php

namespace App\Console\Commands;

use App\Services\QuotaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupWebhookLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhooks:cleanup {--days=30 : Delete webhook delivery logs older than this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old webhook delivery logs to free database space';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days');
        
        $this->info("Cleaning up webhook delivery logs older than {$days} days...");
        
        try {
            $deleted = \App\Models\WebhookDelivery::where('created_at', '<', now()->subDays($days))->delete();
            
            $this->info("Successfully deleted {$deleted} old webhook delivery logs");
            
            Log::info("Webhook delivery log cleanup completed", [
                'deleted_count' => $deleted,
                'older_than_days' => $days,
                'timestamp' => now()->toDateTimeString(),
            ]);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Cleanup failed: {$e->getMessage()}");
            
            Log::error("Webhook delivery log cleanup failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }
}
