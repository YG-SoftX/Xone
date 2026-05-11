<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEndpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'url',
        'events',
        'secret',
        'active',
        'max_retries',
    ];

    protected $casts = [
        'events' => 'array',
        'active' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function deliveries()
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
