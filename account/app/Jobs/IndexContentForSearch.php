<?php

namespace App\Jobs;

use App\Services\UnifiedSearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IndexContentForSearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    protected $service;
    protected $itemType;
    protected $itemId;
    protected $data;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $service, string $itemType, int $itemId, array $data, ?int $userId = null)
    {
        $this->service = $service;
        $this->itemType = $itemType;
        $this->itemId = $itemId;
        $this->data = $data;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(UnifiedSearchService $searchService): void
    {
        try {
            $searchService->indexContent(
                $this->service,
                $this->itemType,
                $this->itemId,
                $this->data,
                $this->userId
            );
        } catch (\Exception $e) {
            Log::error("Failed to index content for search", [
                'service' => $this->service,
                'item_type' => $this->itemType,
                'item_id' => $this->itemId,
                'error' => $e->getMessage(),
            ]);
            
            if ($this->attempts() < $this->tries) {
                throw $e; // Let Laravel retry via $tries
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('IndexContentForSearch permanently failed', [
            'service'   => $this->service,
            'item_type' => $this->itemType,
            'item_id'   => $this->itemId,
            'error'     => $exception->getMessage(),
        ]);
    }
}
