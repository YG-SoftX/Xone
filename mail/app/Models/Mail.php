<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mail extends Model
{
    protected $fillable = [
        'user_id',
        'from',
        'to',
        'subject',
        'body',
        'folder',
        'read',
        'scheduled_at',
        // E2E Encryption fields
        'is_encrypted',
        'encrypted_body',
        'encrypted_subject',
        'encryption_algorithm',
        'encryption_key_id',
        'sender_signature',
        'recipient_public_key_id',
    ];

    protected $casts = [
        'read' => 'boolean',
        'user_id' => 'integer',
        'scheduled_at' => 'datetime',
        'is_encrypted' => 'boolean',
    ];

    /**
     * Check if email is encrypted
     */
    public function isEncrypted(): bool
    {
        return $this->is_encrypted ?? false;
    }

    /**
     * Get decrypted body (automatically decrypts if encrypted)
     */
    public function getDecryptedBodyAttribute(): ?string
    {
        if (!$this->is_encrypted || empty($this->encrypted_body)) {
            return $this->body;
        }

        try {
            $recipient = auth()->user();
            
            if (!$recipient || empty($recipient->private_key)) {
                return '[Encrypted - No private key available]';
            }

            $encryptionService = app(\App\Services\MailEncryptionService::class);
            
            // Decrypt using hybrid method
            $decryptionData = json_decode($this->encrypted_body, true);
            
            if (isset($decryptionData['encrypted_content'])) {
                // Hybrid encryption (RSA + AES)
                return $encryptionService->decryptEmailHybrid(
                    $decryptionData['encrypted_content'],
                    $decryptionData['encrypted_key'],
                    $decryptionData['iv'],
                    decrypt($recipient->private_key)
                );
            } else {
                // Simple RSA encryption
                return $encryptionService->decryptEmail(
                    $this->encrypted_body,
                    decrypt($recipient->private_key)
                );
            }
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt email', [
                'mail_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return '[Decryption Failed]';
        }
    }

    /**
     * Get decrypted subject
     */
    public function getDecryptedSubjectAttribute(): ?string
    {
        if (!$this->is_encrypted || empty($this->encrypted_subject)) {
            return $this->subject;
        }

        try {
            $recipient = auth()->user();
            
            if (!$recipient || empty($recipient->private_key)) {
                return '[Encrypted Subject]';
            }

            $encryptionService = app(\App\Services\MailEncryptionService::class);
            
            return $encryptionService->decryptEmail(
                $this->encrypted_subject,
                decrypt($recipient->private_key)
            );
        } catch (\Exception $e) {
            return '[Encrypted Subject]';
        }
    }

    /**
     * Verify sender signature
     */
    public function verifySignature(): bool
    {
        if (empty($this->sender_signature) || empty($this->body)) {
            return false;
        }

        try {
            $sender = User::where('email', $this->from)->first();
            
            if (!$sender || empty($sender->public_key)) {
                return false;
            }

            $encryptionService = app(\App\Services\MailEncryptionService::class);
            
            return $encryptionService->verifyEmailSignature(
                $this->body,
                $this->sender_signature,
                $sender->public_key
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Scope to get only encrypted emails
     */
    public function scopeEncrypted($query)
    {
        return $query->where('is_encrypted', true);
    }

    /**
     * Scope to get only unencrypted emails
     */
    public function scopeUnencrypted($query)
    {
        return $query->where('is_encrypted', false)->orWhereNull('is_encrypted');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
