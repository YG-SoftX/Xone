<?php

namespace App\Http\Controllers;

use App\Models\AppModule;
use App\Jobs\DeployServiceJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * DeploymentController
 * 
 * Manages service deployment operations (git pull, migrations, cache rebuild).
 */
class DeploymentController extends Controller
{
    /**
     * Deploy a specific service.
     */
    public function deploy(Request $request, $id)
    {
        $validated = $request->validate([
            'git_pull' => 'boolean',
            'composer_install' => 'boolean',
            'migrate' => 'boolean',
            'rebuild_cache' => 'boolean',
            'restart_queue' => 'boolean',
        ]);

        try {
            $service = AppModule::findOrFail($id);
            
            $options = array_merge([
                'git_pull' => true,
                'composer_install' => true,
                'migrate' => true,
                'rebuild_cache' => true,
                'restart_queue' => true,
            ], $validated);

            dispatch(new DeployServiceJob($id, $options));
            
            return response()->json([
                'success' => true,
                'message' => "Deployment initiated for {$service->name}",
                'service' => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'slug' => $service->slug,
                ],
                'options' => $options,
            ]);

        } catch (\Exception $e) {
            Log::error('Deployment failed', [
                'service_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate deployment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deploy all services sequentially.
     */
    public function deployAll(Request $request)
    {
        $validated = $request->validate([
            'services' => 'array', // Optional: specific service IDs
            'options' => 'array',
        ]);

        try {
            $serviceIds = $validated['services'] ?? null;
            $options = $validated['options'] ?? [];

            $services = $serviceIds
                ? AppModule::whereIn('id', $serviceIds)->get()
                : AppModule::where('is_active', true)->orderBy('id')->get();

            $deployedCount = 0;
            
            foreach ($services as $service) {
                dispatch(new DeployServiceJob($service->id, $options));
                $deployedCount++;
                
                // Small delay between deployments to avoid overwhelming the system
                sleep(2);
            }
            
            return response()->json([
                'success' => true,
                'message' => "Bulk deployment initiated for {$deployedCount} services",
                'services_deployed' => $deployedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk deployment failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate bulk deployment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get deployment status/history.
     */
    public function getStatus($id)
    {
        try {
            $service = AppModule::with(['deploymentLogs' => function($query) {
                $query->latest()->limit(10);
            }])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'service' => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'status' => $service->status,
                    'last_deployed_at' => $service->last_deployed_at,
                    'deploy_version' => $service->deploy_version,
                ],
                'recent_deployments' => $service->deploymentLogs ?? [],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
