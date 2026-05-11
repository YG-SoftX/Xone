<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentMacro extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'name',
        'script',
        'triggers',
        'is_enabled',
        'created_by'
    ];

    protected $casts = [
        'triggers' => 'array',
        'is_enabled' => 'boolean'
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
