<?php

namespace App\Services;

use Illuminate\Http\Request;

class DeviceFingerprintService
{
    /**
     * Capture comprehensive device fingerprint from request
     */
    public function capture(Request $request): array
    {
        $userAgent = $request->userAgent();
        
        // Defensive: Check if Jenssegers\Agent exists
        if (class_exists('Jenssegers\Agent\Agent')) {
            $agent = new \Jenssegers\Agent\Agent();
            $agent->setUserAgent($userAgent);
            
            $browser = $agent->browser();
            $platform = $agent->platform();
            $deviceType = $agent->deviceType();
            $isMobile = $agent->isMobile();
            $isTablet = $agent->isTablet();
            $isDesktop = $agent->isDesktop();
        } else {
            // Fallback: Basic manual parsing if library is missing
            $browser = $this->parseBrowser($userAgent);
            $platform = $this->parsePlatform($userAgent);
            $isMobile = (bool)preg_match('/Mobile|Android|iPhone/i', $userAgent);
            $isTablet = (bool)preg_match('/Tablet|iPad/i', $userAgent);
            $isDesktop = !$isMobile && !$isTablet;
            $deviceType = $isMobile ? 'mobile' : ($isTablet ? 'tablet' : 'desktop');
        }

        return [
            // Browser & OS Info
            'browser' => $browser,
            'browser_version' => 'unknown', // Version parsing is complex without the lib
            'platform' => $platform,
            'platform_version' => 'unknown',
            'device_type' => $deviceType,
            'is_mobile' => $isMobile,
            'is_tablet' => $isTablet,
            'is_desktop' => $isDesktop,
            
            // Screen & Display (from client-side JS, passed in request)
            'screen_resolution' => $request->input('screen_resolution'),
            'color_depth' => $request->input('color_depth', 24),
            'pixel_ratio' => $request->input('pixel_ratio', 1),
            
            // Network & Location
            'ip_address' => $request->ip(),
            'timezone' => $request->input('timezone', config('app.timezone')),
            'language' => $request->getPreferredLanguage(),
            'languages' => $request->getLanguages(),
            
            // Hardware Identifiers (if provided by mobile app)
            'imei' => $request->input('imei'),
            'android_id' => $request->input('android_id'),
            'idfa' => $request->input('idfa'),
            'mac_address_hash' => $request->input('mac_address_hash'),
            
            // Advanced Fingerprinting (Canvas/WebGL hashes from client)
            'canvas_fingerprint' => $request->input('canvas_fingerprint'),
            'webgl_fingerprint' => $request->input('webgl_fingerprint'),
            'fonts_hash' => $request->input('fonts_hash'),
            'audio_fingerprint' => $request->input('audio_fingerprint'),
            
            // Behavioral Data
            'touch_support' => $request->input('touch_support', false),
            'cookie_enabled' => $request->input('cookie_enabled', true),
            'java_enabled' => $request->input('java_enabled', false),
            
            // Geolocation (with user permission)
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'geolocation_accuracy' => $request->input('geolocation_accuracy'),
            
            // Generated unique fingerprint hash
            'fingerprint_hash' => $this->generateFingerprintHash($request),
            
            // Risk indicators
            'is_vpn' => $this->detectVPN($request->ip()),
            'is_proxy' => $this->detectProxy($request->ip()),
            'is_tor' => $this->detectTor($request->ip()),
            'is_datacenter' => $this->isDatacenterIP($request->ip()),
            
            // Timestamp
            'captured_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate unique fingerprint hash combining multiple signals
     */
    private function generateFingerprintHash(Request $request): string
    {
        $signals = [
            $request->userAgent(),
            $request->input('screen_resolution'),
            $request->input('canvas_fingerprint'),
            $request->input('webgl_fingerprint'),
            $request->input('fonts_hash'),
            $request->getPreferredLanguage(),
            $request->ip(),
        ];

        // Filter out null values
        $signals = array_filter($signals);

        return hash('sha256', implode('|', $signals));
    }

    /**
     * Detect if IP is using VPN
     */
    private function detectVPN(string $ip): bool
    {
        // Integration with VPN detection service (e.g., IPQualityScore, Abstract API)
        // For now, basic check - implement actual API call in production
        
        $vpnIndicators = [
            'vpn',
            'proxy',
            'anonymous',
        ];

        // Check against known VPN IP ranges (simplified)
        return false; // TODO: Implement actual VPN detection API
    }

    /**
     * Detect if IP is using proxy
     */
    private function detectProxy(string $ip): bool
    {
        // Check for common proxy headers
        $proxyHeaders = [
            'HTTP_VIA',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_PROXY_CONNECTION',
        ];

        foreach ($proxyHeaders as $header) {
            if (isset($_SERVER[$header])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect if IP is Tor exit node
     */
    private function detectTor(string $ip): bool
    {
        // Check against known Tor exit nodes list
        // TODO: Integrate with Tor Project API or maintain local list
        return false;
    }

    /**
     * Check if IP belongs to datacenter/cloud provider
     */
    private function isDatacenterIP(string $ip): bool
    {
        // Check against known datacenter IP ranges
        // AWS, GCP, Azure, DigitalOcean, etc.
        
        $datacenterRanges = [
            '52.',    // AWS
            '35.',    // GCP
            '13.',    // Azure
            '104.',   // Cloudflare
        ];

        foreach ($datacenterRanges as $range) {
            if (strpos($ip, $range) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate risk score based on device data
     */
    public function calculateRiskScore(array $deviceData): int
    {
        $score = 0;

        // VPN/Proxy usage increases risk
        if ($deviceData['is_vpn'] ?? false) {
            $score += 20;
        }
        if ($deviceData['is_proxy'] ?? false) {
            $score += 15;
        }
        if ($deviceData['is_tor'] ?? false) {
            $score += 30;
        }

        // Datacenter IP increases risk
        if ($deviceData['is_datacenter'] ?? false) {
            $score += 10;
        }

        // Missing advanced fingerprints (possible spoofing)
        if (empty($deviceData['canvas_fingerprint'])) {
            $score += 5;
        }
        if (empty($deviceData['webgl_fingerprint'])) {
            $score += 5;
        }

        // Cap at 100
        return min($score, 100);
    }

    /**
     * Compare two device fingerprints for similarity
     */
    public function compareFingerprints(array $fp1, array $fp2): float
    {
        $matchingSignals = 0;
        $totalSignals = 0;

        $comparisonFields = [
            'browser',
            'platform',
            'screen_resolution',
            'canvas_fingerprint',
            'webgl_fingerprint',
            'fonts_hash',
            'language',
        ];

        foreach ($comparisonFields as $field) {
            if (isset($fp1[$field]) && isset($fp2[$field])) {
                $totalSignals++;
                if ($fp1[$field] === $fp2[$field]) {
                    $matchingSignals++;
                }
            }
        }

        if ($totalSignals === 0) {
            return 0.0;
        }

        return round(($matchingSignals / $totalSignals) * 100, 2);
    }

    /**
     * Manual browser parsing fallback
     */
    private function parseBrowser(string $userAgent): string
    {
        if (str_contains($userAgent, 'MSIE') || str_contains($userAgent, 'Trident/7')) return 'Internet Explorer';
        if (str_contains($userAgent, 'Edge')) return 'Edge';
        if (str_contains($userAgent, 'Firefox')) return 'Firefox';
        if (str_contains($userAgent, 'Chrome')) return 'Chrome';
        if (str_contains($userAgent, 'Safari')) return 'Safari';
        if (str_contains($userAgent, 'Opera') || str_contains($userAgent, 'OPR')) return 'Opera';
        return 'Unknown Browser';
    }

    /**
     * Manual platform parsing fallback
     */
    private function parsePlatform(string $userAgent): string
    {
        if (str_contains($userAgent, 'Windows')) return 'Windows';
        if (str_contains($userAgent, 'Macintosh') || str_contains($userAgent, 'Mac OS X')) return 'macOS';
        if (str_contains($userAgent, 'Android')) return 'Android';
        if (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') || str_contains($userAgent, 'iPod')) return 'iOS';
        if (str_contains($userAgent, 'Linux')) return 'Linux';
        return 'Unknown Platform';
    }
}
