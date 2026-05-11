<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ServiceManagementController extends Controller
{
    /**
     * Display service management dashboard
     */
    public function index()
    {
        $services = DB::table('service_configurations')
            ->orderBy('service_name')
            ->get();

        // Get today's usage stats
        $today = Carbon::today()->toDateString();
        $usageStats = DB::table('service_usage_stats')
            ->where('date', $today)
            ->get()
            ->keyBy('service_key');

        return view('admin.services.index', compact('services', 'usageStats'));
    }

    /**
     * Enable a service
     */
    public function enable($serviceKey)
    {
        $this->authorizeAdmin();

        DB::table('service_configurations')
            ->where('service_key', $serviceKey)
            ->update([
                'is_enabled' => true,
                'is_maintenance_mode' => false,
                'disabled_at' => null,
                'enabled_at' => now(),
                'last_modified_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        // Log the action
        $this->logServiceAction($serviceKey, 'enabled', 'Service enabled by admin');

        return redirect()->back()->with('success', ucfirst($serviceKey) . ' service has been enabled.');
    }

    /**
     * Disable a service
     */
    public function disable(Request $request, $serviceKey)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::table('service_configurations')
            ->where('service_key', $serviceKey)
            ->update([
                'is_enabled' => false,
                'disabled_at' => now(),
                'last_modified_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        // Log the action
        $this->logServiceAction($serviceKey, 'disabled', $validated['reason'] ?? 'Service disabled by admin');

        return redirect()->back()->with('success', ucfirst($serviceKey) . ' service has been disabled.');
    }

    /**
     * Enable maintenance mode
     */
    public function enableMaintenance(Request $request, $serviceKey)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'maintenance_message' => 'nullable|string|max:1000',
        ]);

        DB::table('service_configurations')
            ->where('service_key', $serviceKey)
            ->update([
                'is_maintenance_mode' => true,
                'maintenance_message' => $validated['maintenance_message'] ?? 'This service is temporarily under maintenance. Please try again later.',
                'last_modified_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        // Log the action
        $this->logServiceAction($serviceKey, 'maintenance_on', $validated['maintenance_message'] ?? 'Maintenance mode enabled');

        return redirect()->back()->with('success', ucfirst($serviceKey) . ' is now in maintenance mode.');
    }

    /**
     * Disable maintenance mode
     */
    public function disableMaintenance($serviceKey)
    {
        $this->authorizeAdmin();

        DB::table('service_configurations')
            ->where('service_key', $serviceKey)
            ->update([
                'is_maintenance_mode' => false,
                'maintenance_message' => null,
                'last_modified_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        // Log the action
        $this->logServiceAction($serviceKey, 'maintenance_off', 'Maintenance mode disabled');

        return redirect()->back()->with('success', ucfirst($serviceKey) . ' maintenance mode has been disabled.');
    }

    /**
     * Update service settings
     */
    public function updateSettings(Request $request, $serviceKey)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'settings' => 'nullable|array',
        ]);

        DB::table('service_configurations')
            ->where('service_key', $serviceKey)
            ->update([
                'settings' => json_encode($validated['settings'] ?? []),
                'last_modified_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', ucfirst($serviceKey) . ' settings updated successfully.');
    }

    /**
     * View service access logs
     */
    public function logs($serviceKey)
    {
        $this->authorizeAdmin();

        $logs = DB::table('service_access_logs')
            ->where('service_key', $serviceKey)
            ->join('users', 'service_access_logs.admin_user_id', '=', 'users.id')
            ->select(
                'service_access_logs.*',
                'users.name as admin_name',
                'users.email as admin_email'
            )
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $service = DB::table('service_configurations')
            ->where('service_key', $serviceKey)
            ->first();

        return view('admin.services.logs', compact('logs', 'service'));
    }

    /**
     * View service analytics
     */
    public function analytics($serviceKey, Request $request)
    {
        $this->authorizeAdmin();

        $period = $request->input('period', '7'); // days
        $startDate = Carbon::now()->subDays($period)->toDateString();

        $stats = DB::table('service_usage_stats')
            ->where('service_key', $serviceKey)
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->get();

        $service = DB::table('service_configurations')
            ->where('service_key', $serviceKey)
            ->first();

        // Calculate totals
        $totals = [
            'total_active_users' => $stats->sum('active_users'),
            'total_actions' => $stats->sum('total_actions'),
            'total_api_calls' => $stats->sum('api_calls'),
            'avg_response_time' => $stats->avg('avg_response_time_ms'),
            'total_errors' => $stats->sum('error_count'),
        ];

        return view('admin.services.analytics', compact('stats', 'service', 'totals', 'period'));
    }

    /**
     * Seed initial service configurations
     */
    public function seedServices()
    {
        $this->authorizeAdmin();

        $services = [
            [
                'service_key' => 'mail',
                'service_name' => 'YG Mail',
                'description' => 'Email service with custom domain support',
                'is_enabled' => true,
                'settings' => json_encode([
                    'max_attachment_size_mb' => 25,
                    'max_recipients_per_email' => 100,
                ]),
            ],
            [
                'service_key' => 'drive',
                'service_name' => 'YG Drive',
                'description' => 'Cloud storage and file sharing',
                'is_enabled' => true,
                'settings' => json_encode([
                    'max_file_size_mb' => 5120,
                    'allowed_file_types' => '*',
                ]),
            ],
            [
                'service_key' => 'docs',
                'service_name' => 'YG Docs',
                'description' => 'Document editor and collaboration',
                'is_enabled' => true,
                'settings' => json_encode([
                    'max_collaborators' => 50,
                    'auto_save_interval_seconds' => 30,
                ]),
            ],
            [
                'service_key' => 'xcel',
                'service_name' => 'YG Xcel',
                'description' => 'Spreadsheet application with formulas',
                'is_enabled' => true,
                'settings' => json_encode([
                    'max_cells_per_sheet' => 1000000,
                    'max_charts_per_sheet' => 20,
                ]),
            ],
            [
                'service_key' => 'meet',
                'service_name' => 'YG Meet',
                'description' => 'Video conferencing and meetings',
                'is_enabled' => true,
                'settings' => json_encode([
                    'max_participants' => 100,
                    'max_meeting_duration_minutes' => 480,
                ]),
            ],
            [
                'service_key' => 'forms',
                'service_name' => 'YG Forms',
                'description' => 'Form builder and survey tool',
                'is_enabled' => true,
                'settings' => json_encode([
                    'max_questions_per_form' => 500,
                    'max_responses_per_form' => 100000,
                ]),
            ],
            [
                'service_key' => 'pay',
                'service_name' => 'YG Pay',
                'description' => 'Payment processing and digital wallet',
                'is_enabled' => true,
                'settings' => json_encode([
                    'max_transaction_amount' => 100000,
                    'daily_transfer_limit' => 500000,
                ]),
            ],
            [
                'service_key' => 'ai',
                'service_name' => 'YG AI',
                'description' => 'Artificial intelligence and automation',
                'is_enabled' => true,
                'settings' => json_encode([
                    'max_queries_per_day' => 1000,
                    'enable_smart_replies' => true,
                ]),
            ],
        ];

        foreach ($services as $service) {
            DB::table('service_configurations')->updateOrInsert(
                ['service_key' => $service['service_key']],
                $service
            );
        }

        return redirect()->route('admin.services.index')
            ->with('success', 'Service configurations seeded successfully!');
    }

    /**
     * Authorize admin access
     */
    protected function authorizeAdmin()
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403, 'Unauthorized access');
    }

    /**
     * Log service action
     */
    protected function logServiceAction(string $serviceKey, string $action, ?string $reason = null)
    {
        DB::table('service_access_logs')->insert([
            'service_key' => $serviceKey,
            'user_id' => auth()->id(),
            'action' => $action,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'admin_user_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
