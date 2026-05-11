<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiUsageLog extends Model
{
    use HasFactory;

    public $timestamps = false; // Only use created_at

    protected $fillable = [
        'credential_id',
        'project_id',
        'product_id',
        'endpoint',
        'method',
        'status_code',
        'response_time_ms',
        'ip_address',
        'user_agent',
        'request_metadata',
        'created_at',
    ];

    protected $casts = [
        'request_metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the credential used
     */
    public function credential()
    {
        return $this->belongsTo(ApiCredential::class);
    }

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
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter by status code
     */
    public function scopeByStatus($query, $statusCode)
    {
        return $query->where('status_code', $statusCode);
    }

    /**
     * Get average response time for period
     */
    public static function getAverageResponseTime($projectId, $days = 30)
    {
        return self::where('project_id', $projectId)
            ->where('created_at', '>=', now()->subDays($days))
            ->avg('response_time_ms');
    }

    /**
     * Get requests count by endpoint
     */
    public static function getRequestsByEndpoint($projectId, $days = 30)
    {
        return self::where('project_id', $projectId)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('endpoint, COUNT(*) as count')
            ->groupBy('endpoint')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Get error rate
     */
    public static function getErrorRate($projectId, $days = 30)
    {
        $total = self::where('project_id', $projectId)
            ->where('created_at', '>=', now()->subDays($days))
            ->count();

        $errors = self::where('project_id', $projectId)
            ->where('created_at', '>=', now()->subDays($days))
            ->where('status_code', '>=', 400)
            ->count();

        return $total > 0 ? round(($errors / $total) * 100, 2) : 0;
    }
}
