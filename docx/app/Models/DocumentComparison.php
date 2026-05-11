<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentComparison extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'compare_with_id',
        'created_by',
        'differences',
        'summary'
    ];

    protected $casts = [
        'differences' => 'array'
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function comparedWith()
    {
        return $this->belongsTo(Document::class, 'compare_with_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
