<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'type',
        'reference_position',
        'content',
        'number',
        'created_by'
    ];

    protected $casts = [
        'reference_position' => 'integer',
        'number' => 'integer'
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
