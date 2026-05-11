<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceAccountLink;
use App\Models\FraudAlert;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDeviceAnalyticsController extends Controller
{
    /**
     * Main device analytics dashboard
     */
    public function index(Request $request)
    {
        // Overall statistics
        $stats = [
            'total_devices' => UserDevice::count(),
            'active_devices' => UserDevice::where('is_blocked', false)->count(),
            'blocked_devices' => UserDevice::where('is_blocked', true)->count(),
            'trusted_devices' => UserDevice::where('is_trusted', true)->count(),
            'avg_reputation_score' => round(UserDevice::avg('device_reputation_score'), 2),
            'total_fraud_alerts' => FraudAlert::where('status', 'open')->count(),
            'critical_alerts' => FraudAlert::where('status', 'open')->where('severity', 'critical')->count(),
            'suspicious_device_links' => DeviceAccountLink::where('is_suspicious', true)->count(),
        ];

        // Recent fraud alerts
        $recentAlerts = FraudAlert::with(['user', 'device'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Suspicious device links (account farming detection)
        $suspiciousLinks = DeviceAccountLink::with('user')
            ->where('is_suspicious', true)
            ->orderBy('account_count', 'desc')
            ->limit(15)
            ->get();

        // Low reputation devices
        $lowReputationDevices = UserDevice::with('user')
            ->where('device_reputation_score', '<', 30)
            ->orderBy('device_reputation_score', 'asc')
            ->limit(15)
            ->get();

        // Geographic distribution
        $geoDistribution = UserDevice::selectRaw('country_code, COUNT(*) as count')
            ->whereNotNull('country_code')
            ->groupBy('country_code')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        // Device type distribution
        $deviceTypeDist = UserDevice::selectRaw('device_type, COUNT(*) as count')
            ->whereNotNull('device_type')
            ->groupBy('device_type')
            ->get();

        // OS distribution
        $osDist = UserDevice::selectRaw('os, COUNT(*) as count')
            ->whereNotNull('os')
            ->groupBy('os')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return view('admin.device-analytics.index', compact(
            'stats',
            'recentAlerts',
            'suspiciousLinks',
            'lowReputationDevices',
            'geoDistribution',
            'deviceTypeDist',
            'osDist'
        ));
    }

    /**
     * View all fraud alerts
     */
    public function fraudAlerts(Request $request)
    {
        $query = FraudAlert::with(['user', 'device', 'reviewer']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('alert_type')) {
            $query->where('alert_type', $request->alert_type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $alerts = $query->orderBy('created_at', 'desc')->paginate(50);

        $alertTypes = FraudAlert::select('alert_type')
            ->distinct()
            ->pluck('alert_type');

        return view('admin.device-analytics.fraud-alerts', compact('alerts', 'alertTypes'));
    }

    /**
     * Review and resolve a fraud alert
     */
    public function reviewAlert(Request $request, $alertId)
    {
        $request->validate([
            'action' => 'required|in:resolve,false_positive,investigate',
            'notes' => 'nullable|string|max:1000',
        ]);

        $alert = FraudAlert::findOrFail($alertId);
        $adminId = session('admin_user_id');

        switch ($request->action) {
            case 'resolve':
                $alert->resolve($adminId, $request->notes);
                $message = 'Alert marked as resolved';
                break;
            
            case 'false_positive':
                $alert->markAsFalsePositive($adminId, $request->notes);
                $message = 'Alert marked as false positive';
                break;
            
            case 'investigate':
                $alert->update([
                    'status' => 'investigating',
                    'reviewed_by' => $adminId,
                    'reviewed_at' => now(),
                ]);
                $message = 'Alert marked as under investigation';
                break;
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * View suspicious device links (cross-account detection)
     */
    public function suspiciousLinks(Request $request)
    {
        $query = DeviceAccountLink::with(['user', 'device']);

        if ($request->filled('min_accounts')) {
            $query->where('account_count', '>=', $request->min_accounts);
        }

        $links = $query->orderByDesc('account_count')->paginate(50);

        return view('admin.device-analytics.suspicious-links', compact('links'));
    }

    /**
     * View device details with full history
     */
    public function showDevice($deviceId)
    {
        $device = UserDevice::with(['user', 'activityLogs.user'])
            ->findOrFail($deviceId);

        $activityLogs = $device->activityLogs()
            ->with('user')
            ->orderBy('occurred_at', 'desc')
            ->paginate(50);

        $linkedAccounts = DeviceAccountLink::where('device_fingerprint', function($query) use ($device) {
            $query->select('device_fingerprint')
                ->from('device_account_links')
                ->where('device_id', $device->id)
                ->limit(1);
        })->with('user')->get();

        $fraudAlerts = FraudAlert::where('device_id', $deviceId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.device-analytics.device-detail', compact(
            'device',
            'activityLogs',
            'linkedAccounts',
            'fraudAlerts'
        ));
    }

    /**
     * Block/unblock device
     */
    public function toggleBlock(Request $request, $deviceId)
    {
        $device = UserDevice::findOrFail($deviceId);
        $device->update(['is_blocked' => !$device->is_blocked]);

        $action = $device->is_blocked ? 'blocked' : 'unblocked';
        
        return redirect()->back()->with('success', "Device {$action} successfully.");
    }

    /**
     * Trust/untrust device
     */
    public function toggleTrust(Request $request, $deviceId)
    {
        $device = UserDevice::findOrFail($deviceId);
        $device->update(['is_trusted' => !$device->is_trusted]);

        $action = $device->is_trusted ? 'trusted' : 'untrusted';
        
        return redirect()->back()->with('success', "Device {$action} successfully.");
    }

    /**
     * Get device activity timeline (API endpoint for charts)
     */
    public function activityTimeline(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'days' => 'integer|min:1|max:90',
        ]);

        $days = $request->input('days', 30);
        $userId = $request->user_id;

        $timeline = DB::table('device_activity_logs')
            ->where('user_id', $userId)
            ->where('occurred_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(occurred_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($timeline);
    }

    /**
     * Export device data for compliance (GDPR)
     */
    public function exportUserData($userId)
    {
        $user = User::findOrFail($userId);
        
        $devices = UserDevice::where('user_id', $userId)->get();
        $activityLogs = \App\Models\DeviceActivityLog::where('user_id', $userId)->get();
        $fraudAlerts = FraudAlert::where('user_id', $userId)->get();
        $accountLinks = DeviceAccountLink::where('user_id', $userId)->get();

        $exportData = [
            'user' => $user->toArray(),
            'devices' => $devices->toArray(),
            'activity_logs' => $activityLogs->toArray(),
            'fraud_alerts' => $fraudAlerts->toArray(),
            'account_links' => $accountLinks->toArray(),
            'exported_at' => now()->toIso8601String(),
        ];

        return response()->json($exportData, 200, [], JSON_PRETTY_PRINT)
            ->header('Content-Disposition', "attachment; filename=device_data_user_{$userId}.json");
    }
}
