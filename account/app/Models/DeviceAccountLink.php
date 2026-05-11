<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceAccountLink extends Model
{
    protected $fillable = [
        'device_fingerprint',
        'user_id',
        'device_id',
        'ip_address',
        'account_count',
        'account_ids',
        'is_suspicious',
        'suspicion_reason',
        'first_detected_at',
        'last_updated_at',
    ];

    protected $casts = [
        'account_ids' => 'array',
        'is_suspicious' => 'boolean',
        'first_detected_at' => 'datetime',
        'last_updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(UserDevice::class);
    }

    /**
     * Get all users linked to this device fingerprint
     */
    public function getLinkedUsersAttribute()
    {
        if (!$this->account_ids) {
            return collect();
        }
        
        return User::whereIn('id', $this->account_ids)->get();
    }
}
