<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomDomain extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'domain',
        'verification_token',
        'is_verified',
        'verified_at',
        'mx_configured',
        'spf_configured',
        'dkim_configured',
        'dmarc_configured',
        'catch_all_enabled',
        'catch_all_email',
        'tls_enforced',
        'spam_filtering',
        'virus_scanning',
        'max_mailboxes',
        'max_storage_gb',
        'daily_send_limit',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'mx_configured' => 'boolean',
        'spf_configured' => 'boolean',
        'dkim_configured' => 'boolean',
        'dmarc_configured' => 'boolean',
        'catch_all_enabled' => 'boolean',
        'tls_enforced' => 'boolean',
        'spam_filtering' => 'boolean',
        'virus_scanning' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user who owns this domain
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all mailboxes for this domain
     */
    public function mailboxes(): HasMany
    {
        return $this->hasMany(Mailbox::class, 'domain_id');
    }

    /**
     * Check if domain is fully configured
     */
    public function isFullyConfigured(): bool
    {
        return $this->is_verified && 
               $this->mx_configured && 
               $this->spf_configured && 
               $this->dkim_configured;
    }

    /**
     * Get DNS verification records
     */
    public function getDnsVerificationRecords(): array
    {
        return [
            'TXT' => [
                'name' => $this->domain,
                'value' => "yg-verify={$this->verification_token}",
                'description' => 'Domain ownership verification'
            ],
            'MX' => [
                'name' => $this->domain,
                'value' => "mail.{$this->domain}",
                'priority' => 10,
                'description' => 'Mail exchange record'
            ],
            'SPF' => [
                'name' => $this->domain,
                'type' => 'TXT',
                'value' => "v=spf1 include:_spf.ygxone.com ~all",
                'description' => 'Sender Policy Framework'
            ],
            'DKIM' => [
                'name' => "yg._domainkey.{$this->domain}",
                'type' => 'TXT',
                'value' => "v=DKIM1; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8A...",
                'description' => 'DomainKeys Identified Mail'
            ],
            'DMARC' => [
                'name' => "_dmarc.{$this->domain}",
                'type' => 'TXT',
                'value' => "v=DMARC1; p=quarantine; rua=mailto:dmarc@ygxone.com",
                'description' => 'Domain-based Message Authentication'
            ],
        ];
    }

    /**
     * Scope: Active domains only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Verified domains only
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}
