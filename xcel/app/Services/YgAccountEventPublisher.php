<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * YG Account Event Publisher for YG Xcel
 * 
 * Publishes spreadsheet events to central YG Account for cross-module synchronization:
 * - Spreadsheet saved → Search indexing, Data preview
 * - Spreadsheet deleted → Search index removal
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
     * Publish spreadsheet saved event
     * Triggers: Search indexing, Data preview generation
     */
    public function publishSpreadsheetSaved(int $spreadsheetId, int $userId, string $title, ?string $description): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'xcel',
                    'event_type' => 'spreadsheet_saved',
                    'payload' => [
                        'user_id' => $userId,
                        'spreadsheet_id' => $spreadsheetId,
                        'title' => $title,
                        'description' => $description,
                        'saved_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            if ($response->successful()) {
                Log::info('Spreadsheet saved event published', ['spreadsheet_id' => $spreadsheetId]);
                return true;
            }

            Log::warning('Failed to publish spreadsheet saved', ['spreadsheet_id' => $spreadsheetId]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception publishing spreadsheet saved', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Publish spreadsheet deleted event
     * Triggers: Search index removal
     */
    public function publishSpreadsheetDeleted(int $spreadsheetId, int $userId): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'xcel',
                    'event_type' => 'spreadsheet_deleted',
                    'payload' => [
                        'user_id' => $userId,
                        'spreadsheet_id' => $spreadsheetId,
                        'deleted_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Exception publishing spreadsheet deleted', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
