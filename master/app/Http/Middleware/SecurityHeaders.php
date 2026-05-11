<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            // Strictest CSP for the master admin panel — no inline scripts allowed
            $response->headers->set('Content-Security-Policy',
                "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
                "style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; " .
                "font-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; " .
                "base-uri 'self'; form-action 'self';"
            );
        }

        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
