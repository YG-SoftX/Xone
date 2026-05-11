<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageQuota extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quota_bytes',
        'plan',
    ];

    protected $casts = [
        'quota_bytes' => 'integer',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUsedBytesAttribute(): int
    {
        return $this->user->storage_used ?? 0;
    }

    public function getUsedPercentAttribute(): float
    {
        if ($this->quota_bytes <= 0) return 0;
        return min(100, round(($this->used_bytes / $this->quota_bytes) * 100, 1));
    }

    public function getQuotaForHumansAttribute(): string
    {
        return $this->formatBytes($this->quota_bytes);
    }

    public function getUsedForHumansAttribute(): string
    {
        return $this->formatBytes($this->used_bytes);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)   return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)      return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
