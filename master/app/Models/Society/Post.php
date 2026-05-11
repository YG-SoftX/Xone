<?php

namespace App\Models\Society;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Post extends Model
{
    protected $fillable = [
        'user_id', 'content', 'media_url', 'media_type', 'is_hidden', 'is_public', 'trending_score'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
