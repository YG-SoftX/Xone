<?php
/**
 * WebSearch — Hybrid Sovereign Search
 *
 * Search waterfall (each step only runs when the previous returns too few results):
 *   1. Local BM25 SQLite index   — zero cost, instant, grows over time
 *   2. On-demand crawl           — crawls relevant pages, indexes into SQLite, re-searches
 *   3. External fallback API     — Brave / SerpAPI for guaranteed global coverage
 *      → fallback results are indexed back into SQLite (free next time)
 *
 * Config keys (set in .env):
 *   YUGA_FALLBACK_PROVIDER  = brave | serpapi | none  (default: none)
 *   YUGA_BRAVE_API_KEY      = your Brave Search API key
 *   YUGA_SERPAPI_KEY        = your SerpAPI key
 */
class WebSearch
{
    private string $data_dir;
    private array  $config;
    private int    $min_results    = 3;   // use fallback when local has fewer than this
    private int    $ondemand_pages = 15;  // pages crawled on-demand per query

    /** Pre-injected instances (set by API to share one SQLite handle) */
    public ?object $index    = null;
    public ?object $crawler  = null;
    public ?object $fallback = null;

    public function __construct(array $config = [])
    {
        $this->config   = $config;
        $this->data_dir = $config['data_dir'] ?? (
            defined('YUGA_ROOT') ? YUGA_ROOT . '/data' : __DIR__ . '/../data'
        );
    }

    // ── Public search entry point ─────────────────────────────────────────
    public function search(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if (!$query) return [];

        $idx   = $this->getIndex();
        $terms = $idx->tokenize($query);

        // Step 1 — local BM25 index
        $rows = $idx->search($terms, $limit * 3);

        // Step 2 — on-demand crawl when too sparse
        if (count($rows) < $this->min_results) {
            try {
                $this->getCrawler()->crawlQuery($query, $this->ondemand_pages);
                $rows = $idx->search($terms, $limit * 3);
            } catch (Throwable $e) {
                // Crawl failed (no outbound HTTP, firewall, etc.) — move to fallback
            }
        }

        // Step 3 — free fallback sources (Wikipedia, DuckDuckGo, HackerNews)
        $fallbackEnabled = (bool) ($this->config['fallback_enabled'] ?? true);
        if ($fallbackEnabled && count($rows) < $this->min_results) {
            $fb       = $this->getFallback();
            $external = $fb->search($query, $limit);
            if (!empty($external)) {
                // Store fallback results in local index — free next time
                $this->backfillIndex($external, $idx);
                // Merge: local results win ties, fallback fills the rest
                return $this->merge(
                    $this->formatResults($rows),
                    $external,
                    $limit
                );
            }
        }

        return array_slice($this->formatResults($rows), 0, $limit);
    }

    // ── Format SearchIndex rows → standard result shape ──────────────────
    private function formatResults(array $rows): array
    {
        $out = [];
        foreach ($rows as $i => $r) {
            $snippet = $r['description'] ?? '';
            if (!$snippet && !empty($r['content'])) {
                $snippet = mb_substr(strip_tags($r['content']), 0, 220);
            }
            $out[] = [
                'title'   => $r['title']  ?: (parse_url($r['url'], PHP_URL_HOST) ?? $r['url']),
                'url'     => $r['url'],
                'snippet' => $snippet,
                'content' => $r['content'] ?? '',
                'domain'  => $r['domain']  ?? '',
                'score'   => round((float) ($r['score'] ?? 0.0), 4),
                'rank'    => $i + 1,
                'source'  => 'local',
            ];
        }
        return $out;
    }

    // ── Merge local + fallback results, deduplicated by URL ──────────────
    private function merge(array $local, array $fallback, int $limit): array
    {
        $seen   = [];
        $merged = [];
        foreach (array_merge($local, $fallback) as $r) {
            $url = rtrim($r['url'] ?? '', '/');
            if ($url && !isset($seen[$url])) {
                $seen[$url] = true;
                $merged[]   = $r;
            }
        }
        return array_slice($merged, 0, $limit);
    }

    // ── Store fallback results in local index so future searches are free ─
    private function backfillIndex(array $results, object $idx): void
    {
        foreach ($results as $r) {
            if (empty($r['url']) || empty($r['title'])) continue;
            try {
                $idx->indexPage(
                    url:    $r['url'],
                    title:  $r['title'],
                    desc:   $r['snippet'] ?? '',
                    body:   $r['snippet'] ?? '',
                    status: 200,
                );
            } catch (Throwable) {
                // Best-effort — never fatal
            }
        }
    }

    // ── Lazy loaders ─────────────────────────────────────────────────────
    private function getIndex(): object
    {
        if ($this->index !== null) return $this->index;
        if (!class_exists('SearchIndex')) {
            require_once __DIR__ . '/SearchIndex.php';
        }
        $this->index = new SearchIndex($this->data_dir);
        return $this->index;
    }

    private function getCrawler(): object
    {
        if ($this->crawler !== null) return $this->crawler;
        if (!class_exists('GlobalCrawler')) {
            require_once __DIR__ . '/GlobalCrawler.php';
        }
        $gc = new GlobalCrawler($this->data_dir);
        $gc->indexInstance = $this->getIndex();
        $this->crawler     = $gc;
        return $this->crawler;
    }

    private function getFallback(): object
    {
        if ($this->fallback !== null) return $this->fallback;
        if (!class_exists('FallbackSearch')) {
            require_once __DIR__ . '/FallbackSearch.php';
        }
        $this->fallback = new FallbackSearch($this->config);
        return $this->fallback;
    }
}
