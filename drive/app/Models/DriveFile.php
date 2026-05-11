<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class DriveFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'folder_id',
        'name',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'extension',
        'is_starred',
        'is_trashed',
        'description',
        'shared_link',
        'shared_link_expires_at',
        'download_count',
        'checksum',
    ];

    protected $casts = [
        'size'                   => 'integer',
        'is_starred'             => 'boolean',
        'is_trashed'             => 'boolean',
        'download_count'         => 'integer',
        'shared_link_expires_at' => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DriveFolder::class, 'folder_id');
    }

    public function shares(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FileShare::class);
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FileActivity::class);
    }

    public function getSizeForHumansAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)   return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)      return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }

    public function getIconAttribute(): string
    {
        return match(true) {
            str_starts_with($this->mime_type ?? '', 'image/')       => 'heroicon-o-photo',
            str_starts_with($this->mime_type ?? '', 'video/')       => 'heroicon-o-film',
            str_starts_with($this->mime_type ?? '', 'audio/')       => 'heroicon-o-musical-note',
            in_array($this->extension, ['pdf'])                     => 'heroicon-o-document',
            in_array($this->extension, ['doc', 'docx'])             => 'heroicon-o-document-text',
            in_array($this->extension, ['xls', 'xlsx', 'csv'])      => 'heroicon-o-table-cells',
            in_array($this->extension, ['zip', 'rar', 'tar', 'gz']) => 'heroicon-o-archive-box',
            default                                                  => 'heroicon-o-document',
        };
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('drive.download', $this->id);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }
}
