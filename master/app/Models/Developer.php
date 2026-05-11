<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Developer extends Model
{
    protected $guarded = [];

    public function thirdPartyApps(): HasMany
    {
        return $this->hasMany(ThirdPartyApp::class);
    }
}
