<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentChart extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'chart_type',
        'data',
        'options',
        'position',
        'created_by'
    ];

    protected $casts = [
        'data' => 'array',
        'options' => 'array',
        'position' => 'array'
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
