<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
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
        'content_json',
        'version_number',
        'change_summary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'content_json' => 'array',
        'version_number' => 'integer',
    ];

    /**
     * Get the document that owns the version.
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the user who created this version.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the next version number for the associated document.
     *
     * @return int
     */
    public function getNextVersionNumber()
    {
        return $this->document->versions()->max('version_number') + 1;
    }
}
