<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * IsolatedPaymentSecurity Middleware
 * 
 * Provides maximum security isolation for nulled/third-party payment modules.
 * Implements defense-in-depth with multiple security layers.
 */
class IsolatedPaymentSecurity
{
    private $allowedMethods = ['POST', 'GET'];
    private $maxPayloadSize = 1048576; // 1MB
    private $blockedHeaders = [
        'X-Forwarded-For',
        'X-Real-IP',
        'Client-IP',
    ];
    
    /**
     * Handle incoming request with strict security validation
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Layer 1: Method validation
            $this->validateHttpMethod($request);
            
            // Layer 2: IP validation and blocking
            $this->validateIpAddress($request);
            
            // Layer 3: Rate limiting
            $this->enforceRateLimiting($request);
            
            // Layer 4: Payload size validation
            $this->validatePayloadSize($request);
            
            // Layer 5: Header sanitization
            $this->sanitizeHeaders($request);
            
            // Layer 6: Input validation
            $this->validateInputs($request);
            
            // Layer 7: CSRF protection (if applicable)
            $this->verifyCsrfToken($request);
            
            // Log security check passed
            Log::channel('payment_security')->info('Request passed security checks', [
                'ip' => $request->ip(),
                'method' => $request->method(),
                'path' => $request->path(),
                'timestamp' => Carbon::now()->toIso8601String(),
            ]);
            
            return $next($request);
            
        } catch (\Exception $e) {
            // Log security violation
            Log::channel('payment_security')->warning('Security violation detected', [
                'ip' => $request->ip(),
                'method' => $request->method(),
                'path' => $request->path(),
                'error' => $e->getMessage(),
                'timestamp' => Carbon::now()->toIso8601String(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Security validation failed',
                'code' => 'SECURITY_VIOLATION',
                'message' => config('app.debug') ? $e->getMessage() : 'Request blocked by security policy',
            ], 403);
        }
    }
    
    /**
     * Validate HTTP method is allowed
     */
    private function validateHttpMethod(Request $request): void
    {
        if (!in_array($request->method(), $this->allowedMethods)) {
            throw new \RuntimeException("HTTP method {$request->method()} not allowed");
        }
    }
    
    /**
     * Validate and block suspicious IP addresses
     */
    private function validateIpAddress(Request $request): void
    {
        $ip = $request->ip();
        
        // Block known malicious ranges (customize as needed)
        $blockedRanges = config('security.blocked_ip_ranges', []);
        
        foreach ($blockedRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                throw new \RuntimeException("IP address {$ip} is blocked");
            }
        }
        
        // Check for VPN/Proxy/Tor (if detection service available)
        if ($this->isSuspiciousIp($ip)) {
            Log::warning('Suspicious IP detected', ['ip' => $ip]);
            // Optionally block or flag for review
        }
    }
    
    /**
     * Enforce rate limiting per IP
     */
    private function enforceRateLimiting(Request $request): void
    {
        $key = "isolated_payment_rate:" . $request->ip();
        $maxAttempts = config('security.payment_rate_limit', 100);
        $decayMinutes = config('security.payment_rate_window', 60);
        
        $attempts = cache()->get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            throw new \RuntimeException("Rate limit exceeded. Maximum {$maxAttempts} requests per {$decayMinutes} minutes");
        }
        
        cache()->put($key, $attempts + 1, now()->addMinutes($decayMinutes));
    }
    
    /**
     * Validate payload size
     */
    private function validatePayloadSize(Request $request): void
    {
        $contentLength = $request->header('Content-Length', 0);
        
        if ($contentLength > $this->maxPayloadSize) {
            throw new \RuntimeException("Payload size exceeds maximum allowed ({$this->maxPayloadSize} bytes)");
        }
        
        // Also check actual content size
        $actualSize = strlen($request->getContent());
        if ($actualSize > $this->maxPayloadSize) {
            throw new \RuntimeException("Request body exceeds maximum allowed size");
        }
    }
    
    /**
     * Sanitize and validate request headers
     */
    private function sanitizeHeaders(Request $request): void
    {
        // Remove potentially dangerous headers
        foreach ($this->blockedHeaders as $header) {
            if ($request->headers->has($header)) {
                Log::warning('Blocked header detected', [
                    'header' => $header,
                    'value' => $request->header($header),
                    'ip' => $request->ip(),
                ]);
                $request->headers->remove($header);
            }
        }
        
        // Validate Content-Type for POST requests
        if ($request->isMethod('POST')) {
            $contentType = $request->header('Content-Type', '');
            if (!str_contains($contentType, 'application/json') && 
                !str_contains($contentType, 'multipart/form-data') &&
                !str_contains($contentType, 'application/x-www-form-urlencoded')) {
                throw new \RuntimeException('Invalid Content-Type header');
            }
        }
    }
    
    /**
     * Validate input parameters
     */
    private function validateInputs(Request $request): void
    {
        // Check for SQL injection patterns
        $input = $request->all();
        $suspiciousPatterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER)\b)/i',
            '/(--|#|\/\*|\*\/)/',
            '/(\b(OR|AND)\b\s+\d+\s*=\s*\d+)/i',
            '/(\'|\"|`)/',
        ];
        
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        throw new \RuntimeException("Suspicious input detected in field: {$key}");
                    }
                }
            }
        }
        
        // Check for XSS patterns
        $xssPatterns = [
            '/<script[^>]*>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/eval\(/i',
        ];
        
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                foreach ($xssPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        throw new \RuntimeException("Potential XSS attack detected in field: {$key}");
                    }
                }
            }
        }
    }
    
    /**
     * Verify CSRF token for state-changing operations
     */
    private function verifyCsrfToken(Request $request): void
    {
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE')) {
            // Laravel's CSRF middleware should handle this, but add extra check
            if (!$request->hasSession() || !$request->session()->token()) {
                Log::warning('Missing CSRF token', ['ip' => $request->ip()]);
                // Don't throw here - let Laravel's CSRF middleware handle it
            }
        }
    }
    
    /**
     * Check if IP is in CIDR range
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $bits) = explode('/', $range);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $mask = -1 << (32 - (int)$bits);
        
        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
    
    /**
     * Check if IP is suspicious (VPN/Proxy/Tor)
     */
    private function isSuspiciousIp(string $ip): bool
    {
        // This would integrate with a threat intelligence API in production
        // For now, implement basic checks
        
        // Check if IP is from datacenter ranges (common for bots)
        $datacenterRanges = config('security.datacenter_ip_ranges', []);
        foreach ($datacenterRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }
        
        return false;
    }
}
