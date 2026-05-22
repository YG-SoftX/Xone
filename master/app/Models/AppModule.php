<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppModule extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'is_active' => 'boolean',
        'is_core' => 'boolean',
        'config' => 'array',
        'health_data' => 'array',
        'installed_at' => 'datetime',
        'last_seen' => 'datetime',
        'last_health_check' => 'datetime',
    ];
}