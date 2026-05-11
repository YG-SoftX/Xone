<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'key',
        'name',
        'restrictions',
        'rate_limit',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'restrictions' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    public function scopeActive($query)
    {
        return $query->whereNull('expires_at')
            ->orWhere('expires_at', '>', now());
    }
}
