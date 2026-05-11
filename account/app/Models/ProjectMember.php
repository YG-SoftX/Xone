<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
    ];

    /**
     * Get the project
     */
    public function project()
    {
        return $this->belongsTo(DeveloperProject::class);
    }

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if member is owner
     */
    public function isOwner()
    {
        return $this->role === 'owner';
    }

    /**
     * Check if member can edit
     */
    public function canEdit()
    {
        return in_array($this->role, ['owner', 'editor']);
    }

    /**
     * Check if member is billing admin
     */
    public function isBillingAdmin()
    {
        return in_array($this->role, ['owner', 'billing_admin']);
    }
}
