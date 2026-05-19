<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'tickets';

    protected $fillable = [
        'user_id',
        'subject',
        'category',
        'priority',
        'status',
        'messages',
    ];

    protected function casts(): array
    {
        return [
            'messages' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'pending']);
    }

    public function getLastMessageAttribute()
    {
        $messages = $this->messages ?? [];
        return end($messages) ?: null;
    }

    public function getMessageCountAttribute()
    {
        return count($this->messages ?? []);
    }
}
