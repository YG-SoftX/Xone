<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformFeature;
use App\Models\YgService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminFeatureController extends Controller
{
    public function index()
    {
        $features = PlatformFeature::orderBy('feature_name')->get();
        $services = YgService::orderBy('service_name')->get();

        return view('admin.features.index', compact('features', 'services'));
    }

    public function toggleFeature(Request $request, $id)
    {
        $request->validate(['is_enabled' => 'required|boolean']);

        $feature = PlatformFeature::findOrFail($id);
        $feature->update(['is_enabled' => (bool) $request->is_enabled]);

        return redirect()->back()->with('success', "Feature '{$feature->feature_name}' " . ($request->is_enabled ? 'enabled' : 'disabled') . '.');
    }

    public function toggleFeatureVisibility(Request $request, $id)
    {
        $request->validate(['is_public' => 'required|boolean']);

        $feature = PlatformFeature::findOrFail($id);
        $feature->update(['is_public' => (bool) $request->is_public]);

        return redirect()->back()->with('success', "Feature visibility updated.");
    }

    public function toggleService(Request $request, $id)
    {
        $request->validate(['is_active' => 'required|boolean']);

        $service = YgService::findOrFail($id);
        $service->update(['is_active' => (bool) $request->is_active]);

        return redirect()->back()->with('success', "Service '{$service->service_name}' " . ($request->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function setMaintenance(Request $request, $id)
    {
        $request->validate(['is_maintenance' => 'required|boolean']);

        $service = YgService::findOrFail($id);
        $service->update(['is_maintenance' => (bool) $request->is_maintenance]);

        return redirect()->back()->with('success', "Service maintenance mode " . ($request->is_maintenance ? 'enabled' : 'disabled') . '.');
    }

    public function checkServiceHealth($id)
    {
        $service = YgService::findOrFail($id);
        $status = $service->checkHealth();

        return redirect()->back()->with('success', "Health check complete. Status: {$status}");
    }
}
