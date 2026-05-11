<?php

namespace App\Listeners;

use App\Events\NewEmailReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateContactStats
{
    /**
     * Handle the event.
     */
    public function handle(NewEmailReceived $event): void
    {
        $email = $event->emailData['from_email'] ?? null;
        if (!$email) return;

        try {
            // Find contact by email in the shared database
            // Note: contacts table stores emails as JSON [{email: "..."}]
            $contact = DB::table('contacts')
                ->where('user_id', auth()->id()) // Ensure it's for the same user
                ->where('emails', 'LIKE', "%{$email}%")
                ->first();

            if ($contact) {
                DB::table('contacts')
                    ->where('id', $contact->id)
                    ->update([
                        'last_contacted_at' => now(),
                        'contact_count'     => DB::raw('contact_count + 1'),
                    ]);
                
                Log::info("Updated contact stats for: {$email}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to update contact stats: " . $e->getMessage());
        }
    }
}
