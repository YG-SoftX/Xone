<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSuggestion extends Model
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
        'original_content',
        'position_start',
        'position_end',
        'status',
        'resolved_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'position_start' => 'integer',
        'position_end' => 'integer',
    ];

    /**
     * Get the document that the suggestion belongs to.
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the user who created the suggestion.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who resolved the suggestion.
     */
    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Accept the suggestion and apply it to the document.
     *
     * @param int|null $userId
     * @return bool
     */
    public function accept(?int $userId = null)
    {
        $this->status = 'accepted';
        $this->resolved_by = $userId;

        // Apply the suggestion content to the document
        if ($this->document && $this->document->content) {
            $content = $this->document->content;
            $originalContent = $this->original_content;
            $newContent = $this->content;

            if ($originalContent && $this->position_start !== null && $this->position_end !== null) {
                $content = substr_replace(
                    $content,
                    $newContent,
                    $this->position_start,
                    $this->position_end - $this->position_start
                );
            } elseif ($originalContent) {
                $content = str_replace($originalContent, $newContent, $content);
            }

            $this->document->content = $content;
            $this->document->save();
        }

        return $this->save();
    }

    /**
     * Reject the suggestion.
     *
     * @param int|null $userId
     * @return bool
     */
    public function reject(?int $userId = null)
    {
        $this->status = 'rejected';
        $this->resolved_by = $userId;

        return $this->save();
    }
}
