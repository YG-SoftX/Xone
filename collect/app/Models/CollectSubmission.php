<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CollectSubmission extends Model
{
    use LogsActivity;

    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['data', 'form_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->metadata = array_merge($model->metadata ?? [], [
                'verification_hash' => hash('sha256', uniqid() . microtime()),
                'origin' => request()->ip(),
            ]);
        });
    }

    public function form()
    {
        return $this->belongsTo(CollectForm::class);
    }
}
