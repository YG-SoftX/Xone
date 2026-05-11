<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationAnnouncement extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'title',
        'content',
        'priority', // high, normal, low
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
