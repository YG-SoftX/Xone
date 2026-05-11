<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Cache;

class ApiKeyService
{
    /**
     * Generate new API key
     */
    public function generateApiKey(Project $project, array $data): ApiKey
    {
        $apiKey = $project->apiKeys()->create([
            'key' => 'yg_' . bin2hex(random_bytes(32)),
            'name' => $data['name'],
            'rate_limit' => $data['rate_limit'] ?? 1000,
            'restrictions' => $data['restrictions'] ?? [],
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        // Cache the plain key temporarily for display (will be shown only once)
        Cache::put('api_key_temp_' . $apiKey->id, $apiKey->key, now()->addMinutes(5));

        return $apiKey;
    }

    /**
     * Revoke API key
     */
    public function revokeApiKey(ApiKey $apiKey): bool
    {
        return $apiKey->delete();
    }

    /**
     * Rotate API key (revoke old, create new)
     */
    public function rotateApiKey(ApiKey $oldKey): ApiKey
    {
        $project = $oldKey->project;
        
        // Create new key with same settings
        $newKey = $this->generateApiKey($project, [
            'name' => $oldKey->name . ' (Rotated)',
            'rate_limit' => $oldKey->rate_limit,
            'restrictions' => $oldKey->restrictions,
            'expires_at' => $oldKey->expires_at,
        ]);

        // Revoke old key
        $oldKey->delete();

        return $newKey;
    }

    /**
     * Validate API key
     */
    public function validateApiKey(string $key): ?ApiKey
    {
        $apiKey = ApiKey::where('key', $key)->first();

        if (!$apiKey) {
            return null;
        }

        // Check if expired
        if ($apiKey->isExpired()) {
            return null;
        }

        // Update last used timestamp
        $apiKey->update(['last_used_at' => now()]);

        return $apiKey;
    }

    /**
     * Check rate limit
     */
    public function checkRateLimit(ApiKey $apiKey): bool
    {
        $cacheKey = 'api_rate_limit_' . $apiKey->id;
        $currentCount = Cache::get($cacheKey, 0);

        if ($currentCount >= $apiKey->rate_limit) {
            return false;
        }

        // Increment counter with TTL of 1 hour
        Cache::increment($cacheKey);
        Cache::expire($cacheKey, 3600);

        return true;
    }

    /**
     * Get remaining requests
     */
    public function getRemainingRequests(ApiKey $apiKey): int
    {
        $cacheKey = 'api_rate_limit_' . $apiKey->id;
        $currentCount = Cache::get($cacheKey, 0);

        return max(0, $apiKey->rate_limit - $currentCount);
    }

    /**
     * Apply IP restrictions
     */
    public function isIpAllowed(ApiKey $apiKey, string $ip): bool
    {
        $restrictions = $apiKey->restrictions;

        if (empty($restrictions['allowed_ips'])) {
            return true; // No IP restrictions
        }

        return in_array($ip, $restrictions['allowed_ips']);
    }

    /**
     * Apply referrer restrictions
     */
    public function isReferrerAllowed(ApiKey $apiKey, ?string $referrer): bool
    {
        $restrictions = $apiKey->restrictions;

        if (empty($restrictions['allowed_referrers'])) {
            return true; // No referrer restrictions
        }

        if (!$referrer) {
            return false;
        }

        foreach ($restrictions['allowed_referrers'] as $allowed) {
            if (str_contains($referrer, $allowed)) {
                return true;
            }
        }

        return false;
    }
}
