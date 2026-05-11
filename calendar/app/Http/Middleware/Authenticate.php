<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            $accountUrl  = rtrim(config('services.yg_account.url', 'http://localhost:8000'), '/');
            $callbackUrl = url('/sso/callback');
            return redirect($accountUrl . '/sso/initiate?service=YG+Calendar&callback=' . urlencode($callbackUrl));
        }

        return $next($request);
    }
}
