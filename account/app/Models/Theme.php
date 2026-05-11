<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'service',
        'name',
        'is_active',
        'colors',
        'fonts',
        'logos',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'colors' => 'array',
        'fonts' => 'array',
        'logos' => 'array',
        'settings' => 'array',
    ];
}
