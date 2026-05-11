<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * YG Account Event Publisher for YG DocX
 * 
 * Publishes document events to central YG Account for cross-module synchronization:
 * - Document saved → Search indexing, Content preview generation
 * - Document deleted → Search index removal
 */
class YgAccountEventPublisher
{
    protected string $baseUrl;
    protected string $apiToken;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.yg_account.api_base', env('YG_ACCOUNT_API_BASE', 'http://localhost:8000/api')), '/');
        $this->apiToken = config('services.yg_account.api_token', env('YG_ACCOUNT_API_TOKEN', ''));
    }

    /**
     * Publish document saved event
     * Triggers: Search indexing, Content preview
     */
    public function publishDocumentSaved(int $documentId, int $userId, string $title, string $content, ?string $description): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'docs',
                    'event_type' => 'document_saved',
                    'payload' => [
                        'user_id' => $userId,
                        'document_id' => $documentId,
                        'title' => $title,
                        'content_preview' => substr($content, 0, 2000), // Preview only for performance
                        'description' => $description,
                        'saved_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            if ($response->successful()) {
                Log::info('Document saved event published', ['document_id' => $documentId]);
                return true;
            }

            Log::warning('Failed to publish document saved', ['document_id' => $documentId]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception publishing document saved', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Publish document deleted event
     * Triggers: Search index removal
     */
    public function publishDocumentDeleted(int $documentId, int $userId): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'docs',
                    'event_type' => 'document_deleted',
                    'payload' => [
                        'user_id' => $userId,
                        'document_id' => $documentId,
                        'deleted_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Exception publishing document deleted', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
