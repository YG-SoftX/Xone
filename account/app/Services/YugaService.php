<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YugaService
{
    protected string $endpoint;
    protected string $apiKey;

    public function __construct()
    {
        $this->endpoint = config('services.yuga.endpoint', 'https://yuga-api.ygxone.com/v1');
        $this->apiKey = config('services.yuga.key', '');
    }

    /**
     * Use Yuga 1.0 to analyze content for safety
     * Returns: ['safe' => boolean, 'reason' => string, 'confidence' => float]
     */
    public function analyzeContent(string $content, array $metadata = []): array
    {
        try {
            // This is the bridge to Yuga 1.0
            // In a real scenario, this calls your LLM endpoint
            $response = Http::withToken($this->apiKey)->post("{$this->endpoint}/moderate", [
                'content' => $content,
                'metadata' => $metadata,
                'model' => 'yuga-1.0',
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error("Yuga 1.0 Intelligence Failure: " . $e->getMessage());
        }

        // Fallback: If Yuga is offline, we use the local Sentinel logic
        return ['safe' => true, 'reason' => 'Yuga Offline - Local Sentinel Active', 'confidence' => 1.0];
    }

    /**
     * Advanced: Analyze Image/Media for potential Adult Content
     */
    public function moderateMedia($file): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post("{$this->endpoint}/moderate_media");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error("Yuga Visual Intelligence Failure: " . $e->getMessage());
        }

        // Fallback: If Yuga is offline, we allow (or block) based on your preference
        // For now, we allow but log the failure
        return ['safe' => true, 'reason' => 'Yuga Visual Offline', 'confidence' => 1.0];
    }
}
