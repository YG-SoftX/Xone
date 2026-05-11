<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceActivityLog extends Model
{
    protected $fillable = [
        'device_id',
        'user_id',
        'activity_type',
        'ip_address',
        'latitude',
        'longitude',
        'user_agent',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function device()
    {
        return $this->belongsTo(UserDevice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
