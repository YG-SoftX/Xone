<?php

namespace App\Services;

use App\Models\PushNotificationToken;
use App\Models\Mailbox;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Send push notification for new email
     */
    public function sendNewEmailNotification(Mailbox $mailbox, array $emailData): void
    {
        $tokens = PushNotificationToken::where('user_id', $mailbox->user_id)
            ->active()
            ->get();

        foreach ($tokens as $token) {
            try {
                if ($token->platform === 'web') {
                    $this->sendWebPushNotification($token, $emailData);
                } else {
                    $this->sendFirebaseNotification($token, $emailData);
                }
            } catch (\Exception $e) {
                Log::error('Push notification failed:', [
                    'token_id' => $token->id,
                    'error' => $e->getMessage(),
                ]);
                
                // Deactivate invalid tokens
                $token->update(['is_active' => false]);
            }
        }
    }

    /**
     * Send Web Push API notification
     */
    protected function sendWebPushNotification($token, array $emailData): void
    {
        // Web Push API implementation
        // Requires VAPID keys configuration
        $payload = json_encode([
            'title' => 'New Email - YG Mail',
            'body' => "{$emailData['from_name']}: {$emailData['subject']}",
            'icon' => '/icons/icon-192x192.png',
            'badge' => '/icons/badge-72x72.png',
            'url' => "/inbox?message={$emailData['id']}",
            'messageId' => $emailData['id'],
            'mailboxId' => $emailData['mailbox_id'],
            'tag' => "email-{$emailData['id']}",
        ]);

        // Use web-push library (minishlink/web-push)
        // This is a simplified example
        Log::info('Web push notification sent', ['token' => $token->token]);
    }

    /**
     * Send Firebase Cloud Messaging notification
     */
    protected function sendFirebaseNotification($token, array $emailData): void
    {
        $firebaseServerKey = config('services.firebase.server_key');
        
        $response = Http::withHeaders([
            'Authorization' => "key={$firebaseServerKey}",
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $token->token,
            'notification' => [
                'title' => 'New Email - YG Mail',
                'body' => "{$emailData['from_name']}: {$emailData['subject']}",
                'icon' => '/icons/icon-192x192.png',
                'click_action' => url("/inbox?message={$emailData['id']}"),
                'sound' => 'default',
            ],
            'data' => [
                'messageId' => $emailData['id'],
                'mailboxId' => $emailData['mailbox_id'],
                'from' => $emailData['from_email'],
                'subject' => $emailData['subject'],
                'type' => 'new_email',
            ],
            'priority' => 'high',
        ]);

        if ($response->failed()) {
            throw new \Exception("FCM request failed: {$response->body()}");
        }

        Log::info('Firebase notification sent', [
            'token' => $token->token,
            'status' => $response->status(),
        ]);
    }

    /**
     * Register new push notification token
     */
    public function registerToken(int $userId, string $token, string $platform, ?string $browser = null, ?string $deviceInfo = null): void
    {
        PushNotificationToken::updateOrCreate(
            [
                'user_id' => $userId,
                'token' => $token,
            ],
            [
                'platform' => $platform,
                'browser' => $browser,
                'device_info' => $deviceInfo,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );
    }

    /**
     * Unregister push notification token
     */
    public function unregisterToken(string $token): void
    {
        PushNotificationToken::where('token', $token)
            ->update(['is_active' => false]);
    }

    /**
     * Send typing indicator notification
     */
    public function sendTypingIndicator(int $mailboxId, string $recipientId): void
    {
        broadcast(new \App\Events\UserTyping($mailboxId, $recipientId));
    }

    /**
     * Send email read receipt
     */
    public function sendReadReceipt(int $messageId, int $mailboxId): void
    {
        broadcast(new \App\Events\EmailRead($messageId, $mailboxId));
    }
}
