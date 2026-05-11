<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailQuota extends Model
{
    protected $fillable = [
        'user_id',
        'daily_sent',
        'daily_limit',
        'date',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'daily_sent' => 'integer',
        'daily_limit' => 'integer',
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasRemainingQuota(): bool
    {
        return $this->daily_sent < $this->daily_limit;
    }

    public function remainingQuota(): int
    {
        return max(0, $this->daily_limit - $this->daily_sent);
    }
}
