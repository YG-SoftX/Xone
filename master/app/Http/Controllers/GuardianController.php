<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Drive\File;
use App\Models\Society\Post;
use App\Models\Society\RLFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuardianController extends Controller
{
    /**
     * Display the Master Sovereign Command Center
     */
    public function index()
    {
        // 1. Financial Intelligence (Master View)
        $financialStats = $this->getFinancialStats();

        // 2. Neural Data Sovereignty (Master Audit)
        $sovereigntyStats = [
            'total_private_docs' => File::where('is_ai_training', true)->count(),
            'total_storage_bytes' => File::where('is_ai_training', true)->sum('size_bytes'),
            'sovereign_users' => File::where('is_ai_training', true)->distinct('user_id')->count(),
        ];

        // 4. Safety Sentinel Activity (Master Feed)
        $safetyActivity = ActivityLog::where('category', 'Security')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $sentinelStats = [
            'total_blocks' => ActivityLog::where('category', 'Security')->count(),
            'positive_rl_signals' => RLFeedback::where('signal', '>', 0)->count(),
            'negative_rl_signals' => RLFeedback::where('signal', '<', 0)->count(),
        ];

        // 5. YugaLLM Training Queue (New)
        $trainingStats = DB::connection('mysql')->table('ai_training_queue')
            ->selectRaw('COUNT(*) as total, MAX(created_at) as last_activity')
            ->first();

        return view('guardian.dashboard', compact(
            'financialStats',
            'sovereigntyStats',
            'safetyActivity',
            'sentinelStats',
            'trainingStats'
        ));

    }

    private function getFinancialStats()
    {
        return \Illuminate\Support\Facades\Cache::remember('guardian_financial_stats', 300, function () {
            $mrr = DB::table('pay_subscriptions')->where('status', 'active')->sum('amount');
            $totalVolume = DB::table('pay_transactions')->where('status', 'completed')->sum('amount');

            $active = DB::table('pay_subscriptions')->where('status', 'active')->count();
            $cancelled = DB::table('pay_subscriptions')
                ->whereIn('status', ['cancelled', 'expired'])
                ->where('updated_at', '>=', now()->subDays(30))
                ->count();

            $churnRate = ($active + $cancelled) > 0 ? ($cancelled / ($active + $cancelled)) * 100 : 0;

            return [
                'mrr' => $mrr,
                'total_volume' => $totalVolume,
                'churn_rate' => round($churnRate, 1),
                'currency' => 'USD'
            ];
        });
    }

}
