<?php
/**
 * Reasoner — Higher-quality reasoning layer for Yuga
 *
 * Sits between Agent and Brain. Improves answer quality through:
 *
 *   1. Query Decomposition   — splits compound questions into sub-questions
 *   2. Multi-hop Retrieval   — iterative BM25 passes, each refining the query
 *   3. Evidence Aggregation  — deduplicates and re-ranks hits from all hops
 *   4. Confidence Routing    — decides when to stop early vs dig deeper
 *   5. Iterative Refinement  — uses initial answer to seed a second retrieval
 *
 * No external LLM. No extra dependencies. Works entirely on Yuga's own BM25 + SLM.
 *
 * Usage:
 *   $reasoner = new Reasoner($brain);
 *   $result   = $reasoner->reason("What is the Pro plan price and does it include API access?");
 *   echo $result['answer'];
 */
class Reasoner {

    private Brain $brain;

    // Tuning
    public int   $max_hops             = 3;    // retrieval hops per sub-question
    public int   $top_k                = 5;    // BM25 hits per hop
    public float $high_confidence      = 3.5;  // score above this → return immediately
    public float $low_confidence       = 1.2;  // score below this → more hops needed
    public float $temperature          = 0.7;
    public bool  $iterative_refinement = true; // second-pass retrieval using initial answer

    public function __construct(Brain $brain) {
        $this->brain = $brain;
    }

    // ── Main entry point ───────────────────────────────────────────────
    public function reason(string $question): array {
        if ($this->brain->corpusSize() === 0) {
            return $this->noKnowledge($question);
        }

        // 1. Decompose compound question into sub-questions
        $subQuestions = $this->decompose($question);

        // 2. Multi-hop retrieval for each sub-question
        $allEvidence = [];
        $hopTrace    = [];
        foreach ($subQuestions as $sq) {
            [$hits, $trace] = $this->multiHop($sq);
            $allEvidence    = array_merge($allEvidence, $hits);
            $hopTrace[]     = ['sub_question' => $sq, 'hops' => $trace];
        }

        // 3. Deduplicate and re-rank all evidence against the original question
        $evidence = $this->rankEvidence($allEvidence, $question);

        // 4. Confidence check — high confidence: return top hit directly
        $confidence = $evidence[0]['score'] ?? 0.0;
        if ($confidence >= $this->high_confidence) {
            $answer = $this->polish($evidence[0]['text']);
            return $this->result($answer, $evidence, $subQuestions, $hopTrace, $confidence, 'direct');
        }

        // 5. Synthesize answer from top-N evidence pieces
        $answer = $this->synthesize($question, $evidence);

        // 6. Iterative refinement — use the answer to do one more retrieval pass
        if ($this->iterative_refinement && $confidence < $this->high_confidence && strlen($answer) > 20) {
            $refinedHits = $this->brain->retrieve($answer . ' ' . $question, $this->top_k);
            $merged      = $this->rankEvidence(array_merge($evidence, $refinedHits), $question);
            $refined     = $this->synthesize($question, $merged);
            // Only use refined answer if it's meaningfully longer / different
            if (strlen($refined) > strlen($answer) + 10) {
                $answer   = $refined;
                $evidence = $merged;
            }
        }

        $finalConf = $evidence[0]['score'] ?? 0.0;
        return $this->result($answer, $evidence, $subQuestions, $hopTrace, $finalConf, 'synthesized');
    }

    // ── Query decomposition ────────────────────────────────────────────
    // Splits compound questions so each part gets its own retrieval pass.
    // "What is the Free plan price and does it include API access?"
    //   → ["What is the Free plan price", "does it include API access"]
    private function decompose(string $question): array {
        // Split on coordinating conjunctions that join two clauses
        $parts = preg_split(
            '/\s+(?:and|also|as well as|additionally|furthermore|moreover|but also|plus)\s+/i',
            $question
        );

        $subQuestions = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if (strlen($part) < 4) continue;

            // If part looks like a fragment (no verb), prepend the subject from original
            if (!preg_match('/\b(is|are|does|do|can|will|has|have|what|how|who|when|where|why)\b/i', $part)) {
                $part = $question; // fall back to full question for this part
            }

            $subQuestions[] = $part;
        }

        // Deduplicate (if decomposition produced the same string multiple times)
        $subQuestions = array_values(array_unique($subQuestions));

        return $subQuestions ?: [$question];
    }

    // ── Multi-hop BM25 retrieval ───────────────────────────────────────
    // Each hop retrieves evidence, then reformulates the query using
    // key terms from the top result to find related sentences in hop 2+.
    private function multiHop(string $query): array {
        $allHits      = [];
        $seenTexts    = [];
        $currentQuery = $query;
        $trace        = [];

        for ($hop = 0; $hop < $this->max_hops; $hop++) {
            $hits = $this->brain->retrieve($currentQuery, $this->top_k);
            if (empty($hits)) break;

            $trace[] = ['hop' => $hop + 1, 'query' => $currentQuery, 'hits' => count($hits), 'top_score' => $hits[0]['score']];

            // Collect unique hits
            foreach ($hits as $hit) {
                $key = substr($hit['text'], 0, 60);
                if (!isset($seenTexts[$key])) {
                    $seenTexts[$key] = true;
                    $allHits[]       = $hit;
                }
            }

            // Stop early if top result is very confident
            if ($hits[0]['score'] >= $this->high_confidence) break;

            // Stop if confidence is improving negligibly
            if ($hop > 0 && $hits[0]['score'] < $this->low_confidence) break;

            // Reformulate query for next hop using key terms from top result
            $nextQuery = $this->reformulateQuery($query, $hits[0]['text']);

            // Don't re-run if reformulation produced nothing new
            if ($nextQuery === $currentQuery) break;

            $currentQuery = $nextQuery;
        }

        return [$allHits, $trace];
    }

    // ── Query reformulation ────────────────────────────────────────────
    // Extracts key terms from retrieved evidence that weren't in the
    // original query, then appends them to create a richer second query.
    private function reformulateQuery(string $original, string $evidence): string {
        $origTerms     = $this->keyTerms($original);
        $evidenceTerms = $this->keyTerms($evidence);
        $newTerms      = array_diff($evidenceTerms, $origTerms);
        $newTerms      = array_slice(array_values($newTerms), 0, 4);

        if (empty($newTerms)) return $original;

        return $original . ' ' . implode(' ', $newTerms);
    }

    // ── Evidence deduplication and re-ranking ─────────────────────────
    // After collecting hits from multiple hops/sub-questions, removes
    // duplicates and re-scores everything against the original question.
    private function rankEvidence(array $hits, string $question): array {
        // Deduplicate by first 60 chars of text
        $unique  = [];
        $seen    = [];
        foreach ($hits as $hit) {
            $key = substr(trim($hit['text']), 0, 60);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[]   = $hit;
            }
        }

        // Re-score: original BM25 score + term-overlap bonus
        $qTerms = $this->keyTerms($question);
        foreach ($unique as &$hit) {
            $hTerms       = $this->keyTerms($hit['text']);
            $overlap      = count(array_intersect($qTerms, $hTerms));
            $lengthBonus  = min(strlen($hit['text']) / 200, 0.5); // slightly favour longer sentences
            $hit['score'] = round($hit['score'] + ($overlap * 0.4) + $lengthBonus, 3);
        }
        unset($hit);

        usort($unique, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($unique, 0, $this->top_k);
    }

    // ── Answer synthesis ──────────────────────────────────────────────
    // Builds the final answer from top-ranked evidence.
    // Uses Brain's SLM to generate for low-confidence cases,
    // or directly assembles evidence sentences for high-confidence cases.
    private function synthesize(string $question, array $evidence): string {
        if (empty($evidence)) {
            return "I don't have enough information in my knowledge base to answer that.";
        }

        $top  = $evidence[0];
        $conf = $top['score'];

        // High confidence: top sentence is almost certainly the answer
        if ($conf >= $this->high_confidence) {
            return $this->polish($top['text']);
        }

        // Medium confidence: combine top 2-3 evidence pieces
        if ($conf >= $this->low_confidence) {
            $parts = array_slice($evidence, 0, 3);
            $combined = implode(' ', array_map(fn($e) => rtrim($e['text'], '.!?'), $parts));
            return $this->polish($combined . '.');
        }

        // Low confidence: let Brain's SLM generate from the best seed
        // This uses the retrieval-grounded generation path in Brain::answer()
        return $this->brain->answer($question, $this->temperature);
    }

    // ── Extract key terms (for reformulation and re-ranking) ──────────
    private function keyTerms(string $text): array {
        $text  = strtolower(preg_replace('/[^a-z0-9\s]/i', ' ', $text));
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $stop  = [
            'the','a','an','is','are','was','were','be','been','being',
            'have','has','had','do','does','did','will','would','could',
            'should','may','might','can','to','of','in','on','at','by',
            'for','with','about','and','or','but','not','it','this','that',
            'i','we','you','he','she','they','what','how','who','when','where','why',
        ];
        $filtered = array_filter($words, fn($w) => !in_array($w, $stop) && strlen($w) > 2);
        return array_values(array_unique($filtered));
    }

    // ── Polish output text ─────────────────────────────────────────────
    private function polish(string $t): string {
        $t = trim($t);
        if (!$t) return $t;
        $t[0] = strtoupper($t[0]);
        $t = preg_replace('/\s+([.!?,;:])/', '$1', $t);
        if (!in_array(substr($t, -1), ['.', '!', '?'])) $t .= '.';
        // Return max 3 sentences
        $sents = preg_split('/(?<=[.!?])\s+/', $t);
        return implode(' ', array_slice($sents, 0, 3));
    }

    // ── No knowledge fallback ──────────────────────────────────────────
    private function noKnowledge(string $question): array {
        return $this->result(
            "I haven't learned anything yet. Please run the self-learning crawler first.",
            [], [$question], [], 0.0, 'no_knowledge'
        );
    }

    // ── Build result array ─────────────────────────────────────────────
    private function result(
        string $answer,
        array  $evidence,
        array  $subQuestions,
        array  $hopTrace,
        float  $confidence,
        string $mode
    ): array {
        return [
            'answer'        => $answer,
            'confidence'    => round($confidence, 3),
            'mode'          => $mode,           // 'direct' | 'synthesized' | 'no_knowledge'
            'sub_questions' => $subQuestions,
            'evidence'      => array_map(fn($e) => [
                'text'  => $e['text'],
                'score' => $e['score'],
            ], $evidence),
            'hop_trace'     => $hopTrace,
        ];
    }
}
