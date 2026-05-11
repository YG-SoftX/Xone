<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentPresence extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'user_id',
        'session_id',
        'cursor_position',
        'selection',
        'last_active'
    ];

    protected $casts = [
        'cursor_position' => 'array',
        'selection' => 'array',
        'last_active' => 'datetime'
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
