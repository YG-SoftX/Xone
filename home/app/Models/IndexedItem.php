<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class IndexedItem extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'source_id',
        'service',
        'title',
        'content',
        'snippet',
        'url',
        'user_id',
        'metadata',
        'relevance_boost',
    ];

    protected $casts = [
        'metadata' => 'array',
        'relevance_boost' => 'float',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'service' => $this->service,
            'user_id' => $this->user_id,
        ];
    }
}
