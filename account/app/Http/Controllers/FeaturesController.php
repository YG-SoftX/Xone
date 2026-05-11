<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Exception;

class FeaturesController extends Controller
{
    /**
     * Third-Party Apps Management
     */
    public function thirdPartyApps(Request $request)
    {
        try {
            $user = $request->user();
            
            // Get all OAuth tokens / connected apps
            $connectedApps = $user->tokens->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'scopes' => $token->abilities ?? [],
                    'last_used' => $token->last_used_at?->format('d M Y, H:i'),
                    'created_at' => $token->created_at?->format('d M Y'),
                    'expires_at' => $token->expires_at?->format('d M Y'),
                ];
            });

            return Inertia::render('ThirdPartyApps', [
                'connectedApps' => $connectedApps,
            ]);
        } catch (Exception $e) {
            Log::error('Third party apps error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load third-party apps.');
        }
    }

    /**
     * Revoke a third-party app access
     */
    public function revokeThirdPartyApp(Request $request, $tokenId)
    {
        try {
            $user = $request->user();
            $token = $user->tokens()->where('id', $tokenId)->firstOrFail();
            $tokenName = $token->name;
            $token->delete();

            ActivityLog::record(
                $user->id,
                'Revoked access for: ' . $tokenName,
                'Security',
                '🔒',
                $request->ip()
            );

            return redirect()->back()->with('success', 'App access revoked.');
        } catch (Exception $e) {
            Log::error('Revoke app error: ' . $e->getMessage());
            return back()->with('error', 'Failed to revoke app access.');
        }
    }

    /**
     * Device Management - Show all active sessions/devices
     */
    public function devices(Request $request)
    {
        try {
            $user = $request->user();

            $devices = DB::table('sessions')
                ->where('user_id', $user->id)
                ->get()
                ->map(function ($session) {
                    $ua = $session->user_agent ?? '';
                    $os = 'Unknown OS';
                    $browser = 'Unknown Browser';
                    $device = 'Unknown Device';

                    // Parse OS
                    if (str_contains($ua, 'Windows')) $os = 'Windows';
                    elseif (str_contains($ua, 'Macintosh')) $os = 'macOS';
                    elseif (str_contains($ua, 'Android')) $os = 'Android';
                    elseif (str_contains($ua, 'iPhone')) $os = 'iOS';
                    elseif (str_contains($ua, 'iPad')) $os = 'iPadOS';
                    elseif (str_contains($ua, 'Linux')) $os = 'Linux';

                    // Parse browser
                    if (str_contains($ua, 'Chrome')) $browser = 'Chrome';
                    elseif (str_contains($ua, 'Firefox')) $browser = 'Firefox';
                    elseif (str_contains($ua, 'Safari')) $browser = 'Safari';
                    elseif (str_contains($ua, 'Edge')) $browser = 'Edge';

                    // Parse device type
                    if (str_contains($ua, 'Mobile')) $device = 'Mobile';
                    elseif (str_contains($ua, 'Tablet')) $device = 'Tablet';
                    else $device = 'Desktop';

                    return [
                        'id' => $session->id,
                        'is_current' => $session->id === session()->getId(),
                        'ip_address' => $session->ip_address,
                        'os' => $os,
                        'browser' => $browser,
                        'device' => $device,
                        'last_active' => date('Y-m-d H:i:s', $session->last_activity),
                    ];
                });

            return Inertia::render('DeviceManagement', [
                'devices' => $devices,
            ]);
        } catch (Exception $e) {
            Log::error('Devices error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load devices.');
        }
    }

    /**
     * Terminate a device session
     */
    public function terminateDevice(Request $request, $sessionId)
    {
        try {
            DB::table('sessions')
                ->where('user_id', $request->user()->id)
                ->where('id', $sessionId)
                ->delete();

            ActivityLog::record(
                $request->user()->id,
                'Terminated device session',
                'Device',
                '📱',
                $request->ip()
            );

            return redirect()->back()->with('success', 'Device session terminated.');
        } catch (Exception $e) {
            Log::error('Terminate device error: ' . $e->getMessage());
            return back()->with('error', 'Failed to terminate device.');
        }
    }

    /**
     * People & Sharing - Manage shared contacts and sharing settings
     */
    public function peopleSharing(Request $request)
    {
        try {
            $user = $request->user();

            // Get user's profile
            $profile = $user->profile;
            
            // Get activity logs related to sharing
            $sharingActivity = ActivityLog::where('user_id', $user->id)
                ->where('type', 'Sharing')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(fn($a) => [
                    'id' => $a->id,
                    'action' => $a->action,
                    'time' => $a->created_at?->diffForHumans(),
                ]);

            // Sharing settings (prototype - would be stored in DB in production)
            $sharingSettings = [
                'share_profile' => $profile?->share_profile ?? false,
                'share_activity' => $profile?->share_activity ?? false,
                'allow_sharing_requests' => $profile?->allow_sharing_requests ?? true,
            ];

            return Inertia::render('PeopleSharing', [
                'sharingActivity' => $sharingActivity,
                'sharingSettings' => $sharingSettings,
                'profile' => $profile,
            ]);
        } catch (Exception $e) {
            Log::error('People sharing error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load people & sharing.');
        }
    }

    /**
     * Update sharing settings
     */
    public function updateSharingSettings(Request $request)
    {
        try {
            $request->validate([
                'share_profile' => 'boolean',
                'share_activity' => 'boolean',
                'allow_sharing_requests' => 'boolean',
            ]);

            $profile = $request->user()->profile ?? $request->user()->profile()->create([]);
            
            $profile->update([
                'share_profile' => $request->boolean('share_profile', false),
                'share_activity' => $request->boolean('share_activity', false),
                'allow_sharing_requests' => $request->boolean('allow_sharing_requests', true),
            ]);

            ActivityLog::record(
                $request->user()->id,
                'Updated sharing settings',
                'Sharing',
                '👥',
                $request->ip()
            );

            return redirect()->back()->with('success', 'Sharing settings updated.');
        } catch (Exception $e) {
            Log::error('Update sharing settings error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update sharing settings.');
        }
    }

    /**
     * YG Drive Storage Management
     */
    public function ygDriveStorage(Request $request)
    {
        try {
            $user = $request->user();

            // Storage usage stats (prototype values - would integrate with actual storage in production)
            $storageStats = [
                'used' => 2400000000, // 2.4 GB in bytes
                'total' => 15000000000, // 15 GB in bytes
                'used_formatted' => '2.4 GB',
                'total_formatted' => '15 GB',
                'percentage' => 16,
                'breakdown' => [
                    ['name' => 'YG Drive Files', 'used' => 1500000000, 'formatted' => '1.5 GB'],
                    ['name' => 'YG Mail Attachments', 'used' => 600000000, 'formatted' => '600 MB'],
                    ['name' => 'YG DocX Documents', 'used' => 200000000, 'formatted' => '200 MB'],
                    ['name' => 'Backups', 'used' => 100000000, 'formatted' => '100 MB'],
                ],
            ];

            // Recent file activity
            $fileActivity = ActivityLog::where('user_id', $user->id)
                ->where('type', 'Drive')
                ->orderBy('created_at', 'desc')
                ->limit(15)
                ->get()
                ->map(fn($a) => [
                    'id' => $a->id,
                    'action' => $a->action,
                    'time' => $a->created_at?->diffForHumans(),
                    'icon' => $a->icon,
                ]);

            return Inertia::render('YGDriveStorage', [
                'storageStats' => $storageStats,
                'fileActivity' => $fileActivity,
            ]);
        } catch (Exception $e) {
            Log::error('YG Drive storage error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load storage information.');
        }
    }

    /**
     * Data & Privacy Management
     */
    public function dataPrivacy(Request $request)
    {
        try {
            $user = $request->user();

            // Data collection settings
            $dataSettings = [
                'web_app_activity' => $user->web_app_activity ?? true,
                'timeline_history' => $user->timeline_history ?? true,
                'device_access_logs' => $user->device_access_logs ?? true,
                'yg_pay_ledger' => $user->yg_pay_ledger ?? true,
                'personalized_ads' => $user->personalized_ads ?? false,
            ];

            // Activity summary
            $activitySummary = [
                'total_logins' => ActivityLog::where('user_id', $user->id)->where('type', 'Login')->count(),
                'total_searches' => ActivityLog::where('user_id', $user->id)->where('type', 'Search')->count(),
                'total_actions' => ActivityLog::where('user_id', $user->id)->count(),
                'account_age' => $user->created_at?->diffForHumans(),
            ];

            // Download data options
            $dataExportOptions = [
                ['name' => 'Account Data', 'description' => 'Profile, settings, and preferences'],
                ['name' => 'Activity History', 'description' => 'All activity logs and interactions'],
                ['name' => 'YG Drive Files', 'description' => 'All stored files and documents'],
                ['name' => 'YG Mail Data', 'description' => 'Emails, contacts, and attachments'],
                ['name' => 'Complete Data Export', 'description' => 'All data associated with your account'],
            ];

            return Inertia::render('DataPrivacy', [
                'dataSettings' => $dataSettings,
                'activitySummary' => $activitySummary,
                'dataExportOptions' => $dataExportOptions,
            ]);
        } catch (Exception $e) {
            Log::error('Data privacy error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load data & privacy settings.');
        }
    }

    /**
     * Update data & privacy settings
     */
    public function updateDataPrivacySettings(Request $request)
    {
        try {
            $request->validate([
                'web_app_activity' => 'boolean',
                'timeline_history' => 'boolean',
                'device_access_logs' => 'boolean',
                'yg_pay_ledger' => 'boolean',
                'personalized_ads' => 'boolean',
            ]);

            $user = $request->user();
            $user->update([
                'web_app_activity' => $request->boolean('web_app_activity', true),
                'timeline_history' => $request->boolean('timeline_history', true),
                'device_access_logs' => $request->boolean('device_access_logs', true),
                'yg_pay_ledger' => $request->boolean('yg_pay_ledger', true),
                'personalized_ads' => $request->boolean('personalized_ads', false),
            ]);

            ActivityLog::record(
                $user->id,
                'Updated privacy settings',
                'Privacy',
                '🔒',
                $request->ip()
            );

            return redirect()->back()->with('success', 'Privacy settings updated.');
        } catch (Exception $e) {
            Log::error('Update privacy settings error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update privacy settings.');
        }
    }
}
