<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordCredential extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'passwords';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'site_name',
        'site_url',
        'username',
        'encrypted_password',
        'notes',
        'category',
        'last_used_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'encrypted_password' => 'encrypted',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the user that owns the password.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
