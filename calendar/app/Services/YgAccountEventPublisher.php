<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * YG Account Event Publisher for YG Calendar
 * 
 * Publishes calendar events to central YG Account for cross-module synchronization:
 * - Event created → Send invitation emails to attendees
 * - Event updated → Notify attendees of changes
 * - Event deleted → Cancel invitations, remove from search
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
     * Publish calendar event created
     * Triggers: Invitation emails to attendees, Search indexing
     */
    public function publishEventCreated(int $eventId, int $userId, string $title, string $startTime, string $endTime, ?string $location, ?string $description, array $attendees): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'calendar',
                    'event_type' => 'calendar_event_created',
                    'payload' => [
                        'id' => $eventId,
                        'user_id' => $userId,
                        'title' => $title,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'location' => $location,
                        'description' => $description,
                        'attendees' => $attendees,
                        'organizer_email' => auth()->check() ? auth()->user()->email : '',
                        'created_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            if ($response->successful()) {
                Log::info('Calendar event created published', [
                    'event_id' => $eventId,
                    'attendee_count' => count($attendees),
                ]);
                return true;
            }

            Log::warning('Failed to publish calendar event created', [
                'event_id' => $eventId,
                'status' => $response->status(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception publishing calendar event created', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Publish calendar event updated
     * Triggers: Update notification emails to attendees
     */
    public function publishEventUpdated(int $eventId, int $userId, string $title): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'calendar',
                    'event_type' => 'calendar_event_updated',
                    'payload' => [
                        'id' => $eventId,
                        'user_id' => $userId,
                        'title' => $title,
                        'updated_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Exception publishing calendar event updated', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Publish calendar event deleted
     * Triggers: Cancellation emails, Search index removal
     */
    public function publishEventDeleted(int $eventId, int $userId): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'calendar',
                    'event_type' => 'calendar_event_deleted',
                    'payload' => [
                        'id' => $eventId,
                        'user_id' => $userId,
                        'deleted_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Exception publishing calendar event deleted', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
