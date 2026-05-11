<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'articles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'author_id',
        'slug',
        'title',
        'excerpt',
        'content',
        'featured_image',
        'category',
        'tags',
        'is_published',
        'published_at',
        'views',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tags' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'views' => 'integer',
    ];

    /**
     * Relationship: The author of the article.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Scope: Only published articles.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Scope: Filter by category.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Get recent articles.
     */
    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit($limit);
    }

    /**
     * Scope: Get popular articles by view count.
     */
    public function scopePopular(Builder $query, int $limit = 10): Builder
    {
        return $query
            ->published()
            ->orderBy('views', 'desc')
            ->limit($limit);
    }

    /**
     * Helper: Increment the view count.
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }

    /**
     * Helper: Get the URL for this article.
     */
    public function getUrl(): string
    {
        return route('articles.show', ['slug' => $this->slug]);
    }

    /**
     * Helper: Estimate read time in minutes based on word count.
     * Assumes average reading speed of 200 words per minute.
     */
    public function getReadTime(): int
    {
        $wordCount = str_word_count(strip_tags($this->content ?? ''));
        $minutes = (int) ceil($wordCount / 200);

        return max(1, $minutes);
    }

    /**
     * Helper: Get the formatted published date.
     */
    public function getFormattedDate(string $format = 'F j, Y'): string
    {
        return $this->published_at
            ? $this->published_at->format($format)
            : '';
    }
}
