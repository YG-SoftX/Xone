<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    use HasFactory;

    public $timestamps = false; // Only use created_at

    protected $fillable = [
        'webhook_id',
        'event_type',
        'payload',
        'status_code',
        'response_body',
        'attempt',
        'success',
        'next_retry_at',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'success' => 'boolean',
        'next_retry_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Get the webhook
     */
    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Mark as successful
     */
    public function markSuccess($statusCode, $responseBody = null)
    {
        $this->update([
            'status_code' => $statusCode,
            'response_body' => $responseBody,
            'success' => true,
        ]);

        $this->webhook->recordSuccess();
    }

    /**
     * Mark as failed with retry
     */
    public function markFailed($statusCode, $responseBody = null, $maxRetries = 5)
    {
        $shouldRetry = $this->attempt < $maxRetries;

        $this->update([
            'status_code' => $statusCode,
            'response_body' => $responseBody,
            'success' => false,
            'next_retry_at' => $shouldRetry 
                ? now()->addMinutes(pow(2, $this->attempt)) // Exponential backoff
                : null,
        ]);

        $this->webhook->recordFailure();
    }

    /**
     * Check if should be retried
     */
    public function shouldRetry()
    {
        return !$this->success && 
               $this->next_retry_at && 
               $this->next_retry_at->isPast();
    }
}
