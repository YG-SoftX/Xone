<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NoteAiService
{
    /**
     * Summarize a note using YG AI Node
     */
    public static function summarize(string $content): string
    {
        $prompt = "Summarize the following note in 2-3 concise sentences. Be factual and clear:\n\n{$content}";
        return self::callAi($prompt) ?? substr(strip_tags($content), 0, 200) . '...';
    }

    /**
     * Suggest tags for a note based on its content
     */
    public static function suggestTags(string $content): array
    {
        $prompt = "Analyze the following note and suggest 3-5 relevant single-word tags. Return only a comma-separated list:\n\n{$content}";
        $result = self::callAi($prompt);
        if (!$result) return [];
        return array_map('trim', explode(',', $result));
    }

    /**
     * Convert note to a publishable blog post format
     */
    public static function prepareForJournal(string $title, string $content): array
    {
        $prompt = "Transform the following private note into a professional, engaging blog post. 
        Return a JSON object with keys: title (string), excerpt (string, max 160 chars), body (HTML string).
        
        Original title: {$title}
        Content: {$content}";

        $result = self::callAi($prompt);
        if (!$result) {
            return [
                'title'   => $title,
                'excerpt' => substr(strip_tags($content), 0, 160),
                'body'    => "<p>{$content}</p>",
            ];
        }

        return json_decode($result, true) ?? [
            'title'   => $title,
            'excerpt' => substr(strip_tags($content), 0, 160),
            'body'    => "<p>{$content}</p>",
        ];
    }

    private static function callAi(string $prompt): ?string
    {
        try {
            $response = Http::timeout(20)->post(config('services.yg_ai.endpoint', 'http://yg-ai.ygxone.com/api/v1/chat'), [
                'prompt'  => $prompt,
                'api_key' => config('services.yg_ai.key'),
                'max_tokens' => 600,
            ]);
            if ($response->successful()) {
                return $response->json('response');
            }
        } catch (\Exception $e) {}
        return null;
    }
}
