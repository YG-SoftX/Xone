<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class UserDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_name',
        'device_type',
        'os',
        'os_version',
        'browser',
        'browser_version',
        'ip_address',
        'device_id',
        'imei',
        'android_id',
        'idfa',
        'mac_address_hash',
        'latitude',
        'longitude',
        'location_accuracy',
        'location_history',
        'country_code',
        'city',
        'region',
        
        // Device Fingerprinting
        'canvas_fingerprint',
        'webgl_fingerprint',
        'fonts_hash',
        'screen_resolution',
        'color_depth',
        'pixel_ratio',
        'timezone',
        'language',
        'hardware_concurrency',
        'device_memory',
        'touch_support',
        
        // Security & Risk
        'device_reputation_score',
        'risk_flags',
        'login_count',
        'failed_login_attempts',
        'first_seen_at',
        'last_location_update',
        
        // Session Management
        'is_trusted',
        'is_blocked',
        'last_active_at',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'is_blocked' => 'boolean',
        'last_active_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_location_update' => 'datetime',
        'location_history' => 'array',
        'risk_flags' => 'array',
        'hardware_concurrency' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(DeviceActivityLog::class);
    }

    public function fraudAlerts()
    {
        return $this->hasMany(FraudAlert::class);
    }

    /**
     * Register or update device with enhanced fingerprinting data
     */
    public static function registerOrUpdate($userId, array $data)
    {
        // Generate composite fingerprint if not provided
        if (!isset($data['device_fingerprint'])) {
            $data['device_fingerprint'] = self::generateFingerprint($data);
        }
        
        // Calculate reputation score based on device history
        $existingDevice = static::where('user_id', $userId)
            ->where('device_id', $data['device_id'] ?? null)
            ->first();
        
        if ($existingDevice) {
            // Update existing device
            $existingDevice->update(array_merge($data, [
                'login_count' => $existingDevice->login_count + 1,
                'last_active_at' => now(),
            ]));
            
            // Log activity
            DeviceActivityLog::create([
                'device_id' => $existingDevice->id,
                'user_id' => $userId,
                'activity_type' => 'login',
                'ip_address' => $data['ip_address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
            ]);
            
            return $existingDevice;
        } else {
            // Create new device
            $newDevice = static::create(array_merge($data, [
                'user_id' => $userId,
                'login_count' => 1,
                'first_seen_at' => now(),
                'last_active_at' => now(),
            ]));
            
            // Log creation
            DeviceActivityLog::create([
                'device_id' => $newDevice->id,
                'user_id' => $userId,
                'activity_type' => 'device_registered',
                'ip_address' => $data['ip_address'] ?? null,
            ]);
            
            // Check for cross-account links
            self::checkCrossAccountLinks($newDevice);
            
            return $newDevice;
        }
    }

    /**
     * Generate unique device fingerprint from multiple signals
     */
    public static function generateFingerprint(array $deviceData): string
    {
        $signals = [
            $deviceData['canvas_fingerprint'] ?? '',
            $deviceData['webgl_fingerprint'] ?? '',
            $deviceData['screen_resolution'] ?? '',
            $deviceData['timezone'] ?? '',
            $deviceData['language'] ?? '',
            $deviceData['os'] ?? '',
            $deviceData['browser'] ?? '',
            ($deviceData['hardware_concurrency'] ?? null) ? json_encode($deviceData['hardware_concurrency']) : '',
            $deviceData['device_memory'] ?? '',
        ];
        
        return hash('sha256', implode('|', array_filter($signals)));
    }

    /**
     * Check if this device is used by multiple accounts (potential fraud)
     */
    private static function checkCrossAccountLinks(self $device): void
    {
        // Find all accounts using similar device fingerprint
        $fingerprint = $device->canvas_fingerprint ?? $device->webgl_fingerprint ?? null;
        
        if (!$fingerprint) {
            return;
        }
        
        $matchingDevices = static::where(function($query) use ($fingerprint) {
            $query->where('canvas_fingerprint', $fingerprint)
                  ->orWhere('webgl_fingerprint', $fingerprint);
        })
        ->where('user_id', '!=', $device->user_id)
        ->pluck('user_id')
        ->unique();
        
        if ($matchingDevices->count() > 0) {
            $allAccountIds = $matchingDevices->push($device->user_id)->sort()->values();
            
            // Create or update cross-account link
            DeviceAccountLink::updateOrCreate(
                [
                    'device_fingerprint' => $fingerprint,
                    'user_id' => $device->user_id,
                ],
                [
                    'device_id' => $device->id,
                    'ip_address' => $device->ip_address,
                    'account_count' => $allAccountIds->count(),
                    'account_ids' => $allAccountIds->toArray(),
                    'is_suspicious' => $allAccountIds->count() >= 3, // Flag if 3+ accounts
                    'suspicion_reason' => $allAccountIds->count() >= 3 
                        ? "Multiple accounts ({$allAccountIds->count()}) detected on same device" 
                        : null,
                    'last_updated_at' => now(),
                ]
            );
            
            // Create fraud alert if suspicious
            if ($allAccountIds->count() >= 3) {
                FraudAlert::create([
                    'user_id' => $device->user_id,
                    'device_id' => $device->id,
                    'alert_type' => 'account_farming',
                    'severity' => 'high',
                    'description' => "Device linked to {$allAccountIds->count()} different accounts",
                    'evidence' => [
                        'account_ids' => $allAccountIds->toArray(),
                        'device_fingerprint' => $fingerprint,
                        'ip_address' => $device->ip_address,
                    ],
                ]);
                
                Log::warning('Potential account farming detected', [
                    'device_id' => $device->id,
                    'user_id' => $device->user_id,
                    'linked_accounts' => $allAccountIds->toArray(),
                ]);
            }
        }
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    /**
     * Check for impossible travel (login from distant location in short time)
     */
    public static function detectImpossibleTravel(int $userId, float $newLat, float $newLon): ?array
    {
        $lastDevice = static::where('user_id', $userId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('last_active_at', 'desc')
            ->first();
        
        if (!$lastDevice || !$lastDevice->last_active_at) {
            return null;
        }
        
        $distance = self::calculateDistance(
            $lastDevice->latitude,
            $lastDevice->longitude,
            $newLat,
            $newLon
        );
        
        $timeDiff = now()->diffInMinutes($lastDevice->last_active_at);
        
        // Assume max travel speed of 900 km/h (commercial jet)
        $maxPossibleDistance = ($timeDiff / 60) * 900;
        
        if ($distance > $maxPossibleDistance && $distance > 100) { // > 100km threshold
            return [
                'previous_location' => [
                    'lat' => $lastDevice->latitude,
                    'lon' => $lastDevice->longitude,
                    'time' => $lastDevice->last_active_at,
                ],
                'new_location' => [
                    'lat' => $newLat,
                    'lon' => $newLon,
                    'time' => now(),
                ],
                'distance_km' => round($distance, 2),
                'time_minutes' => $timeDiff,
                'required_speed_kmh' => round(($distance / $timeDiff) * 60, 2),
            ];
        }
        
        return null;
    }

    /**
     * Update device reputation score based on behavior
     */
    public function updateReputationScore(): void
    {
        $score = 50; // Base score
        
        // Positive factors
        if ($this->login_count > 10) $score += 10;
        if ($this->is_trusted) $score += 15;
        if ($this->failed_login_attempts == 0) $score += 10;
        
        // Negative factors
        if ($this->is_blocked) $score -= 40;
        if ($this->failed_login_attempts > 5) $score -= 20;
        if ($this->risk_flags && count($this->risk_flags) > 0) {
            $score -= (count($this->risk_flags) * 10);
        }
        
        // Clamp to 0-100
        $this->device_reputation_score = max(0, min(100, $score));
        $this->save();
    }
}
