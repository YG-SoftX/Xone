<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniversalFooterItem extends Model
{
    use HasFactory;

    protected $table = 'universal_footer_items';

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
        'order' => 'integer',
    ];

    /**
     * Scope for a specific service or global items
     */
    public function scopeForService($query, $serviceKey)
    {
        return $query->where(function ($q) use ($serviceKey) {
            $q->where('service_key', $serviceKey)
              ->orWhere('service_key', 'global');
        });
    }

    /**
     * Scope for footer items only
     */
    public function scopeFooter($query)
    {
        return $query->where('position', 'footer');
    }

    /**
     * Scope for active items only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('label');
    }

    /**
     * Get formatted icon class with fallback
     */
    public function getIconClassAttribute(): string
    {
        return $this->icon ?? 'fas fa-link';
    }

    /**
     * Check if item should open in new tab
     */
    public function shouldOpenInNewTab(): bool
    {
        return $this->is_external || 
               (isset($this->metadata['new_tab']) && $this->metadata['new_tab']);
    }

    /**
     * Get target attribute for anchor tag
     */
    public function getTargetAttribute(): string
    {
        return $this->shouldOpenInNewTab() ? '_blank' : '_self';
    }

    /**
     * Get rel attribute for security
     */
    public function getRelAttribute(): string
    {
        return $this->shouldOpenInNewTab() ? 'noopener noreferrer' : '';
    }
}
