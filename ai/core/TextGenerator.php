<?php
/**
 * TextGenerator — Higher-quality text generation for Yuga
 *
 * Wraps Brain's SLM with smarter generation strategies.
 * No external LLM. Works 100% on Yuga's own trained weights.
 *
 * Generation modes:
 *   complete($prompt)   — open-ended continuation of any text
 *   expand($topic)      — retrieves corpus context, then generates a paragraph
 *   bestOf($prompt, $n) — generates N candidates, returns the best one
 *   fill($template)     — fills [BLANK] slots in a template string
 *
 * All modes support top-p (nucleus) sampling + repetition penalty
 * for dramatically better output than plain temperature sampling.
 *
 * Memory presets (set via Brain or pass custom Transformer):
 *   micro  ~65K params  cPanel 128MB
 *   small  ~325K params cPanel 128MB
 *   medium ~1.4M params VPS   512MB
 *   large  ~6M params   Server 2GB
 */
class TextGenerator {

    private Brain $brain;

    // ── Generation defaults (override per call via $opts) ──────────────
    public float $temperature  = 0.8;
    public float $topP         = 0.9;   // nucleus sampling (0.9 recommended)
    public float $repPenalty   = 1.5;   // repetition penalty
    public int   $maxTokens    = 80;    // max tokens to generate
    public int   $candidates   = 3;     // candidates for bestOf mode

    public function __construct(Brain $brain) {
        $this->brain = $brain;
    }

    // ── Mode 1: complete ───────────────────────────────────────────────
    // Pure autoregressive continuation. Generates directly from the seed.
    // Best for: text completion, sentence continuation, creative writing.
    public function complete(string $prompt, array $opts = []): array {
        [$temp, $topP, $rep, $maxTok] = $this->opts($opts);

        $text = $this->brain->complete($prompt, $maxTok, $temp, $topP, $rep);

        return [
            'mode'   => 'complete',
            'prompt' => $prompt,
            'text'   => $text,
            'full'   => trim($prompt . ' ' . $text),
        ];
    }

    // ── Mode 2: expand ────────────────────────────────────────────────
    // Retrieves relevant corpus context (BM25), injects it as seed,
    // then generates an expanded paragraph grounded in that context.
    // Best for: topic expansion, knowledge-grounded paragraphs.
    public function expand(string $topic, array $opts = []): array {
        [$temp, $topP, $rep, $maxTok] = $this->opts($opts);

        // Lower temperature for factual expansion
        $temp = min($temp, 0.65);

        // Retrieve top context sentences from corpus
        $hits    = $this->brain->retrieve($topic, 3);
        $context = implode(' ', array_column($hits, 'text'));

        if (empty($context)) {
            // No corpus knowledge — fall back to pure generation
            $text = $this->brain->complete($topic, $maxTok, $temp, $topP, $rep);
            return [
                'mode'       => 'expand',
                'topic'      => $topic,
                'text'       => $text,
                'confidence' => 0.0,
                'grounded'   => false,
            ];
        }

        // Seed generation with retrieved context
        $seed = $context;
        $text = $this->brain->complete($seed, $maxTok, $temp, $topP, $rep);

        // Trim seed from output if it was echoed back
        $full = trim($context . ' ' . $text);
        $full = $this->deduplicateSentences($full);

        return [
            'mode'       => 'expand',
            'topic'      => $topic,
            'text'       => $full,
            'confidence' => round($hits[0]['score'] ?? 0.0, 3),
            'grounded'   => true,
            'sources'    => count($hits),
        ];
    }

    // ── Mode 3: bestOf ────────────────────────────────────────────────
    // Generates $n candidates at slightly varying temperatures,
    // then picks the best one by scoring (length + diversity + fluency).
    // Best for: when you want the highest quality single output.
    public function bestOf(string $prompt, ?int $n = null, array $opts = []): array {
        [$temp, $topP, $rep, $maxTok] = $this->opts($opts);
        $n = $n ?? $this->candidates;

        $candidates  = [];
        $baseTemp    = $temp;

        for ($i = 0; $i < $n; $i++) {
            // Vary temperature slightly per candidate for diversity
            $t    = $baseTemp + ($i * 0.08) - ($n * 0.04);
            $t    = max(0.3, min(1.2, $t));
            $text = $this->brain->complete($prompt, $maxTok, $t, $topP, $rep);
            $candidates[] = [
                'text'  => $text,
                'score' => $this->scoreCandidate($text, $prompt),
                'temp'  => round($t, 2),
            ];
        }

        // Sort by score descending
        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

        return [
            'mode'       => 'best_of',
            'prompt'     => $prompt,
            'best'       => $candidates[0]['text'],
            'full'       => trim($prompt . ' ' . $candidates[0]['text']),
            'candidates' => $candidates,
            'n'          => $n,
        ];
    }

    // ── Mode 4: fill ─────────────────────────────────────────────────
    // Fills [BLANK] placeholders in a template string.
    // Each [BLANK] gets its own generation call seeded by surrounding text.
    // Best for: structured content generation, form filling, templates.
    //
    // Example:
    //   fill("Our product [BLANK] and it costs [BLANK].")
    //   → "Our product helps businesses automate support and it costs $29/month."
    public function fill(string $template, array $opts = []): array {
        [$temp, $topP, $rep, $maxTok] = $this->opts($opts);

        $filled  = $template;
        $blanks  = [];
        $count   = substr_count($template, '[BLANK]');

        for ($i = 0; $i < $count; $i++) {
            $pos     = strpos($filled, '[BLANK]');
            if ($pos === false) break;

            // Seed = everything before this blank (last 100 chars)
            $seed   = trim(substr($filled, 0, $pos));
            $seed   = substr($seed, -100);

            // Use low max tokens — we just need a word or short phrase
            $insert = $this->brain->complete($seed, min($maxTok, 12), $temp, $topP, $rep);
            $insert = $this->extractFirstPhrase($insert);

            $blanks[]      = $insert;
            $filled        = substr_replace($filled, $insert, $pos, strlen('[BLANK]'));
        }

        return [
            'mode'     => 'fill',
            'template' => $template,
            'filled'   => $filled,
            'blanks'   => $blanks,
        ];
    }

    // ── Scoring: picks the best candidate in bestOf ────────────────────
    // Score = length bonus + diversity (unique words ratio) + fluency (ends cleanly)
    private function scoreCandidate(string $text, string $prompt): float {
        if (!$text) return 0.0;

        $words   = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $unique  = count(array_unique(array_map('strtolower', $words)));
        $total   = count($words) ?: 1;

        // Length bonus: reward 10–40 word outputs
        $lenScore = min($total / 20, 1.5);

        // Diversity bonus: penalise repetitive outputs
        $divScore = $unique / $total;

        // Fluency bonus: ends with punctuation
        $fluency  = in_array(substr(trim($text), -1), ['.', '!', '?']) ? 0.3 : 0.0;

        // Penalise if text starts by echoing the prompt
        $promptWords = preg_split('/\s+/', strtolower(trim($prompt)), -1, PREG_SPLIT_NO_EMPTY);
        $textStart   = array_slice(preg_split('/\s+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 5);
        $overlap     = count(array_intersect($promptWords, $textStart));
        $echopenalty = $overlap > 3 ? 0.5 : 0.0;

        return round($lenScore + $divScore + $fluency - $echopenalty, 4);
    }

    // ── Remove duplicate sentences from generated text ─────────────────
    private function deduplicateSentences(string $text): string {
        $sents = preg_split('/(?<=[.!?])\s+/', trim($text));
        $seen  = [];
        $out   = [];
        foreach ($sents as $s) {
            $key = strtolower(substr(trim($s), 0, 40));
            if ($key && !isset($seen[$key])) {
                $seen[$key] = true;
                $out[]      = $s;
            }
        }
        return implode(' ', array_slice($out, 0, 5)); // max 5 sentences
    }

    // ── Extract first phrase/clause from generated text ─────────────────
    // Used by fill() to get a compact insert for [BLANK] slots
    private function extractFirstPhrase(string $text): string {
        $text = trim($text);
        if (!$text) return 'something';

        // Stop at punctuation or conjunction
        preg_match('/^[^.!?,;]+/', $text, $m);
        $phrase = trim($m[0] ?? $text);

        // Limit to 5 words
        $words = preg_split('/\s+/', $phrase, -1, PREG_SPLIT_NO_EMPTY);
        return implode(' ', array_slice($words, 0, 5));
    }

    // ── Parse options array ────────────────────────────────────────────
    private function opts(array $opts): array {
        return [
            (float)($opts['temperature']   ?? $this->temperature),
            (float)($opts['top_p']         ?? $this->topP),
            (float)($opts['rep_penalty']   ?? $this->repPenalty),
            (int)  ($opts['max_tokens']    ?? $this->maxTokens),
        ];
    }
}
