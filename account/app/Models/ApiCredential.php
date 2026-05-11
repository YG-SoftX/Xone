<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiCredential extends Model
{
    use HasFactory;

    protected $table = 'api_credentials';

    protected $fillable = [
        'project_id',
        'type',
        'name',
        'identifier',
        'secret',
        'scopes',
        'restrictions',
        'expires_at',
        'last_used_at',
        'total_requests',
        'is_active',
        'created_by_ip',
        'created_by_ua',
    ];

    protected $casts = [
        'scopes' => 'array',
        'restrictions' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'total_requests' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'secret', // Never expose secret in API responses
    ];

    /**
     * Get the project this credential belongs to
     */
    public function project()
    {
        return $this->belongsTo(DeveloperProject::class, 'project_id');
    }

    /**
     * Get usage logs for this credential
     */
    public function usageLogs()
    {
        return $this->hasMany(ApiUsageLog::class, 'credential_id');
    }

    /**
     * Verify the credential secret.
     */
    public function verifySecret(string $secret): bool
    {
        return hash_equals($this->secret, hash('sha256', $secret));
    }

    /**
     * Check if credential is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if credential has IP restriction
     */
    public function hasIpRestriction(): bool
    {
        return isset($this->restrictions['allowed_ips']) && 
               !empty($this->restrictions['allowed_ips']);
    }

    /**
     * Check if IP is allowed
     */
    public function isIpAllowed(string $ip): bool
    {
        if (!$this->hasIpRestriction()) {
            return true;
        }

        return in_array($ip, $this->restrictions['allowed_ips']);
    }

    /**
     * Increment request count and update last used timestamp
     */
    public function incrementUsage(): void
    {
        $this->increment('total_requests');
        $this->update(['last_used_at' => now()]);
    }
}
