<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'status',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name) . '-' . Str::random(6);
            }
        });
    }

    /**
     * Get the user that owns the project.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team members for the project.
     */
    public function teamMembers()
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * Get the OAuth applications for the project.
     */
    public function oauthApplications()
    {
        return $this->hasMany(OAuthApplication::class);
    }

    /**
     * Get the API keys for the project.
     */
    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Get the Play Store apps for the project.
     */
    public function playStoreApps()
    {
        return $this->hasMany(PlayStoreApp::class);
    }

    /**
     * Get the subscriptions for the project.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the invoices for the project.
     */
    public function invoices()
    {
        return $this->hasMany(BillingInvoice::class);
    }

    /**
     * Get the AI usage logs for the project.
     */
    public function aiUsageLogs()
    {
        return $this->hasMany(AiUsageLog::class);
    }

    /**
     * Get the webhook endpoints for the project.
     */
    public function webhookEndpoints()
    {
        return $this->hasMany(WebhookEndpoint::class);
    }

    /**
     * Get the active subscription.
     */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
    }

    /**
     * Check if project has active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * Get total API calls today.
     */
    public function getApiCallsTodayAttribute(): int
    {
        return $this->aiUsageLogs()
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Get total spend.
     */
    public function getTotalSpendAttribute(): float
    {
        return $this->invoices()
            ->where('status', 'paid')
            ->sum('amount');
    }

    /**
     * Scope a query to only include active projects.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include projects for a user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
