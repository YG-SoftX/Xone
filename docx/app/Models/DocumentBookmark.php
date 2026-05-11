<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentBookmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'name',
        'position',
        'created_by'
    ];

    protected $casts = [
        'position' => 'integer'
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
