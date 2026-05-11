<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'content', 'color', 
        'is_pinned', 'is_archived', 'is_deleted', 'labels'
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_archived' => 'boolean',
        'is_deleted' => 'boolean',
        'labels' => 'array',
        'archived_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function checklists()
    {
        return $this->hasMany(NoteChecklist::class)->orderBy('order');
    }

    public function collaborators()
    {
        return $this->hasMany(NoteCollaborator::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false)->where('is_deleted', false);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function togglePin()
    {
        $this->update(['is_pinned' => !$this->is_pinned]);
    }

    public function archive()
    {
        $this->update([
            'is_archived' => true,
            'archived_at' => now(),
            'is_pinned' => false
        ]);
    }

    public function restoreFromArchive()
    {
        $this->update([
            'is_archived' => false,
            'archived_at' => null
        ]);
    }

    public function moveToTrash()
    {
        $this->update([
            'is_deleted' => true,
            'deleted_at' => now(),
            'is_pinned' => false
        ]);
    }

    public function restoreFromTrash()
    {
        $this->update([
            'is_deleted' => false,
            'deleted_at' => null
        ]);
    }
}
