<?php
class Brain
{
    private Transformer $lm;
    private Tokenizer $tok;
    private Retriever $ret;
    private ModelStore $store;
    private string $name;

    public function __construct(string $name, ModelStore $store)
    {
        $this->name = $name;
        $this->store = $store;
        $this->loadAll();
    }

    /**
     * Context-aware chat — uses Memory to enrich the BM25 retrieval query.
     *
     * The SLM itself is too small to process conversation history, but the
     * retrieval query can be enriched with key terms from previous turns.
     * This makes follow-up questions ("what about it?", "and the price?")
     * retrieve the right corpus sentences by inheriting context from history.
     *
     * Flow:
     *   1. Load last N turns from Memory
     *   2. Extract key terms from recent context
     *   3. If current question is ambiguous, append those terms to BM25 query
     *   4. Run answer() with the enriched query
     *   5. Persist both turns to Memory
     */
    public function chat(
        string $question,
        string $sessionId,
        Memory $memory,
        float $temp = 0.72
    ): array {
        // 1. Load recent history (last 3 turns = 6 messages)
        $history = $memory->getHistory($sessionId, 6);

        // 2. Build enriched retrieval query
        $enriched = $this->enrichQuery($question, $history);

        // 3. Save user message before answering
        $memory->addMessage($sessionId, 'user', $question);

        // 4. Answer using enriched query for retrieval, original question for display
        $answer = $this->answerEnriched($enriched, $question, $temp);

        // 5. Persist assistant response
        $memory->addMessage($sessionId, 'assistant', $answer);

        return [
            'answer' => $answer,
            'session_id' => $sessionId,
            'enriched_query' => $enriched !== $question ? $enriched : null,
            'turns' => intdiv(count($history), 2) + 1,
        ];
    }

    /**
     * Enrich the BM25 query using key terms from conversation history.
     * Short/ambiguous questions ("what about it?", "and the price?") inherit
     * topic context from the previous assistant response.
     */
    private function enrichQuery(string $question, array $history): string
    {
        // If question is long and specific, don't touch it
        $words = preg_split('/\s+/', trim($question), -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) >= 6)
            return $question;

        // Check for clear pronoun/reference ambiguity
        $ambiguous = preg_match(
            '/^(what about|tell me more|and the|is it|how about|what is it|more about|'
            . 'does it|can it|will it|that|this|those|these)\b/i',
            trim($question)
        );

        if (!$ambiguous && count($words) >= 4)
            return $question;

        // Extract key terms from the last assistant response
        $contextTerms = [];
        foreach (array_reverse($history) as $msg) {
            if ($msg['role'] === 'assistant') {
                $contextTerms = $this->keyTerms($msg['content']);
                break;
            }
        }

        // Also grab subject terms from the last user message
        foreach (array_reverse($history) as $msg) {
            if ($msg['role'] === 'user' && $msg['content'] !== $question) {
                $contextTerms = array_unique(
                    array_merge($contextTerms, $this->keyTerms($msg['content']))
                );
                break;
            }
        }

        if (empty($contextTerms))
            return $question;

        // Append up to 4 context terms that aren't already in the question
        $qTerms = $this->keyTerms($question);
        $newTerms = array_diff(array_slice($contextTerms, 0, 6), $qTerms);
        $append = implode(' ', array_slice(array_values($newTerms), 0, 4));

        return $append ? $question . ' ' . $append : $question;
    }

    /**
     * answer() variant that uses an enriched query for BM25 but keeps
     * the original question visible in generation seed for coherence.
     */
    private function answerEnriched(string $enriched, string $original, float $temp): string
    {
        if (!$this->lm->ready || $this->ret->N === 0)
            return "I haven't learned anything yet. Please run the self-learning crawler first.";

        // Retrieve using enriched query (context-aware)
        $hits = $this->ret->query($enriched, 3);

        // Fall back to original question if enriched found nothing
        if (empty($hits)) {
            $hits = $this->ret->query($original, 3);
        }

        if (empty($hits))
            return "I don't have specific information about that yet.";

        $seed = $hits[0]['text'];
        if ($hits[0]['score'] > 3.5)
            return $this->polish($seed);

        $generated = $this->lm->generate($this->tok, $seed, 35, $temp, true);
        $answer = trim($seed . ' ' . $generated);

        if (isset($hits[1]) && $hits[1]['score'] > 1.8 && strlen($answer) < 140) {
            $extra = trim($hits[1]['text']);
            if ($extra !== $seed)
                $answer = rtrim($answer, '. ') . '. ' . $extra;
        }

        return $this->polish($answer);
    }

    /** Extract meaningful terms from text (used for query enrichment). */
    private function keyTerms(string $text): array
    {
        $text = strtolower(preg_replace('/[^a-z0-9\s]/i', ' ', $text));
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $stop = [
            'the',
            'a',
            'an',
            'is',
            'are',
            'was',
            'were',
            'be',
            'been',
            'have',
            'has',
            'had',
            'do',
            'does',
            'did',
            'will',
            'would',
            'could',
            'should',
            'may',
            'might',
            'can',
            'to',
            'of',
            'in',
            'on',
            'at',
            'by',
            'for',
            'with',
            'and',
            'or',
            'but',
            'not',
            'it',
            'this',
            'that',
            'i',
            'we',
            'you',
            'he',
            'she',
            'they',
            'what',
            'how',
            'who',
            'when',
            'where',
            'why',
            'about',
            'just',
            'also',
            'more',
            'tell',
            'me',
        ];
        $filtered = array_filter($words, fn($w) => !in_array($w, $stop) && strlen($w) > 2);
        return array_values(array_unique($filtered));
    }

    public function answer(string $q, float $temp = 0.72): string
    {
        if (!$this->lm->ready || $this->ret->N === 0)
            return "I haven't learned anything yet. Please run the self-learning crawler first.";

        $hits = $this->ret->query($q, 5);
        if (empty($hits))
            return "I don't have specific information about that yet.";

        // High-confidence direct retrieval — no generation needed
        if ($hits[0]['score'] > 3.5)
            return $this->polish($hits[0]['text']);

        // Filter hits to those with meaningful score
        $good = array_filter($hits, fn($h) => $h['score'] > 1.0);
        if (empty($good))
            return $this->polish($hits[0]['text']);

        $seed = $hits[0]['text'];

        // Only generate if the seed is short and score is moderate
        if ($hits[0]['score'] > 2.5 || strlen($seed) > 120) {
            // Seed is good enough — just combine top hits
            $answer = $seed;
            foreach (array_slice(array_values($good), 1, 2) as $h) {
                $extra = trim($h['text']);
                if ($extra !== $seed && strlen($answer) < 200) {
                    $answer = rtrim($answer, '. ') . '. ' . $extra;
                }
            }
            return $this->polish($answer);
        }

        // Generate to extend short/low-confidence seeds
        $generated = $this->lm->generate($this->tok, $seed, 35, $temp, true);
        $answer = trim($seed . ' ' . $generated);

        // Append second hit if answer is still short
        if (isset($hits[1]) && $hits[1]['score'] > 1.5 && strlen($answer) < 160) {
            $extra = trim($hits[1]['text']);
            if ($extra !== $seed)
                $answer = rtrim($answer, '. ') . '. ' . $extra;
        }

        return $this->polish($answer);
    }

    public function learn(string $text, int $maxSteps = 20000): array
    {
        $text = $this->clean($text);
        if (strlen($text) < 20)
            return ['error' => 'text too short'];
        $this->ret->addText($text);
        if (!$this->tok->built) {
            $this->tok->build($text);
            $this->lm->init($this->tok->vocab_size);
        } else {
            if ($this->tok->extend($text))
                $this->lm->growVocab($this->tok->vocab_size);
        }
        $T = $this->lm->T;
        $ids = $this->tok->encode($text, true, true);
        $pairs = [];
        for ($i = 0; $i + $T + 1 <= count($ids); $i++)
            $pairs[] = array_slice($ids, $i, $T + 1);
        if (empty($pairs))
            return ['loss' => 0, 'steps' => 0, 'vocab' => $this->tok->vocab_size];
        $result = $this->lm->trainPairs($pairs, $maxSteps);
        $this->saveAll();
        return $result + ['vocab' => $this->tok->vocab_size, 'sentences' => $this->ret->N];
    }

    public function learnFromURL(string $url): array
    {
        $html = $this->fetch($url);
        if (!$html)
            return ['error' => "Could not fetch $url"];
        $text = $this->html2text($html);
        return strlen($text) < 50 ? ['error' => 'Not enough text'] : $this->learn($text);
    }

    public function learnFromSite(string $base, int $maxPages = 30, ?callable $cb = null): array
    {
        $base = rtrim($base, '/');
        $queue = [$base];
        $visited = [];
        $corpus = '';
        $pages = 0;
        $sm = $this->fetch($base . '/sitemap.xml');
        if ($sm && str_contains($sm, '<loc>')) {
            preg_match_all('/<loc>(.*?)<\/loc>/s', $sm, $m);
            foreach ($m[1] as $u) {
                if ($this->sameDomain(trim($u), $base))
                    $queue[] = trim($u);
            }
        }
        $home = $this->fetch($base);
        if ($home) {
            preg_match_all('/href=["\']([^"\'#?]+)["\']/', $home, $lm);
            foreach ($lm[1] as $h) {
                $a = $this->toAbs($h, $base);
                if ($a && $this->sameDomain($a, $base))
                    $queue[] = $a;
            }
        }
        foreach (array_unique($queue) as $url) {
            if ($pages >= $maxPages || memory_get_usage(true) > 90 * 1024 * 1024)
                break;
            if (isset($visited[$url]))
                continue;
            if ($cb)
                $cb(['url' => $url, 'page' => $pages + 1]);
            $html = $this->fetch($url);
            if (!$html) {
                $visited[$url] = true;
                continue;
            }
            $corpus .= "\n\n" . $this->html2text($html);
            $visited[$url] = true;
            $pages++;
            usleep(300000);
        }
        $result = $corpus ? $this->learn($corpus) : ['error' => 'No content crawled'];
        return $result + ['pages' => $pages];
    }

    /** Beam search generation — higher quality than sampling for structured output. */
    public function generateBeam(
        string $seed,
        int $maxTokens = 80,
        int $beamWidth = 3,
        float $lengthPenalty = 0.9
    ): string {
        return $this->lm->ready
            ? $this->lm->generateBeam($this->tok, $seed, $maxTokens, $beamWidth, $lengthPenalty)
            : 'Not trained yet.';
    }

    // Raw BM25 retrieval — returns scored sentence hits for Reasoner
    public function retrieve(string $query, int $k = 5): array
    {
        return $this->ret->query($query, $k);
    }

    // Corpus size — used by Reasoner to gauge knowledge coverage
    public function corpusSize(): int
    {
        return $this->ret->N;
    }

    public function complete(
        string $seed,
        int $max = 50,
        float $temp = 0.8,
        float $topP = 1.0,
        float $repPenalty = 1.5
    ): string {
        return $this->lm->ready
            ? $this->lm->generate($this->tok, $seed, $max, $temp, true, $topP, $repPenalty)
            : 'Not trained yet.';
    }

    public function status(): array
    {
        return [
            'ready' => $this->lm->ready,
            'vocab' => $this->tok->vocab_size,
            'steps' => $this->lm->steps,
            'loss' => round($this->lm->lastLoss, 4),
            'sentences' => $this->ret->N,
            'model_type' => 'transformer',
            'arch' => "D={$this->lm->D} H={$this->lm->H} L={$this->lm->L} T={$this->lm->T}",
            'params' => array_sum(array_map('count', $this->lm->p)),
        ];
    }

    private function saveAll(): void
    {
        $this->store->saveModel('brain_' . $this->name, [
            'lm' => $this->lm->save(),
            'tok' => $this->tok->save(),
            'ret' => $this->ret->save(),
        ]);
    }

    private function loadAll(): void
    {
        $s = $this->store->loadModel('brain_' . $this->name);
        if ($s) {
            $this->lm = Transformer::fromArray($s['lm']);
            $this->tok = Tokenizer::fromArray($s['tok']);
            $this->ret = Retriever::fromArray($s['ret']);
        } else {
            $this->lm = new Transformer();
            $this->tok = new Tokenizer();
            $this->ret = new Retriever();
        }
    }

    private function polish(string $t): string
    {
        $t = trim($t);
        if ($t)
            $t[0] = strtoupper($t[0]);
        $t = preg_replace('/\s+([.!?,;:])/', '$1', $t);
        if ($t && !in_array(substr($t, -1), ['.', '!', '?']))
            $t .= '.';
        $sents = preg_split('/(?<=[.!?])\s+/', $t);
        return implode(' ', array_slice($sents, 0, 3));
    }

    private function clean(string $t): string
    {
        return trim(preg_replace('/[^\x20-\x7E]/', '', preg_replace('/\s+/', ' ', strtolower($t))));
    }

    private function html2text(string $h): string
    {
        $h = preg_replace('/<(script|style|nav|footer)[^>]*>.*?<\/\1>/is', '', $h);
        $h = preg_replace('/<(p|div|h[1-6]|li|br)[^>]*>/', "\n", $h);
        $t = html_entity_decode(strip_tags($h), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return mb_substr(trim(preg_replace('/\s+/', ' ', $t)), 0, 8000);
    }

    private function fetch(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Yuga/1.0',
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $r = curl_exec($ch);
            // curl_close is deprecated in PHP 8.0+ as CurlHandle is an object that auto-closes
            return $r ?: null;
        }
        return @file_get_contents(
            $url,
            false,
            stream_context_create(['http' => ['timeout' => 10]])
        ) ?: null;
    }

    private function sameDomain(string $u, string $b): bool
    {
        return parse_url($u, PHP_URL_HOST) === parse_url($b, PHP_URL_HOST);
    }

    private function toAbs(string $href, string $base): ?string
    {
        if (str_starts_with($href, 'http'))
            return $href;
        if (str_starts_with($href, '#') || str_starts_with($href, 'mailto:'))
            return null;
        $p = parse_url($base);
        return ($p['scheme'] ?? 'https') . '://' . ($p['host'] ?? '') . '/' . ltrim($href, '/');
    }
}
