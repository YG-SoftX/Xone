<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $fillable = [
        'mail_id',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
    ];

    protected $casts = [
        'mail_id' => 'integer',
        'file_size' => 'integer',
    ];

    public function mail(): BelongsTo
    {
        return $this->belongsTo(Mail::class);
    }

    public function getDownloadUrl(): string
    {
        return route('mail.attachment.download', $this->id);
    }
}
