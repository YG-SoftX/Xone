<?php
/**
 * GlobalCrawler — Cross-domain web crawler for the YG Sovereign Search Index
 *
 * Unlike DeepCrawler (same-domain only), GlobalCrawler follows links ACROSS
 * the entire open web, building a broad full-text index in SearchIndex (SQLite).
 *
 * Features:
 *   - Cross-domain BFS with domain diversity cap
 *   - Categorised seed list (news, tech, science, business, etc.)
 *   - sitemap.xml discovery — indexes full sites automatically
 *   - RSS/Atom feed parsing — fresh content without a news API
 *   - robots.txt caching — polite crawler
 *   - Query-driven on-demand mode — fetch relevant pages for a specific query
 *   - Resumable: frontier persisted as JSON; runs safely under cron
 *   - Indexes DIRECTLY into SearchIndex for BM25 search — no external API needed
 *
 * Usage:
 *   $gc = new GlobalCrawler(YUGA_ROOT . '/data');
 *
 *   // Start a broad background crawl of all seed categories:
 *   $gc->crawlSeeds(['news','tech','science','business','education']);
 *
 *   // On-demand: fetch pages relevant to a query (called from WebSearch):
 *   $gc->crawlQuery('machine learning tutorials');
 */
class GlobalCrawler
{
    private string $data_dir;
    private string $frontier_file;
    private string $robots_cache_file;

    // ── Limits ────────────────────────────────────────────────────────
    public int   $max_pages_per_run  = 300;   // pages crawled per run()
    public int   $max_pages_per_domain = 50;  // domain diversity cap
    public int   $max_depth         = 4;      // BFS depth limit
    public float $delay             = 0.25;   // seconds between requests
    public int   $timeout           = 10;     // HTTP timeout per request
    public int   $max_chars         = 50000;  // text chars per page (for indexing)
    public int   $memory_limit_mb   = 110;    // stop crawl if RAM exceeds this

    // ── Skip list ─────────────────────────────────────────────────────
    private static array $SKIP_EXT = [
        'jpg','jpeg','png','gif','svg','webp','ico','bmp',
        'pdf','zip','gz','tar','rar','7z',
        'css','js','json','xml','woff','woff2','ttf','eot',
        'mp4','mp3','avi','mov','ogg','wav',
    ];

    // ── Categorised seed domains ──────────────────────────────────────
    // These are globally significant, freely crawlable sites.
    // The crawler follows cross-domain links, so the index grows far beyond these seeds.
    public static array $SEEDS = [
        'news' => [
            'https://www.bbc.com/news',
            'https://feeds.bbci.co.uk/news/rss.xml',          // RSS
            'https://apnews.com',
            'https://www.reuters.com',
            'https://www.aljazeera.com',
            'https://www.theguardian.com/international',
            'https://www.npr.org',
            'https://thehill.com',
            'https://www.huffpost.com',
            'https://www.vox.com',
        ],
        'tech' => [
            'https://news.ycombinator.com',
            'https://dev.to',
            'https://techcrunch.com',
            'https://www.theverge.com/tech',
            'https://arstechnica.com',
            'https://www.wired.com',
            'https://www.zdnet.com',
            'https://developer.mozilla.org/en-US/docs/Web/JavaScript',
            'https://developer.mozilla.org/en-US/docs/Learn',
            'https://css-tricks.com',
            'https://smashingmagazine.com',
            'https://stackoverflow.com/questions?tab=Newest&pagesize=50',
        ],
        'science' => [
            'https://en.wikipedia.org/wiki/Main_Page',
            'https://en.wikipedia.org/wiki/Portal:Science',
            'https://www.sciencedaily.com',
            'https://www.scientificamerican.com',
            'https://phys.org',
            'https://www.nasa.gov/news',
            'https://www.nature.com/news',
            'https://www.newscientist.com',
            'https://bigthink.com',
            'https://www.popsci.com',
        ],
        'business' => [
            'https://www.investopedia.com',
            'https://www.economist.com',
            'https://hbr.org',
            'https://www.fastcompany.com',
            'https://fortune.com',
            'https://www.inc.com',
            'https://www.entrepreneur.com',
            'https://www.businessinsider.com',
            'https://finance.yahoo.com',
        ],
        'education' => [
            'https://www.khanacademy.org',
            'https://www.coursera.org/articles',
            'https://www.edx.org/learn',
            'https://en.wikibooks.org/wiki/Main_Page',
            'https://www.britannica.com',
            'https://www.howstuffworks.com',
            'https://www.thoughtco.com',
            'https://www.sparknotes.com',
            'https://www.cliffsnotes.com',
        ],
        'health' => [
            'https://www.webmd.com',
            'https://www.healthline.com',
            'https://www.mayoclinic.org',
            'https://medlineplus.gov',
            'https://www.nih.gov/news-events',
            'https://www.cdc.gov',
            'https://www.who.int/news',
        ],
        'culture' => [
            'https://www.smithsonianmag.com',
            'https://www.historytoday.com',
            'https://www.nationalgeographic.com',
            'https://www.atlasobscura.com',
            'https://lithub.com',
            'https://www.poetryfoundation.org',
            'https://www.artsy.net/articles',
        ],
        'general' => [
            'https://en.wikipedia.org/wiki/Special:Random',
            'https://www.wikihow.com/Main-Page',
            'https://www.quora.com',
            'https://medium.com',
            'https://substack.com',
            'https://www.reddit.com/r/explainlikeimfive/top/?t=month',
            'https://www.mentalfloss.com',
            'https://www.snopes.com',
        ],
    ];

    // Category keywords for query-driven seed selection
    private static array $CATEGORY_KEYWORDS = [
        'news'      => ['news','latest','today','breaking','report','announce','update','2024','2025','2026'],
        'tech'      => ['javascript','python','php','code','programming','developer','api','software','framework',
                        'database','linux','docker','git','web','app','mobile','ai','machine learning','algorithm'],
        'science'   => ['science','physics','chemistry','biology','research','study','experiment','discovery',
                        'space','nasa','climate','quantum','atom','gene','dna','evolution'],
        'business'  => ['business','startup','invest','stock','finance','market','economy','company','revenue',
                        'entrepreneur','venture','ipo','profit','growth','strategy'],
        'health'    => ['health','medical','disease','symptom','treatment','medicine','diet','exercise',
                        'mental health','vaccine','cancer','diabetes','nutrition'],
        'education' => ['learn','tutorial','course','how to','guide','explained','introduction','beginners',
                        'study','school','university','degree','lesson','training'],
    ];

    public function __construct(string $data_dir)
    {
        $this->data_dir         = rtrim($data_dir, '/');
        $this->frontier_file    = $this->data_dir . '/global_frontier.json';
        $this->robots_cache_file = $this->data_dir . '/robots_cache.json';
    }

    // ── Broad seed crawl (run from admin or cron) ─────────────────────
    /**
     * @param  string[] $categories  e.g. ['news','tech','science']  — empty = all
     * @param  callable|null $on_page  progress callback
     */
    public function crawlSeeds(array $categories = [], ?callable $on_page = null): array
    {
        $idx = $this->getIndex();

        if (empty($categories)) $categories = array_keys(self::$SEEDS);

        $seeds = [];
        foreach ($categories as $cat) {
            foreach (self::$SEEDS[$cat] ?? [] as $url) {
                $seeds[] = ['url' => $url, 'depth' => 0, 'category' => $cat];
            }
        }

        return $this->runCrawl($seeds, $idx, $on_page);
    }

    // ── Query-driven on-demand crawl ──────────────────────────────────
    /**
     * Called by WebSearch when the index has no / too few results.
     * Detects the query's category and fetches from relevant seed sources.
     * Returns the number of new pages indexed.
     */
    public function crawlQuery(string $query, int $maxPages = 30): int
    {
        $idx   = $this->getIndex();
        $cats  = $this->detectCategories($query);
        $terms = $this->queryTerms($query);

        // Build seed URLs for this query
        $seeds = [];

        // Wikipedia is always a reliable starting point for any topic
        $wikiSlug = implode('_', array_map('ucfirst', array_slice($terms, 0, 4)));
        $wikiEnc  = urlencode(implode(' ', array_slice($terms, 0, 4)));
        $seeds[] = ['url' => 'https://en.wikipedia.org/wiki/' . $wikiSlug,             'depth' => 0, 'category' => 'general'];
        $seeds[] = ['url' => 'https://en.wikipedia.org/w/index.php?search=' . $wikiEnc, 'depth' => 0, 'category' => 'general'];
        $seeds[] = ['url' => 'https://www.britannica.com/search?query=' . $wikiEnc,     'depth' => 0, 'category' => 'education'];

        // RSS feeds for news queries
        if (in_array('news', $cats, true)) {
            $seeds[] = ['url' => 'https://feeds.bbci.co.uk/news/rss.xml',             'depth' => 0, 'category' => 'news'];
            $seeds[] = ['url' => 'https://apnews.com/rss',                            'depth' => 0, 'category' => 'news'];
        }

        // Category-specific seeds (top 3 per category)
        foreach ($cats as $cat) {
            foreach (array_slice(self::$SEEDS[$cat] ?? [], 0, 3) as $url) {
                $seeds[] = ['url' => $url, 'depth' => 0, 'category' => $cat];
            }
        }

        $saved = $this->max_pages_per_run;
        $this->max_pages_per_run = $maxPages;
        $result = $this->runCrawl($seeds, $idx, null, $terms);
        $this->max_pages_per_run = $saved;

        return $result['pages'] ?? 0;
    }

    // ── Core BFS crawl loop ───────────────────────────────────────────
    private function runCrawl(
        array    $seeds,
        object   $idx,
        ?callable $on_page,
        array    $filterTerms = []
    ): array {
        $frontier     = $this->loadFrontier();
        $domainCounts = $frontier['domain_counts'] ?? [];
        $visited      = $frontier['visited']       ?? [];

        // Enqueue seeds that haven't been visited
        foreach ($seeds as $s) {
            $url = $s['url'];
            if (!isset($visited[$url]) && !$this->inFrontier($frontier['queue'] ?? [], $url)) {
                $frontier['queue'][] = $s;
            }
        }

        $pages   = 0;
        $errors  = 0;
        $indexed = 0;

        while (!empty($frontier['queue']) && $pages < $this->max_pages_per_run) {

            if ($this->overMemory()) break;

            $item     = array_shift($frontier['queue']);
            $url      = $item['url'];
            $depth    = (int) ($item['depth'] ?? 0);
            $category = $item['category'] ?? 'general';

            if (isset($visited[$url])) continue;
            if ($depth > $this->max_depth) {
                $visited[$url] = 'skip';
                continue;
            }

            $domain = parse_url($url, PHP_URL_HOST) ?? '';

            // Domain diversity cap
            if (($domainCounts[$domain] ?? 0) >= $this->max_pages_per_domain) {
                $visited[$url] = 'capped';
                continue;
            }

            $visited[$url] = time();
            $pages++;

            // ── Fetch ──────────────────────────────────────────────────
            $html = $this->fetch($url);
            if (!$html) {
                $errors++;
                continue;
            }

            // ── Parse RSS/Atom (if URL is a feed) ─────────────────────
            if ($this->isFeed($html, $url)) {
                $feedUrls = $this->parseFeed($html);
                foreach ($feedUrls as $fu) {
                    if (!isset($visited[$fu]) && !$this->inFrontier($frontier['queue'], $fu)) {
                        $frontier['queue'][] = ['url' => $fu, 'depth' => $depth + 1, 'category' => $category];
                    }
                }
                continue;   // don't index the feed XML itself
            }

            // ── Try sitemap.xml on root domains ───────────────────────
            if ($depth === 0) {
                $this->enqueueSitemapUrls($url, $visited, $frontier['queue']);
            }

            // ── Extract metadata + text ────────────────────────────────
            $meta = $this->extractMeta($html, $url, $domain);
            if (strlen($meta['content']) < 50) continue;

            // Filter relevance for query-driven mode
            if ($filterTerms && !$this->isRelevant($meta, $filterTerms)) continue;

            // ── Index into SearchIndex (SQLite BM25) ──────────────────
            $idx->indexPage($meta);
            $domainCounts[$domain] = ($domainCounts[$domain] ?? 0) + 1;
            $indexed++;

            if ($on_page) {
                $on_page([
                    'url'     => $url,
                    'depth'   => $depth,
                    'indexed' => $indexed,
                    'pages'   => $pages,
                    'queued'  => count($frontier['queue']),
                ]);
            }

            // ── Discover links (cross-domain) ──────────────────────────
            if ($depth < $this->max_depth) {
                $links = $this->extractLinks($html, $url);
                foreach ($links as $link) {
                    $ld = parse_url($link, PHP_URL_HOST) ?? '';
                    if (isset($visited[$link])) continue;
                    if (($domainCounts[$ld] ?? 0) >= $this->max_pages_per_domain) continue;
                    if ($this->inFrontier($frontier['queue'], $link)) continue;
                    if (!$this->isAllowed($link)) continue;
                    $frontier['queue'][] = ['url' => $link, 'depth' => $depth + 1, 'category' => $category];
                }
            }

            // Checkpoint every 20 pages
            if ($pages % 20 === 0) {
                $this->saveFrontier([
                    'queue'        => array_slice($frontier['queue'], 0, 5000),
                    'visited'      => $visited,
                    'domain_counts' => $domainCounts,
                ]);
            }

            if ($this->delay > 0) usleep((int)($this->delay * 1_000_000));
        }

        $this->saveFrontier([
            'queue'         => array_slice($frontier['queue'], 0, 5000),
            'visited'       => $visited,
            'domain_counts' => $domainCounts,
        ]);

        return [
            'pages'   => $pages,
            'indexed' => $indexed,
            'errors'  => $errors,
            'queued'  => count($frontier['queue']),
            'domains' => count($domainCounts),
        ];
    }

    // ── Detect query categories ───────────────────────────────────────
    public function detectCategories(string $query): array
    {
        $q    = strtolower($query);
        $cats = [];
        foreach (self::$CATEGORY_KEYWORDS as $cat => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($q, $kw)) {
                    $cats[] = $cat;
                    break;
                }
            }
        }
        return $cats ?: ['general'];
    }

    // ── Check if page content is relevant to query terms ─────────────
    private function isRelevant(array $meta, array $terms): bool
    {
        if (empty($terms)) return true;
        $text  = strtolower($meta['title'] . ' ' . $meta['description'] . ' ' . mb_substr($meta['content'], 0, 2000));
        $hits  = 0;
        foreach ($terms as $t) {
            if (str_contains($text, $t)) $hits++;
        }
        return $hits >= max(1, intdiv(count($terms), 3));
    }

    // ── Extract query terms (stopword-stripped) ───────────────────────
    private function queryTerms(string $query): array
    {
        static $stop = ['what','is','are','how','the','a','an','of','in','on','at','to',
                        'for','and','or','but','with','about','does','do','can','will'];
        $words = preg_split('/\W+/', strtolower($query)) ?: [];
        return array_values(array_filter($words, fn($w) => strlen($w) >= 3 && !in_array($w, $stop, true)));
    }

    // ── Extract page metadata ─────────────────────────────────────────
    private function extractMeta(string $html, string $url, string $domain): array
    {
        // Title
        preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $tm);
        $title = html_entity_decode(strip_tags($tm[1] ?? ''), ENT_QUOTES, 'UTF-8');

        // Meta description (try both attribute orders)
        preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']{0,500})/i', $html, $dm);
        if (empty($dm[1])) {
            preg_match('/<meta[^>]+content=["\']([^"\']{0,500})[^>]+name=["\']description["\']/i', $html, $dm);
        }
        $description = html_entity_decode(trim($dm[1] ?? ''), ENT_QUOTES, 'UTF-8');

        // OG description fallback
        if (!$description) {
            preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']{0,500})/i', $html, $ogd);
            $description = html_entity_decode(trim($ogd[1] ?? ''), ENT_QUOTES, 'UTF-8');
        }

        // Body text
        $content = $this->htmlToText($html);

        // Language detection (simple heuristic)
        $lang = 'en';
        preg_match('/<html[^>]+lang=["\']([a-z]{2})/i', $html, $lm);
        if (!empty($lm[1])) $lang = strtolower($lm[1]);

        return [
            'url'         => $url,
            'domain'      => $domain,
            'title'       => mb_substr(trim($title), 0, 255),
            'description' => mb_substr($description, 0, 500),
            'content'     => mb_substr($content, 0, $this->max_chars),
            'lang'        => $lang,
            'status'      => 200,
        ];
    }

    // ── sitemap.xml discovery ─────────────────────────────────────────
    private function enqueueSitemapUrls(string $page_url, array &$visited, array &$queue): void
    {
        $parsed = parse_url($page_url);
        if (!$parsed) return;
        $root    = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        $sitemap = $root . '/sitemap.xml';

        if (isset($visited[$sitemap])) return;
        $visited[$sitemap] = 'sitemap';

        $xml = $this->fetch($sitemap);
        if (!$xml) return;

        // Parse sitemap index or urlset
        preg_match_all('/<loc>\s*(https?[^<]+)\s*<\/loc>/i', $xml, $locs);
        foreach (array_slice($locs[1], 0, 100) as $loc) {
            $loc = trim($loc);
            if (!filter_var($loc, FILTER_VALIDATE_URL)) continue;
            // Sub-sitemaps
            if (str_ends_with($loc, '.xml') || str_contains($loc, 'sitemap')) {
                $sub = $this->fetch($loc);
                if ($sub) {
                    preg_match_all('/<loc>\s*(https?[^<]+)\s*<\/loc>/i', $sub, $sl);
                    foreach (array_slice($sl[1], 0, 200) as $su) {
                        $su = trim($su);
                        if (!isset($visited[$su]) && filter_var($su, FILTER_VALIDATE_URL)) {
                            $queue[] = ['url' => $su, 'depth' => 1, 'category' => 'general'];
                        }
                    }
                }
                continue;
            }
            if (!isset($visited[$loc])) {
                $queue[] = ['url' => $loc, 'depth' => 1, 'category' => 'general'];
            }
        }
    }

    // ── RSS/Atom parsing ──────────────────────────────────────────────
    private function isFeed(string $body, string $url): bool
    {
        return str_ends_with(strtolower(parse_url($url, PHP_URL_PATH) ?? ''), '.xml')
            || str_contains($body, '<rss ')
            || str_contains($body, '<feed ')
            || str_contains($body, '<channel>');
    }

    private function parseFeed(string $xml): array
    {
        // <link> or <link href="..."> or <enclosure url="...">
        $urls = [];
        preg_match_all('/<link[^>]*>([^<]+)<\/link>/i', $xml, $m1);
        foreach ($m1[1] as $u) {
            $u = trim(strip_tags($u));
            if (filter_var($u, FILTER_VALIDATE_URL) && !str_ends_with($u, '.xml')) $urls[] = $u;
        }
        preg_match_all('/<link[^>]+href=["\']([^"\']+)["\'][^>]*\/?>/i', $xml, $m2);
        foreach ($m2[1] as $u) {
            $u = trim($u);
            if (filter_var($u, FILTER_VALIDATE_URL) && !str_ends_with($u, '.xml')) $urls[] = $u;
        }
        return array_unique(array_slice($urls, 0, 50));
    }

    // ── Cross-domain link extraction ──────────────────────────────────
    private function extractLinks(string $html, string $page_url): array
    {
        $parsed = parse_url($page_url);
        $scheme = $parsed['scheme'] ?? 'https';
        $host   = $parsed['host']   ?? '';

        preg_match_all('/href=["\']([^"\'#?][^"\']*)["\']/', $html, $m);

        $links = [];
        foreach ($m[1] as $href) {
            $href = trim($href);
            if (!$href) continue;
            if (str_starts_with($href, 'mailto:') || str_starts_with($href, 'javascript:')) continue;

            // Resolve relative URLs
            if (str_starts_with($href, '//')) {
                $href = $scheme . ':' . $href;
            } elseif (str_starts_with($href, '/')) {
                $href = $scheme . '://' . $host . $href;
            } elseif (!str_starts_with($href, 'http')) {
                continue;
            }

            $href = strtok($href, '#');     // drop fragments
            if (!filter_var($href, FILTER_VALIDATE_URL)) continue;

            $ext = strtolower(pathinfo(parse_url($href, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            if (in_array($ext, self::$SKIP_EXT, true)) continue;

            $links[] = $href;
            if (count($links) >= 60) break;
        }

        return array_unique($links);
    }

    // ── HTML → clean plain text ───────────────────────────────────────
    private function htmlToText(string $html): string
    {
        $html = preg_replace(
            '/<(script|style|nav|footer|header|aside|form|noscript|iframe|svg)[^>]*>.*?<\/\1>/is',
            '', $html
        );
        $html = preg_replace('/<(p|div|h[1-6]|li|br|tr|article|section|blockquote)[^>]*>/i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    // ── robots.txt compliance ─────────────────────────────────────────
    private function isAllowed(string $url): bool
    {
        $parsed = parse_url($url);
        if (!$parsed) return false;
        $root   = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        $path   = $parsed['path'] ?? '/';

        $cache = $this->loadRobotsCache();
        if (!isset($cache[$root])) {
            $robots = $this->fetch($root . '/robots.txt');
            $cache[$root] = $this->parseRobots($robots ?? '');
            $this->saveRobotsCache($cache);
        }

        $rules = $cache[$root];
        foreach ($rules as $disallow) {
            if ($disallow && str_starts_with($path, $disallow)) return false;
        }
        return true;
    }

    private function parseRobots(string $txt): array
    {
        $disallow = [];
        $relevant = false;
        foreach (explode("\n", $txt) as $line) {
            $line = trim($line);
            if (stripos($line, 'User-agent:') === 0) {
                $ua       = trim(substr($line, 11));
                $relevant = ($ua === '*' || stripos($ua, 'Yuga') !== false);
            }
            if ($relevant && stripos($line, 'Disallow:') === 0) {
                $p = trim(substr($line, 9));
                if ($p) $disallow[] = $p;
            }
        }
        return $disallow;
    }

    // ── HTTP fetch ────────────────────────────────────────────────────
    private function fetch(string $url): ?string
    {
        if (!function_exists('curl_init')) {
            $ctx  = stream_context_create(['http' => [
                'timeout'    => $this->timeout,
                'user_agent' => 'YugaBot/1.0 (+https://ygxone.com/bot)',
                'header'     => "Accept: text/html,application/xml,application/rss+xml\r\n",
            ]]);
            $body = @file_get_contents($url, false, $ctx);
            return ($body && strlen($body) > 100) ? $body : null;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_ENCODING       => 'gzip',
            CURLOPT_USERAGENT      => 'YugaBot/1.0 (+https://ygxone.com/bot)',
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml,application/rss+xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
            ],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($body && $code < 400 && strlen($body) > 100) ? $body : null;
    }

    // ── Crawl statistics ──────────────────────────────────────────────
    public function stats(): array
    {
        $f = $this->loadFrontier();
        $idx = $this->getIndex();
        return [
            'frontier_queued'     => count($f['queue']         ?? []),
            'frontier_visited'    => count($f['visited']       ?? []),
            'domains_crawled'     => count($f['domain_counts'] ?? []),
            'index'               => $idx->stats(),
        ];
    }

    // ── Reset frontier ────────────────────────────────────────────────
    public function resetFrontier(): void
    {
        if (file_exists($this->frontier_file)) unlink($this->frontier_file);
    }

    // ── Helpers ───────────────────────────────────────────────────────
    private function overMemory(): bool
    {
        return memory_get_usage(true) > $this->memory_limit_mb * 1024 * 1024;
    }

    private function inFrontier(array $queue, string $url): bool
    {
        foreach ($queue as $item) {
            if ($item['url'] === $url) return true;
        }
        return false;
    }

    private function loadFrontier(): array
    {
        if (!file_exists($this->frontier_file)) return ['queue' => [], 'visited' => [], 'domain_counts' => []];
        return json_decode(file_get_contents($this->frontier_file), true)
            ?? ['queue' => [], 'visited' => [], 'domain_counts' => []];
    }

    private function saveFrontier(array $data): void
    {
        file_put_contents($this->frontier_file, json_encode($data, JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    private function loadRobotsCache(): array
    {
        if (!file_exists($this->robots_cache_file)) return [];
        return json_decode(file_get_contents($this->robots_cache_file), true) ?? [];
    }

    private function saveRobotsCache(array $cache): void
    {
        // Keep only recent 500 domains
        if (count($cache) > 500) $cache = array_slice($cache, -500, null, true);
        file_put_contents($this->robots_cache_file, json_encode($cache, JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    // ── Lazy-load SearchIndex ─────────────────────────────────────────
    public ?object $indexInstance = null;

    public function getIndex(): object
    {
        if ($this->indexInstance !== null) return $this->indexInstance;
        if (!class_exists('SearchIndex')) {
            require_once __DIR__ . '/SearchIndex.php';
        }
        $this->indexInstance = new SearchIndex($this->data_dir);
        return $this->indexInstance;
    }
}
