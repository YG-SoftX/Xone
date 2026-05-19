<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Article extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'category',
        'type',
        'published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    public function scopeFaq($query)
    {
        return $query->where('type', 'faq');
    }

    public function scopeArticles($query)
    {
        return $query->where('type', 'article');
    }

    public function scopeByCategory($query, ?string $category)
    {
        if ($category) {
            return $query->where('category', $category);
        }
        return $query;
    }

    public static function categories(): array
    {
        return [
            'getting-started'  => 'Getting Started',
            'account'          => 'Account & Billing',
            'mail'             => 'YG Mail',
            'xcel'             => 'YG Xcel',
            'docx'             => 'YG DocX',
            'troubleshooting'  => 'Troubleshooting',
            'security'         => 'Security & Privacy',
        ];
    }

    public function categoryLabel(): string
    {
        return static::categories()[$this->category] ?? $this->category ?? 'Uncategorized';
    }

    public function excerpt(int $length = 150): string
    {
        return Str::limit(strip_tags($this->content), $length);
    }
}
