<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KYC extends Model
{
    protected $fillable = [
        'user_id',
        'document_type',
        'document_number',
        'document_path',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
