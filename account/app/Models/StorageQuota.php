<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageQuota extends Model
{
    protected $fillable = ['user_id', 'quota_bytes', 'used_bytes'];

    protected $casts = [
        'quota_bytes' => 'integer',
        'used_bytes'  => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRemainingBytesAttribute(): int
    {
        return max(0, $this->quota_bytes - $this->used_bytes);
    }

    public function getUsedPercentAttribute(): float
    {
        if ($this->quota_bytes === 0) return 100.0;
        return round(($this->used_bytes / $this->quota_bytes) * 100, 1);
    }

    public function getQuotaForHumansAttribute(): string
    {
        return self::formatBytes($this->quota_bytes);
    }

    public function getUsedForHumansAttribute(): string
    {
        return self::formatBytes($this->used_bytes);
    }

    public function hasSpace(int $bytes): bool
    {
        return ($this->used_bytes + $bytes) <= $this->quota_bytes;
    }

    public function consume(int $bytes): void
    {
        $this->increment('used_bytes', $bytes);
    }

    public function release(int $bytes): void
    {
        $this->decrement('used_bytes', max(0, $bytes));
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 ** 4) return round($bytes / 1024 ** 4, 1) . ' TB';
        if ($bytes >= 1024 ** 3) return round($bytes / 1024 ** 3, 1) . ' GB';
        if ($bytes >= 1024 ** 2) return round($bytes / 1024 ** 2, 1) . ' MB';
        if ($bytes >= 1024)      return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
