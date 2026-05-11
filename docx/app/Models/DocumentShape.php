<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentShape extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'type',
        'properties',
        'content',
        'z_index',
        'created_by'
    ];

    protected $casts = [
        'properties' => 'array',
        'z_index' => 'integer'
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
