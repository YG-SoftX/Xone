<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeveloperProject extends Model
{
    use HasFactory;

    protected $table = 'developer_projects';

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'project_id',
        'description',
        'website_url',
        'environment',
        'labels',
        'is_active',
        'last_activity_at',
    ];

    protected $casts = [
        'labels' => 'array',
        'is_active' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    /**
     * Boot method to generate project_id automatically
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->project_id)) {
                $project->project_id = 'ygxone-' . Str::slug($project->name) . '-' . Str::lower(Str::random(6));
            }
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name) . '-' . Str::random(6);
            }
        });
    }

    /**
     * Get the owner of the project
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get team members
     */
    public function members()
    {
        return $this->hasMany(ProjectMember::class);
    }

    /**
     * Get API credentials
     */
    public function credentials()
    {
        return $this->hasMany(ApiCredential::class);
    }

    /**
     * Get product subscriptions
     */
    public function subscriptions()
    {
        return $this->hasMany(ProductSubscription::class);
    }

    /**
     * Get quotas
     */
    public function quotas()
    {
        return $this->hasMany(ProjectQuota::class);
    }

    /**
     * Get usage logs
     */
    public function usageLogs()
    {
        return $this->hasMany(ApiUsageLog::class);
    }

    /**
     * Get webhooks
     */
    public function webhooks()
    {
        return $this->hasMany(Webhook::class);
    }

    /**
     * Get billing account link
     */
    public function billing()
    {
        return $this->hasOne(ProjectBilling::class);
    }

    /**
     * Check if user has access to this project
     */
    public function hasAccess($userId)
    {
        return $this->owner_id === $userId || 
               $this->members()->where('user_id', $userId)->exists();
    }

    /**
     * Get user's role in this project
     */
    public function getUserRole($userId)
    {
        if ($this->owner_id === $userId) {
            return 'owner';
        }

        $member = $this->members()->where('user_id', $userId)->first();
        return $member ? $member->role : null;
    }
}
