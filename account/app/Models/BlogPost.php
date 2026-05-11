<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'author_id',
        'title',
        'slug',
        'summary',
        'content',
        'featured_image',
        'category',
        'status', // draft, pending_approval, published, rejected
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($post) {
            $post->slug = Str::slug($post->title);
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getReadTimeAttribute()
    {
        $wordsPerMinute = 200;
        $words = str_word_count(strip_tags($this->content));
        return ceil($words / $wordsPerMinute);
    }
}
