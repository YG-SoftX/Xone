<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HandleErrors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            // Log the error
            Log::error('Unhandled exception: ' . $e->getMessage(), [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            // For Inertia requests, return with error flash message
            if ($request->header('X-Inertia')) {
                return back()->with('error', 'An unexpected error occurred. Please try again.');
            }

            // For API requests, return JSON error
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Internal Server Error',
                    'message' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
                ], 500);
            }

            // For web requests, show error page
            if (config('app.debug')) {
                throw $e;
            }

            return response()->view('errors.custom', [
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }
}
