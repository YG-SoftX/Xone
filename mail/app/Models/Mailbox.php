<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mailbox extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'domain_id',
        'user_id',
        'local_part',
        'full_email',
        'display_name',
        'is_active',
        'auto_reply_enabled',
        'auto_reply_message',
        'auto_reply_starts_at',
        'auto_reply_ends_at',
        'storage_used_bytes',
        'storage_quota_bytes',
        'forwarding_enabled',
        'forward_to_email',
        'keep_copy',
        'spam_score_threshold',
        'quarantine_spam',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_reply_enabled' => 'boolean',
        'auto_reply_starts_at' => 'datetime',
        'auto_reply_ends_at' => 'datetime',
        'forwarding_enabled' => 'boolean',
        'keep_copy' => 'boolean',
        'quarantine_spam' => 'boolean',
    ];

    /**
     * Get the domain this mailbox belongs to
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(CustomDomain::class, 'domain_id');
    }

    /**
     * Get the user who owns this mailbox
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all email aliases for this mailbox
     */
    public function aliases(): HasMany
    {
        return $this->hasMany(EmailAlias::class, 'mailbox_id');
    }

    /**
     * Get all email rules for this mailbox
     */
    public function rules(): HasMany
    {
        return $this->hasMany(EmailRule::class, 'mailbox_id');
    }

    /**
     * Get all folders for this mailbox
     */
    public function folders(): HasMany
    {
        return $this->hasMany(EmailFolder::class, 'mailbox_id');
    }

    /**
     * Get storage usage percentage
     */
    public function getStorageUsagePercentage(): float
    {
        if ($this->storage_quota_bytes == 0) {
            return 0;
        }
        
        return round(($this->storage_used_bytes / $this->storage_quota_bytes) * 100, 2);
    }

    /**
     * Check if storage quota is exceeded
     */
    public function isStorageExceeded(): bool
    {
        return $this->storage_used_bytes >= $this->storage_quota_bytes;
    }

    /**
     * Get remaining storage in bytes
     */
    public function getRemainingStorage(): int
    {
        return max(0, $this->storage_quota_bytes - $this->storage_used_bytes);
    }

    /**
     * Format storage used in human-readable format
     */
    public function getFormattedStorageUsed(): string
    {
        return $this->formatBytes($this->storage_used_bytes);
    }

    /**
     * Format storage quota in human-readable format
     */
    public function getFormattedStorageQuota(): string
    {
        return $this->formatBytes($this->storage_quota_bytes);
    }

    /**
     * Helper to format bytes
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope: Active mailboxes only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if auto-reply is currently active
     */
    public function isAutoReplyActive(): bool
    {
        if (!$this->auto_reply_enabled) {
            return false;
        }

        $now = now();
        
        if ($this->auto_reply_starts_at && $now->lt($this->auto_reply_starts_at)) {
            return false;
        }

        if ($this->auto_reply_ends_at && $now->gt($this->auto_reply_ends_at)) {
            return false;
        }

        return true;
    }
}
