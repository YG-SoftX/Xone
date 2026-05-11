<?php

namespace App\Models\Society;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    protected $table = 'society_posts';

    protected $fillable = [
        'user_id',
        'content',
        'media_type',
        'media_url',
        'thumbnail',
        'likes_count',
        'comments_count',
        'shares_count',
        'is_public',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function isLikedBy(User $user): bool
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * Calculate and update the trending score
     */
    public function updateTrendingScore(): void
    {
        $hoursOld = $this->created_at->diffInHours(now());
        
        // Reddit-style Hot Algorithm (Simplified)
        // Score = (Likes * 3) + (Comments * 2) - (HoursOld * 0.5)
        $score = ($this->likes_count * 3) + ($this->comments_count * 2) - ($hoursOld * 0.5);
        
        $this->update(['trending_score' => max(0, $score)]);
    }
}
