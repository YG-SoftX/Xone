<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteCollaborator extends Model
{
    use HasFactory;

    protected $fillable = ['note_id', 'user_id', 'permission'];

    public function note()
    {
        return $this->belongsTo(Note::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function canEdit()
    {
        return $this->permission === 'edit';
    }
}
