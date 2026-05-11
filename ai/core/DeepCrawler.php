<?php
/**
 * DeepCrawler — Recursive BFS site crawler
 *
 * Unlike SelfLearner (which only reads homepage + sitemap links),
 * DeepCrawler uses breadth-first search to recursively follow ALL
 * internal links — discovering the full site up to max_depth levels.
 *
 * Progress is saved between runs so a cron job can resume on large sites.
 *
 * Usage:
 *   $crawler = new DeepCrawler(YUGA_ROOT . '/data');
 *   $result  = $crawler->crawl('https://example.com', $brain);
 *
 * Via API:
 *   POST /api/?action=deep_crawl  {model, url, max_pages, max_depth}
 */
class DeepCrawler {

    private string $data_dir;

    public int   $max_pages    = 200;   // hard cap on total pages
    public int   $max_depth    = 5;     // BFS depth limit
    public float $delay        = 0.4;   // seconds between requests
    public int   $timeout      = 10;    // HTTP timeout per page
    public int   $max_chars    = 3000;  // chars extracted per page
    public int   $train_steps  = 20000; // training steps after crawl
    public int   $memory_limit = 96;    // MB — stop if exceeded

    private array  $queue          = [];
    private array  $visited        = [];
    private int    $pages          = 0;
    private string $domain         = '';
    private string $progress_file  = '';

    public function __construct(string $data_dir) {
        $this->data_dir = $data_dir;
    }

    // ── Main crawl entry point ─────────────────────────────────────────
    public function crawl(string $start_url, object $brain, ?callable $on_page = null): array {
        $start_url           = rtrim($start_url, '/');
        $this->domain        = parse_url($start_url, PHP_URL_HOST) ?? '';
        $this->progress_file = $this->data_dir . '/crawl_' . md5($start_url) . '.json';

        $this->loadProgress($start_url);

        $corpus    = '';
        $errors    = [];
        $new_pages = 0;

        while (!empty($this->queue) && $this->pages < $this->max_pages) {
            if ($this->isOverMemory()) {
                $errors[] = "Memory limit ({$this->memory_limit}MB) reached — crawl paused. Resume next run.";
                break;
            }

            $item  = array_shift($this->queue);
            $url   = $item['url'];
            $depth = $item['depth'];

            if (isset($this->visited[$url])) continue;
            if ($depth > $this->max_depth) { $this->visited[$url] = false; continue; }

            $html = $this->fetchRaw($url);
            if (!$html) {
                $this->visited[$url] = false;
                $errors[] = "Failed: $url";
                continue;
            }

            $text = $this->htmlToText($html);
            if (strlen(trim($text)) < 30) { $this->visited[$url] = false; continue; }

            $corpus .= "\n\n" . $text;
            $this->visited[$url] = time();
            $this->pages++;
            $new_pages++;

            if ($on_page) {
                $on_page([
                    'url'        => $url,
                    'depth'      => $depth,
                    'pages_done' => $this->pages,
                    'queue_size' => count($this->queue),
                    'chars'      => strlen($corpus),
                ]);
            }

            // BFS expansion — discover links on this page
            if ($depth < $this->max_depth) {
                foreach ($this->extractLinks($html, $url) as $link) {
                    if (!isset($this->visited[$link]) && !$this->inQueue($link)) {
                        $this->queue[] = ['url' => $link, 'depth' => $depth + 1];
                    }
                }
            }

            // Save progress checkpoint every 10 pages
            if ($new_pages % 10 === 0) $this->saveProgress();

            usleep((int)($this->delay * 1_000_000));
        }

        $this->saveProgress();

        // Train on collected corpus
        $train_result = ['loss' => 0, 'steps' => 0];
        if (strlen($corpus) > 100) {
            $clean        = $this->cleanText($corpus);
            $train_result = $brain->learn($clean, $this->train_steps);
        }

        $complete = empty($this->queue);
        if ($complete && file_exists($this->progress_file)) {
            unlink($this->progress_file);
        }

        return [
            'pages'      => $new_pages,
            'total_seen' => count($this->visited),
            'queued'     => count($this->queue),
            'loss'       => $train_result['loss']  ?? 0,
            'steps'      => $train_result['steps'] ?? 0,
            'chars'      => strlen($corpus),
            'complete'   => $complete,
            'domain'     => $this->domain,
            'errors'     => $errors,
        ];
    }

    // ── Check progress of an in-progress crawl ────────────────────────
    public function getProgress(string $start_url): array {
        $file = $this->data_dir . '/crawl_' . md5(rtrim($start_url, '/')) . '.json';
        if (!file_exists($file)) return ['started' => false, 'url' => $start_url];
        $d = json_decode(file_get_contents($file), true) ?? [];
        return [
            'started'    => true,
            'url'        => $start_url,
            'pages_seen' => count($d['visited'] ?? []),
            'queued'     => count($d['queue']   ?? []),
            'domain'     => $d['domain']         ?? '',
            'started_at' => $d['started_at']     ?? 0,
            'updated_at' => $d['updated_at']     ?? 0,
        ];
    }

    // ── Reset crawl state (re-crawl from scratch) ─────────────────────
    public function reset(string $start_url): void {
        $file = $this->data_dir . '/crawl_' . md5(rtrim($start_url, '/')) . '.json';
        if (file_exists($file)) unlink($file);
    }

    // ── Extract all same-domain links from HTML ───────────────────────
    private function extractLinks(string $html, string $page_url): array {
        $links = [];
        preg_match_all('/href=["\']([^"\'#?][^"\']*)["\']/', $html, $m);

        // Non-content extensions to skip
        static $skip_ext = ['jpg','jpeg','png','gif','svg','webp','ico','pdf',
                            'zip','gz','tar','css','js','woff','woff2','ttf','eot','mp4','mp3'];

        foreach ($m[1] as $href) {
            $href = trim($href);
            if (!$href) continue;
            if (str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')
             || str_starts_with($href, 'javascript:') || str_starts_with($href, '#')) continue;

            $abs = $this->toAbsolute($href, $page_url);
            if (!$abs || !filter_var($abs, FILTER_VALIDATE_URL)) continue;

            $ext = strtolower(pathinfo(parse_url($abs, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            if (in_array($ext, $skip_ext, true)) continue;

            if ($this->isSameDomain($abs)) {
                $links[] = strtok($abs, '?#'); // strip query string + fragment
            }
        }

        return array_unique($links);
    }

    // ── HTTP fetch ────────────────────────────────────────────────────
    private function fetchRaw(string $url): ?string {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_ENCODING       => 'gzip',
                CURLOPT_USERAGENT      => 'Yuga-DeepCrawler/1.0',
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ($body && $code < 400) ? $body : null;
        }

        $ctx  = stream_context_create(['http' => [
            'timeout'    => $this->timeout,
            'user_agent' => 'Yuga-DeepCrawler/1.0',
        ]]);
        $body = @file_get_contents($url, false, $ctx);
        return $body ?: null;
    }

    // ── HTML → clean text ─────────────────────────────────────────────
    private function htmlToText(string $html): string {
        $html = preg_replace('/<(script|style|nav|footer|header|aside)[^>]*>.*?<\/\1>/is', '', $html);
        $html = preg_replace('/<(p|div|h[1-6]|li|br|tr|blockquote)[^>]*>/i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return mb_substr(trim($text), 0, $this->max_chars);
    }

    // ── Prepare text for transformer training ─────────────────────────
    private function cleanText(string $text): string {
        $text = strtolower($text);
        $text = preg_replace('/[^\x20-\x7E]/u', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    // ── Helpers ───────────────────────────────────────────────────────
    private function isSameDomain(string $url): bool {
        return (parse_url($url, PHP_URL_HOST) ?? '') === $this->domain;
    }

    private function toAbsolute(string $href, string $base): ?string {
        if (str_starts_with($href, 'http')) return $href;
        if (str_starts_with($href, '//'))   return 'https:' . $href;
        if (!$href || str_starts_with($href, '#')) return null;
        $p      = parse_url($base);
        $scheme = $p['scheme'] ?? 'https';
        $host   = $p['host']   ?? $this->domain;
        if (str_starts_with($href, '/')) return "$scheme://$host$href";
        $dir = rtrim(dirname($p['path'] ?? '/'), '/');
        return "$scheme://$host$dir/$href";
    }

    private function inQueue(string $url): bool {
        foreach ($this->queue as $item) {
            if ($item['url'] === $url) return true;
        }
        return false;
    }

    private function isOverMemory(): bool {
        return memory_get_usage(true) > $this->memory_limit * 1024 * 1024;
    }

    // ── Persist + restore progress ────────────────────────────────────
    private function saveProgress(): void {
        $times = array_filter(array_values($this->visited), fn($v) => is_int($v) && $v > 0);
        file_put_contents($this->progress_file, json_encode([
            'domain'     => $this->domain,
            'visited'    => $this->visited,
            'queue'      => array_slice($this->queue, 0, 2000),
            'pages'      => $this->pages,
            'started_at' => $times ? min($times) : time(),
            'updated_at' => time(),
        ], JSON_UNESCAPED_SLASHES));
    }

    private function loadProgress(string $start_url): void {
        if (!file_exists($this->progress_file)) {
            $this->queue   = [['url' => $start_url, 'depth' => 0]];
            $this->visited = [];
            $this->pages   = 0;
            return;
        }
        $d             = json_decode(file_get_contents($this->progress_file), true) ?? [];
        $this->visited = $d['visited'] ?? [];
        $this->queue   = $d['queue']   ?? [['url' => $start_url, 'depth' => 0]];
        $this->pages   = $d['pages']   ?? 0;
    }
}
