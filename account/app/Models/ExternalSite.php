<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'domain',
        'verification_token',
        'status', // pending, verified, blocked
        'total_revenue',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function adUnits()
    {
        return $this->hasMany(AdUnit::class, 'site_id');
    }
}
