<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailRule extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'trigger_type',
        'trigger_value',
        'action_type',
        'action_value',
        'is_active',
        'priority',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
