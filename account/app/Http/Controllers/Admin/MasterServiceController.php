<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\YgService;
use App\Models\PlatformFeature;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class MasterServiceController extends Controller
{
    /**
     * Service Health Dashboard
     */
    public function index()
    {
        $services = YgService::orderBy('service_name')->get()->map(function ($service) {
            return [
                ...$service->toArray(),
                'health' => $this->checkServiceHealth($service),
            ];
        });

        return view('admin.master.services.index', compact('services'));
    }

    /**
     * Check health of a specific service
     */
    public function checkHealth($id)
    {
        $service = YgService::findOrFail($id);
        $health = $this->checkServiceHealth($service);

        $service->update([
            'last_health_check' => now(),
            'status' => $health['status'] ?? 'unknown',
        ]);

        $responseTime = $health['response_time'] ?? 'N/A';
        return redirect()->back()->with(
            $health['status'] === 'operational' ? 'success' : 'error',
            "{$service->service_name}: {$health['status']} ({$responseTime}ms)"
        );
    }

    /**
     * Check All Services Health
     */
    public function checkAllHealth()
    {
        YgService::all()->each(function ($service) {
            $health = $this->checkServiceHealth($service);
            $service->update([
                'last_health_check' => now(),
                'status' => $health['status'] ?? 'unknown',
            ]);
        });

        return redirect()->back()->with('success', 'All services health checked.');
    }

    /**
     * Toggle Service Active/Inactive
     */
    public function toggleService($id)
    {
        $service = YgService::findOrFail($id);
        $service->update(['is_active' => !$service->is_active]);

        return redirect()->back()->with('success', "{$service->service_name} " . ($service->is_active ? 'activated' : 'deactivated'));
    }

    /**
     * Set Maintenance Mode
     */
    public function setMaintenance(Request $request, $id)
    {
        $service = YgService::findOrFail($id);
        $service->update(['is_maintenance' => (bool) $request->is_maintenance]);

        return redirect()->back()->with('success', "Maintenance mode " . ($request->is_maintenance ? 'enabled' : 'disabled'));
    }

    /**
     * Feature Toggles
     */
    public function features()
    {
        $features = PlatformFeature::orderBy('feature_name')->get();
        return view('admin.master.services.features', compact('features'));
    }

    /**
     * Toggle Feature
     */
    public function toggleFeature(Request $request, $id)
    {
        $feature = PlatformFeature::findOrFail($id);
        $feature->update(['is_enabled' => (bool) $request->is_enabled]);

        return redirect()->back()->with('success', "{$feature->feature_name} " . ($request->is_enabled ? 'enabled' : 'disabled'));
    }

    /**
     * Toggle Feature Visibility
     */
    public function toggleFeatureVisibility(Request $request, $id)
    {
        $feature = PlatformFeature::findOrFail($id);
        $feature->update(['is_public' => (bool) $request->is_public]);

        return redirect()->back()->with('success', "Feature visibility updated");
    }

    /**
     * Helper: Check service health via HTTP
     */
    private function checkServiceHealth(YgService $service): array
    {
        if (!$service->url) {
            return ['status' => 'not_configured', 'response_time' => null];
        }

        $healthUrl = $service->health_check_url ?? $service->url . '/up';
        $start = microtime(true);

        try {
            $response = Http::timeout(5)->get($healthUrl);
            $responseTime = round((microtime(true) - $start) * 1000);

            if ($response->successful()) {
                return ['status' => 'operational', 'response_time' => $responseTime];
            }

            return ['status' => 'degraded', 'response_time' => $responseTime];
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $start) * 1000);
            return ['status' => 'down', 'response_time' => $responseTime, 'error' => $e->getMessage()];
        }
    }
}
