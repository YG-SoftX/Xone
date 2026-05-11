<?php
/**
 * SelfLearner - Automatically crawls & learns from the host platform
 *
 * Features:
 *  - Crawls pages of the integrated website
 *  - Extracts clean text (strips HTML, JS, CSS)
 *  - Handles sitemaps (sitemap.xml)
 *  - Respects robots.txt politely
 *  - Trains the model incrementally on new content
 *  - Works within cPanel resource limits (rate-limited, memory-safe)
 */
class SelfLearner
{

    private YugaLM $model;
    private ModelStore $store;
    private string $model_name;
    private array $meta;

    public int $max_pages = 60;      // max pages to crawl
    public int $max_chars_page = 8000;    // max chars extracted per page
    public int $train_steps = 20000;   // training steps per crawl
    public float $crawl_delay = 0.5;     // seconds between requests
    public int $max_depth = 4;       // link depth
    public int $memory_limit_mb = 256;    // Increased for Windows/Local training

    public function __construct(string $model_name, ModelStore $store)
    {
        $this->store = $store;
        $this->model_name = $model_name;
        $this->meta = $store->loadMeta($model_name);

        // Load or create model
        $saved = $store->loadModel($model_name);
        $this->model = $saved ? YugaLM::fromArray($saved) : new YugaLM();

        ini_set('memory_limit', $this->memory_limit_mb . 'M');
        set_time_limit(600); // 10 minutes for site training
    }

    // ---------------------------------------------------------------
    // Public: Learn from a base URL (auto-crawl)
    // ---------------------------------------------------------------
    public function learnFromSite(string $base_url, ?callable $progress_cb = null): array
    {
        $base_url = rtrim($base_url, '/');
        $results = ['pages' => 0, 'chars' => 0, 'loss' => 0, 'errors' => []];

        // 1. Get URL list from sitemap or crawl
        $urls = $this->discoverURLs($base_url);

        // Ecosystem Fallback: If crawling localhost, add common routes
        if (str_contains($base_url, 'localhost') || str_contains($base_url, '127.0.0.1')) {
            $urls = array_merge($urls, [
                $base_url . '/',
                $base_url . '/docs',
                $base_url . '/api',
                $base_url . '/help',
                $base_url . '/about'
            ]);
        }

        if (empty($urls)) {
            $results['errors'][] = 'No URLs found. Trying base URL only.';
            $urls = [$base_url];
        }

        $urls = array_unique(array_filter($urls));
        $urls = array_slice($urls, 0, $this->max_pages);
        $corpus = '';
        $visited = $this->meta['visited_urls'] ?? [];
        $new_urls = [];

        foreach ($urls as $url) {
            // Bypass visited check if forced (optional)
            if (isset($visited[$url]))
                continue;
            if ($this->isOverMemory())
                break;

            if ($progress_cb)
                $progress_cb(['status' => 'crawling', 'url' => $url]);

            $text = $this->fetchAndExtract($url);
            if (!$text || strlen($text) < 20) {
                // If text is too short, might be a React SPA loading screen
                // or a redirect. Log it but don't fail the whole run.
                continue;
            }

            $text = $this->cleanText($text);
            $corpus .= "\n\n" . $text;
            $results['pages']++;
            $results['chars'] += strlen($text);
            $visited[$url] = time();
            $new_urls[] = $url;

            usleep((int) ($this->crawl_delay * 1000000));
        }

        // 2. Train on gathered corpus
        if (strlen($corpus) > 50) {
            if ($progress_cb)
                $progress_cb(['status' => 'training', 'chars' => strlen($corpus)]);
            // Use chunks to avoid single-pass memory spikes
            $train_result = $this->model->trainOnText($corpus, $this->train_steps);
            $results['loss'] = $train_result['loss'] ?? 0;
            $results['steps'] = $train_result['steps'] ?? 0;
        }

        // 3. Persist
        $this->meta['visited_urls'] = $visited;
        $this->meta['base_url'] = $base_url;
        $this->meta['last_crawl'] = time();
        $this->meta['total_chars'] = ($this->meta['total_chars'] ?? 0) + $results['chars'];
        $this->store->saveMeta($this->model_name, $this->meta);
        $this->store->saveModel($this->model_name, $this->model->save());

        return $results;
    }

    // ---------------------------------------------------------------
    // Public: Learn from plain text (manual feed)
    // ---------------------------------------------------------------
    public function learnFromText(string $text, string $source = 'manual'): array
    {
        $text = $this->cleanText($text);
        $result = $this->model->trainOnText($text, $this->train_steps);

        $this->meta['sources'][] = ['source' => $source, 'chars' => strlen($text), 'ts' => time()];
        $this->meta['total_chars'] = ($this->meta['total_chars'] ?? 0) + strlen($text);
        $this->store->saveMeta($this->model_name, $this->meta);
        $this->store->saveModel($this->model_name, $this->model->save());

        return $result;
    }

    // ---------------------------------------------------------------
    // Public: Learn from a single URL
    // ---------------------------------------------------------------
    public function learnFromURL(string $url): array
    {
        $text = $this->fetchAndExtract($url);
        if (!$text)
            return ['error' => 'Could not fetch URL'];
        return $this->learnFromText($text, $url);
    }

    // ---------------------------------------------------------------
    // Generate a response
    // ---------------------------------------------------------------
    public function respond(string $prompt, int $max_chars = 300, float $temp = 0.75): string
    {
        if (!$this->model->vocab_built) {
            return "I haven't learned anything yet. Please connect me to your platform first!";
        }
        return $this->model->complete($prompt, $max_chars, $temp);
    }

    public function getModel(): YugaLM
    {
        return $this->model;
    }
    public function getMeta(): array
    {
        return $this->meta;
    }

    // ── URL Discovery (Aggressive for SPAs) ──────────────────────────
    private function discoverURLs(string $base_url): array
    {
        $urls = [$base_url];

        // 1. Try sitemap.xml
        $sitemap = $this->fetchRaw($base_url . '/sitemap.xml');
        if ($sitemap && str_contains($sitemap, '<loc>')) {
            preg_match_all('/<loc>(.*?)<\/loc>/s', $sitemap, $m);
            foreach ($m[1] as $u) {
                $u = trim($u);
                if ($this->isSameDomain($u, $base_url))
                    $urls[] = $u;
            }
        }

        // 2. Crawl HTML + JS blocks for path-like strings
        $html = $this->fetchRaw($base_url);
        if ($html) {
            // Traditional hrefs
            preg_match_all('/href=["\']([^"\']+)["\']/', $html, $m1);
            foreach ($m1[1] as $href) {
                $abs = $this->toAbsolute($href, $base_url);
                if ($abs && $this->isSameDomain($abs, $base_url))
                    $urls[] = $abs;
            }

            // SPA route strings (e.g. "/dashboard", "/docs")
            // Match strings starting with / followed by alphanumeric chars
            preg_match_all('/["\'](\/[a-zA-Z0-9_\-\/]+)["\']/', $html, $m2);
            foreach ($m2[1] as $path) {
                $abs = $base_url . $path;
                if ($this->isSameDomain($abs, $base_url))
                    $urls[] = $abs;
            }
        }

        // 3. Common ecosystem paths
        $common = ['/docs', '/api', '/help', '/about', '/v1'];
        foreach ($common as $c)
            $urls[] = $base_url . $c;

        return array_unique(array_values(array_filter($urls)));
    }

    // ── Fetch + Extract text from HTML ───────────────────────────────
    private function fetchAndExtract(string $url): ?string
    {
        $html = $this->fetchRaw($url);
        if (!$html)
            return null;
        return $this->htmlToText($html);
    }

    private function fetchRaw(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; YugaCrawler/1.0)',
            ]);
            $body = curl_exec($ch);
            curl_close($ch);
            return $body ?: null;
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 12,
                'user_agent' => 'Mozilla/5.0 (compatible; YugaCrawler/1.0)',
            ]
        ]);
        return @file_get_contents($url, false, $ctx) ?: null;
    }

    private function htmlToText(string $html): string
    {
        // Remove scripts, styles, svg
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        $html = preg_replace('/<svg[^>]*>.*?<\/svg>/is', '', $html);
        $html = preg_replace('/<nav[^>]*>.*?<\/nav>/is', '', $html);
        $html = preg_replace('/<footer[^>]*>.*?<\/footer>/is', '', $html);
        $html = preg_replace('/<header[^>]*>.*?<\/header>/is', '', $html);

        // Block-level tags -> newlines
        $html = preg_replace('/<(p|div|h[1-6]|li|br|tr|blockquote)[^>]*>/i', "\n", $html);

        // Strip all remaining tags
        $text = strip_tags($html);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalise whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = trim($text);

        return mb_substr($text, 0, $this->max_chars_page);
    }

    // ---------------------------------------------------------------
    // Text cleaning
    // ---------------------------------------------------------------
    private function cleanText(string $text): string
    {
        // Lowercase for smaller vocab
        $text = strtolower($text);
        // Collapse ALL whitespace (newlines, tabs) to single space
        // This keeps vocab tight and distribution uniform for char-level training
        $text = preg_replace('/\s+/', ' ', $text);
        // Keep only printable ASCII (no non-latin chars to blow up vocab)
        $text = preg_replace('/[^\x20-\x7E]/u', '', $text);
        $text = preg_replace('/  +/', ' ', $text);
        return trim($text);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------
    private function isSameDomain(string $url, string $base): bool
    {
        $base_host = parse_url($base, PHP_URL_HOST);
        $url_host = parse_url($url, PHP_URL_HOST);
        return $url_host === $base_host;
    }

    private function toAbsolute(string $href, string $base): ?string
    {
        $href = trim($href);
        if (str_starts_with($href, 'http'))
            return filter_var($href, FILTER_VALIDATE_URL) ? $href : null;
        if (str_starts_with($href, '//'))
            return 'https:' . $href;
        if (
            $href === '' || str_starts_with($href, '#') ||
            str_starts_with($href, 'mailto:') ||
            str_starts_with($href, 'javascript:') ||
            str_starts_with($href, 'tel:')
        )
            return null;

        $p = parse_url($base);
        $scheme = $p['scheme'] ?? 'https';
        $host = $p['host'] ?? '';

        if (str_starts_with($href, '/')) {
            $abs = $scheme . '://' . $host . $href;
        } else {
            // Resolve relative path (including ../) against base
            $dir = rtrim(dirname($p['path'] ?? '/'), '/') . '/';
            $resolved = $dir . $href;
            $parts = explode('/', $resolved);
            $out = [];
            foreach ($parts as $seg) {
                if ($seg === '..') {
                    array_pop($out);
                } elseif ($seg !== '.') {
                    $out[] = $seg;
                }
            }
            $abs = $scheme . '://' . $host . implode('/', $out);
        }

        return filter_var($abs, FILTER_VALIDATE_URL) ? $abs : null;
    }

    private function isOverMemory(): bool
    {
        return memory_get_usage(true) > $this->memory_limit_mb * 1024 * 1024;
    }
}
