<?php

namespace App\Services;

use Exception;

class AiService
{
    /**
     * Summarize the content of a file.
     */
    public function summarizeFile(string $content): string
    {
        if (empty($content)) {
            return "File is empty or could not be read.";
        }

        try {
            return $this->callYuga('summarize', $content);
        } catch (Exception $e) {
            return "AI Error: " . $e->getMessage();
        }
    }

    /**
     * Call the local Yuga AI engine.
     */
    protected function callYuga(string $action, string $text): string
    {
        // Path to the yg-ai core
        $aiPath = base_path('../yg-ai');
        $brainPath = $aiPath . '/core/Brain.php';
        $storePath = $aiPath . '/core/ModelStore.php';

        if (!file_exists($brainPath)) {
            return "AI Core not found at $aiPath. Ensure yg-ai is installed.";
        }

        // Include the local core classes
        require_once $aiPath . '/core/YugaLM.php';
        require_once $aiPath . '/core/ModelStore.php';
        require_once $aiPath . '/core/Tokenizer.php';
        require_once $aiPath . '/core/Transformer.php';
        require_once $aiPath . '/core/Retriever.php';
        require_once $aiPath . '/core/Brain.php';
        require_once $aiPath . '/core/TextGenerator.php';

        $store = new \ModelStore($aiPath . '/data');
        $brain = new \Brain('default', $store);
        $gen = new \TextGenerator($brain);

        $prompt = match ($action) {
            'summarize' => "Summarize the following document content into 3 key bullet points:\n\n" . mb_substr($text, 0, 2000),
            default => $text
        };

        $result = $gen->complete($prompt, ['max_tokens' => 150, 'temperature' => 0.5]);

        return $result['text'] ?? "Unable to generate intelligence.";
    }
}
