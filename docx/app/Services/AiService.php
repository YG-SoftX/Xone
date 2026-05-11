<?php

namespace App\Services;

use Exception;

class AiService
{
    /**
     * Expand the given text using AI.
     */
    public function expandText(string $text): string
    {
        return $this->callYuga('expand', $text);
    }

    /**
     * Proofread and correct the given text.
     */
    public function proofreadText(string $text): string
    {
        return $this->callYuga('proofread', $text);
    }

    /**
     * Call the local Yuga AI engine.
     */
    protected function callYuga(string $action, string $text): string
    {
        $aiPath = base_path('../yg-ai');
        $brainPath = $aiPath . '/core/Brain.php';

        if (!file_exists($brainPath)) {
            return $text . "\n\n(AI Error: Core not found)";
        }

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
            'expand' => "Expand the following text with more detail and professional tone while keeping the original meaning:\n\n" . $text,
            'proofread' => "Proofread the following text for grammar, spelling, and professional clarity. Return the corrected version only:\n\n" . $text,
            default => $text
        };

        $result = $gen->complete($prompt, ['max_tokens' => 500, 'temperature' => 0.7]);

        return $result['text'] ?? $text;
    }
}
