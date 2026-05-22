<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\AppModule; // Assuming this model exists for managing apps
use Illuminate\Support\Facades\Log;

class ElectronAppController extends Controller
{
    /**
     * Register an electron app instance with the admin panel
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appId' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'version' => 'required|string|max:50',
            'platform' => 'required|string|max:50',
            'arch' => 'required|string|max:20',
            'installedAt' => 'required|date',
            'config' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Create or update the electron app record
            $appModule = AppModule::updateOrCreate(
                ['slug' => $request->appId],
                [
                    'name' => $request->name,
                    'type' => 'electron',
                    'version' => $request->version,
                    'platform' => $request->platform,
                    'arch' => $request->arch,
                    'status' => 'registered',
                    'last_seen' => now(),
                    'installed_at' => $request->installedAt,
                    'config' => json_encode($request->config),
                    'is_active' => true,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Electron app registered successfully',
                'app' => $appModule,
            ]);
        } catch (\Exception $e) {
            Log::error('Electron app registration failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Receive heartbeat from electron app
     */
    public function heartbeat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appId' => 'required|string|exists:app_modules,slug',
            'health' => 'required|array',
            'health.version' => 'required|string',
            'health.platform' => 'required|string',
            'health.arch' => 'required|string',
            'health.uptime' => 'required|integer',
            'health.timestamp' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $app = AppModule::where('slug', $request->appId)->firstOrFail();

            // Update the app's status and last seen time
            $app->update([
                'status' => 'online',
                'last_seen' => now(),
                'last_health_check' => now(),
                'health_data' => json_encode($request->health),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Heartbeat received',
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Electron app heartbeat failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Heartbeat failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get configuration for an electron app
     */
    public function getConfig(Request $request, string $appId)
    {
        try {
            $app = AppModule::where('slug', $appId)->firstOrFail();

            // Return the current configuration for the electron app
            $config = json_decode($app->config ?? '{}', true) ?: [];

            return response()->json([
                'success' => true,
                'config' => $config,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Get electron app config failed', [
                'error' => $e->getMessage(),
                'app_id' => $appId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not retrieve config: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update configuration for an electron app
     */
    public function updateConfig(Request $request, string $appId)
    {
        $validator = Validator::make($request->all(), [
            'config' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $app = AppModule::where('slug', $appId)->firstOrFail();

            $app->update([
                'config' => json_encode($request->config),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuration updated successfully',
                'config' => $request->config,
            ]);
        } catch (\Exception $e) {
            Log::error('Update electron app config failed', [
                'error' => $e->getMessage(),
                'app_id' => $appId,
                'config' => $request->config,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Config update failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get status of all electron apps
     */
    public function getStatus()
    {
        try {
            $electronApps = AppModule::where('type', 'electron')
                ->select('id', 'name', 'slug', 'version', 'platform', 'status', 'last_seen', 'last_health_check')
                ->get();

            return response()->json([
                'success' => true,
                'apps' => $electronApps,
                'count' => $electronApps->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Get electron apps status failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not retrieve status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unregister an electron app
     */
    public function unregister(Request $request, string $appId)
    {
        try {
            $app = AppModule::where('slug', $appId)->firstOrFail();

            $app->update([
                'status' => 'offline',
                'is_active' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Electron app unregistered successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Electron app unregister failed', [
                'error' => $e->getMessage(),
                'app_id' => $appId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unregister failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}