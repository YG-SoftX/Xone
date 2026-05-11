<?php
/**
 * LiveRAG — Retrieval-Augmented Generation over the Sovereign Search Index
 *
 * Pipeline (fully self-hosted, no external LLM required):
 *   query
 *     → WebSearch (own SQLite BM25 index, on-demand crawl)
 *     → Pre-indexed content (no redundant HTTP fetches)
 *     → Chunk + BM25 re-rank
 *     → Local Brain synthesis (Reasoner / YugaLM)
 *     → answer + sources + related_questions
 *
 * External LLM backends (Claude / OpenAI / Ollama) are supported as
 * OPTIONAL overrides — set llm_backend in config.php to enable them.
 *
 * Usage:
 *   $rag    = new LiveRAG(new WebSearch($config['web_search'] ?? []), $brain);
 *   $result = $rag->query("What is quantum computing?");
 *   // $result['answer']            → synthesised answer
 *   // $result['sources']           → [{index, title, url, snippet, fetched}]
 *   // $result['related_questions'] → AI-generated follow-up questions
 *   // $result['context']           → raw grounding text
 */
class LiveRAG
{
    private WebSearch $search;
    private ?object   $brain;     // Brain instance — optional

    public int   $max_sources        = 5;
    public int   $max_chars_per_page = 4000;
    public int   $chunk_size         = 350;
    public int   $top_chunks         = 6;
    public int   $fetch_timeout      = 8;

    public function __construct(WebSearch $search, ?object $brain = null)
    {
        $this->search = $search;
        $this->brain  = $brain;
    }

    // ── Main entry: search → retrieve → synthesise ────────────────────
    public function query(string $question, array $opts = []): array
    {
        $limit    = (int)  ($opts['sources']  ?? $this->max_sources);
        $generate = (bool) ($opts['generate'] ?? true);
        $config   = $opts['config'] ?? [];

        // 1. Search own index (WebSearch returns pre-indexed content)
        $results = $this->search->search($question, $limit);
        if (empty($results)) {
            return [
                'ok'                 => false,
                'answer'             => 'No results found in the index yet. Try crawling more pages via the admin panel.',
                'sources'            => [],
                'related_questions'  => [],
                'context'            => '',
                'query'              => $question,
            ];
        }

        // 2. Build chunks — prefer pre-indexed content, fall back to HTTP fetch
        $all_chunks = [];
        $fetched    = [];

        foreach ($results as $idx => $r) {
            // Use content already in the index (avoids redundant HTTP)
            $text = $r['content'] ?? '';

            // Only fetch live if the index entry has no usable text
            if (strlen($text) < 50 && filter_var($r['url'], FILTER_VALIDATE_URL)) {
                $text = $this->fetchPage($r['url']) ?? '';
            }

            if (!$text && $r['snippet']) {
                $text = ($r['title'] ? $r['title'] . '. ' : '') . $r['snippet'];
            }
            if (!$text) continue;

            $text        = mb_substr($text, 0, $this->max_chars_per_page);
            $fetched[$idx] = true;

            foreach ($this->chunk($text) as $c) {
                $all_chunks[] = ['text' => $c, 'source' => $r, 'idx' => $idx + 1];
            }
        }

        // Fallback: snippet-only chunks when no content was available
        if (empty($all_chunks)) {
            foreach ($results as $idx => $r) {
                if ($r['snippet']) {
                    $all_chunks[] = [
                        'text'   => ($r['title'] ? $r['title'] . '. ' : '') . $r['snippet'],
                        'source' => $r,
                        'idx'    => $idx + 1,
                    ];
                }
            }
        }

        // 3. BM25 re-rank chunks against the query
        $scored = $this->rankChunks($all_chunks, $question);
        $top    = array_slice($scored, 0, $this->top_chunks);

        // 4. Build context + sources list
        $context = $this->buildContext($top);
        $sources = $this->buildSources($results, $fetched);

        // 5. Generate answer
        // Priority: Own Brain (Reasoner/YugaLM) → External LLM (opt-in) → Extractive
        $answer = '';
        if ($generate && $context) {
            // 5a. Local Brain — no external API required
            if ($this->brain) {
                $answer = $this->synthesiseWithBrain($question, $top);
            }

            // 5b. Optional external LLM override (only if configured)
            if (!$answer) {
                $backend = $config['llm_backend'] ?? 'none';
                if ($backend !== 'none') {
                    $answer = $this->synthesiseExternal($question, $context, $config);
                }
            }
        }

        // 5c. Extractive fallback (always works, no model needed)
        if (!$answer) {
            $answer = $this->extractiveAnswer($question, $top);
        }

        // 6. Generate related questions using own AI
        $related = $this->generateRelatedQuestions($question, $top, $this->brain);

        return [
            'ok'                => true,
            'answer'            => $answer,
            'sources'           => $sources,
            'related_questions' => $related,
            'context'           => $context,
            'query'             => $question,
        ];
    }

    // ── Synthesise answer using own Brain/Reasoner ────────────────────
    private function synthesiseWithBrain(string $question, array $chunks): string
    {
        if (!$this->brain) return '';

        // If the Brain has no trained corpus yet, skip generation
        try {
            if (method_exists($this->brain, 'corpusSize') && $this->brain->corpusSize() === 0) {
                // Fall through to extractive
                return '';
            }
        } catch (\Throwable $e) {
            return '';
        }

        // Build a query that combines the question with top-chunk keywords
        // so the Brain's BM25 retrieval is guided by the web evidence.
        $topText = implode(' ', array_column(array_slice($chunks, 0, 3), 'text'));
        $keyTerms = $this->extractKeyTerms($topText, 6);
        $enriched = $question . ' ' . implode(' ', $keyTerms);

        try {
            $answer = $this->brain->answer($enriched, 0.68);
        } catch (\Throwable $e) {
            return '';
        }

        // Reject generic "haven't learned anything yet" replies
        if (str_contains($answer, "haven't learned") || str_contains($answer, "don't have specific")) {
            return '';
        }

        return $answer;
    }

    // ── Generate AI-powered related questions ─────────────────────────
    private function generateRelatedQuestions(
        string $question,
        array  $chunks,
        ?object $brain
    ): array {
        $terms   = $this->extractKeyTerms($question, 4);
        $topText = implode(' ', array_column(array_slice($chunks, 0, 3), 'text'));
        $ctxTerms = $this->extractKeyTerms($topText, 6);
        $all = array_unique(array_merge($terms, $ctxTerms));

        // Base entity = first 2 meaningful terms
        $entity = implode(' ', array_slice($all, 0, 2));
        if (!$entity) $entity = $question;

        // Rule-based templates (reliable even without a trained model)
        $templates = [
            'How does {entity} work?',
            'What are the benefits of {entity}?',
            'What is the history of {entity}?',
            'How is {entity} different from alternatives?',
            'What are common uses of {entity}?',
            'What are the latest developments in {entity}?',
        ];

        // Pick 3 templates that don't duplicate the original question
        $qLow  = strtolower($question);
        $out   = [];
        foreach ($templates as $tpl) {
            $q = str_replace('{entity}', ucfirst($entity), $tpl);
            if (strtolower($q) !== $qLow) {
                $out[] = $q;
            }
            if (count($out) >= 3) break;
        }

        // If Brain is trained, try to generate one more personalised question
        if ($brain && count($all) >= 2) {
            try {
                $seed   = 'Questions related to ' . $question . ':';
                $gen    = '';
                if (method_exists($brain, 'answer')) {
                    $gen = $brain->answer('What else should someone know about ' . $entity . '?', 0.8);
                }
                if ($gen && !str_contains($gen, "haven't learned") && strlen($gen) > 10) {
                    // Clean it into a question form
                    $gen = ucfirst(trim(preg_replace('/^(what|how|why|is|are|can|does)\s+/i', '', $gen)));
                    if ($gen && !in_array($gen, $out, true)) {
                        array_unshift($out, $gen . (str_ends_with($gen, '?') ? '' : '?'));
                        $out = array_slice($out, 0, 3);
                    }
                }
            } catch (\Throwable $e) {
                // non-fatal
            }
        }

        return $out;
    }

    // ── Extract top keywords from text ────────────────────────────────
    private function extractKeyTerms(string $text, int $n = 5): array
    {
        $stop = [
            'the','a','an','and','or','but','in','on','at','to','for','of','with',
            'is','are','was','were','be','been','have','has','had','do','does','did',
            'will','would','could','should','may','might','can','it','its','this',
            'that','these','those','not','no','so','if','then','than','more','also',
            'just','up','about','into','over','after','all','what','how','when',
            'where','who','which','i','you','he','she','we','they','as','by','from',
        ];

        $words = preg_split('/\W+/', strtolower($text)) ?: [];
        $freq  = [];
        foreach ($words as $w) {
            if (strlen($w) < 3 || in_array($w, $stop, true)) continue;
            $freq[$w] = ($freq[$w] ?? 0) + 1;
        }
        arsort($freq);
        return array_slice(array_keys($freq), 0, $n);
    }

    // ── Optional external LLM synthesis (Claude / OpenAI / Ollama) ───
    private function synthesiseExternal(string $q, string $context, array $cfg): string
    {
        $backend = $cfg['llm_backend'] ?? '';
        $prompt  = $this->buildPrompt($q, $context);

        if ($backend === 'claude' && !empty($cfg['anthropic_api_key'])) {
            return $this->callClaude($prompt, $cfg['anthropic_api_key']);
        }
        if ($backend === 'openai' && !empty($cfg['openai_api_key'])) {
            return $this->callOpenAI($prompt, $cfg['openai_api_key']);
        }
        if ($backend === 'ollama' && !empty($cfg['ollama_url'])) {
            return $this->callOllama($prompt, $cfg['ollama_url'], $cfg['ollama_model'] ?? 'llama3.2');
        }
        return '';
    }

    private function callClaude(string $prompt, string $key): string
    {
        $data = [
            'model'      => 'claude-3-haiku-20240307',
            'max_tokens' => 1024,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ];
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $key,
                'anthropic-version: 2023-06-01',
            ],
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        $d = json_decode($res, true);
        return $d['content'][0]['text'] ?? '';
    }

    private function callOpenAI(string $prompt, string $key): string
    {
        $data = [
            'model'    => 'gpt-3.5-turbo',
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ];
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $key,
            ],
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        $d = json_decode($res, true);
        return $d['choices'][0]['message']['content'] ?? '';
    }

    private function callOllama(string $prompt, string $url, string $model): string
    {
        $data = ['model' => $model, 'prompt' => $prompt, 'stream' => false];
        $ch   = curl_init(rtrim($url, '/') . '/api/generate');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        $d = json_decode($res, true);
        return $d['response'] ?? '';
    }

    // ── Fetch a live web page (only when content not in index) ────────
    private function fetchPage(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $this->fetch_timeout,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_ENCODING       => 'gzip',
                CURLOPT_USERAGENT      => 'YugaBot/1.0 (+https://ygxone.com/bot)',
            ]);
            $html = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (!$html || $code >= 400) return null;
        } else {
            $ctx  = stream_context_create(['http' => [
                'timeout'    => $this->fetch_timeout,
                'user_agent' => 'YugaBot/1.0',
            ]]);
            $html = @file_get_contents($url, false, $ctx);
            if (!$html) return null;
        }
        return $this->htmlToText($html);
    }

    // ── HTML → clean plain text ───────────────────────────────────────
    private function htmlToText(string $html): string
    {
        $html = preg_replace(
            '/<(script|style|nav|footer|header|aside|form)[^>]*>.*?<\/\1>/is',
            '', $html
        );
        $html = preg_replace(
            '/<(p|div|h[1-6]|li|br|tr|article|section)[^>]*>/i',
            "\n", $html
        );
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return mb_substr(trim($text), 0, $this->max_chars_per_page);
    }

    // ── Split text into sentence-aware chunks ─────────────────────────
    private function chunk(string $text): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text) ?: [$text];
        $chunks    = [];
        $buf       = '';

        foreach ($sentences as $s) {
            $s = trim($s);
            if (!$s) continue;
            if (strlen($buf) + strlen($s) > $this->chunk_size) {
                if ($buf) $chunks[] = trim($buf);
                $buf = $s;
            } else {
                $buf .= ($buf ? ' ' : '') . $s;
            }
        }
        if ($buf) $chunks[] = trim($buf);

        return array_values(array_filter($chunks, fn($c) => strlen($c) >= 30));
    }

    // ── BM25 re-ranking of chunks against query ───────────────────────
    private function rankChunks(array $chunks, string $query): array
    {
        $terms = array_unique(array_filter(
            preg_split('/\W+/', strtolower($query)) ?: [],
            fn($t) => strlen($t) > 2
        ));

        if (empty($terms)) return $chunks;

        $all_text = strtolower(implode(' ', array_column($chunks, 'text')));
        $avgLen   = array_sum(array_map(fn($c) => str_word_count($c['text']), $chunks))
                  / max(1, count($chunks));
        $k1 = 1.5;
        $b  = 0.75;

        foreach ($chunks as &$c) {
            $doc   = strtolower($c['text']);
            $dl    = max(str_word_count($doc), 1);
            $score = 0.0;
            foreach ($terms as $term) {
                $tf = substr_count($doc, $term);
                if ($tf === 0) continue;
                $df    = max(1, substr_count($all_text, $term));
                $idf   = log(1 + count($chunks) / $df);
                $score += $idf * (($tf * ($k1 + 1))
                        / ($tf + $k1 * (1 - $b + $b * $dl / max(1, $avgLen))));
            }
            $c['score'] = $score;
        }
        unset($c);

        usort($chunks, fn($a, $b) => $b['score'] <=> $a['score']);
        return $chunks;
    }

    // ── Build citation-numbered context string ────────────────────────
    private function buildContext(array $chunks): string
    {
        $parts = [];
        foreach ($chunks as $c) {
            $parts[] = "[{$c['idx']}] " . $c['text'];
        }
        return implode("\n\n", $parts);
    }

    // ── Build sources list ────────────────────────────────────────────
    private function buildSources(array $results, array $fetched): array
    {
        $sources = [];
        foreach ($results as $i => $r) {
            $sources[] = [
                'index'   => $i + 1,
                'title'   => $r['title'],
                'url'     => $r['url'],
                'snippet' => $r['snippet'],
                'domain'  => $r['domain'] ?? '',
                'score'   => $r['score']  ?? 0.0,
                'fetched' => isset($fetched[$i]),
            ];
        }
        return $sources;
    }

    // ── RAG prompt template (for external LLMs) ───────────────────────
    private function buildPrompt(string $question, string $context): string
    {
        return "Answer the following question using only the search results below. "
             . "Be concise and accurate. Cite sources as [1], [2], etc. "
             . "If the answer is not in the results, say so.\n\n"
             . "Search results:\n" . $context . "\n\n"
             . "Question: " . $question . "\n\nAnswer:";
    }

    // ── Extractive fallback — pull best sentence from top chunks ──────
    private function extractiveAnswer(string $question, array $chunks): string
    {
        if (empty($chunks)) return 'No relevant results found in the index.';

        $terms = array_unique(array_filter(
            preg_split('/\W+/', strtolower($question)) ?: [],
            fn($t) => strlen($t) > 3
        ));

        $best_score = -1;
        $best_sent  = '';

        foreach (array_slice($chunks, 0, 4) as $c) {
            foreach (preg_split('/(?<=[.!?])\s+/', $c['text']) ?: [$c['text']] as $s) {
                $score = 0;
                foreach ($terms as $t) {
                    if (str_contains(strtolower($s), $t)) $score++;
                }
                if ($score > $best_score && strlen($s) > 20) {
                    $best_score = $score;
                    $best_sent  = $s;
                }
            }
        }

        // Combine best sentence + supporting sentence for a fuller answer
        $answer = $best_sent ?: $chunks[0]['text'];
        if (!$best_sent && isset($chunks[1])) {
            $extra = $chunks[1]['text'];
            if ($extra !== $answer) {
                $answer = rtrim($answer, '. ') . '. ' . $extra;
            }
        }

        $src = $chunks[0]['source'] ?? [];
        $ref = !empty($src['url']) ? " [1] — " . ($src['title'] ?: $src['url']) : '';
        return $answer . $ref;
    }
}
