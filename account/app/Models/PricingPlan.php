<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'slug',
        'description',
        'price',
        'monthly_price',
        'currency',
        'billing_cycle',
        'trial_days',
        'features',
        'quotas',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'trial_days' => 'integer',
        'features' => 'array',
        'quotas' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product that owns the pricing plan.
     */
    public function product()
    {
        return $this->belongsTo(ApiProduct::class, 'product_id');
    }
}
