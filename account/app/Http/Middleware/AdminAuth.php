<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    // Hard cap: force re-login after 8 hours regardless of activity.
    private const ABSOLUTE_TIMEOUT_SECONDS = 8 * 60 * 60;

    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('admin_user_id')) {
            return redirect()->route('admin.login');
        }

        $adminId = session('admin_user_id');

        // ── Idle timeout ─────────────────────────────────────────────────────
        $idleTimeout = config('admin.session_timeout', 30) * 60;

        if (session()->has('admin_last_activity') &&
            (time() - session('admin_last_activity')) > $idleTimeout) {
            $this->expireSession($adminId, 'idle timeout');

            return redirect()->route('admin.login')
                ->with('error', 'Your session has expired. Please log in again.');
        }

        // ── Absolute session lifetime ─────────────────────────────────────────
        if (session()->has('admin_login_time') &&
            (time() - session('admin_login_time')) > self::ABSOLUTE_TIMEOUT_SECONDS) {
            $this->expireSession($adminId, 'absolute timeout (8 h)');

            return redirect()->route('admin.login')
                ->with('error', 'Your session has expired after 8 hours. Please log in again.');
        }

        // ── IP address binding ────────────────────────────────────────────────
        // On first request after login the session may not have admin_ip yet.
        if (!session()->has('admin_ip')) {
            session(['admin_ip' => $request->ip()]);
        } elseif (session('admin_ip') !== $request->ip()) {
            Log::warning('Admin session IP mismatch — possible session hijacking attempt', [
                'admin_user_id'  => $adminId,
                'session_ip'     => session('admin_ip'),
                'request_ip'     => $request->ip(),
                'url'            => $request->fullUrl(),
            ]);
            // Invalidate the session to be safe.
            $this->expireSession($adminId, 'IP address changed');

            return redirect()->route('admin.login')
                ->with('error', 'Session invalidated due to IP address change. Please log in again.');
        }

        // ── User-agent binding ────────────────────────────────────────────────
        if (!session()->has('admin_ua')) {
            session(['admin_ua' => $request->userAgent()]);
        } elseif (session('admin_ua') !== $request->userAgent()) {
            Log::warning('Admin session user-agent mismatch', [
                'admin_user_id' => $adminId,
                'url'           => $request->fullUrl(),
            ]);
            $this->expireSession($adminId, 'user-agent changed');

            return redirect()->route('admin.login')
                ->with('error', 'Session invalidated. Please log in again.');
        }

        session(['admin_last_activity' => time()]);

        return $next($request);
    }

    private function expireSession(int $adminId, string $reason): void
    {
        Log::info('Admin session expired', [
            'admin_user_id' => $adminId,
            'reason'        => $reason,
        ]);

        session()->forget([
            'admin_user_id',
            'admin_last_activity',
            'admin_login_time',
            'admin_ip',
            'admin_ua',
        ]);
    }
}
