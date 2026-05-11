<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'plan_id',
        'provider',
        'provider_subscription_id',
        'status',
        'current_period_start',
        'current_period_end',
        'trial_ends_at',
        'canceled_at',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'trial_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
