<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotificationToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'browser',
        'device_info',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Active tokens only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by platform
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
