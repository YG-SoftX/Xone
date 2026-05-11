<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiProduct;
use App\Models\ApiUsageLog;
use App\Models\DeveloperProject;
use App\Models\ProductSubscription;
use App\Models\ProjectQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /**
     * List all projects for authenticated user (owner or team member)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = DeveloperProject::where(function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id);
              });
        });

        // Apply filters
        if ($request->filled('environment')) {
            $query->where('environment', $request->environment);
        }
        
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('project_id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $projects = $query->with(['owner:id,name,email', 'members.user:id,name,email'])
            ->withCount(['credentials as active_credentials_count' => function($q) {
                $q->where('is_active', true);
            }])
            ->withCount('subscriptions')
            ->orderBy('last_activity_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $projects->map(function($project) use ($user) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'project_id' => $project->project_id,
                    'slug' => $project->slug,
                    'description' => $project->description,
                    'website_url' => $project->website_url,
                    'environment' => $project->environment,
                    'is_active' => $project->is_active,
                    'labels' => $project->labels,
                    'owner' => [
                        'id' => $project->owner->id,
                        'name' => $project->owner->name,
                        'email' => $project->owner->email,
                    ],
                    'my_role' => $project->getUserRole($user->id),
                    'active_credentials_count' => $project->active_credentials_count,
                    'subscriptions_count' => $project->subscriptions_count,
                    'created_at' => $project->created_at->toIso8601String(),
                    'updated_at' => $project->updated_at->toIso8601String(),
                    'last_activity_at' => $project->last_activity_at?->toIso8601String(),
                ];
            }),
            'pagination' => [
                'current_page' => $projects->currentPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
                'last_page' => $projects->lastPage(),
            ],
        ]);
    }

    /**
     * Show single project details with full relationships
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where(function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id);
              });
        })->findOrFail($id);

        // Update last activity timestamp
        $project->update(['last_activity_at' => now()]);

        $project->load([
            'owner:id,name,email',
            'members.user:id,name,email',
            'subscriptions.product:id,name,display_name,icon',
            'quotas.product:id,name',
            'credentials' => function($q) {
                $q->select('id', 'project_id', 'type', 'name', 'identifier', 
                          'is_active', 'last_used_at', 'total_requests', 'expires_at');
            }
        ]);

        // Calculate usage statistics
        $todayUsage = ApiUsageLog::where('project_id', $project->id)
            ->whereDate('created_at', today())
            ->count();

        $monthUsage = ApiUsageLog::where('project_id', $project->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return response()->json([
            'success' => true,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'project_id' => $project->project_id,
                'slug' => $project->slug,
                'description' => $project->description,
                'website_url' => $project->website_url,
                'environment' => $project->environment,
                'is_active' => $project->is_active,
                'labels' => $project->labels,
                'owner' => [
                    'id' => $project->owner->id,
                    'name' => $project->owner->name,
                    'email' => $project->owner->email,
                ],
                'members' => $project->members->map(function($member) {
                    return [
                        'id' => $member->id,
                        'user_id' => $member->user_id,
                        'name' => $member->user->name,
                        'email' => $member->user->email,
                        'role' => $member->role,
                    ];
                }),
                'subscriptions' => $project->subscriptions->map(function($sub) {
                    return [
                        'product_id' => $sub->product->id,
                        'product_name' => $sub->product->name,
                        'display_name' => $sub->product->display_name,
                        'icon' => $sub->product->icon,
                        'is_active' => $sub->is_active,
                        'activated_at' => $sub->activated_at?->toIso8601String(),
                    ];
                }),
                'quotas' => $project->quotas->map(function($quota) {
                    return [
                        'product_name' => $quota->product->name,
                        'daily_limit' => $quota->daily_limit,
                        'daily_used' => $quota->daily_used,
                        'daily_remaining' => $quota->getRemainingDaily(),
                        'daily_percentage' => $quota->getDailyUsagePercentage(),
                        'monthly_limit' => $quota->monthly_limit,
                        'monthly_used' => $quota->monthly_used,
                        'monthly_remaining' => $quota->getRemainingMonthly(),
                    ];
                }),
                'credentials' => $project->credentials,
                'usage_stats' => [
                    'requests_today' => $todayUsage,
                    'requests_this_month' => $monthUsage,
                ],
                'created_at' => $project->created_at->toIso8601String(),
                'updated_at' => $project->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create new developer project
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'website_url' => 'nullable|url|max:255',
            'environment' => 'required|in:development,staging,production',
            'labels' => 'nullable|array|max:10',
            'labels.*' => 'string|max:50',
        ]);

        $user = Auth::user();

        // Check project limit (free tier: max 5 projects)
        $projectCount = DeveloperProject::where('owner_id', $user->id)->count();
        if ($projectCount >= 5) {
            return response()->json([
                'error' => 'Project limit reached. Upgrade to Pro for unlimited projects.',
                'current_count' => $projectCount,
                'limit' => 5,
            ], 403);
        }

        $project = DeveloperProject::create([
            'owner_id' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'website_url' => $validated['website_url'] ?? null,
            'environment' => $validated['environment'],
            'labels' => $validated['labels'] ?? [],
            'is_active' => true,
        ]);

        // Auto-subscribe to free tier products and create default quotas
        $freeProducts = ApiProduct::where('is_active', true)->get();
        foreach ($freeProducts as $product) {
            ProductSubscription::create([
                'project_id' => $project->id,
                'product_id' => $product->id,
                'is_active' => true,
                'activated_at' => now(),
            ]);

            ProjectQuota::create([
                'project_id' => $project->id,
                'product_id' => $product->id,
                'daily_limit' => $product->default_daily_quota,
                'monthly_limit' => $product->default_monthly_quota,
                'rate_limit_per_minute' => 60,
                'daily_reset_date' => now()->toDateString(),
                'monthly_reset_date' => now()->toDateString(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully',
            'project' => $project->fresh()->load('subscriptions.product'),
        ], 201);
    }

    /**
     * Update project details
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $project = DeveloperProject::where('owner_id', $user->id)->findOrFail($id);

        // Only owner can update
        if ($project->owner_id !== $user->id) {
            return response()->json(['error' => 'Only project owner can update'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'website_url' => 'nullable|url|max:255',
            'environment' => 'sometimes|required|in:development,staging,production',
            'labels' => 'nullable|array|max:10',
            'labels.*' => 'string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        $project->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully',
            'project' => $project->fresh(),
        ]);
    }

    /**
     * Delete project (owner only)
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $project = DeveloperProject::where('owner_id', $user->id)->findOrFail($id);

        // Only owner can delete
        if ($project->owner_id !== $user->id) {
            return response()->json(['error' => 'Only project owner can delete'], 403);
        }

        // Soft delete - cascade will handle related records
        $projectName = $project->name;
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => "Project '{$projectName}' deleted successfully",
        ]);
    }

    /**
     * Activate/Deactivate project
     */
    public function activate(Request $request, $id)
    {
        $user = Auth::user();
        $project = DeveloperProject::where('owner_id', $user->id)->findOrFail($id);

        // Only owner can toggle activation
        if ($project->owner_id !== $user->id) {
            return response()->json(['error' => 'Only project owner can activate/deactivate'], 403);
        }

        $newStatus = !$project->is_active;
        $project->update(['is_active' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => $newStatus ? 'Project activated' : 'Project deactivated',
            'is_active' => $project->is_active,
        ]);
    }
}
