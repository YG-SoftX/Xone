<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedCredential extends Model
{
    protected $fillable = [
        'user_id',
        'site_name',
        'site_url',
        'username',
        'password',
        'icon',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
