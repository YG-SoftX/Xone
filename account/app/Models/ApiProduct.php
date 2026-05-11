<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'icon',
        'endpoints',
        'price_per_1000_calls',
        'default_daily_quota',
        'default_monthly_quota',
        'is_active',
        'requires_approval',
    ];

    protected $casts = [
        'endpoints' => 'array',
        'price_per_1000_calls' => 'decimal:4',
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
    ];

    /**
     * Get subscriptions to this product
     */
    public function subscriptions()
    {
        return $this->hasMany(ProductSubscription::class);
    }

    /**
     * Get pricing plans for this product
     */
    public function pricingPlans()
    {
        return $this->hasMany(PricingPlan::class);
    }

    /**
     * Get usage logs for this product
     */
    public function usageLogs()
    {
        return $this->hasMany(ApiUsageLog::class);
    }
}
