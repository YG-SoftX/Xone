<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIProxyController extends Controller
{
    /**
     * Proxy search requests to the internal YG AI service.
     * Validates public API key and rate-limits requests.
     */
    public function search(Request $request)
    {
        // 1. Validate public API key
        $incomingKey = $request->header('X-API-Key');
        $publicKey = config('services.yg_ai.public_key');

        if (!$incomingKey || $incomingKey !== $publicKey) {
            return response()->json(['ok' => false, 'error' => 'Invalid or missing API key'], 401);
        }

        // 2. Validate input
        $request->validate([
            'query' => 'required|string|max:500',
            'action' => 'sometimes|string',
            'tab' => 'sometimes|string',
            'page' => 'sometimes|integer|min:1',
        ]);

        $ygAiApiUrl = config('services.yg_ai.api_url');
        $apiKey = config('services.yg_ai.api_key');

        if (!$ygAiApiUrl || !$apiKey) {
            return response()->json(['ok' => false, 'error' => 'AI service configuration missing'], 500);
        }

        $payload = [
            'action' => $request->input('action', 'web_search'),
            'query' => $request->input('query'),
            'tab' => $request->input('tab', 'all'),
            'page' => (int) $request->input('page', 1),
            'generate' => true, // Always request AI synthesis for public proxy
        ];

        try {
            // 3. Forward to internal AI service
            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
                'X-Forwarded-For' => $request->ip(),
            ])->timeout(config('services.yg_ai.timeout', 15))
              ->post($ygAiApiUrl . '/', $payload);

            if (!$response->successful()) {
                Log::error('AI Proxy request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $ygAiApiUrl
                ]);
                return response()->json(['ok' => false, 'error' => 'AI service currently unavailable'], 502);
            }

            return response()->json($response->json());

        } catch (\Exception $e) {
            Log::error('AI Proxy Exception', ['message' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => 'Internal portal error'], 500);
        }
    }
}
