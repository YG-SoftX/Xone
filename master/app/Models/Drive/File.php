<?php

namespace App\Models\Drive;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $table = 'drive_files';

    protected $fillable = [
        'user_id', 'name', 'mime_type', 'size_bytes', 'storage_path', 'is_public', 'is_ai_training'
    ];
}
