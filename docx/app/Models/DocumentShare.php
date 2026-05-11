<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentShare extends Model
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
        'email',
        'permission',
        'created_by',
    ];

    /**
     * Get the document that is shared.
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the user who has access to the document.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who was shared the document via email.
     */
    public function emailUser()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    /**
     * Get the user who created this share.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the shared user can edit the document.
     *
     * @return bool
     */
    public function canEdit()
    {
        return in_array($this->permission, ['edit', 'owner']);
    }

    /**
     * Check if the shared user can comment on the document.
     *
     * @return bool
     */
    public function canComment()
    {
        return in_array($this->permission, ['comment', 'edit', 'owner']);
    }

    /**
     * Check if the shared user can view the document.
     *
     * @return bool
     */
    public function canView()
    {
        return in_array($this->permission, ['view', 'comment', 'edit', 'owner']);
    }
}
