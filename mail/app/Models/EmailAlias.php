<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAlias extends Model
{
    protected $fillable = [
        'mailbox_id',
        'alias_email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class);
    }
}
