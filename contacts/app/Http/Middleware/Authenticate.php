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
            $accountUrl  = config('services.yg_account.url', 'http://localhost:8000');
            $callbackUrl = url('/sso/callback');

            return redirect($accountUrl . '/sso/initiate?service=YG+Contacts&callback=' . urlencode($callbackUrl));
        }

        return $next($request);
    }
}
