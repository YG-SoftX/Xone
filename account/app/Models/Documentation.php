<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documentation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'documentation';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category',
        'slug',
        'title',
        'summary',
        'content',
        'order',
        'parent_id',
        'metadata',
        'is_published',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_published' => 'boolean',
        'metadata' => 'array',
        'order' => 'integer',
    ];

    /**
     * Scope for searching articles.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('title', 'LIKE', "%{$term}%")
                     ->orWhere('summary', 'LIKE', "%{$term}%")
                     ->orWhere('content', 'LIKE', "%{$term}%");
    }
}
