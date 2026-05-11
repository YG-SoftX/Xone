<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteChecklist extends Model
{
    use HasFactory;

    protected $fillable = ['note_id', 'item_text', 'is_completed', 'order'];

    protected $casts = [
        'is_completed' => 'boolean',
    ];

    public function note()
    {
        return $this->belongsTo(Note::class);
    }

    public function toggle()
    {
        $this->update(['is_completed' => !$this->is_completed]);
    }
}
