<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Twilio SMS Notification Service
 * 
 * Sends SMS alerts for critical cron job failures and system events.
 */
class TwilioNotifier
{
    protected ?string $accountSid;
    protected ?string $authToken;
    protected ?string $fromNumber;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.account_sid');
        $this->authToken = config('services.twilio.auth_token');
        $this->fromNumber = config('services.twilio.from_number');
    }

    /**
     * Send SMS alert
     */
    public function sendSms(string $to, string $message): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Twilio not configured',
                ];
            }

            // Validate phone number format
            if (!$this->isValidPhoneNumber($to)) {
                return [
                    'success' => false,
                    'message' => 'Invalid phone number format',
                ];
            }

            // Truncate message to SMS limit (160 chars per segment)
            $truncatedMessage = $this->truncateMessage($message);

            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->timeout(10)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", [
                    'From' => $this->fromNumber,
                    'To' => $to,
                    'Body' => $truncatedMessage,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::channel('cron')->info('SMS sent successfully', [
                    'to' => $to,
                    'message_id' => $data['sid'] ?? null,
                    'status' => $data['status'] ?? 'unknown',
                ]);

                return [
                    'success' => true,
                    'message_id' => $data['sid'],
                    'status' => $data['status'],
                    'segments' => $data['num_segments'] ?? 1,
                ];
            }

            $error = $response->json();
            
            Log::channel('cron')->error('SMS sending failed', [
                'to' => $to,
                'error_code' => $error['code'] ?? null,
                'error_message' => $error['message'] ?? 'Unknown error',
            ]);

            return [
                'success' => false,
                'message' => $error['message'] ?? 'Failed to send SMS',
                'error_code' => $error['code'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::channel('cron')->error('SMS exception: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send critical alert SMS with formatting
     */
    public function sendCriticalAlert(string $to, string $jobName, string $errorMessage): array
    {
        $message = "🚨 YG ACCOUNT ALERT\n";
        $message .= "Job: {$jobName}\n";
        $message .= "Error: " . substr($errorMessage, 0, 80) . "\n";
        $message .= "Check admin panel immediately.";

        return $this->sendSms($to, $message);
    }

    /**
     * Send backup verification failure SMS
     */
    public function sendBackupFailureAlert(string $to, string $details): array
    {
        $message = "⚠️ BACKUP FAILURE\n";
        $message .= "YG Account backup verification failed.\n";
        $message .= substr($details, 0, 100);

        return $this->sendSms($to, $message);
    }

    /**
     * Test SMS functionality
     */
    public function testSms(string $to): array
    {
        $message = "✅ YG Account SMS Test\n";
        $message .= "This is a test message from your cron job monitoring system.\n";
        $message .= "Time: " . now()->format('H:i:s');

        return $this->sendSms($to, $message);
    }

    /**
     * Check if Twilio is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->accountSid) && 
               !empty($this->authToken) && 
               !empty($this->fromNumber);
    }

    /**
     * Get account balance (for monitoring)
     */
    public function getBalance(): ?float
    {
        try {
            if (!$this->isConfigured()) {
                return null;
            }

            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->timeout(5)
                ->get("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Balance.json");

            if ($response->successful()) {
                return (float)$response->json()['balance'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get Twilio balance: ' . $e->getMessage());
            return null;
        }
    }

    // ── Private Helper Methods ──────────────────────────────────────────────

    protected function isValidPhoneNumber(string $phone): bool
    {
        // Basic validation: must start with + and have 10-15 digits
        return preg_match('/^\+[1-9]\d{9,14}$/', $phone) === 1;
    }

    protected function truncateMessage(string $message, int $maxLength = 160): string
    {
        if (strlen($message) <= $maxLength) {
            return $message;
        }

        return substr($message, 0, $maxLength - 3) . '...';
    }
}
