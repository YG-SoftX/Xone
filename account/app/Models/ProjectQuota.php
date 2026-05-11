<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectQuota extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'product_id',
        'daily_limit',
        'monthly_limit',
        'rate_limit_per_minute',
        'daily_used',
        'monthly_used',
        'daily_reset_date',
        'monthly_reset_date',
    ];

    protected $casts = [
        'daily_reset_date' => 'date',
        'monthly_reset_date' => 'date',
    ];

    /**
     * Get the project
     */
    public function project()
    {
        return $this->belongsTo(DeveloperProject::class);
    }

    /**
     * Get the product
     */
    public function product()
    {
        return $this->belongsTo(ApiProduct::class);
    }

    /**
     * Check if daily quota is exceeded
     */
    public function isDailyQuotaExceeded()
    {
        $this->checkAndResetDaily();
        return $this->daily_used >= $this->daily_limit;
    }

    /**
     * Check if monthly quota is exceeded
     */
    public function isMonthlyQuotaExceeded()
    {
        $this->checkAndResetMonthly();
        return $this->monthly_used >= $this->monthly_limit;
    }

    /**
     * Increment usage counters
     */
    public function incrementUsage()
    {
        $this->checkAndResetDaily();
        $this->checkAndResetMonthly();

        $this->increment('daily_used');
        $this->increment('monthly_used');
    }

    /**
     * Check and reset daily counter if needed
     */
    protected function checkAndResetDaily()
    {
        if (!$this->daily_reset_date || \Carbon\Carbon::parse($this->daily_reset_date)->isToday()) {
            return;
        }

        $this->update([
            'daily_used' => 0,
            'daily_reset_date' => now()->toDateString(),
        ]);
    }

    /**
     * Check and reset monthly counter if needed
     */
    protected function checkAndResetMonthly()
    {
        if (!$this->monthly_reset_date || $this->monthly_reset_date->month === now()->month) {
            return;
        }

        $this->update([
            'monthly_used' => 0,
            'monthly_reset_date' => now()->toDateString(),
        ]);
    }

    /**
     * Get remaining daily requests
     */
    public function getRemainingDaily()
    {
        $this->checkAndResetDaily();
        return max(0, $this->daily_limit - $this->daily_used);
    }

    /**
     * Get remaining monthly requests
     */
    public function getRemainingMonthly()
    {
        $this->checkAndResetMonthly();
        return max(0, $this->monthly_limit - $this->monthly_used);
    }

    /**
     * Get usage percentage
     */
    public function getDailyUsagePercentage()
    {
        return $this->daily_limit > 0 
            ? round(($this->daily_used / $this->daily_limit) * 100, 2) 
            : 0;
    }
}
