<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'domain',
        'is_verified',
        'verification_token',
        'verified_at',
        'mx_setup',
        'spf_setup',
        'dkim_setup',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'mx_setup' => 'boolean',
        'spf_setup' => 'boolean',
        'dkim_setup' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
