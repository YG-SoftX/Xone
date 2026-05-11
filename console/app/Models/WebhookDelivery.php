<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_endpoint_id',
        'event_type',
        'payload',
        'status_code',
        'response_body',
        'attempt',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'delivered_at' => 'datetime',
    ];

    public function webhookEndpoint()
    {
        return $this->belongsTo(WebhookEndpoint::class);
    }

    public function isSuccess(): bool
    {
        return $this->status_code >= 200 && $this->status_code < 300;
    }

    public function scopeSuccessful($query)
    {
        return $query->whereBetween('status_code', [200, 299]);
    }
}
