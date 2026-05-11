<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NeuralSummaryService
{
    /**
     * Summarize a collection of chat messages using YG AI Node
     */
    public static function summarize(array $messages): array
    {
        // Sanitize each message body before building the transcript
        // to prevent prompt injection from user-controlled content
        $transcript = collect($messages)->map(function ($msg) {
            $name = strip_tags($msg['sender']['name'] ?? 'Unknown');
            $body = strip_tags($msg['body'] ?? '');
            $body = mb_substr($body, 0, 500); // cap per message
            return "[{$name}]: {$body}";
        })->implode("\n");

        // Cap total transcript length
        $transcript = mb_substr($transcript, 0, 8000);

        $prompt = "Analyze the following chat transcript and extract:\n"
            . "1. A concise 2-3 sentence summary.\n"
            . "2. Up to 3 key decisions made.\n"
            . "3. Up to 3 action items with responsible party (if mentioned).\n"
            . "Respond ONLY in JSON with keys: summary, decisions (array), action_items (array).\n\n"
            . "Transcript:\n{$transcript}";

        try {
            $response = Http::timeout(30)->post(
                config('services.yg_ai.endpoint', 'http://yg-ai.ygxone.com/api/v1/chat'),
                [
                    'model'      => 'neural-sovereign-v1',
                    'prompt'     => $prompt,
                    'api_key'    => config('services.yg_ai.key'),
                    'max_tokens' => 500,
                ]
            );

            if ($response->successful()) {
                $data   = $response->json();
                $parsed = json_decode($data['response'] ?? '', true);
                if (is_array($parsed)
                    && isset($parsed['summary'])
                    && isset($parsed['decisions'])
                    && isset($parsed['action_items'])) {
                    return $parsed;
                }
            }
        } catch (\Exception $e) {
            // Graceful fallback if AI node is offline
        }

        return self::fallbackSummary($messages);
    }

    /**
     * Fallback summary when AI node is offline
     */
    private static function fallbackSummary(array $messages): array
    {
        $count    = count($messages);
        $senders  = collect($messages)->pluck('sender.name')->unique()->implode(', ');

        return [
            'summary'      => "This conversation contains {$count} messages between {$senders}.",
            'decisions'    => [],
            'action_items' => [],
        ];
    }
}
