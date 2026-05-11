<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * YG Account Event Publisher for YG Contacts
 * 
 * Publishes contact events to central YG Account for cross-module synchronization:
 * - Contact created → Search indexing, Email auto-complete
 * - Contact updated → Last_contacted timestamp updates from emails
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
     * Publish contact created event
     * Triggers: Search indexing, Email autocomplete suggestions
     */
    public function publishContactCreated(int $contactId, int $userId, string $firstName, string $lastName, string $email, ?string $phone): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'contacts',
                    'event_type' => 'contact_created',
                    'payload' => [
                        'id' => $contactId,
                        'user_id' => $userId,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'phone' => $phone,
                        'created_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            if ($response->successful()) {
                Log::info('Contact created event published', ['contact_id' => $contactId]);
                return true;
            }

            Log::warning('Failed to publish contact created', ['contact_id' => $contactId]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception publishing contact created', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Publish contact updated event
     * Triggers: Last_contacted updates, Search re-indexing
     */
    public function publishContactUpdated(int $contactId, int $userId, string $email): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'contacts',
                    'event_type' => 'contact_updated',
                    'payload' => [
                        'id' => $contactId,
                        'user_id' => $userId,
                        'email' => $email,
                        'updated_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Exception publishing contact updated', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
