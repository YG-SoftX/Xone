<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileDevice extends Model
{
    protected $guarded = [];
    protected $casts = [
        'is_secured' => 'boolean',
        'remote_wipe_pending' => 'boolean',
    ];
}
