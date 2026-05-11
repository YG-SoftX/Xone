<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class CronJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'command',
        'schedule',
        'description',
        'is_enabled',
        'is_system',
        'last_run_at',
        'next_run_at',
        'total_runs',
        'failed_runs',
        'last_output',
        'status',
        'metadata',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_system' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'total_runs' => 'integer',
        'failed_runs' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_runs === 0) {
            return 100.0;
        }
        
        $successful = $this->total_runs - $this->failed_runs;
        return round(($successful / $this->total_runs) * 100, 2);
    }

    /**
     * Check if job is healthy (success rate > 90%)
     */
    public function getIsHealthyAttribute(): bool
    {
        return $this->success_rate >= 90;
    }

    /**
     * Get human-readable next run time
     */
    public function getNextRunHumanAttribute(): string
    {
        if (!$this->next_run_at) {
            return 'Not scheduled';
        }
        
        return $this->next_run_at->diffForHumans();
    }

    /**
     * Get last run status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'success' => 'success',
            'running' => 'warning',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Generate cPanel-compatible cron command
     */
    public function getCpanelCommandAttribute(): string
    {
        // Extract PHP path and artisan command
        if (preg_match('#(/usr/bin/php|/usr/local/bin/php).*?(artisan\s+\S+)#', $this->command, $matches)) {
            $phpPath = $matches[1];
            $artisanCmd = $matches[2];
            
            return "{$phpPath} /home/YOUR_CPANEL_USERNAME/{$artisanCmd} >> /dev/null 2>&1";
        }
        
        return $this->command;
    }

    /**
     * Scope: Only enabled jobs
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope: System jobs only
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope: User-manageable jobs
     */
    public function scopeUserManageable($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Record successful execution
     */
    public function recordSuccess(string $output = ''): void
    {
        $this->update([
            'last_run_at' => now(),
            'status' => 'success',
            'last_output' => substr($output, 0, 1000), // Limit output length
            'total_runs' => $this->total_runs + 1,
            'next_run_at' => $this->calculateNextRun(),
        ]);
    }

    /**
     * Record failed execution
     */
    public function recordFailure(string $error = ''): void
    {
        $this->update([
            'last_run_at' => now(),
            'status' => 'failed',
            'last_output' => substr($error, 0, 1000),
            'failed_runs' => $this->failed_runs + 1,
            'total_runs' => $this->total_runs + 1,
        ]);
    }

    /**
     * Calculate next run time based on cron schedule
     */
    protected function calculateNextRun(): ?Carbon
    {
        try {
            // This is a simplified calculation
            // For production, consider using dragonmantank/cron-expression package
            return now()->addMinute(); // Default to next minute for * * * * *
        } catch (\Exception $e) {
            return null;
        }
    }
}
