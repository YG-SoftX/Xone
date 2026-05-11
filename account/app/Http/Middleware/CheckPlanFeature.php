<?php

namespace App\Http\Middleware;

use App\Services\PlanService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanFeature
{
    public function __construct(private readonly PlanService $plans) {}

    /**
     * Gate a route behind a plan feature.
     *
     * Usage in routes:
     *   ->middleware('plan.feature:admin_panel')
     *   ->middleware('plan.feature:audit_logs')
     *
     * Web requests get an upgrade redirect; API requests get a 403 JSON.
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (!$user || !$this->plans->can($user, $feature)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'This feature requires a higher plan.',
                    'feature' => $feature,
                    'upgrade' => url('/settings/billing/upgrade'),
                ], 403);
            }

            return redirect()
                ->route('billing.upgrade')
                ->with('upgrade_reason', "The \"{$feature}\" feature is not included in your current plan.");
        }

        return $next($request);
    }
}
