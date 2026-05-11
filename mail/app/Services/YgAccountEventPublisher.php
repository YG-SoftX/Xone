<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * YG Account Event Publisher for YG Mail
 * 
 * Publishes mail events to central YG Account for cross-module synchronization:
 * - Email received → Calendar event creation, Contact updates
 * - Email sent → Search indexing, Analytics
 * - Attachments → Unified storage integration
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
     * Publish email received event
     * Triggers: Calendar event creation, Contact last_contacted update
     */
    public function publishEmailReceived(int $messageId, int $userId, string $subject, string $fromEmail, string $fromName, string $bodyPlain): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'mail',
                    'event_type' => 'email_received',
                    'payload' => [
                        'user_id' => $userId,
                        'message_id' => $messageId,
                        'subject' => $subject,
                        'body_plain' => substr($bodyPlain, 0, 5000), // Limit size for performance
                        'from_email' => $fromEmail,
                        'from_name' => $fromName,
                        'received_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            if ($response->successful()) {
                Log::info('Email received event published', [
                    'message_id' => $messageId,
                    'from' => $fromEmail,
                ]);
                return true;
            }

            Log::warning('Failed to publish email received event', [
                'message_id' => $messageId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception publishing email received event', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Publish email sent event
     * Triggers: Search indexing, Contact updates
     */
    public function publishEmailSent(int $messageId, int $userId, string $subject, array $toEmails, string $bodyPlain): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'mail',
                    'event_type' => 'email_sent',
                    'payload' => [
                        'user_id' => $userId,
                        'message_id' => $messageId,
                        'subject' => $subject,
                        'body_plain' => substr($bodyPlain, 0, 5000),
                        'to_emails' => $toEmails,
                        'sent_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            if ($response->successful()) {
                Log::info('Email sent event published', [
                    'message_id' => $messageId,
                    'to_count' => count($toEmails),
                ]);
                return true;
            }

            Log::warning('Failed to publish email sent event', [
                'message_id' => $messageId,
                'status' => $response->status(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception publishing email sent event', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Publish attachment uploaded event
     * Triggers: Unified Drive storage sync, Search indexing
     */
    public function publishAttachmentUploaded(int $attachmentId, int $userId, string $filename, string $mimeType, int $size, string $storagePath, int $messageId): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'mail',
                    'event_type' => 'file_uploaded',
                    'payload' => [
                        'user_id' => $userId,
                        'file_id' => $attachmentId,
                        'name' => $filename,
                        'mime_type' => $mimeType,
                        'size' => $size,
                        'path' => $storagePath,
                        'source' => 'mail_attachment',
                        'message_id' => $messageId,
                    ],
                    'user_id' => $userId,
                ]);

            if ($response->successful()) {
                Log::info('Attachment upload event published', [
                    'attachment_id' => $attachmentId,
                    'filename' => $filename,
                ]);
                return true;
            }

            Log::warning('Failed to publish attachment upload event', [
                'attachment_id' => $attachmentId,
                'status' => $response->status(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception publishing attachment upload event', [
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Publish email deleted event
     * Triggers: Search index removal, Storage cleanup
     */
    public function publishEmailDeleted(int $messageId, int $userId): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->post("{$this->baseUrl}/events/publish", [
                    'service' => 'mail',
                    'event_type' => 'email_deleted',
                    'payload' => [
                        'user_id' => $userId,
                        'message_id' => $messageId,
                        'deleted_at' => now()->toIso8601String(),
                    ],
                    'user_id' => $userId,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Exception publishing email deleted event', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
