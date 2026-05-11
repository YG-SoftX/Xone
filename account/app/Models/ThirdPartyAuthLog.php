<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThirdPartyAuthLog extends Model
{
    protected $fillable = [
        'app_id',
        'user_id',
        'event_type',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * The app this log belongs to.
     */
    public function app()
    {
        return $this->belongsTo(ThirdPartyApp::class, 'app_id');
    }

    /**
     * The user who authenticated (if applicable).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
