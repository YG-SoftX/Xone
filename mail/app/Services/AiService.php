<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AiService
{
    private $brain;
    private $textGenerator;

    public function __construct()
    {
        $this->initializeAi();
    }

    private function initializeAi()
    {
        $aiPath = base_path('../yg-ai/core');
        
        // Include core AI files from yg-ai
        require_once $aiPath . '/Transformer.php';
        require_once $aiPath . '/Tokenizer.php';
        require_once $aiPath . '/Retriever.php';
        require_once $aiPath . '/ModelStore.php';
        require_once $aiPath . '/Brain.php';
        require_once $aiPath . '/TextGenerator.php';

        try {
            $store = new \ModelStore(base_path('../yg-ai/data/models'));
            $this->brain = new \Brain('mail_ai', $store);
            $this->textGenerator = new \TextGenerator($this->brain);
        } catch (\Exception $e) {
            Log::error('YG-AI Initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Summarize an email body into 3 concise bullet points.
     */
    public function summarize(string $body): string
    {
        if (!$this->textGenerator) return "AI service unavailable.";

        $prompt = "Summarize this email in 3 bullet points: " . strip_tags($body);
        $result = $this->textGenerator->complete($prompt, ['max_tokens' => 100, 'temperature' => 0.5]);
        
        return $result['text'] ?? "Unable to summarize.";
    }

    /**
     * Suggest 3 quick reply options based on email content.
     */
    public function suggestReplies(string $body): array
    {
        if (!$this->textGenerator) return [];

        $prompt = "Based on this email: \"" . strip_tags($body) . "\", suggest 3 short reply options. 1. Formal 2. Casual 3. Action-oriented.";
        $result = $this->textGenerator->bestOf($prompt, 3, ['max_tokens' => 80]);

        // Clean up and split the AI output
        $text = $result['best'] ?? '';
        $options = preg_split('/\d\./', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        return array_slice(array_map('trim', $options), 0, 3);
    }

    /**
     * Analyze sentiment of the email.
     */
    public function analyzeSentiment(string $body): string
    {
        if (!$this->textGenerator) return "Neutral";

        $prompt = "The sentiment of this email is (Friendly/Urgent/Frustrated/Neutral): " . strip_tags($body);
        $result = $this->textGenerator->complete($prompt, ['max_tokens' => 5]);
        
        return trim($result['text'] ?? "Neutral");
    }
}
