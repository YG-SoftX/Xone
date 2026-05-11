<?php

namespace App\Models\Society;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    protected $table = 'society_reports';

    protected $fillable = [
        'user_id',
        'reportable_id',
        'reportable_type',
        'reason',
        'details',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }
}
