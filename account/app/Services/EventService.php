<?php

namespace App\Services;

use App\Models\ServiceEvent;
use Illuminate\Support\Facades\Log;

class EventService
{
    /**
     * Publish event to event log for cross-service sync
     */
    public function publish(string $service, string $eventType, array $payload, ?int $userId = null, ?int $mailboxId = null): void
    {
        try {
            ServiceEvent::create([
                'service' => $service,
                'user_id' => $userId ?? auth()->id(),
                'mailbox_id' => $mailboxId,
                'event_type' => $eventType,
                'payload' => json_encode($payload),
            ]);

            Log::info("Event published: {$service}.{$eventType}", [
                'user_id' => $userId,
                'mailbox_id' => $mailboxId,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to publish event: {$service}.{$eventType}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get unprocessed events for user since timestamp
     */
    public function getUnprocessedEvents(int $userId, string $since, ?string $service = null)
    {
        $query = ServiceEvent::where('user_id', $userId)
            ->where('created_at', '>', $since)
            ->where('processed', false)
            ->orderBy('created_at', 'asc');

        if ($service) {
            $query->where('service', $service);
        }

        return $query->get();
    }

    /**
     * Mark events as processed
     */
    public function markAsProcessed(array $eventIds): void
    {
        ServiceEvent::whereIn('id', $eventIds)
            ->update([
                'processed' => true,
                'processed_at' => now(),
            ]);
    }

    /**
     * Clean old events (older than 24 hours)
     */
    public function cleanupOldEvents(): int
    {
        return ServiceEvent::where('created_at', '<', now()->subHours(24))
            ->delete();
    }

    /**
     * Process pending events (for cron job)
     */
    public function processPendingEvents(): int
    {
        $events = ServiceEvent::where('processed', false)
            ->where('created_at', '<', now()->subMinutes(5))
            ->limit(100)
            ->get();

        foreach ($events as $event) {
            // Process event (send notifications, update caches, etc.)
            $this->handleEvent($event);
            
            $event->update([
                'processed' => true,
                'processed_at' => now(),
            ]);
        }

        return $events->count();
    }

    /**
     * Handle individual event
     */
    protected function handleEvent(ServiceEvent $event): void
    {
        switch ($event->event_type) {
            case 'new_email':
                $this->handleNewEmail($event);
                break;
            case 'file_uploaded':
                $this->handleFileUploaded($event);
                break;
            case 'payment_completed':
                $this->handlePaymentCompleted($event);
                break;
            default:
                Log::info("Unhandled event type: {$event->event_type}");
        }
    }

    /**
     * Handle new email event
     */
    protected function handleNewEmail(ServiceEvent $event): void
    {
        $payload = json_decode($event->payload, true);
        
        // Create notification
        if ($event->user_id) {
            $user = \App\Models\User::find($event->user_id);
            if ($user) {
                $user->notify(new \App\Notifications\NewEmailNotification($payload));
            }
        }

        // Update search index
        $this->indexEmailForSearch($payload);
    }

    /**
     * Handle file uploaded event.
     *
     * Storage quota is tracked directly by YG Drive's FileController via
     * $user->increment('storage_used'). EventService must not duplicate that
     * increment — doing so would over-count usage.
     */
    protected function handleFileUploaded(ServiceEvent $event): void
    {
        $payload = json_decode($event->payload, true);

        // Index for search only — quota accounting belongs to YG Drive
        $this->indexFileForSearch($payload);
    }

    /**
     * Handle payment completed event
     */
    protected function handlePaymentCompleted(ServiceEvent $event): void
    {
        $payload = json_decode($event->payload, true);
        
        // Send receipt email
        if ($event->user_id) {
            $user = \App\Models\User::find($event->user_id);
            if ($user) {
                \Mail::to($user->email)->send(new \App\Mail\PaymentReceipt($payload));
            }
        }
    }

    /**
     * Index email for search
     */
    protected function indexEmailForSearch(array $emailData): void
    {
        \App\Models\SearchIndex::create([
            'user_id' => $emailData['user_id'] ?? null,
            'service' => 'mail',
            'item_id' => $emailData['message_id'],
            'item_type' => 'message',
            'title' => $emailData['subject'] ?? '',
            'content' => $emailData['body_plain'] ?? '',
            'metadata' => json_encode([
                'from' => $emailData['from_email'] ?? '',
                'received_at' => $emailData['received_at'] ?? now(),
            ]),
            'indexed_at' => now(),
        ]);
    }

    /**
     * Index file for search
     */
    protected function indexFileForSearch(array $fileData): void
    {
        \App\Models\SearchIndex::create([
            'user_id' => $fileData['user_id'] ?? null,
            'service' => 'drive',
            'item_id' => $fileData['file_id'],
            'item_type' => 'file',
            'title' => $fileData['name'] ?? '',
            'content' => $fileData['description'] ?? '',
            'metadata' => json_encode([
                'mime_type' => $fileData['mime_type'] ?? '',
                'size' => $fileData['size'] ?? 0,
            ]),
            'indexed_at' => now(),
        ]);
    }
}
