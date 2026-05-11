<?php

namespace App\Services;

use App\Models\Mail;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class EncryptedEmailService
{
    protected MailEncryptionService $encryptionService;

    public function __construct()
    {
        $this->encryptionService = app(MailEncryptionService::class);
    }

    /**
     * Send encrypted email
     */
    public function sendEncryptedEmail(
        User $sender,
        string $recipientEmail,
        string $subject,
        string $body,
        array $attachments = []
    ): Mail {
        // Find recipient
        $recipient = User::where('email', $recipientEmail)->first();

        if (!$recipient) {
            throw new \Exception("Recipient not found: {$recipientEmail}");
        }

        // Check if recipient has encryption enabled
        if (!$recipient->hasEncryptionEnabled() || !$recipient->hasEncryptionKeys()) {
            // Fall back to unencrypted email
            return $this->sendUnencryptedEmail($sender, $recipientEmail, $subject, $body, $attachments);
        }

        // Encrypt subject
        $encryptedSubject = $this->encryptionService->encryptEmail(
            $subject,
            $recipient->public_key
        );

        // Encrypt body using hybrid encryption (better for large content)
        $encryptedBodyData = $this->encryptionService->encryptEmailHybrid(
            $body,
            $recipient->public_key
        );

        // Sign the original body for authentication
        $signature = $this->encryptionService->signEmail(
            $body,
            decrypt($sender->private_key)
        );

        // Create encrypted email record
        $mail = Mail::create([
            'user_id' => $recipient->id,
            'from' => $sender->email,
            'to' => $recipientEmail,
            'subject' => '[Encrypted] ' . substr($subject, 0, 30) . '...',
            'body' => null, // Don't store plain text
            'is_encrypted' => true,
            'encrypted_subject' => $encryptedSubject,
            'encrypted_body' => json_encode($encryptedBodyData),
            'encryption_algorithm' => $encryptedBodyData['algorithm'],
            'sender_signature' => $signature,
            'recipient_public_key_id' => $recipient->id,
            'folder' => 'inbox',
            'read' => false,
        ]);

        // Handle encrypted attachments
        foreach ($attachments as $attachment) {
            $this->attachEncryptedFile($mail, $attachment, $recipient->public_key);
        }

        Log::info('Encrypted email sent', [
            'mail_id' => $mail->id,
            'from' => $sender->email,
            'to' => $recipientEmail,
        ]);

        return $mail;
    }

    /**
     * Send unencrypted email (fallback)
     */
    public function sendUnencryptedEmail(
        User $sender,
        string $recipientEmail,
        string $subject,
        string $body,
        array $attachments = []
    ): Mail {
        $mail = Mail::create([
            'user_id' => User::where('email', $recipientEmail)->value('id'),
            'from' => $sender->email,
            'to' => $recipientEmail,
            'subject' => $subject,
            'body' => $body,
            'is_encrypted' => false,
            'folder' => 'inbox',
            'read' => false,
        ]);

        // Handle regular attachments
        foreach ($attachments as $attachment) {
            $mail->attachments()->create($attachment);
        }

        return $mail;
    }

    /**
     * Attach encrypted file to email
     */
    protected function attachEncryptedFile(Mail $mail, array $fileData, string $recipientPublicKey): void
    {
        $encryptionService = $this->encryptionService;

        // Encrypt file content
        $encryptedData = $encryptionService->encryptAttachment(
            $fileData['content'],
            $recipientPublicKey
        );

        // Store encrypted attachment
        $mail->attachments()->create([
            'filename' => $fileData['filename'],
            'mime_type' => $fileData['mime_type'],
            'file_size' => strlen($fileData['content']),
            'file_path' => null, // Store in encrypted form
            'is_encrypted' => true,
            'encryption_metadata' => json_encode($encryptedData),
        ]);
    }

    /**
     * Read and decrypt email
     */
    public function readEncryptedEmail(Mail $mail, User $recipient): array
    {
        if (!$mail->is_encrypted) {
            return [
                'subject' => $mail->subject,
                'body' => $mail->body,
                'is_encrypted' => false,
                'signature_verified' => null,
            ];
        }

        // Decrypt using model accessors
        $decryptedSubject = $mail->decrypted_subject;
        $decryptedBody = $mail->decrypted_body;

        // Verify sender signature
        $signatureVerified = $mail->verifySignature();

        // Mark as read
        $mail->update(['read' => true]);

        // Decrypt attachments
        $decryptedAttachments = [];
        foreach ($mail->attachments as $attachment) {
            if ($attachment->is_encrypted) {
                $metadata = json_decode($attachment->encryption_metadata, true);
                
                try {
                    $decryptedContent = $this->encryptionService->decryptAttachment(
                        $metadata['encrypted_content'],
                        $metadata['encrypted_key'],
                        $metadata['iv'],
                        decrypt($recipient->private_key)
                    );

                    $decryptedAttachments[] = [
                        'filename' => $attachment->filename,
                        'mime_type' => $attachment->mime_type,
                        'content' => $decryptedContent,
                        'is_encrypted' => false,
                    ];
                } catch (\Exception $e) {
                    Log::error('Failed to decrypt attachment', [
                        'attachment_id' => $attachment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $decryptedAttachments[] = [
                    'filename' => $attachment->filename,
                    'mime_type' => $attachment->mime_type,
                    'file_path' => $attachment->file_path,
                    'is_encrypted' => false,
                ];
            }
        }

        return [
            'subject' => $decryptedSubject,
            'body' => $decryptedBody,
            'is_encrypted' => true,
            'signature_verified' => $signatureVerified,
            'sender_email' => $mail->from,
            'received_at' => $mail->created_at,
            'attachments' => $decryptedAttachments,
        ];
    }

    /**
     * Enable E2E encryption for user
     */
    public function enableEncryptionForUser(User $user): array
    {
        if ($user->hasEncryptionKeys()) {
            return [
                'success' => false,
                'message' => 'Encryption keys already exist',
            ];
        }

        $keys = $user->generateEncryptionKeys();

        return [
            'success' => true,
            'message' => 'E2E encryption enabled successfully',
            'public_key' => $keys['public_key'],
        ];
    }

    /**
     * Rotate encryption keys for user
     */
    public function rotateEncryptionKeys(User $user): array
    {
        // Archive old key
        if ($user->public_key) {
            \DB::table('email_encryption_keys')->insert([
                'user_id' => $user->id,
                'key_version' => 'v' . now()->timestamp,
                'public_key' => $user->public_key,
                'activated_at' => now()->subDay(),
                'deactivated_at' => now(),
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Generate new keys
        $keys = $user->generateEncryptionKeys();

        Log::info('Encryption keys rotated', ['user_id' => $user->id]);

        return [
            'success' => true,
            'message' => 'Encryption keys rotated successfully',
        ];
    }

    /**
     * Check if both users can communicate with E2E encryption
     */
    public function canCommunicateSecurely(string $senderEmail, string $recipientEmail): bool
    {
        $sender = User::where('email', $senderEmail)->first();
        $recipient = User::where('email', $recipientEmail)->first();

        if (!$sender || !$recipient) {
            return false;
        }

        return $sender->hasEncryptionEnabled() &&
               $recipient->hasEncryptionEnabled() &&
               $sender->hasEncryptionKeys() &&
               $recipient->hasEncryptionKeys();
    }
}
