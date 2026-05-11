<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_id',
        'logo',
        'primary_color',
        'welcome_message',
        'footer_content',
        'notification_sound',
        'tos_content',
        'privacy_content',
        'support_url',
        'profile_background_color',
        'profile_background_image',
        'role_settings',
        'primary_font',
        'favicon',
        'login_settings',
        'anniversary_template',
        'avatar_frame',
        'revenue_share_percentage',
        'status',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->hasMany(User::class, 'organization_id');
    }

    public function domains()
    {
        return $this->hasMany(OrganizationDomain::class);
    }
}
