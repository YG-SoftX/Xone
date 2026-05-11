<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'folder_id',
        'title',
        'content',
        'content_json',
        'thumbnail',
        'document_type',
        'status',
        'word_count',
        'page_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'content_json' => 'array',
        'word_count' => 'integer',
        'page_count' => 'integer',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<string, string>
     */
    protected $dates = ['deleted_at'];

    /**
     * Get the user that owns the document.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the folder that contains the document.
     */
    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Get all versions of the document.
     */
    public function versions()
    {
        return $this->hasMany(DocumentVersion::class)->orderBy('version_number', 'desc');
    }

    /**
     * Get all shares of the document.
     */
    public function shares()
    {
        return $this->hasMany(DocumentShare::class);
    }

    /**
     * Get all comments on the document.
     */
    public function comments()
    {
        return $this->hasMany(DocumentComment::class)->latest();
    }

    /**
     * Get all suggestions on the document.
     */
    public function suggestions()
    {
        return $this->hasMany(DocumentSuggestion::class)->latest();
    }

    /**
     * Get all activity for the document.
     */
    public function activity()
    {
        return $this->hasMany(DocumentActivity::class)->latest();
    }

    /**
     * Increment the word count by one.
     *
     * @return int
     */
    public function incrementWordCount()
    {
        $this->word_count = (int) str_word_count(strip_tags($this->content ?? ''));
        $this->save();

        return $this->word_count;
    }

    /**
     * Calculate and update the page count based on content length.
     * Assumes ~3000 characters per page.
     *
     * @return int
     */
    public function calculatePageCount()
    {
        $charsPerPge = 3000;
        $contentLength = strlen(strip_tags($this->content ?? ''));
        $this->page_count = max(1, (int) ceil($contentLength / $charsPerPge));
        $this->save();

        return $this->page_count;
    }

    /**
     * Create a new version snapshot of the document.
     *
     * @param \App\Models\User $user
     * @param string|null $changeSummary
     * @return \App\Models\DocumentVersion
     */
    public function createVersion(User $user, ?string $changeSummary = null)
    {
        return $this->versions()->create([
            'user_id' => $user->id,
            'content' => $this->content,
            'content_json' => $this->content_json,
            'version_number' => $this->versions()->max('version_number') + 1,
            'change_summary' => $changeSummary,
        ]);
    }

    /**
     * Check if a user can access this document.
     *
     * @param int $userId
     * @return bool
     */
    public function canAccess($userId)
    {
        if ($this->user_id === $userId) {
            return true;
        }

        return $this->shares()->where('user_id', $userId)->exists()
            || $this->shares()->where('email', User::find($userId)?->email)->exists();
    }

    /**
     * Get the thumbnail URL or null if no thumbnail.
     *
     * @return string|null
     */
    public function getThumbnailUrl()
    {
        if (!$this->thumbnail) {
            return null;
        }

        if (Str::startsWith($this->thumbnail, ['http://', 'https://'])) {
            return $this->thumbnail;
        }

        return asset('storage/' . $this->thumbnail);
    }
}
