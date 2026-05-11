<?php

namespace App\Http\Middleware;

use App\Models\UserDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DeviceFingerprinting
{
    /**
     * Handle an incoming request and capture device fingerprint data
     */
    public function handle(Request $request, Closure $next)
    {
        // Only track authenticated users
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $deviceData = $this->extractDeviceData($request);

        // Register or update device
        try {
            $device = UserDevice::registerOrUpdate($user->id, $deviceData);

            // Check for impossible travel if location data available
            if (isset($deviceData['latitude']) && isset($deviceData['longitude'])) {
                $impossibleTravel = UserDevice::detectImpossibleTravel(
                    $user->id,
                    $deviceData['latitude'],
                    $deviceData['longitude']
                );

                if ($impossibleTravel) {
                    $this->createImpossibleTravelAlert($user, $device, $impossibleTravel);
                }
            }

            // Update device reputation score periodically
            if ($device->login_count % 10 === 0) {
                $device->updateReputationScore();
            }

            // Attach device to request for downstream use
            $request->merge(['current_device' => $device]);

        } catch (\Exception $e) {
            Log::error('Device fingerprinting failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $next($request);
    }

    /**
     * Extract comprehensive device data from request
     */
    private function extractDeviceData(Request $request): array
    {
        $data = [
            // Basic info
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            
            // Browser/OS detection from user agent
            'browser' => $this->detectBrowser($request->userAgent()),
            'browser_version' => $this->detectBrowserVersion($request->userAgent()),
            'os' => $this->detectOS($request->userAgent()),
            'os_version' => $this->detectOSVersion($request->userAgent()),
            'device_type' => $this->detectDeviceType($request->userAgent()),
            'device_name' => $this->generateDeviceName($request->userAgent()),
        ];

        // Extract client-side fingerprinting data from headers/cookies
        if ($request->hasHeader('X-Device-Fingerprint')) {
            $fingerprint = json_decode($request->header('X-Device-Fingerprint'), true);
            
            if ($fingerprint) {
                $data = array_merge($data, [
                    // Hardware identifiers
                    'imei' => $fingerprint['imei'] ?? null,
                    'android_id' => $fingerprint['android_id'] ?? null,
                    'idfa' => $fingerprint['idfa'] ?? null,
                    
                    // Device fingerprinting
                    'canvas_fingerprint' => $fingerprint['canvas'] ?? null,
                    'webgl_fingerprint' => $fingerprint['webgl'] ?? null,
                    'fonts_hash' => $fingerprint['fonts'] ?? null,
                    'screen_resolution' => $fingerprint['screen_resolution'] ?? null,
                    'color_depth' => $fingerprint['color_depth'] ?? null,
                    'pixel_ratio' => $fingerprint['pixel_ratio'] ?? null,
                    'timezone' => $fingerprint['timezone'] ?? null,
                    'language' => $fingerprint['language'] ?? null,
                    'hardware_concurrency' => $fingerprint['cpu_cores'] ?? null,
                    'device_memory' => $fingerprint['device_memory'] ?? null,
                    'touch_support' => $fingerprint['touch_support'] ?? false,
                    
                    // Geolocation (if permission granted)
                    'latitude' => $fingerprint['latitude'] ?? null,
                    'longitude' => $fingerprint['longitude'] ?? null,
                    'location_accuracy' => $fingerprint['accuracy'] ?? null,
                ]);
            }
        }

        // Generate unique device ID if not provided
        if (!isset($data['device_id'])) {
            $data['device_id'] = $this->generateDeviceId($data);
        }

        return $data;
    }

    /**
     * Detect browser from user agent
     */
    private function detectBrowser(?string $userAgent): ?string
    {
        if (!$userAgent) return null;

        if (preg_match('/Chrome\/([\d.]+)/', $userAgent)) return 'Chrome';
        if (preg_match('/Firefox\/([\d.]+)/', $userAgent)) return 'Firefox';
        if (preg_match('/Safari\/([\d.]+)/', $userAgent)) return 'Safari';
        if (preg_match('/Edge\/([\d.]+)/', $userAgent)) return 'Edge';
        if (preg_match('/MSIE ([\d.]+)/', $userAgent)) return 'Internet Explorer';
        
        return 'Unknown';
    }

    /**
     * Detect browser version
     */
    private function detectBrowserVersion(?string $userAgent): ?string
    {
        if (!$userAgent) return null;

        if (preg_match('/Chrome\/([\d.]+)/', $userAgent, $matches)) return $matches[1];
        if (preg_match('/Firefox\/([\d.]+)/', $userAgent, $matches)) return $matches[1];
        if (preg_match('/Version\/([\d.]+).*Safari/', $userAgent, $matches)) return $matches[1];
        
        return null;
    }

    /**
     * Detect operating system
     */
    private function detectOS(?string $userAgent): ?string
    {
        if (!$userAgent) return null;

        if (preg_match('/Windows NT ([\d.]+)/', $userAgent)) return 'Windows';
        if (preg_match('/Mac OS X ([\d_]+)/', $userAgent)) return 'macOS';
        if (preg_match('/Linux/', $userAgent)) return 'Linux';
        if (preg_match('/Android ([\d.]+)/', $userAgent)) return 'Android';
        if (preg_match('/iPhone OS ([\d_]+)/', $userAgent)) return 'iOS';
        if (preg_match('/iPad/', $userAgent)) return 'iPadOS';
        
        return 'Unknown';
    }

    /**
     * Detect OS version
     */
    private function detectOSVersion(?string $userAgent): ?string
    {
        if (!$userAgent) return null;

        if (preg_match('/Windows NT ([\d.]+)/', $userAgent, $matches)) return $matches[1];
        if (preg_match('/Mac OS X ([\d_]+)/', $userAgent, $matches)) return str_replace('_', '.', $matches[1]);
        if (preg_match('/Android ([\d.]+)/', $userAgent, $matches)) return $matches[1];
        if (preg_match('/iPhone OS ([\d_]+)/', $userAgent, $matches)) return str_replace('_', '.', $matches[1]);
        
        return null;
    }

    /**
     * Detect device type
     */
    private function detectDeviceType(?string $userAgent): ?string
    {
        if (!$userAgent) return 'desktop';

        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            return preg_match('/Tablet|iPad/', $userAgent) ? 'tablet' : 'mobile';
        }

        return 'desktop';
    }

    /**
     * Generate human-readable device name
     */
    private function generateDeviceName(?string $userAgent): string
    {
        $os = $this->detectOS($userAgent) ?? 'Unknown OS';
        $browser = $this->detectBrowser($userAgent) ?? 'Unknown Browser';
        $deviceType = $this->detectDeviceType($userAgent);
        
        return "{$os} - {$browser} ({$deviceType})";
    }

    /**
     * Generate unique device ID from fingerprint components
     */
    private function generateDeviceId(array $data): string
    {
        $components = [
            $data['canvas_fingerprint'] ?? '',
            $data['webgl_fingerprint'] ?? '',
            $data['screen_resolution'] ?? '',
            $data['timezone'] ?? '',
            $data['os'] ?? '',
            $data['browser'] ?? '',
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    /**
     * Create fraud alert for impossible travel
     */
    private function createImpossibleTravelAlert($user, $device, array $travelData): void
    {
        \App\Models\FraudAlert::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
            'alert_type' => 'impossible_travel',
            'severity' => 'critical',
            'description' => "Impossible travel detected: {$travelData['distance_km']}km in {$travelData['time_minutes']} minutes",
            'evidence' => $travelData,
            'status' => 'open',
        ]);

        Log::warning('Impossible travel detected', [
            'user_id' => $user->id,
            'device_id' => $device->id,
            'travel_data' => $travelData,
        ]);

        // Optionally block the device temporarily
        if ($travelData['required_speed_kmh'] > 2000) {
            $device->update([
                'is_blocked' => true,
                'risk_flags' => array_merge(
                    $device->risk_flags ?? [],
                    ['impossible_travel_detected' => now()->toIso8601String()]
                ),
            ]);

            $device->updateReputationScore();
        }
    }
}
