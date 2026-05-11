<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * YG Account Event Publisher
 * 
 * Publishes events from YG Collect to the central YG Account event system
 * for cross-module synchronization (search indexing, notifications, etc.)
 */
class YgAccountEventPublisher
{
    protected string $baseUrl;
    protected string $apiToken;

    public function __construct()
    {
        // Get YG Account API configuration from .env
        $this->baseUrl = rtrim(config('services.yg_account.api_base', env('YG_ACCOUNT_API_BASE', 'http://localhost:8000/api')), '/');
        $this->apiToken = config('services.yg_account.api_token', env('YG_ACCOUNT_API_TOKEN', ''));
    }

    /**
     * Publish form created event
     *
     * @param int $formId
     * @param int $userId
     * @param string $formTitle
     * @param array $metadata
     * @return bool
     */
    public function publishFormCreated(int $formId, int $userId, string $formTitle, array $metadata = []): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'forms',
                    'event_type' => 'form_created',
                    'payload' => [
                        'user_id' => $userId,
                        'form_id' => $formId,
                        'title' => $formTitle,
                        'created_at' => now()->toIso8601String(),
                        ...$metadata,
                    ],
                    'user_id' => $userId,
                ]);

            if ($response->successful()) {
                Log::info('Form created event published successfully', [
                    'form_id' => $formId,
                    'user_id' => $userId,
                ]);
                return true;
            }

            Log::warning('Failed to publish form created event', [
                'form_id' => $formId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception publishing form created event', [
                'form_id' => $formId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Publish form submission event
     *
     * @param int $submissionId
     * @param int $formId
     * @param int $userId
     * @param string $formTitle
     * @param bool $isEncrypted
     * @return bool
     */
    public function publishFormSubmitted(int $submissionId, int $formId, int $userId, string $formTitle, bool $isEncrypted = false): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'forms',
                    'event_type' => 'form_submitted',
                    'payload' => [
                        'user_id' => $userId,
                        'submission_id' => $submissionId,
                        'form_id' => $formId,
                        'form_title' => $formTitle,
                        'is_encrypted' => $isEncrypted,
                        'submitted_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            if ($response->successful()) {
                Log::info('Form submission event published successfully', [
                    'submission_id' => $submissionId,
                    'form_id' => $formId,
                ]);
                return true;
            }

            Log::warning('Failed to publish form submission event', [
                'submission_id' => $submissionId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception publishing form submission event', [
                'submission_id' => $submissionId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Publish form deleted event
     *
     * @param int $formId
     * @param int $userId
     * @return bool
     */
    public function publishFormDeleted(int $formId, int $userId): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'forms',
                    'event_type' => 'form_deleted',
                    'payload' => [
                        'user_id' => $userId,
                        'form_id' => $formId,
                        'deleted_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            if ($response->successful()) {
                Log::info('Form deleted event published successfully', [
                    'form_id' => $formId,
                ]);
                return true;
            }

            Log::warning('Failed to publish form deleted event', [
                'form_id' => $formId,
                'status' => $response->status(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception publishing form deleted event', [
                'form_id' => $formId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
