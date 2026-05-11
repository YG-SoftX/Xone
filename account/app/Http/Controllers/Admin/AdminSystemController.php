<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminSystemController extends Controller
{
    public function index()
    {
        $dbVersion = 'N/A';
        try {
            $dbVersion = DB::select('SELECT VERSION() as v')[0]->v ?? 'N/A';
        } catch (\Exception) {}

        $diskFree  = @disk_free_space(base_path()) ?: 0;
        $diskTotal = @disk_total_space(base_path()) ?: 0;

        $pendingJobs = 0;
        $failedJobs  = 0;
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs  = DB::table('failed_jobs')->count();
        } catch (\Exception) {}

        $info = [
            'php_version'     => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_version'      => $dbVersion,
            'memory_limit'    => ini_get('memory_limit'),
            'max_exec_time'   => ini_get('max_execution_time') . 's',
            'disk_free_gb'    => round($diskFree / 1073741824, 2),
            'disk_total_gb'   => round($diskTotal / 1073741824, 2),
            'disk_used_pct'   => $diskTotal > 0 ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 1) : 0,
            'pending_jobs'    => $pendingJobs,
            'failed_jobs'     => $failedJobs,
            'maintenance_mode'=> app()->isDownForMaintenance(),
            'app_env'         => config('app.env'),
            'app_debug'       => config('app.debug'),
            'cache_driver'    => config('cache.default'),
            'queue_driver'    => config('queue.default'),
            'session_driver'  => config('session.driver'),
        ];

        return view('admin.system.index', compact('info'));
    }

    public function maintenanceEnable(Request $request)
    {
        if (session('admin_user_id') !== 1) {
            return redirect()->back()->with('error', 'Only super-admin can enable maintenance mode.');
        }

        if (app()->isDownForMaintenance()) {
            return redirect()->back()->with('error', 'Maintenance mode is already active.');
        }

        // Generate a bypass secret so super-admin can still access the site.
        $secret = Str::random(32);
        Artisan::call('down', ['--secret' => $secret]);

        Log::warning('Maintenance mode ENABLED', [
            'admin_user_id' => session('admin_user_id'),
            'ip'            => $request->ip(),
            'bypass_secret' => $secret,
        ]);

        return redirect()->back()->with('success',
            "Maintenance mode enabled. Bypass URL: " . config('app.url') . "/{$secret}"
        );
    }

    public function maintenanceDisable(Request $request)
    {
        if (session('admin_user_id') !== 1) {
            return redirect()->back()->with('error', 'Only super-admin can disable maintenance mode.');
        }

        if (!app()->isDownForMaintenance()) {
            return redirect()->back()->with('error', 'Maintenance mode is not currently active.');
        }

        Artisan::call('up');

        Log::warning('Maintenance mode DISABLED — site is live', [
            'admin_user_id' => session('admin_user_id'),
            'ip'            => $request->ip(),
        ]);

        return redirect()->back()->with('success', 'Maintenance mode disabled. Site is live.');
    }

    public function cacheClear(Request $request)
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');

        Log::info('Admin cleared all caches', ['admin_user_id' => session('admin_user_id')]);

        return redirect()->back()->with('success', 'Application, config, view, and route caches cleared.');
    }

    public function cacheWarm(Request $request)
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        Artisan::call('event:cache');

        Log::info('Admin warmed all caches', ['admin_user_id' => session('admin_user_id')]);

        return redirect()->back()->with('success', 'Config, route, view, and event caches rebuilt.');
    }

    public function queueRestart(Request $request)
    {
        Artisan::call('queue:restart');

        Log::info('Admin restarted queue workers', ['admin_user_id' => session('admin_user_id')]);

        return redirect()->back()->with('success', 'Queue workers signalled to restart after current jobs finish.');
    }
}
