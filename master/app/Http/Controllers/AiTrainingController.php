<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiTrainingController extends Controller
{
    /**
     * Display training queue statistics and recent queries
     */
    public function index()
    {
        // Fetch from shared database
        $stats = DB::connection('mysql')->table('ai_training_queue')
            ->selectRaw('context, COUNT(*) as count, MAX(created_at) as last_seen')
            ->groupBy('context')
            ->get();

        $recentQueries = DB::connection('mysql')->table('ai_training_queue')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $topQueries = DB::connection('mysql')->table('ai_training_queue')
            ->selectRaw('query, COUNT(*) as count')
            ->groupBy('query')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return view('admin.ai-training', [
            'stats' => $stats,
            'recentQueries' => $recentQueries,
            'topQueries' => $topQueries,
        ]);
    }

    /**
     * Clear the training queue (after model has been retrained)
     */
    public function clear()
    {
        DB::connection('mysql')->table('ai_training_queue')->truncate();
        return redirect()->back()->with('success', 'Training queue cleared.');
    }
}
