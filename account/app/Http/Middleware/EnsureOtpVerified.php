<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class EnsureOtpVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $verifiedAt = $request->session()->get('otp_verified_at');

        // Check if verified and if it was in the last 30 minutes
        if ($verifiedAt && Carbon::parse($verifiedAt)->addMinutes(30)->isFuture()) {
            return $next($request);
        }

        // Otherwise, redirect to checkpoint
        return redirect()->route('otp.verify', [
            'action_url' => $request->fullUrl()
        ]);
    }
}
