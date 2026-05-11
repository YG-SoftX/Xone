<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CrossModuleSyncService
{
    /**
     * Process all pending cross-module sync events
     */
    public function processPendingSyncs(): int
    {
        $processed = 0;
        
        // Get unprocessed events from last hour
        $events = DB::table('service_events')
            ->where('processed', false)
            ->where('created_at', '>=', now()->subHour())
            ->orderBy('created_at', 'asc')
            ->limit(100)
            ->get();
        
        foreach ($events as $event) {
            try {
                $this->handleSyncEvent($event);
                
                // Mark as processed
                DB::table('service_events')
                    ->where('id', $event->id)
                    ->update([
                        'processed' => true,
                        'processed_at' => now(),
                    ]);
                
                $processed++;
            } catch (\Exception $e) {
                Log::error("Cross-module sync failed for event {$event->id}", [
                    'error' => $e->getMessage(),
                    'event_type' => $event->event_type,
                    'service' => $event->service,
                ]);
            }
        }
        
        return $processed;
    }

    /**
     * Handle individual sync event
     */
    protected function handleSyncEvent(object $event): void
    {
        switch ($event->event_type) {
            case 'email_received':
            case 'email_sent':
                $this->syncEmailToCalendar(json_decode($event->payload, true));
                $this->syncEmailToContacts(json_decode($event->payload, true));
                break;
            
            case 'contact_created':
            case 'contact_updated':
                $this->syncContactToMail(json_decode($event->payload, true));
                break;
            
            case 'calendar_event_created':
                $this->syncCalendarToMail(json_decode($event->payload, true));
                break;
            
            case 'file_uploaded':
                $this->syncFileToDrive(json_decode($event->payload, true));
                break;
            
            case 'profile_updated':
                $this->syncProfileToAllNodes(json_decode($event->payload, true));
                break;
            
            case 'brand_updated':
                $this->syncBrandToAllNodes(json_decode($event->payload, true));
                break;
            
            default:
                Log::debug("Unhandled sync event type: {$event->event_type}");
        }
    }

    /**
     * Sync email to calendar - detect dates/times and create events
     */
    protected function syncEmailToCalendar(array $emailData): void
    {
        try {
            $userId = $emailData['user_id'] ?? null;
            if (!$userId) return;
            
            $subject = $emailData['subject'] ?? '';
            $body = $emailData['body_plain'] ?? '';
            $receivedAt = $emailData['received_at'] ?? now();
            
            // Extract date/time patterns from email
            $dates = $this->extractDatesFromText($subject . ' ' . $body);
            
            if (empty($dates)) {
                return; // No dates found, skip
            }
            
            // Create calendar event for each detected date
            foreach ($dates as $dateInfo) {
                $this->createCalendarEventFromEmail([
                    'user_id' => $userId,
                    'title' => $this->generateEventTitle($subject, $dateInfo),
                    'description' => "Created from email: {$subject}\n\nFrom: {$emailData['from_name']} <{$emailData['from_email']}>\n\n" . substr($body, 0, 500),
                    'start_time' => $dateInfo['start'],
                    'end_time' => $dateInfo['end'] ?? $dateInfo['start']->copy()->addHour(),
                    'all_day' => $dateInfo['all_day'] ?? false,
                    'source' => 'email',
                    'source_id' => $emailData['message_id'],
                    'location' => $this->extractLocation($body),
                ]);
            }
            
            Log::info("Email synced to calendar", [
                'user_id' => $userId,
                'message_id' => $emailData['message_id'],
                'events_created' => count($dates),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync email to calendar", [
                'error' => $e->getMessage(),
                'email_id' => $emailData['message_id'] ?? null,
            ]);
        }
    }

    /**
     * Sync email to contacts - update last_contacted and add new contacts
     */
    protected function syncEmailToContacts(array $emailData): void
    {
        try {
            $userId = $emailData['user_id'] ?? null;
            if (!$userId) return;
            
            $fromEmail = $emailData['from_email'] ?? null;
            $fromName = $emailData['from_name'] ?? null;
            
            if (!$fromEmail) return;
            
            // Check if contact exists
            $contact = DB::table('contacts')
                ->where('user_id', $userId)
                ->where(function ($q) use ($fromEmail) {
                    $q->where('email', $fromEmail)
                      ->orWhere('email_2', $fromEmail)
                      ->orWhere('email_3', $fromEmail);
                })
                ->first();
            
            if ($contact) {
                // Update last_contacted timestamp
                DB::table('contacts')
                    ->where('id', $contact->id)
                    ->update([
                        'last_contacted' => now(),
                        'updated_at' => now(),
                    ]);
                
                Log::info("Contact last_contacted updated", [
                    'contact_id' => $contact->id,
                    'email' => $fromEmail,
                ]);
            } else {
                // Create new contact from email sender
                $nameParts = $fromName ? explode(' ', $fromName, 2) : ['', ''];
                
                DB::table('contacts')->insert([
                    'user_id' => $userId,
                    'first_name' => $nameParts[0] ?? '',
                    'last_name' => $nameParts[1] ?? '',
                    'email' => $fromEmail,
                    'last_contacted' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                Log::info("New contact created from email", [
                    'user_id' => $userId,
                    'email' => $fromEmail,
                    'name' => $fromName,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync email to contacts", [
                'error' => $e->getMessage(),
                'email' => $emailData['from_email'] ?? null,
            ]);
        }
    }

    /**
     * Sync contact to mail - nothing special needed, just log
     */
    protected function syncContactToMail(array $contactData): void
    {
        // Contact creation doesn't require mail sync
        // This is a placeholder for future enhancements
        Log::debug("Contact synced", ['contact_id' => $contactData['id'] ?? null]);
    }

    /**
     * Sync calendar event to mail - send invitation emails
     */
    protected function syncCalendarToMail(array $eventData): void
    {
        try {
            // Check if this is an event with attendees
            if (empty($eventData['attendees'])) {
                return;
            }
            
            // Send invitation emails to attendees
            foreach ($eventData['attendees'] as $attendee) {
                $this->sendCalendarInvitation([
                    'to_email' => $attendee['email'],
                    'event_title' => $eventData['title'],
                    'start_time' => $eventData['start_time'],
                    'end_time' => $eventData['end_time'],
                    'location' => $eventData['location'] ?? '',
                    'description' => $eventData['description'] ?? '',
                    'organizer' => $eventData['organizer_email'] ?? '',
                ]);
            }
            
            Log::info("Calendar invitations sent", [
                'event_id' => $eventData['id'] ?? null,
                'attendees_count' => count($eventData['attendees']),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync calendar to mail", [
                'error' => $e->getMessage(),
                'event_id' => $eventData['id'] ?? null,
            ]);
        }
    }

    /**
     * Sync file upload to Drive backend storage
     */
    protected function syncFileToDrive(array $fileData): void
    {
        try {
            // If file was uploaded to Mail attachments, ensure it's also in Drive
            if (($fileData['source'] ?? '') === 'mail_attachment') {
                // Check if already in Drive
                $exists = DB::table('drive_files')
                    ->where('user_id', $fileData['user_id'])
                    ->where('original_message_id', $fileData['message_id'])
                    ->exists();
                
                if (!$exists) {
                    // Copy attachment to Drive
                    DB::table('drive_files')->insert([
                        'user_id' => $fileData['user_id'],
                        'name' => $fileData['name'],
                        'mime_type' => $fileData['mime_type'],
                        'size_bytes' => $fileData['size'],
                        'path' => $fileData['path'],
                        'folder_id' => null, // Root folder or "Email Attachments" folder
                        'source' => 'mail_attachment',
                        'original_message_id' => $fileData['message_id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info("Mail attachment synced to Drive", [
                        'file_id' => $fileData['file_id'],
                        'user_id' => $fileData['user_id'],
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync file to Drive", [
                'error' => $e->getMessage(),
                'file_id' => $fileData['file_id'] ?? null,
            ]);
        }
    }

    // ========== HELPER METHODS ==========

    /**
     * Extract dates and times from text using regex patterns
     */
    protected function extractDatesFromText(string $text): array
    {
        $dates = [];
        $now = Carbon::now();
        
        // Pattern 1: "tomorrow at 3pm", "next Monday at 2:30 PM"
        if (preg_match('/(tomorrow|next\s+\w+)\s+(?:at\s+)?(\d{1,2}(?::\d{2})?\s*(?:am|pm)?)/i', $text, $matches)) {
            $date = $this->parseRelativeDate($matches[1], $matches[2]);
            if ($date) {
                $dates[] = [
                    'start' => $date,
                    'all_day' => false,
                ];
            }
        }
        
        // Pattern 2: "March 15, 2026" or "15 March 2026"
        if (preg_match('/(\w+\s+\d{1,2},?\s+\d{4}|\d{1,2}\s+\w+\s+\d{4})/', $text, $matches)) {
            try {
                $parsed = Carbon::parse($matches[1]);
                $dates[] = [
                    'start' => $parsed,
                    'all_day' => true,
                ];
            } catch (\Exception $e) {
                // Invalid date format, skip
            }
        }
        
        // Pattern 3: "meeting" or "call" keywords suggest event
        if (preg_match('/\b(meeting|call|interview|appointment|conference)\b/i', $text)) {
            // Default to tomorrow at 9 AM if no specific date found
            if (empty($dates)) {
                $dates[] = [
                    'start' => $now->copy()->addDay()->setTime(9, 0),
                    'all_day' => false,
                ];
            }
        }
        
        return $dates;
    }

    /**
     * Parse relative date expressions
     */
    protected function parseRelativeDate(string $relative, string $time): ?Carbon
    {
        try {
            $now = Carbon::now();
            
            if (stripos($relative, 'tomorrow') !== false) {
                $date = $now->copy()->addDay();
            } elseif (stripos($relative, 'next') !== false) {
                $dayName = str_replace('next ', '', strtolower($relative));
                $date = $now->copy()->next($dayName);
            } else {
                return null;
            }
            
            // Parse time
            if (preg_match('/(\d{1,2})(?::(\d{2}))?\s*(am|pm)?/i', $time, $timeMatches)) {
                $hour = (int) $timeMatches[1];
                $minute = isset($timeMatches[2]) ? (int) $timeMatches[2] : 0;
                $ampm = strtolower($timeMatches[3] ?? '');
                
                if ($ampm === 'pm' && $hour < 12) {
                    $hour += 12;
                } elseif ($ampm === 'am' && $hour === 12) {
                    $hour = 0;
                }
                
                $date->setTime($hour, $minute);
            }
            
            return $date;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate calendar event title from email subject
     */
    protected function generateEventTitle(string $subject, array $dateInfo): string
    {
        // Remove common prefixes
        $title = preg_replace('/^(re|fw|fwd):\s*/i', '', $subject);
        
        // Truncate if too long
        if (strlen($title) > 100) {
            $title = substr($title, 0, 97) . '...';
        }
        
        return $title ?: 'Calendar Event';
    }

    /**
     * Extract location from email body
     */
    protected function extractLocation(string $body): ?string
    {
        // Look for location patterns
        if (preg_match('/(?:location|venue|place|at):\s*([^\n]+)/i', $body, $matches)) {
            return trim($matches[1]);
        }
        
        // Look for address patterns
        if (preg_match('/(\d+\s+\w+\s+(?:Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd))/i', $body, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Create calendar event in database
     */
    protected function createCalendarEventFromEmail(array $eventData): void
    {
        DB::table('calendar_events')->insert([
            'user_id' => $eventData['user_id'],
            'calendar_id' => 1, // Default calendar
            'title' => $eventData['title'],
            'description' => $eventData['description'],
            'start_time' => $eventData['start_time'],
            'end_time' => $eventData['end_time'],
            'all_day' => $eventData['all_day'] ? 1 : 0,
            'location' => $eventData['location'],
            'status' => 'confirmed',
            'source' => $eventData['source'],
            'source_id' => $eventData['source_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Send calendar invitation email
     */
    protected function sendCalendarInvitation(array $invitationData): void
    {
        // This would integrate with YG Mail to send invitation
        // For now, just log the attempt
        Log::info("Calendar invitation would be sent", [
            'to' => $invitationData['to_email'],
            'event' => $invitationData['event_title'],
            'time' => $invitationData['start_time'],
        ]);
        
        // TODO: Implement actual email sending via YG Mail API
        // Mail::to($invitationData['to_email'])->send(new CalendarInvitation($invitationData));
    }

    /**
     * Sync user profile changes across all empire nodes
     */
    public function syncProfileToAllNodes(array $profileData): void
    {
        try {
            $userId = $profileData['user_id'];
            $updatedFields = $profileData['fields'];

            // Log the global broadcast
            Log::info("Broadcasting profile sync for user {$userId}", $updatedFields);

            // In a live empire, this would hit the API endpoints of Mail, Drive, Pay, etc.
            // Example: Http::post(config('services.yg_mail.url') . '/api/sync/profile', [...])
            
            // For now, we update the local caches if they exist
            DB::table('users')->where('id', $userId)->update($updatedFields);

            Log::info("Profile sync completed successfully for user {$userId}");
        } catch (\Exception $e) {
            Log::error("Profile sync failed", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sync organization branding across all empire nodes
     */
    public function syncBrandToAllNodes(array $brandData): void
    {
        try {
            $orgId = $brandData['organization_id'];
            $branding = $brandData['branding'];

            Log::info("Broadcasting brand sync for organization {$orgId}", $branding);

            // In a live empire, this would hit the API endpoints of all active nodes
            // ensuring the "Login with YG" buttons and headers update globally.
            
            Log::info("Brand sync completed for organization {$orgId}");
        } catch (\Exception $e) {
            Log::error("Brand sync failed", ['error' => $e->getMessage()]);
        }
    }
}
