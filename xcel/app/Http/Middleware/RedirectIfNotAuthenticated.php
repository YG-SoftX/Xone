<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfNotAuthenticated {
    public function handle(Request $request, Closure $next) {
        if (!Auth::check()) {
            $accountUrl = config('services.yg_account.url', 'http://localhost:8000');
            return redirect($accountUrl . '/sso/initiate?service=YG+Xcel&callback=' . urlencode(url('/sso/callback')));
        }
        return $next($request);
    }
}
