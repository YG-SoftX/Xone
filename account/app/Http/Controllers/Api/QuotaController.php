<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeveloperProject;
use App\Models\ProjectQuota;
use App\Models\ApiProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuotaController extends Controller
{
    /**
     * Get quota status for all products in a project
     */
    public function index(Request $request, $projectId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where(function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id);
              });
        })->findOrFail($projectId);

        $quotas = ProjectQuota::where('project_id', $project->id)
            ->with('product')
            ->get()
            ->map(function(ProjectQuota $quota) {
                return [
                    'product' => [
                        'id' => $quota->product->id,
                        'name' => $quota->product->name,
                        'display_name' => $quota->product->display_name,
                        'icon' => $quota->product->icon,
                    ],
                    'daily' => [
                        'limit' => $quota->daily_limit,
                        'used' => $quota->daily_used,
                        'remaining' => $quota->getRemainingDaily(),
                        'percentage' => $quota->getDailyUsagePercentage(),
                        'reset_date' => $quota->daily_reset_date ? \Carbon\Carbon::parse($quota->daily_reset_date)->format('Y-m-d') : null,
                        'is_exceeded' => $quota->isDailyQuotaExceeded(),
                    ],
                    'monthly' => [
                        'limit' => $quota->monthly_limit,
                        'used' => $quota->monthly_used,
                        'remaining' => $quota->getRemainingMonthly(),
                        'percentage' => $quota->monthly_limit > 0 
                            ? round(($quota->monthly_used / $quota->monthly_limit) * 100, 2) 
                            : 0,
                        'reset_date' => $quota->monthly_reset_date ? \Carbon\Carbon::parse($quota->monthly_reset_date)->format('Y-m-d') : null,
                        'is_exceeded' => $quota->isMonthlyQuotaExceeded(),
                    ],
                    'rate_limit' => [
                        'per_minute' => $quota->rate_limit_per_minute,
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'project_id' => $project->project_id,
            ],
            'quotas' => $quotas,
        ]);
    }

    /**
     * Update quota limits (requires billing upgrade or admin)
     */
    public function update(Request $request, $projectId, $productId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where('owner_id', $user->id)->findOrFail($projectId);

        // Only owner can update quotas
        if ($project->owner_id !== $user->id) {
            return response()->json(['error' => 'Only project owner can update quotas'], 403);
        }

        $quota = ProjectQuota::where('project_id', $project->id)
            ->where('product_id', $productId)
            ->firstOrFail();

        $validated = $request->validate([
            'daily_limit' => 'sometimes|integer|min:100|max:1000000',
            'monthly_limit' => 'sometimes|integer|min:3000|max:30000000',
            'rate_limit_per_minute' => 'sometimes|integer|min:1|max:1000',
        ]);

        // Check if user has permission to increase quotas (paid plan required)
        $planSubscription = \App\Models\ProjectPlanSubscription::where('project_id', $project->id)
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->first();

        if ($planSubscription) {
            // User is on a paid plan - allow increases up to plan limits
            $plan = $planSubscription->plan;
            
            if (isset($validated['daily_limit']) && $validated['daily_limit'] > $plan->daily_quota) {
                return response()->json([
                    'error' => "Daily limit exceeds your plan maximum of {$plan->daily_quota}. Upgrade to increase.",
                    'current_plan' => $plan->name,
                    'max_daily_limit' => $plan->daily_quota,
                ], 403);
            }

            if (isset($validated['monthly_limit']) && $validated['monthly_limit'] > $plan->monthly_quota) {
                return response()->json([
                    'error' => "Monthly limit exceeds your plan maximum of {$plan->monthly_quota}. Upgrade to increase.",
                    'current_plan' => $plan->name,
                    'max_monthly_limit' => $plan->monthly_quota,
                ], 403);
            }
        } else {
            // Free tier - limited increases allowed
            $freeLimits = [
                'daily_limit' => 10000,
                'monthly_limit' => 300000,
                'rate_limit_per_minute' => 60,
            ];

            foreach ($validated as $field => $value) {
                if ($value > $freeLimits[$field]) {
                    return response()->json([
                        'error' => "Free tier limit exceeded. Upgrade to Pro for higher limits.",
                        'field' => $field,
                        'requested' => $value,
                        'free_tier_limit' => $freeLimits[$field],
                    ], 403);
                }
            }
        }

        $quota->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Quota updated successfully',
            'quota' => $quota->fresh()->load('product'),
        ]);
    }

    /**
     * Get quota usage history
     */
    public function history(Request $request, $projectId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where(function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id);
              });
        })->findOrFail($projectId);

        $days = $request->input('days', 30);
        $startDate = now()->subDays($days);

        // Daily usage aggregation
        $dailyUsage = \App\Models\ApiUsageLog::where('project_id', $project->id)
            ->whereBetween('created_at', [$startDate, now()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total_requests')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill missing dates
        $completeHistory = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $usage = $dailyUsage->firstWhere('date', $date);
            $completeHistory[] = [
                'date' => $date,
                'total_requests' => $usage ? $usage->total_requests : 0,
            ];
        }

        return response()->json([
            'success' => true,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'period' => [
                'days' => $days,
                'start_date' => $startDate->toDateString(),
                'end_date' => now()->toDateString(),
            ],
            'history' => $completeHistory,
        ]);
    }

    /**
     * Get quota alerts and warnings
     */
    public function alerts($projectId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where(function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id);
              });
        })->findOrFail($projectId);

        $quotas = ProjectQuota::where('project_id', $project->id)
            ->with('product')
            ->get();

        $alerts = [];

        foreach ($quotas as $quota) {
            // Check daily quota warnings
            $dailyPercentage = $quota->getDailyUsagePercentage();
            if ($dailyPercentage >= 90) {
                $alerts[] = [
                    'type' => 'critical',
                    'severity' => 'high',
                    'message' => "Daily quota for {$quota->product->display_name} is {$dailyPercentage}% used",
                    'product' => $quota->product->name,
                    'daily_remaining' => $quota->getRemainingDaily(),
                    'action' => 'Upgrade plan or wait for reset',
                ];
            } elseif ($dailyPercentage >= 75) {
                $alerts[] = [
                    'type' => 'warning',
                    'severity' => 'medium',
                    'message' => "Daily quota for {$quota->product->display_name} is {$dailyPercentage}% used",
                    'product' => $quota->product->name,
                    'daily_remaining' => $quota->getRemainingDaily(),
                    'action' => 'Monitor usage closely',
                ];
            }

            // Check monthly quota warnings
            $monthlyPercentage = $quota->monthly_limit > 0 
                ? round(($quota->monthly_used / $quota->monthly_limit) * 100, 2) 
                : 0;

            if ($monthlyPercentage >= 90) {
                $alerts[] = [
                    'type' => 'critical',
                    'severity' => 'high',
                    'message' => "Monthly quota for {$quota->product->display_name} is {$monthlyPercentage}% used",
                    'product' => $quota->product->name,
                    'monthly_remaining' => $quota->getRemainingMonthly(),
                    'action' => 'Upgrade plan immediately',
                ];
            } elseif ($monthlyPercentage >= 75) {
                $alerts[] = [
                    'type' => 'warning',
                    'severity' => 'medium',
                    'message' => "Monthly quota for {$quota->product->display_name} is {$monthlyPercentage}% used",
                    'product' => $quota->product->name,
                    'monthly_remaining' => $quota->getRemainingMonthly(),
                    'action' => 'Consider upgrading plan',
                ];
            }
        }

        return response()->json([
            'success' => true,
            'alerts' => $alerts,
            'alert_count' => count($alerts),
        ]);
    }
}
