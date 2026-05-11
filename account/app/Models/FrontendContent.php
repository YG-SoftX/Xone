<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrontendContent extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'frontend_content';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'title',
        'content',
        'version',
        'locale',
        'is_published',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];
}
