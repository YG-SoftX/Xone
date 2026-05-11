<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileActivity extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'drive_file_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function file(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DriveFile::class, 'drive_file_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
