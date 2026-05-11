<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UnifiedDriveService
{
    protected string $apiBase;

    public function __construct()
    {
        $this->apiBase = rtrim(config('services.yg_account.api_base'), '/');
    }

    /**
     * Upload a file to the central YG Drive
     */
    public function upload($file, string $service = 'mail'): ?array
    {
        try {
            $token = auth()->user()->api_token ?? '';
            
            $response = Http::withToken($token)
                ->attach('file', file_get_contents($file->path()), $file->getClientOriginalName())
                ->post($this->apiBase . '/storage/upload', [
                    'service' => $service,
                ]);

            if ($response->ok()) {
                return $response->json();
            }

            Log::error("Unified Drive upload failed: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("Unified Drive exception: " . $e->getMessage());
            return null;
        }
    }
}
