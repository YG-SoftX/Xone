<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniversalNavigationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_key',
        'position',
        'label',
        'url',
        'icon',
        'order',
        'is_active',
        'is_external',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_external' => 'boolean',
        'metadata' => 'json',
    ];

    /**
     * Scope for a specific service
     */
    public function scopeForService($query, $serviceKey)
    {
        return $query->where('service_key', $serviceKey);
    }

    /**
     * Scope for header items
     */
    public function scopeHeader($query)
    {
        return $query->where('position', 'header')->where('is_active', true)->orderBy('order');
    }

    /**
     * Scope for footer items
     */
    public function scopeFooter($query)
    {
        return $query->where('position', 'footer')->where('is_active', true)->orderBy('order');
    }
}
