<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YgService extends Model
{
    protected $table = 'yg_services';

    protected $fillable = [
        'service_key',
        'service_name',
        'url',
        'is_active',
        'is_maintenance',
        'status',
        'icon',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_maintenance' => 'boolean',
    ];

    /**
     * Get only active services for the launcher
     */
    public static function getLauncherServices()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();
    }
}
