<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfNotAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            $callbackUrl = url('/sso/callback');
            $accountUrl = config('services.yg_account.url', 'http://localhost:8000');
            return redirect($accountUrl . '/sso/initiate?service=YG+DocX&callback=' . urlencode($callbackUrl));
        }

        return $next($request);
    }
}
