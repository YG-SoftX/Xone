<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThirdPartyApp extends Model
{
    protected $guarded = [];

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }
}
