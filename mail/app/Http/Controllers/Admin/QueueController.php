<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class QueueController extends Controller
{
    public function index()
    {
        $failedCount = DB::table('failed_jobs')->count();
        $pendingCount = DB::table('jobs')->count();

        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->take(50)
            ->get();

        return view('admin.queue', compact('failedCount', 'pendingCount', 'failedJobs'));
    }

    public function retryAll()
    {
        Artisan::call('queue:retry', ['all' => true]);
        return back()->with('success', 'All failed jobs have been queued for retry.');
    }

    public function destroy($id)
    {
        DB::table('failed_jobs')->where('id', $id)->delete();
        return back()->with('success', 'Failed job deleted.');
    }

    public function prune()
    {
        // Delete failed jobs older than 7 days
        $deleted = DB::table('failed_jobs')
            ->where('failed_at', '<', now()->subDays(7))
            ->delete();

        return back()->with('success', "{$deleted} old failed jobs pruned.");
    }
}
