<?php

namespace App\Jobs;

use App\Services\UnifiedSearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RemoveContentFromSearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $service;
    protected $itemId;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $service, int $itemId, ?int $userId = null)
    {
        $this->service = $service;
        $this->itemId = $itemId;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(UnifiedSearchService $searchService): void
    {
        try {
            $searchService->removeContent(
                $this->service,
                $this->itemId,
                $this->userId
            );
        } catch (\Exception $e) {
            Log::error("Failed to remove content from search index", [
                'service' => $this->service,
                'item_id' => $this->itemId,
                'error' => $e->getMessage(),
            ]);
            
            // Retry if less than 3 attempts
            if ($this->attempts() < 3) {
                throw $e;
            }
        }
    }
}
