<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarShare extends Model
{
    protected $fillable = ['email', 'permission'];

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }
}
