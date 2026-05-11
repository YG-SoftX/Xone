<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FraudAlert extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'alert_type',
        'severity',
        'description',
        'evidence',
        'status',
        'reviewed_by',
        'reviewed_at',
        'resolution_notes',
    ];

    protected $casts = [
        'evidence' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(UserDevice::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope for open alerts
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope by severity
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Mark alert as resolved
     */
    public function resolve(int $adminUserId, string $notes = ''): void
    {
        $this->update([
            'status' => 'resolved',
            'reviewed_by' => $adminUserId,
            'reviewed_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Mark alert as false positive
     */
    public function markAsFalsePositive(int $adminUserId, string $notes = ''): void
    {
        $this->update([
            'status' => 'false_positive',
            'reviewed_by' => $adminUserId,
            'reviewed_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }
}
