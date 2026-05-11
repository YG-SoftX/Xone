<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'content',
        'content_json',
        'category',
        'is_public',
        'is_system',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'content_json' => 'array',
        'is_public' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Get the user that owns the template.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include public templates.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic(Builder $query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include templates for a specific category.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCategory(Builder $query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include system templates.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystem(Builder $query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Apply this template to a document.
     *
     * @param int $documentId
     * @return \App\Models\Document|null
     */
    public function applyToDocument(int $documentId)
    {
        $document = Document::find($documentId);

        if (!$document) {
            return null;
        }

        $document->content = $this->content;
        $document->content_json = $this->content_json;
        $document->save();

        return $document;
    }
}
