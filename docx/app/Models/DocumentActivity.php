<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentActivity extends Model
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
        'action',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the document that the activity belongs to.
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the user who performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a new activity entry for a document.
     *
     * @param int $documentId
     * @param int $userId
     * @param string $action
     * @param array $metadata
     * @return \App\Models\DocumentActivity
     */
    public static function recordActivity(int $documentId, int $userId, string $action, array $metadata = [])
    {
        return static::create([
            'document_id' => $documentId,
            'user_id' => $userId,
            'action' => $action,
            'metadata' => $metadata,
        ]);
    }
}
