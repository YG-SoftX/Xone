<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CollectProject extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'description', 'user_id', 'status'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function forms()
    {
        return $this->hasMany(CollectForm::class, 'project_id');
    }
}
