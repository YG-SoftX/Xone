<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    /**
     * Attempt to call YG Account AI Service for text generation.
     * Falls back gracefully if the service is unavailable.
     */
    private function callAiApi(string $prompt, array $options = []): ?string
    {
        try {
            $ygAccountUrl = config('services.yg_account.url', 'http://localhost:8000');
            $response = Http::timeout(5)->post($ygAccountUrl . '/api/ai/complete', [
                'prompt' => $prompt,
                'max_tokens' => $options['max_tokens'] ?? 100,
                'temperature' => $options['temperature'] ?? 0.5,
            ]);

            if ($response->successful()) {
                return $response->json('text') ?? $response->json('result');
            }
        } catch (\Exception $e) {
            Log::debug('AI service unavailable: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Summarize an email body into 3 concise bullet points.
     */
    public function summarize(string $body): string
    {
        $prompt = "Summarize this email in 3 bullet points: " . strip_tags($body);
        $result = $this->callAiApi($prompt, ['max_tokens' => 100, 'temperature' => 0.5]);

        return $result ?? "AI service unavailable.";
    }

    /**
     * Suggest 3 quick reply options based on email content.
     */
    public function suggestReplies(string $body): array
    {
        $prompt = "Based on this email: \"" . strip_tags($body) . "\", suggest 3 short reply options. 1. Formal 2. Casual 3. Action-oriented.";
        $result = $this->callAiApi($prompt, ['max_tokens' => 150, 'temperature' => 0.7]);

        if ($result) {
            // Clean up and split the output
            $options = preg_split('/\d\./', $result, -1, PREG_SPLIT_NO_EMPTY);
            return array_slice(array_map('trim', $options), 0, 3);
        }

        return [];
    }

    /**
     * Analyze sentiment of the email.
     */
    public function analyzeSentiment(string $body): string
    {
        $prompt = "The sentiment of this email is (Friendly/Urgent/Frustrated/Neutral): " . strip_tags($body);
        $result = $this->callAiApi($prompt, ['max_tokens' => 5, 'temperature' => 0.3]);

        return trim($result ?? "Neutral");
    }
}
