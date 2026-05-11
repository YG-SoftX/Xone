<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CollectForm extends Model
{
    use LogsActivity;

    protected $casts = [
        'schema' => 'array',
        'settings' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'schema'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function project()
    {
        return $this->belongsTo(CollectProject::class);
    }
}
