<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ServiceConfiguration;
use Symfony\Component\HttpFoundation\Response;

class CheckServiceStatus
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $service = 'account'): Response
    {
        // 1. Allow Super Admins/Admins to bypass maintenance/disabled states
        if (auth()->check() && (auth()->user()->role === 'admin' || auth()->user()->role === 'super_admin')) {
            return $next($request);
        }

        // 2. Check for Global Master Maintenance Switch
        $globalConfig = ServiceConfiguration::where('service_key', 'global')->first();
        if ($globalConfig && $globalConfig->is_maintenance_mode) {
            return response()->view('errors.maintenance', [
                'service_name' => 'YGXone Ecosystem',
                'message' => $globalConfig->maintenance_message ?? 'The entire YGXone ecosystem is undergoing a major infrastructure upgrade.'
            ], 503);
        }

        // 3. Fetch specific service configuration from DB
        $config = ServiceConfiguration::where('service_key', $service)->first();

        // 3. Fallback if no config found (assume live)
        if (!$config) {
            return $next($request);
        }

        // 4. Check if service is completely disabled
        if (!$config->is_enabled) {
            return response()->view('errors.service-disabled', [
                'service_name' => $config->service_name,
                'message' => 'This service is currently unavailable. Please check back later.'
            ], 503);
        }

        // 5. Check if service is in maintenance mode
        if ($config->is_maintenance_mode) {
            // Prevent infinite redirect if we're already on the maintenance page
            if ($request->is('maintenance')) {
                return $next($request);
            }

            return response()->view('errors.maintenance', [
                'service_name' => $config->service_name,
                'message' => $config->maintenance_message ?? 'We are currently performing scheduled maintenance to improve your experience.',
                'retry_after' => 3600 // Optional: suggest retry in 1 hour
            ], 503);
        }

        return $next($request);
    }
}
