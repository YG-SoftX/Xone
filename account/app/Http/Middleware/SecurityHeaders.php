<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a per-request nonce for CSP — stored so Blade/Ziggy can reference it.
        $nonce = base64_encode(random_bytes(16));
        app()->instance('csp-nonce', $nonce);

        // Tell Laravel's Vite helper to stamp the same nonce on its <script> tags.
        Vite::useCspNonce($nonce);

        $response = $next($request);

        // 🛡️ The Impenetrable Header Shield
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), browsing-topics=(), interest-cohort=()');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // Force HTTPS Sovereignty
        $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');

        // Sovereign Content Security Policy
        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; " .
            "font-src 'self' data: https://fonts.gstatic.com; " .
            "img-src 'self' data: https:; " .
            "connect-src 'self' https:; " .
            "frame-ancestors 'none'; " .
            "base-uri 'self'; " .
            "form-action 'self';"
        );

        // Strip Identification
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
