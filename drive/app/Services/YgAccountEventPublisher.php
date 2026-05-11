<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * YG Account Event Publisher for YG Drive
 * 
 * Publishes file events to central YG Account for cross-module synchronization:
 * - File uploaded → Unified storage sync, Search indexing
 * - File deleted → Storage cleanup, Search index removal
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
     * Publish file uploaded event
     * Triggers: Unified storage sync, Search indexing
     */
    public function publishFileUploaded(int $fileId, int $userId, string $name, string $mimeType, int $sizeBytes, string $path, ?int $folderId): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'drive',
                    'event_type' => 'file_uploaded',
                    'payload' => [
                        'user_id' => $userId,
                        'file_id' => $fileId,
                        'name' => $name,
                        'mime_type' => $mimeType,
                        'size' => $sizeBytes,
                        'path' => $path,
                        'folder_id' => $folderId,
                        'uploaded_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            if ($response->successful()) {
                Log::info('File uploaded event published', ['file_id' => $fileId]);
                return true;
            }

            Log::warning('Failed to publish file uploaded', ['file_id' => $fileId]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception publishing file uploaded', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Publish file deleted event
     * Triggers: Storage cleanup, Search index removal
     */
    public function publishFileDeleted(int $fileId, int $userId): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'drive',
                    'event_type' => 'file_deleted',
                    'payload' => [
                        'user_id' => $userId,
                        'file_id' => $fileId,
                        'deleted_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Exception publishing file deleted', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
