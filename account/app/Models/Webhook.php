<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'last_triggered_at',
        'success_count',
        'failure_count',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    protected $hidden = [
        'secret', // Hide signing secret
    ];

    /**
     * Get the project
     */
    public function project()
    {
        return $this->belongsTo(DeveloperProject::class);
    }

    /**
     * Get delivery logs
     */
    public function deliveries()
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Generate HMAC signature for payload
     */
    public function generateSignature($payload)
    {
        return hash_hmac('sha256', $payload, $this->secret);
    }

    /**
     * Increment success count
     */
    public function recordSuccess()
    {
        $this->increment('success_count');
        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * Increment failure count
     */
    public function recordFailure()
    {
        $this->increment('failure_count');
        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * Get success rate
     */
    public function getSuccessRate()
    {
        $total = $this->success_count + $this->failure_count;
        return $total > 0 
            ? round(($this->success_count / $total) * 100, 2) 
            : 100;
    }
}
