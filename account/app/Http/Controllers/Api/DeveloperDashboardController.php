<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeveloperProject;
use App\Models\ApiProduct;
use App\Models\ApiCredential;
use App\Models\ProductSubscription;
use App\Models\ProjectQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DeveloperDashboardController extends Controller
{
    /**
     * Get dashboard overview statistics
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Project statistics
        $projectsCount = DeveloperProject::where('owner_id', $user->id)->count();
        $activeProjects = DeveloperProject::where('owner_id', $user->id)
            ->where('is_active', true)
            ->count();

        // Credential statistics
        $credentialsCount = ApiCredential::whereHas('project', function($query) use ($user) {
            $query->where('owner_id', $user->id);
        })->count();

        $activeCredentials = ApiCredential::whereHas('project', function($query) use ($user) {
            $query->where('owner_id', $user->id);
        })
        ->where('is_active', true)
        ->count();

        // Today's API usage
        $todayUsage = \App\Models\ApiUsageLog::whereHas('project', function($query) use ($user) {
            $query->where('owner_id', $user->id);
        })
        ->whereDate('created_at', today())
        ->count();

        // Billing balance
        $billingAccount = \App\Models\BillingAccount::where('user_id', $user->id)->first();
        $balance = $billingAccount ? $billingAccount->current_balance : 0;

        // Recent projects
        $recentProjects = DeveloperProject::where('owner_id', $user->id)
            ->with(['subscriptions.product'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'project_id' => $project->project_id,
                    'environment' => $project->environment,
                    'is_active' => $project->is_active,
                    'created_at' => $project->created_at->toIso8601String(),
                    'subscriptions_count' => $project->subscriptions->count(),
                ];
            });

        // Active products count
        $activeProducts = ApiProduct::where('is_active', true)->count();

        return response()->json([
            'stats' => [
                'projects_count' => $projectsCount,
                'active_projects' => $activeProjects,
                'active_credentials' => $activeCredentials,
                'requests_today' => $todayUsage,
                'billing_balance' => number_format($balance, 2),
                'available_products' => $activeProducts,
            ],
            'recent_projects' => $recentProjects,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Quick actions - create new project
     */
    public function createProject(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'environment' => 'required|in:development,staging,production',
            'website_url' => 'nullable|url',
        ]);

        $user = Auth::user();

        // Generate unique project ID
        $projectId = 'ygxone-' . Str::slug($validated['name']) . '-' . Str::random(4);

        $project = DeveloperProject::create([
            'owner_id' => $user->id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(6),
            'project_id' => $projectId,
            'description' => $validated['description'] ?? null,
            'website_url' => $validated['website_url'] ?? null,
            'environment' => $validated['environment'],
            'is_active' => true,
        ]);

        // Create default quotas for enabled products
        $enabledProducts = ApiProduct::where('is_active', true)->get();
        foreach ($enabledProducts as $product) {
            ProjectQuota::create([
                'project_id' => $project->id,
                'product_id' => $product->id,
                'daily_limit' => $product->default_daily_quota,
                'monthly_limit' => $product->default_monthly_quota,
                'rate_limit_per_minute' => 60,
            ]);
        }

        return response()->json([
            'success' => true,
            'project' => $project->only(['id', 'name', 'project_id', 'environment']),
        ], 201);
    }
}
