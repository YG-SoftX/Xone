<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentComment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'user_id',
        'content',
        'parent_id',
        'resolved',
        'resolved_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'resolved' => 'boolean',
    ];

    /**
     * Get the document that the comment belongs to.
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the user who created the comment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who resolved the comment.
     */
    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get all reply comments for this comment.
     */
    public function replies()
    {
        return $this->hasMany(DocumentComment::class, 'parent_id');
    }

    /**
     * Resolve the comment.
     *
     * @param int $userId
     * @return bool
     */
    public function resolve($userId)
    {
        $this->resolved = true;
        $this->resolved_by = $userId;

        return $this->save();
    }
}
