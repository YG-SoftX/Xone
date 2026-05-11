<?php

namespace App\Http\Controllers;

use App\Models\AppModule;
use App\Jobs\HealthCheckJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * HealthCheckController
 * 
 * Manual health check triggers for individual or all services.
 */
class HealthCheckController extends Controller
{
    /**
     * Trigger health check for all services.
     */
    public function checkAll()
    {
        try {
            dispatch(new HealthCheckJob());
            
            return response()->json([
                'success' => true,
                'message' => 'Health check initiated for all services',
                'job_id' => dispatch(new HealthCheckJob())->getJobId(),
            ]);

        } catch (\Exception $e) {
            Log::error('Health check failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate health check',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger health check for specific service.
     */
    public function checkService($slug)
    {
        try {
            $service = AppModule::where('slug', $slug)->firstOrFail();
            
            dispatch(new HealthCheckJob($slug));
            
            return response()->json([
                'success' => true,
                'message' => "Health check initiated for {$service->name}",
                'service' => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'slug' => $service->slug,
                    'current_status' => $service->status,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Service '{$slug}' not found",
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get current health status of all services.
     */
    public function getStatus()
    {
        $services = AppModule::select('id', 'name', 'slug', 'status', 'last_health_check', 'consecutive_failures')
            ->orderBy('name')
            ->get();

        $summary = [
            'total' => $services->count(),
            'healthy' => $services->where('status', 'healthy')->count(),
            'degraded' => $services->where('status', 'degraded')->count(),
            'unhealthy' => $services->where('status', 'unhealthy')->count(),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'services' => $services,
        ]);
    }
}
