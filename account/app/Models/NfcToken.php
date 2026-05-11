<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NfcToken extends Model
{
    protected $fillable = [
        'user_id',
        'token_uid',
        'device_name',
        'device_type',
        'is_active',
        'daily_limit',
        'transaction_limit',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(NfcTransaction::class);
    }

    public static function generateToken($userId, $deviceName, $deviceType = 'card')
    {
        return static::create([
            'user_id' => $userId,
            'token_uid' => 'NFC-' . strtoupper(Str::random(16)),
            'device_name' => $deviceName,
            'device_type' => $deviceType,
            'is_active' => true,
            'daily_limit' => 1000,
            'transaction_limit' => 100,
        ]);
    }
}
