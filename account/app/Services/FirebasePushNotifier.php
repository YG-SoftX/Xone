<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging (FCM) Push Notification Service
 * 
 * Sends push notifications to mobile devices for critical alerts.
 */
class FirebasePushNotifier
{
    protected ?string $serverKey;
    protected ?string $projectId;

    public function __construct()
    {
        $this->serverKey = config('services.firebase.server_key');
        $this->projectId = config('services.firebase.project_id');
    }

    /**
     * Send push notification to specific device token
     */
    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = []): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Firebase not configured',
                ];
            }

            $payload = [
                'to' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => 1,
                ],
                'data' => array_merge([
                    'type' => 'cron_alert',
                    'timestamp' => now()->timestamp,
                ], $data),
                'priority' => 'high',
            ];

            $response = Http::withHeaders([
                'Authorization' => "key={$this->serverKey}",
                'Content-Type' => 'application/json',
            ])
            ->timeout(10)
            ->post('https://fcm.googleapis.com/fcm/send', $payload);

            if ($response->successful()) {
                $result = $response->json();
                
                Log::channel('cron')->info('Push notification sent', [
                    'device_token' => substr($deviceToken, 0, 8) . '...',
                    'message_id' => $result['message_id'] ?? null,
                    'success_count' => $result['success'] ?? 0,
                    'failure_count' => $result['failure'] ?? 0,
                ]);

                return [
                    'success' => true,
                    'message_id' => $result['message_id'] ?? null,
                    'result' => $result,
                ];
            }

            $error = $response->json();
            
            Log::channel('cron')->error('Push notification failed', [
                'error' => $error['error'] ?? 'Unknown error',
            ]);

            return [
                'success' => false,
                'message' => $error['error'] ?? 'Failed to send push notification',
            ];
        } catch (\Exception $e) {
            Log::channel('cron')->error('Push notification exception: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send push notification to multiple devices (topic or tokens)
     */
    public function sendToMultiple(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        try {
            if (empty($deviceTokens)) {
                return [
                    'success' => false,
                    'message' => 'No device tokens provided',
                ];
            }

            // FCM supports up to 1000 tokens per request
            $chunks = array_chunk($deviceTokens, 1000);
            $results = [];

            foreach ($chunks as $chunk) {
                $payload = [
                    'registration_ids' => $chunk,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'sound' => 'default',
                    ],
                    'data' => array_merge([
                        'type' => 'cron_alert',
                        'timestamp' => now()->timestamp,
                    ], $data),
                    'priority' => 'high',
                ];

                $response = Http::withHeaders([
                    'Authorization' => "key={$this->serverKey}",
                    'Content-Type' => 'application/json',
                ])
                ->timeout(10)
                ->post('https://fcm.googleapis.com/fcm/send', $payload);

                if ($response->successful()) {
                    $results[] = $response->json();
                } else {
                    Log::error('Batch push notification failed', [
                        'status' => $response->status(),
                    ]);
                }
            }

            return [
                'success' => true,
                'results' => $results,
                'total_sent' => count($deviceTokens),
            ];
        } catch (\Exception $e) {
            Log::error('Batch push notification exception: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send critical cron job alert via push
     */
    public function sendCronJobAlert(array $deviceTokens, string $jobName, string $errorMessage): array
    {
        $title = "🚨 Cron Job Failed";
        $body = "{$jobName}: " . substr($errorMessage, 0, 100);

        return $this->sendToMultiple($deviceTokens, $title, $body, [
            'job_name' => $jobName,
            'alert_type' => 'cron_failure',
            'action_url' => '/admin/cron-jobs',
        ]);
    }

    /**
     * Send backup verification failure alert
     */
    public function sendBackupFailureAlert(array $deviceTokens, string $details): array
    {
        $title = "⚠️ Backup Verification Failed";
        $body = substr($details, 0, 120);

        return $this->sendToMultiple($deviceTokens, $title, $body, [
            'alert_type' => 'backup_failure',
            'action_url' => '/admin/backups',
        ]);
    }

    /**
     * Test push notification
     */
    public function testPush(string $deviceToken): array
    {
        $title = "✅ YG Account Test";
        $body = "This is a test push notification from your monitoring system.\nTime: " . now()->format('H:i:s');

        return $this->sendToDevice($deviceToken, $title, $body, [
            'test' => 'true',
        ]);
    }

    /**
     * Check if Firebase is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->serverKey) && !empty($this->projectId);
    }

    /**
     * Subscribe device to topic (for group notifications)
     */
    public function subscribeToTopic(string $deviceToken, string $topic): array
    {
        try {
            if (!$this->isConfigured()) {
                return ['success' => false, 'message' => 'Firebase not configured'];
            }

            $response = Http::withHeaders([
                'Authorization' => "key={$this->serverKey}",
                'Content-Type' => 'application/json',
            ])
            ->timeout(5)
            ->post("https://iid.googleapis.com/iid/v1/{$deviceToken}/rel/topics/{$topic}");

            if ($response->successful()) {
                Log::info("Device subscribed to topic: {$topic}");
                return ['success' => true];
            }

            return [
                'success' => false,
                'message' => $response->json()['error'] ?? 'Subscription failed',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
