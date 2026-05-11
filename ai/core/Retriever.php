<?php
/**
 * Retriever — BM25 full-text search over the knowledge corpus
 *
 * BM25 is the same algorithm used by Elasticsearch, Solr, and Google's
 * early ranking. For a ~50KB corpus it runs in < 5ms in PHP.
 *
 * Given a user question it returns the top-K most relevant sentences
 * from the crawled platform content. Those sentences are then used
 * to ground the language model's generation.
 */
class Retriever {

    public array  $sentences  = [];    // raw sentence strings
    public array  $doc_words  = [];    // tokenized sentences
    public array  $idf        = [];    // IDF per word
    public int    $N          = 0;     // total sentence count
    public float  $avgdl      = 0.0;   // average sentence length

    // BM25 tuning
    const K1 = 1.5;
    const B  = 0.75;

    // -------------------------------------------------------------------
    // Build the index from a corpus string
    // -------------------------------------------------------------------
    public function buildIndex(string $corpus): void {
        // Split into sentences
        $raw = preg_split('/(?<=[.!?])\s+/', $corpus);
        $this->sentences = [];
        $this->doc_words = [];

        foreach ($raw as $sent) {
            $sent = trim($sent);
            if (strlen($sent) < 15) continue; // skip too short
            $this->sentences[] = $sent;
            $this->doc_words[] = $this->tokenize($sent);
        }

        $this->N     = count($this->sentences);
        $this->avgdl = $this->N > 0
            ? array_sum(array_map('count', $this->doc_words)) / $this->N
            : 1.0;

        $this->buildIDF();
    }

    // Incrementally add more text
    public function addText(string $text): void {
        $existing = implode(' ', $this->sentences);
        $this->buildIndex($existing . ' ' . $text);
    }

    // -------------------------------------------------------------------
    // Query: return top-K sentences most relevant to the query
    // -------------------------------------------------------------------
    public function query(string $question, int $top_k = 5): array {
        if ($this->N === 0) return [];

        $q_words = $this->tokenize($question);
        $scores  = array_fill(0, $this->N, 0.0);

        foreach ($q_words as $word) {
            $idf = $this->idf[$word] ?? 0.0;
            if ($idf === 0.0) continue;

            foreach ($this->doc_words as $di => $dwords) {
                $tf  = $this->termFreq($dwords, $word);
                if ($tf === 0) continue;
                $dl  = count($dwords);
                $num = $tf * (self::K1 + 1);
                $den = $tf + self::K1 * (1 - self::B + self::B * $dl / $this->avgdl);
                $scores[$di] += $idf * ($num / $den);
            }
        }

        // Sort by score descending
        arsort($scores);
        $results = [];
        foreach (array_slice(array_keys($scores), 0, $top_k) as $idx) {
            if ($scores[$idx] > 0) {
                $results[] = [
                    'text'  => $this->sentences[$idx],
                    'score' => round($scores[$idx], 3),
                ];
            }
        }
        return $results;
    }

    // Convenience: just get the text of top results joined
    public function queryText(string $question, int $top_k = 5): string {
        $hits = $this->query($question, $top_k);
        return implode(' ', array_column($hits, 'text'));
    }

    // -------------------------------------------------------------------
    // Build IDF table
    // -------------------------------------------------------------------
    private function buildIDF(): void {
        $df = [];
        foreach ($this->doc_words as $dwords) {
            foreach (array_unique($dwords) as $w) {
                $df[$w] = ($df[$w] ?? 0) + 1;
            }
        }
        $this->idf = [];
        foreach ($df as $w => $n) {
            // BM25 IDF formula
            $this->idf[$w] = log(($this->N - $n + 0.5) / ($n + 0.5) + 1);
        }
    }

    private function tokenize(string $text): array {
        $text  = strtolower($text);
        $text  = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        // Remove stopwords
        $stop  = ['the','a','an','is','are','was','were','be','been','being',
                   'have','has','had','do','does','did','will','would','could',
                   'should','may','might','shall','can','to','of','in','on',
                   'at','by','for','with','about','and','or','but','not','it',
                   'this','that','these','those','i','we','you','he','she','they'];
        return array_values(array_filter($words, fn($w) => !in_array($w, $stop)));
    }

    private function termFreq(array $doc, string $term): int {
        return count(array_filter($doc, fn($w) => $w === $term));
    }

    // -------------------------------------------------------------------
    // Serialize
    // -------------------------------------------------------------------
    public function save(): array {
        return [
            'sentences' => $this->sentences,
            'doc_words' => $this->doc_words,
            'idf'       => $this->idf,
            'N'         => $this->N,
            'avgdl'     => $this->avgdl,
        ];
    }

    public function load(array $d): void {
        foreach ($d as $k => $v) $this->$k = $v;
    }

    public static function fromArray(array $d): self {
        $r = new self();
        $r->load($d);
        return $r;
    }
}
