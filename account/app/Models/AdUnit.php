<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'type', // display, in-feed, in-article
        'size', // responsive, fixed
        'status', // active, paused
        'total_revenue',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function impressions()
    {
        return $this->hasMany(AdImpression::class);
    }
}
