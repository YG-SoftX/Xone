<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Drive\File;
use App\Models\Society\Post;
use App\Models\Society\RLFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GuardianController extends Controller
{
    /**
     * Display the Sovereign Command Center
     */
    public function index()
    {
        // 1. Financial Intelligence (YG Pay)
        $financialStats = $this->getFinancialStats();

        // 2. Neural Data Sovereignty (YG Drive)
        $sovereigntyStats = [
            'total_private_docs' => File::where('is_ai_training', true)->count(),
            'total_storage_bytes' => File::where('is_ai_training', true)->sum('size_bytes'),
            'sovereign_users' => File::where('is_ai_training', true)->distinct('user_id')->count(),
        ];

        // 3. Safety Sentinel Activity
        $safetyActivity = ActivityLog::where('category', 'Security')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $sentinelStats = [
            'total_blocks' => ActivityLog::where('category', 'Security')->count(),
            'positive_rl_signals' => RLFeedback::where('signal', '>', 0)->count(),
            'negative_rl_signals' => RLFeedback::where('signal', '<', 0)->count(),
        ];

        return view('guardian.dashboard', compact(
            'financialStats', 
            'sovereigntyStats', 
            'safetyActivity', 
            'sentinelStats'
        ));
    }

    /**
     * Synthesize Financial Data from YG Pay
     */
    private function getFinancialStats()
    {
        // Pull latest MRR and Volume
        $mrr = DB::table('pay_subscriptions')->where('status', 'active')->sum('amount');
        $totalVolume = DB::table('pay_transactions')->where('status', 'completed')->sum('amount');
        $transactionCount = DB::table('pay_transactions')->count();
        
        // Calculate Churn Rate (last 30 days)
        $active = DB::table('pay_subscriptions')->where('status', 'active')->count();
        $cancelled = DB::table('pay_subscriptions')
            ->whereIn('status', ['cancelled', 'expired'])
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();
        
        $churnRate = ($active + $cancelled) > 0 ? ($cancelled / ($active + $cancelled)) * 100 : 0;

        return [
            'mrr' => $mrr,
            'total_volume' => $totalVolume,
            'transaction_count' => $transactionCount,
            'churn_rate' => round($churnRate, 1),
            'currency' => 'USD'
        ];
    }
}
