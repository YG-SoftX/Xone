<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'drive_file_id',
        'shared_by_id',
        'shared_with_id',
        'shared_with_email',
        'permission',
        'expires_at',
        'token',
        'is_link_share',
    ];

    protected $casts = [
        'expires_at'    => 'datetime',
        'is_link_share' => 'boolean',
    ];

    public function file(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DriveFile::class, 'drive_file_id');
    }

    public function sharedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by_id');
    }

    public function sharedWith(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
