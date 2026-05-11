<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\BillingInvoice;
use App\Models\AiUsageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Show dashboard with analytics
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get user's projects
        $projects = $user->projects()->withCount(['apiKeys', 'oauthApplications'])->get();

        // Calculate total statistics
        $totalProjects = $projects->count();
        $totalApiKeys = $projects->sum('api_keys_count');
        $totalSpend = BillingInvoice::whereIn('project_id', $projects->pluck('id'))
            ->where('status', 'paid')
            ->sum('amount');

        // API Usage Chart Data (Last 30 days)
        $apiUsageData = $this->getApiUsageData($projects);

        // Revenue Chart Data (Last 12 months)
        $revenueData = $this->getRevenueData($projects);

        // Recent activity
        $recentActivity = $this->getRecentActivity($projects);

        return view('console.dashboard', compact(
            'projects',
            'totalProjects',
            'totalApiKeys',
            'totalSpend',
            'apiUsageData',
            'revenueData',
            'recentActivity'
        ));
    }

    /**
     * Get API usage data for chart
     */
    protected function getApiUsageData($projects)
    {
        $projectIds = $projects->pluck('id');

        $data = AiUsageLog::whereIn('project_id', $projectIds)
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill in missing dates
        $labels = [];
        $counts = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M d');
            
            $found = $data->firstWhere('date', $date);
            $counts[] = $found ? $found->count : 0;
        }

        return [
            'labels' => $labels,
            'data' => $counts,
        ];
    }

    /**
     * Get revenue data for chart
     */
    protected function getRevenueData($projects)
    {
        $projectIds = $projects->pluck('id');

        $data = BillingInvoice::whereIn('project_id', $projectIds)
            ->where('status', 'paid')
            ->whereDate('created_at', '>=', now()->subMonths(12))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Fill in missing months
        $labels = [];
        $totals = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $labels[] = $date->format('M Y');
            
            $found = $data->firstWhere('month', $monthKey);
            $totals[] = $found ? (float) $found->total : 0;
        }

        return [
            'labels' => $labels,
            'data' => $totals,
        ];
    }

    /**
     * Get recent activity
     */
    protected function getRecentActivity($projects)
    {
        $projectIds = $projects->pluck('id');

        $activities = collect();

        // Recent API keys created
        $recentKeys = \App\Models\ApiKey::whereIn('project_id', $projectIds)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($key) => [
                'type' => 'api_key',
                'description' => 'API key "' . $key->name . '" created',
                'created_at' => $key->created_at,
            ]);

        // Recent invoices
        $recentInvoices = BillingInvoice::whereIn('project_id', $projectIds)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($invoice) => [
                'type' => 'invoice',
                'description' => 'Invoice #' . $invoice->invoice_number . ' generated ($' . number_format($invoice->amount, 2) . ')',
                'created_at' => $invoice->created_at,
            ]);

        $activities = $recentKeys->merge($recentInvoices)
            ->sortByDesc('created_at')
            ->take(10)
            ->values();

        return $activities;
    }
}
