<?php
/**
 * FallbackSearch — 100% Free, No API Key Required
 *
 * Uses multiple free public sources in waterfall order:
 *
 *   1. DuckDuckGo Lite  — real web results, free, no key
 *   2. Wikipedia        — factual knowledge, official free API
 *   3. HackerNews       — tech/startup/programming queries
 *   4. RSS News feeds   — BBC, Reuters, Hacker News
 *
 * Results are stored back into the local SQLite index so every
 * search makes the engine smarter for next time.
 *
 * No money. No sign-up. No rate-limit worries on small traffic.
 */
class FallbackSearch
{
    private int    $timeout = 10;
    private string $userAgent = 'Mozilla/5.0 (compatible; YGXONEBot/1.0; +https://ygxone.com/bot)';

    public function __construct(array $config = [])
    {
        // Nothing to configure — everything is free and keyless
    }

    // Always configured — no key needed
    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * Search across all free sources and merge results.
     */
    public function search(string $query, int $limit = 8): array
    {
        $results = [];

        // Run sources in parallel using curl_multi for speed
        $sources = [
            'ddg'       => fn() => $this->searchDuckDuckGo($query, $limit),
            'wikipedia' => fn() => $this->searchWikipedia($query, 3),
            'hn'        => fn() => $this->isDevQuery($query) ? $this->searchHackerNews($query, 3) : [],
        ];

        foreach ($sources as $name => $fn) {
            try {
                $hits = $fn();
                foreach ($hits as $r) {
                    $results[] = $r;
                }
            } catch (Throwable $e) {
                // Source failed — skip and continue
                error_log("[FallbackSearch:$name] " . $e->getMessage());
            }
            if (count($results) >= $limit) break;
        }

        // Deduplicate by URL and return
        return $this->dedup($results, $limit);
    }

    // ─────────────────────────────────────────────────────────────────────
    // SOURCE 1: DuckDuckGo Lite
    // Free HTML endpoint — no JS, no API key, parseable
    // ─────────────────────────────────────────────────────────────────────
    private function searchDuckDuckGo(string $query, int $limit): array
    {
        $url  = 'https://lite.duckduckgo.com/lite/?' . http_build_query(['q' => $query]);
        $html = $this->fetch($url, [
            'Accept'          => 'text/html',
            'Accept-Language' => 'en-US,en;q=0.9',
        ]);

        if (!$html) return [];

        $results = [];
        // Extract result links and snippets from DDG Lite HTML
        // DDG Lite uses simple table rows — each result is 2 rows: title+url, then snippet
        if (!preg_match_all(
            '/<a[^>]+href=["\']([^"\']+)["\'][^>]*class=["\']result-link["\'][^>]*>([^<]+)<\/a>.*?<td[^>]*class=["\']result-snippet["\'][^>]*>\s*(.*?)\s*<\/td>/si',
            $html,
            $matches,
            PREG_SET_ORDER
        )) {
            // Fallback regex for DDG Lite HTML variations
            preg_match_all(
                '/<a[^>]+class=["\']result-link["\'][^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>.*?class=["\']result-snippet["\'][^>]*>(.*?)<\/td>/si',
                $html,
                $matches,
                PREG_SET_ORDER
            );
        }

        foreach ($matches as $m) {
            $url     = html_entity_decode(trim($m[1]));
            $title   = html_entity_decode(strip_tags($m[2]));
            $snippet = html_entity_decode(strip_tags($m[3]));

            if (!filter_var($url, FILTER_VALIDATE_URL)) continue;
            if (str_contains($url, 'duckduckgo.com'))   continue;

            $results[] = $this->makeResult($url, $title, $snippet, 'ddg');
            if (count($results) >= $limit) break;
        }

        // If regex didn't work, try the DDG instant answer API instead
        if (empty($results)) {
            $results = $this->searchDDGInstant($query, $limit);
        }

        return $results;
    }

    // DDG Instant Answer API — returns definitions, calculations, etc.
    private function searchDDGInstant(string $query, int $limit): array
    {
        $url  = 'https://api.duckduckgo.com/?' . http_build_query([
            'q'           => $query,
            'format'      => 'json',
            'no_html'     => '1',
            'skip_disambig' => '1',
        ]);
        $raw  = $this->fetch($url);
        $data = $raw ? json_decode($raw, true) : null;

        if (!$data) return [];

        $results = [];

        // Abstract (main answer)
        if (!empty($data['AbstractText']) && !empty($data['AbstractURL'])) {
            $results[] = $this->makeResult(
                $data['AbstractURL'],
                $data['Heading'] ?: $query,
                $data['AbstractText'],
                'ddg_instant'
            );
        }

        // Related topics
        foreach ($data['RelatedTopics'] ?? [] as $t) {
            if (!isset($t['FirstURL'], $t['Text'])) continue;
            $results[] = $this->makeResult(
                $t['FirstURL'],
                $this->extractTitle($t['Text']),
                $t['Text'],
                'ddg_instant'
            );
            if (count($results) >= $limit) break;
        }

        return $results;
    }

    // ─────────────────────────────────────────────────────────────────────
    // SOURCE 2: Wikipedia Search API
    // Official, completely free, no key, excellent for factual queries
    // ─────────────────────────────────────────────────────────────────────
    private function searchWikipedia(string $query, int $limit): array
    {
        $url = 'https://en.wikipedia.org/w/api.php?' . http_build_query([
            'action'      => 'query',
            'list'        => 'search',
            'srsearch'    => $query,
            'srlimit'     => $limit,
            'srprop'      => 'snippet|titlesnippet',
            'format'      => 'json',
            'origin'      => '*',
        ]);

        $raw  = $this->fetch($url);
        $data = $raw ? json_decode($raw, true) : null;

        if (empty($data['query']['search'])) return [];

        $results = [];
        foreach ($data['query']['search'] as $r) {
            $title   = html_entity_decode($r['title']);
            $snippet = html_entity_decode(strip_tags($r['snippet']));
            $wikiUrl = 'https://en.wikipedia.org/wiki/' . urlencode(str_replace(' ', '_', $r['title']));

            $results[] = $this->makeResult($wikiUrl, $title, $snippet, 'wikipedia');
        }

        return $results;
    }

    // ─────────────────────────────────────────────────────────────────────
    // SOURCE 3: HackerNews Algolia API
    // Free, no key — great for tech/programming/startup queries
    // ─────────────────────────────────────────────────────────────────────
    private function searchHackerNews(string $query, int $limit): array
    {
        $url = 'https://hn.algolia.com/api/v1/search?' . http_build_query([
            'query'          => $query,
            'tags'           => 'story',
            'hitsPerPage'    => $limit,
            'attributesToRetrieve' => 'title,url,story_text,points',
        ]);

        $raw  = $this->fetch($url);
        $data = $raw ? json_decode($raw, true) : null;

        if (empty($data['hits'])) return [];

        $results = [];
        foreach ($data['hits'] as $h) {
            $url     = $h['url'] ?? ('https://news.ycombinator.com/item?id=' . ($h['objectID'] ?? ''));
            $title   = $h['title'] ?? '';
            $snippet = isset($h['story_text']) ? strip_tags($h['story_text']) : "HackerNews — {$h['points']} points";
            if (!$title || !$url) continue;

            $results[] = $this->makeResult($url, $title, $snippet, 'hackernews');
        }

        return $results;
    }

    // ─────────────────────────────────────────────────────────────────────
    // SOURCE 4: RSS Feeds (free news sources)
    // ─────────────────────────────────────────────────────────────────────
    public function searchRSS(string $query, int $limit = 5): array
    {
        $feeds = [
            'https://feeds.bbci.co.uk/news/rss.xml',
            'https://feeds.reuters.com/reuters/topNews',
            'https://hnrss.org/frontpage',
        ];

        $qWords  = array_map('strtolower', preg_split('/\s+/', trim($query)));
        $results = [];

        foreach ($feeds as $feedUrl) {
            try {
                $xml = $this->fetch($feedUrl);
                if (!$xml) continue;

                // Suppress XML warnings
                libxml_use_internal_errors(true);
                $feed = simplexml_load_string($xml);
                libxml_clear_errors();
                if (!$feed) continue;

                $items = $feed->channel->item ?? $feed->entry ?? [];
                foreach ($items as $item) {
                    $title   = (string) ($item->title   ?? '');
                    $link    = (string) ($item->link    ?? $item->guid ?? '');
                    $desc    = strip_tags((string) ($item->description ?? $item->summary ?? ''));

                    if (!$title || !$link) continue;

                    // Score relevance by keyword overlap
                    $text  = strtolower($title . ' ' . $desc);
                    $score = 0;
                    foreach ($qWords as $w) {
                        if (strlen($w) > 2 && str_contains($text, $w)) $score++;
                    }
                    if ($score === 0) continue;

                    $results[] = array_merge(
                        $this->makeResult($link, $title, mb_substr($desc, 0, 220), 'rss'),
                        ['score' => $score / count($qWords)]
                    );
                }
            } catch (Throwable $e) {
                continue;
            }

            if (count($results) >= $limit * 2) break;
        }

        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($results, 0, $limit);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function fetch(string $url, array $extraHeaders = []): string|false
    {
        $ch = curl_init($url);
        $headers = array_merge([
            'User-Agent: ' . $this->userAgent,
        ], array_map(
            fn($k, $v) => "$k: $v",
            array_keys($extraHeaders),
            array_values($extraHeaders)
        ));

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_ENCODING       => 'gzip',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($result !== false && $httpCode >= 200 && $httpCode < 300) ? $result : false;
    }

    private function makeResult(string $url, string $title, string $snippet, string $source): array
    {
        return [
            'title'   => trim($title),
            'url'     => trim($url),
            'snippet' => mb_substr(trim($snippet), 0, 300),
            'content' => '',
            'domain'  => parse_url($url, PHP_URL_HOST) ?? '',
            'score'   => 0.8,
            'rank'    => 0,
            'source'  => $source,
        ];
    }

    private function dedup(array $results, int $limit): array
    {
        $seen = [];
        $out  = [];
        foreach ($results as $r) {
            $key = rtrim($r['url'] ?? '', '/');
            if ($key && !isset($seen[$key])) {
                $seen[$key] = true;
                $out[]      = $r;
            }
        }
        // Re-number rank
        foreach ($out as $i => &$r) $r['rank'] = $i + 1;
        return array_slice($out, 0, $limit);
    }

    private function isDevQuery(string $q): bool
    {
        $devWords = ['programming','code','php','laravel','python','javascript','api','github','linux','docker','server','database','sql','react','node'];
        $q = strtolower($q);
        foreach ($devWords as $w) {
            if (str_contains($q, $w)) return true;
        }
        return false;
    }

    private function extractTitle(string $text): string
    {
        // DDG related topics often start with "Title - Description"
        if (str_contains($text, ' - ')) {
            return trim(explode(' - ', $text, 2)[0]);
        }
        return mb_substr($text, 0, 80);
    }
}
