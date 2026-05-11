<?php
/**
 * Agent — PHP-native agentic loop for Yuga
 *
 * No external LLM. No third-party API. 100% your own model.
 *
 * Architecture:
 *   PHP controls the loop, tool selection, and parameter extraction.
 *   Yuga's SLM (Brain + BM25) handles knowledge retrieval and text generation.
 *
 * How it works:
 *   1. User gives a goal
 *   2. PHP classifies intent → picks a tool (BM25 + rule-based, no LLM)
 *   3. Tool executes (pure PHP)
 *   4. Result feeds back into context
 *   5. Loop repeats until done or max_steps reached
 *   6. Yuga's SLM generates the final response grounded in collected context
 */
class Agent {

    private Brain     $brain;
    private Reasoner  $reasoner;
    private array     $customTools = [];
    private array     $log         = [];

    public int   $max_steps   = 5;
    public float $temperature = 0.7;

    // ── Tool registry: name => keyword description (used for BM25 intent matching)
    private static array $TOOL_DESC = [
        'search_corpus' => 'search find answer question information what who when where why how knowledge',
        'fetch_url'     => 'fetch get load url link http https webpage learn from url address',
        'crawl_site'    => 'crawl scrape entire website all pages sitemap site',
        'learn_text'    => 'learn remember store save add text train knowledge teach',
        'summarize'     => 'summarize summary brief overview condense shorten recap',
        'calculate'     => 'calculate compute math add subtract multiply divide number arithmetic',
        'list_topics'   => 'list topics what do you know subjects categories overview trained',
        'answer'        => 'answer done finished result reply respond final',
    ];

    public function __construct(Brain $brain, ?Reasoner $reasoner = null) {
        $this->brain    = $brain;
        $this->reasoner = $reasoner ?? new Reasoner($brain);
    }

    // ── Register a custom PHP tool ──────────────────────────────────────
    // $fn receives array $params and returns ['text' => '...'] or ['text'=>'...','done'=>true]
    public function registerTool(string $name, string $keywords, callable $fn): void {
        $this->customTools[$name] = $fn;
        static::$TOOL_DESC[$name] = $keywords;
    }

    // ── Run a goal through the agent loop ──────────────────────────────
    public function run(string $goal): array {
        $this->log = [];
        $context   = '';

        for ($step = 1; $step <= $this->max_steps; $step++) {

            // 1. Select tool based on current goal + accumulated context
            $input  = $goal . ($context ? "\n\nContext so far:\n" . $context : '');
            $tool   = $this->selectTool($input);
            $params = $this->extractParams($tool, $goal, $context);

            // 2. Execute tool
            $result = $this->executeTool($tool, $params);

            $this->log[] = [
                'step'   => $step,
                'tool'   => $tool,
                'params' => $params,
                'result' => $result['text'] ?? '',
            ];

            // 3. If tool signals done, stop immediately
            if (!empty($result['done'])) {
                return $this->finish($result['text'] ?? '', $goal);
            }

            // 4. Accumulate context
            $context .= "\n[{$tool}]: " . ($result['text'] ?? '');

            // 5. After first retrieval step, generate final answer
            if ($step >= 2 || $tool === 'search_corpus') {
                $answer = $this->generateAnswer($goal, $context);
                return $this->finish($answer, $goal);
            }
        }

        // Fallback: direct Brain answer
        return $this->finish($this->brain->answer($goal, $this->temperature), $goal);
    }

    // ── Select tool: BM25 keyword match + rule-based overrides ─────────
    private function selectTool(string $input): string {

        // Rule-based overrides (high-confidence patterns)
        if (preg_match('/https?:\/\/\S+/i', $input)) {
            return preg_match('/crawl|entire|all pages|whole site/i', $input)
                ? 'crawl_site'
                : 'fetch_url';
        }

        if (preg_match('/\d[\s]*[+\-*\/][\s]*\d/', $input)) {
            return 'calculate';
        }

        if (preg_match('/what (do you know|topics|have you learned|subjects)/i', $input)) {
            return 'list_topics';
        }

        if (preg_match('/^(summarize|summary of|give me a summary)/i', $input)) {
            return 'summarize';
        }

        if (preg_match('/^(learn|remember|store|save|train on)[:\s]/i', $input)) {
            return 'learn_text';
        }

        // BM25-style keyword scoring against tool descriptions
        $qWords = $this->tokenize($input);
        $scores = [];

        foreach (static::$TOOL_DESC as $tool => $desc) {
            $dWords       = $this->tokenize($desc);
            $dFreq        = array_count_values($dWords);
            $score        = 0.0;
            foreach ($qWords as $w) {
                if (isset($dFreq[$w])) {
                    $score += 1.0 / $dFreq[$w]; // TF-weighted
                }
            }
            $scores[$tool] = $score;
        }

        arsort($scores);
        $top = array_key_first($scores);
        return ($scores[$top] > 0) ? $top : 'search_corpus';
    }

    // ── Extract parameters for each tool from the user's goal ──────────
    private function extractParams(string $tool, string $goal, string $context): array {
        switch ($tool) {

            case 'fetch_url':
            case 'crawl_site':
                preg_match('/https?:\/\/\S+/i', $goal, $m);
                return ['url' => rtrim($m[0] ?? '', '.,;')];

            case 'calculate':
                preg_match('/[\d\s.+\-*\/()]+/', $goal, $m);
                return ['expr' => trim($m[0] ?? '0')];

            case 'summarize':
                return ['text' => $context ?: $goal];

            case 'learn_text':
                // Extract quoted content, or everything after the command word
                if (preg_match('/["\'](.+?)["\']/s', $goal, $m)) {
                    return ['text' => $m[1]];
                }
                $stripped = preg_replace('/^(learn|remember|store|save|train on)[:\s]*/i', '', $goal);
                return ['text' => trim($stripped)];

            default: // search_corpus, answer, list_topics
                return ['query' => $goal];
        }
    }

    // ── Execute the selected tool ───────────────────────────────────────
    private function executeTool(string $tool, array $params): array {

        // Custom registered tool takes priority
        if (isset($this->customTools[$tool])) {
            $result = ($this->customTools[$tool])($params);
            return is_array($result) ? $result : ['text' => (string)$result];
        }

        switch ($tool) {

            case 'search_corpus': {
                $this->reasoner->temperature = $this->temperature;
                $result = $this->reasoner->reason($params['query'] ?? '');
                return [
                    'text'       => $result['answer'],
                    'confidence' => $result['confidence'],
                    'mode'       => $result['mode'],
                    'evidence'   => $result['evidence'],
                ];
            }

            case 'fetch_url': {
                $url = $params['url'] ?? '';
                if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
                    return ['text' => 'No valid URL found in request.'];
                }
                $result = $this->brain->learnFromURL($url);
                if (isset($result['error'])) {
                    return ['text' => 'Could not fetch URL: ' . $result['error']];
                }
                return [
                    'text' => "Fetched and learned from {$url}. "
                            . "Corpus now has " . ($result['sentences'] ?? '?') . " sentences.",
                ];
            }

            case 'crawl_site': {
                $url = $params['url'] ?? '';
                if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
                    return ['text' => 'No valid URL found in request.'];
                }
                $result = $this->brain->learnFromSite($url, 20);
                if (isset($result['error'])) {
                    return ['text' => 'Crawl failed: ' . $result['error']];
                }
                return [
                    'text' => "Crawled " . ($result['pages'] ?? 0) . " pages from {$url}. "
                            . "Learned " . ($result['sentences'] ?? '?') . " sentences.",
                ];
            }

            case 'learn_text': {
                $text = $params['text'] ?? '';
                if (strlen($text) < 10) {
                    return ['text' => 'Not enough text to learn from.'];
                }
                $result = $this->brain->learn($text);
                if (isset($result['error'])) {
                    return ['text' => $result['error']];
                }
                return [
                    'text' => "Learned successfully. Vocab: {$result['vocab']}, sentences: {$result['sentences']}.",
                    'done' => true,
                ];
            }

            case 'summarize': {
                $text = substr($params['text'] ?? '', 0, 400);
                $seed = 'Summary: ' . $text;
                return ['text' => $this->brain->complete($seed, 60, 0.6)];
            }

            case 'calculate': {
                $answer = $this->safeCalc($params['expr'] ?? '0');
                return ['text' => "Result: {$answer}", 'done' => true];
            }

            case 'list_topics': {
                $status = $this->brain->status();
                return [
                    'text' => "Model has {$status['sentences']} sentences in corpus "
                            . "and a vocabulary of {$status['vocab']} words.",
                    'done' => true,
                ];
            }

            default: {
                return ['text' => $this->brain->answer($params['query'] ?? '', $this->temperature)];
            }
        }
    }

    // ── Generate final grounded answer using Reasoner ──────────────────
    private function generateAnswer(string $goal, string $context): string {
        $this->reasoner->temperature = $this->temperature;
        $result = $this->reasoner->reason($goal);
        $answer = $result['answer'];

        // If tool calls gathered extra context (e.g. after fetch_url),
        // and the reasoner answer is weak, supplement with that context
        if ($context && strlen($answer) < 30) {
            $seed   = trim($context) . "\n\nAnswer: ";
            $answer = trim($this->brain->complete(substr($seed, -400), 60, $this->temperature));
        }

        return $answer;
    }

    // ── Wrap final result ──────────────────────────────────────────────
    private function finish(string $answer, string $goal): array {
        return [
            'answer'      => $answer,
            'goal'        => $goal,
            'steps'       => $this->log,
            'total_steps' => count($this->log),
        ];
    }

    // ── Safe calculator: no eval(), recursive descent parser ───────────
    private function safeCalc(string $expr): string {
        $clean = preg_replace('/[^0-9+\-*\/().% ]/', '', $expr);
        if (!trim($clean)) return 'invalid expression';
        try {
            $pos    = 0;
            $result = $this->parseAddSub($clean, $pos);
            return is_finite($result) ? rtrim(rtrim(number_format($result, 8, '.', ''), '0'), '.') : 'overflow';
        } catch (\Throwable $e) {
            return 'calculation error';
        }
    }

    private function parseAddSub(string $e, int &$pos): float {
        $val = $this->parseMulDiv($e, $pos);
        while ($pos < strlen($e)) {
            $this->skipSpaces($e, $pos);
            $op = $e[$pos] ?? '';
            if ($op !== '+' && $op !== '-') break;
            $pos++;
            $right = $this->parseMulDiv($e, $pos);
            $val   = $op === '+' ? $val + $right : $val - $right;
        }
        return $val;
    }

    private function parseMulDiv(string $e, int &$pos): float {
        $val = $this->parseAtom($e, $pos);
        while ($pos < strlen($e)) {
            $this->skipSpaces($e, $pos);
            $op = $e[$pos] ?? '';
            if ($op !== '*' && $op !== '/') break;
            $pos++;
            $right = $this->parseAtom($e, $pos);
            if ($op === '/' && $right == 0) throw new \RuntimeException('division by zero');
            $val = $op === '*' ? $val * $right : $val / $right;
        }
        return $val;
    }

    private function parseAtom(string $e, int &$pos): float {
        $this->skipSpaces($e, $pos);
        if ($pos < strlen($e) && $e[$pos] === '(') {
            $pos++;
            $val = $this->parseAddSub($e, $pos);
            $this->skipSpaces($e, $pos);
            if ($pos < strlen($e) && $e[$pos] === ')') $pos++;
            return $val;
        }
        if ($pos < strlen($e) && $e[$pos] === '-') {
            $pos++;
            return -$this->parseAtom($e, $pos);
        }
        $start = $pos;
        while ($pos < strlen($e) && (ctype_digit($e[$pos]) || $e[$pos] === '.')) $pos++;
        $this->skipSpaces($e, $pos);
        return (float) substr($e, $start, $pos - $start);
    }

    private function skipSpaces(string $e, int &$pos): void {
        while ($pos < strlen($e) && $e[$pos] === ' ') $pos++;
    }

    // ── Tokenizer (mirrors Retriever for consistency) ───────────────────
    private function tokenize(string $text): array {
        $text  = strtolower(preg_replace('/[^a-z0-9\s]/i', ' ', $text));
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $stop  = ['the','a','an','is','are','was','i','me','to','of','in','on',
                  'at','for','and','or','it','this','that','do','you','can'];
        return array_values(array_filter($words, fn($w) => !in_array($w, $stop) && strlen($w) > 1));
    }

    // ── Get execution trace ─────────────────────────────────────────────
    public function getLog(): array { return $this->log; }
}
