<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('search.home')
                ->with('error', 'Please login to access admin area.');
        }
        
        // Check if user has admin role
        // For now, check against a list of admin emails from .env
        $adminEmails = array_filter(explode(',', env('ADMIN_EMAILS', '')));
        $userEmail = auth()->user()?->email ?? '';
        
        // In production, replace with proper role-based authorization
        if (!in_array($userEmail, $adminEmails) && !$this->isAdminUser(auth()->user())) {
            abort(403, 'Unauthorized access. Admin privileges required.');
        }
        
        // Log admin access
        Log::info('Admin dashboard accessed', [
            'user_id' => auth()->id(),
            'email' => $userEmail,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        return $next($request);
    }
    
    /**
     * Check if user has admin privileges
     * Add your custom admin detection logic here
     */
    private function isAdminUser($user): bool
    {
        // Option 1: Check user role field (if exists)
        // return $user->role === 'admin';
        
        // Option 2: Check user permissions
        // return $user->hasPermissionTo('access-admin');
        
        // Option 3: Hardcoded admin IDs (temporary)
        $adminIds = array_filter(explode(',', env('ADMIN_USER_IDS', '')));
        return in_array($user->id, $adminIds);
    }
}
