<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceConfiguration extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_configurations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'service_key',
        'service_name',
        'description',
        'is_enabled',
        'is_maintenance_mode',
        'maintenance_message',
        'settings',
        'enabled_at',
        'disabled_at',
        'last_modified_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'is_maintenance_mode' => 'boolean',
        'settings' => 'array',
        'enabled_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];
}
