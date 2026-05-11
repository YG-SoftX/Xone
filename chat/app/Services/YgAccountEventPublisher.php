<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * YG Account Event Publisher for YG Chat
 * 
 * Publishes chat events to central YG Account for cross-module synchronization:
 * - Message sent → Search indexing, Notification generation
 */
class YgAccountEventPublisher
{
    protected string $baseUrl;
    protected string $apiToken;

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('services.yg_account.api_base', 'http://localhost:8000/api'), '/');
        $this->apiToken = config('services.yg_account.api_token', '');
    }

    /**
     * Publish message sent event
     * Triggers: Search indexing, Real-time notifications
     */
    public function publishMessageSent(int $messageId, int $userId, int $conversationId, string $messageText): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'chat',
                    'event_type' => 'message_sent',
                    'payload' => [
                        'user_id' => $userId,
                        'message_id' => $messageId,
                        'conversation_id' => $conversationId,
                        'message_text' => substr($messageText, 0, 2000), // Limit for performance
                        'sent_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            if ($response->successful()) {
                Log::info('Chat message sent event published', ['message_id' => $messageId]);
                return true;
            }

            Log::warning('Failed to publish chat message', ['message_id' => $messageId]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception publishing chat message', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
