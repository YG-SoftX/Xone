<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Event Controller for YG Ecosystem
 * Handles cross-module synchronization events.
 */
class EventController extends Controller
{
    /**
     * Publish an event from any ecosystem node
     */
    public function publish(Request $request)
    {
        $request->validate([
            'service' => 'required|string',
            'event_type' => 'required|string',
            'payload' => 'required|array',
            'user_id' => 'required|integer',
        ]);

        $service = $request->input('service');
        $eventType = $request->input('event_type');
        $payload = $request->input('payload');
        $userId = $request->input('user_id');

        Log::info("Ecosystem event received: [{$service}] {$eventType}", [
            'user_id' => $userId,
            'event' => $eventType
        ]);

        // Process based on event type
        switch ($eventType) {
            case 'email_received':
            case 'email_sent':
                $this->handleMailEvent($eventType, $payload, $userId);
                break;
            
            case 'file_uploaded':
                $this->handleFileEvent($payload, $userId);
                break;

            case 'email_deleted':
                $this->handleCleanupEvent($payload, $userId);
                break;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Event processed',
        ]);
    }

    /**
     * Sync email data to Contacts and Search Index
     */
    protected function handleMailEvent(string $type, array $payload, int $userId)
    {
        // 1. Update Contact last_contacted
        if (isset($payload['from_email'])) {
            DB::table('contacts')
                ->where('user_id', $userId)
                ->where('email', $payload['from_email'])
                ->update([
                    'last_contacted_at' => now(),
                    'contact_count' => DB::raw('contact_count + 1')
                ]);
        }

        // 2. Trigger Search Re-indexing (Stub for now - usually adds to a job queue)
        Log::debug("Triggering search re-index for user {$userId} on mail event");
    }

    /**
     * Sync file metadata to Unified Drive and Quota
     */
    protected function handleFileEvent(array $payload, int $userId)
    {
        // Update user storage quota usage
        if (isset($payload['size'])) {
            DB::table('storage_quotas')
                ->where('user_id', $userId)
                ->increment('used_bytes', $payload['size']);
        }

        Log::debug("Synced file metadata for user {$userId} from {$payload['source']}");
    }

    /**
     * Cleanup resources when items are deleted
     */
    protected function handleCleanupEvent(array $payload, int $userId)
    {
        // Logic to remove from global search index or clean up storage
        Log::info("Cleanup triggered for user {$userId}", $payload);
    }
}
