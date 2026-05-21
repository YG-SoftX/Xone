<?php

namespace App\Services;

use App\Services\UnifiedSearchService;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;

/**
 * AgentToolService — Implements all tools the AI agent can invoke
 * during a browsing session. Uses DOMDocument/XPath for robust HTML
 * parsing with regex fallback for malformed HTML.
 *
 * Tools:
 *   navigate(url)              — navigate to a URL
 *   click(selector)            — simulate clicking an element
 *   type(selector, text)       — type text into a form field
 *   extract(selector)          — extract text content from elements
 *   extract_structured         — extract structured JSON from page (tables, lists, products, meta)
 *   extract_tables             — extract all tables as JSON
 *   scroll(direction)          — scroll the page
 *   search(query)              — search the YG ecosystem
 *   wait(seconds)              — wait for page load
 *   get_page_content()         — return the current page HTML summary
 */
class AgentToolService
{
    private BrowserProxyService $proxy;

    /** Current page HTML (fetched by navigate) */
    private string $currentPageHtml = '';

    /** Current page URL */
    private string $currentPageUrl = '';

    /** Extracted page metadata */
    private array $pageMeta = [];

    /** Results log for the agent loop */
    private array $toolResults = [];

    /** Cached DOMDocument for current page */
    private ?DOMDocument $dom = null;

    /** Cached DOMXPath for current page */
    private ?DOMXPath $xpath = null;

    /** Whether DOM parsing succeeded */
    private bool $domAvailable = false;

    // ── Page Cache (URL-keyed, per-session) ───────────────────────────────────

    /** URL-keyed page cache: url => { html, meta, tables, links, products, prices, dom, xpath, timestamp } */
    private array $pageCache = [];

    /** Max number of cached pages (LRU eviction) */
    private int $maxCacheEntries = 5;

    /** Cache TTL in seconds (30 min) */
    private int $cacheTtl = 1800;

    /** Cache hits counter for the current session */
    private int $cacheHits = 0;

    /** Cache misses counter */
    private int $cacheMisses = 0;

    public function __construct()
    {
        $this->proxy = app(BrowserProxyService::class);
    }

    // ── Public Tool API ──────────────────────────────────────────────────────

    /**
     * Execute a tool by name with arguments.
     */
    public function execute(string $toolName, array $args = []): array
    {
        $this->toolResults[] = ['tool' => $toolName, 'args' => $args];

        return match ($toolName) {
            'navigate'              => $this->navigate($args['url'] ?? ''),
            'click'                 => $this->click($args['selector'] ?? ''),
            'type'                  => $this->type($args['selector'] ?? '', $args['text'] ?? ''),
            'extract'               => $this->extract($args['selector'] ?? 'body'),
            'extract_structured'    => $this->extractStructured(),
            'extract_tables'        => $this->extractTables(),
            'extract_links'         => $this->extractLinks($args['filter'] ?? ''),
            'scroll'                => $this->scroll($args['direction'] ?? 'down'),
            'search'                => $this->search($args['query'] ?? ''),
            'wait'                  => $this->wait((int) ($args['seconds'] ?? 2)),
            'get_page_content'      => $this->getPageContent(),
            'get_page_title'        => $this->getPageTitle(),
            default                 => ['success' => false, 'error' => "Unknown tool: {$toolName}"],
        };
    }

    /**
     * Get the tool definitions for the LLM prompt.
     */
    public function getToolDefinitions(): string
    {
        return <<<'TOOLS'
Available tools (respond with [[TOOL:name|arg1=val1|arg2=val2]] to use a tool):

[[TOOL:navigate|url=https://example.com]]
  Navigate to a URL. Returns page summary (title, headings, links count, text excerpt).

[[TOOL:click|selector=.btn-primary]]
  Simulate clicking an element. Returns the HTML around the clicked element.

[[TOOL:type|selector=#search|text=hello world]]
  Type text into a form field. Returns the form HTML with the field filled.

[[TOOL:extract|selector=.product-title]]
  Extract text content from elements matching a CSS selector.
  Supports: #id, .class, tag, tag.class, [attr=value]

[[TOOL:extract_structured]]
  Extract ALL structured data from the current page as JSON.
  Returns: page metadata, all headings, all links, all forms, all tables,
  all lists, and detected product/price patterns.
  Best used to get a complete picture of the page.

[[TOOL:extract_tables]]
  Extract ALL tables from the current page as JSON arrays.
  Each table returned with headers and rows.

[[TOOL:extract_links|filter=keyword]]
  Extract all links from the page, optionally filtered by keyword in text or URL.

[[TOOL:scroll|direction=down]]
  Scroll the page up/down. Returns updated page excerpt.

[[TOOL:search|query=latest AI news]]
  Search the YG ecosystem (not the web). Returns search results.

[[TOOL:wait|seconds=3]]
  Wait for page to load. Use after navigation before extracting content.

[[TOOL:get_page_content]]
  Returns a summary of the current page (title, headings, links, text excerpt).

TOOLS;
    }

    // ── DOM Access ────────────────────────────────────────────────────────────

    /**
     * Initialize DOMDocument for the current page HTML.
     * Uses libxml error suppression for malformed HTML.
     */
    private function initDom(): void
    {
        if ($this->dom !== null) return;

        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->domAvailable = false;

        if (empty($this->currentPageHtml)) return;

        libxml_use_internal_errors(true);
        $loaded = $this->dom->loadHTML(
            '<?xml encoding="UTF-8">' . $this->currentPageHtml,
            LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET
        );
        libxml_clear_errors();

        if ($loaded) {
            $this->xpath = new DOMXPath($this->dom);
            $this->domAvailable = true;
        }
    }

    /**
     * Convert a CSS selector to an XPath query.
     * Supports: #id, .class, tag, tag.class, [attr=value], tag#id.class
     */
    private function cssToXPath(string $selector): string
    {
        // Handle body / * special case
        if ($selector === 'body' || $selector === '*') return '//body';

        $xpath = '//';

        // Extract tag
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)/', $selector, $m)) {
            $xpath .= $m[1];
            $selector = substr($selector, strlen($m[1]));
        } else {
            $xpath .= '*';
        }

        // Handle #id
        if (preg_match('/#([a-zA-Z][\w-]*)/', $selector, $m)) {
            $xpath .= "[@id='{$m[1]}']";
        }

        // Handle .class (supports multiple)
        if (preg_match_all('/\.([a-zA-Z][\w-]*)/', $selector, $m)) {
            foreach ($m[1] as $class) {
                $xpath .= "[contains(concat(' ',normalize-space(@class),' '),' {$class} ')]";
            }
        }

        // Handle [attr=value]
        if (preg_match('/\[([a-zA-Z][\w-]*)\s*=\s*["\']([^"\']+)["\']\]/', $selector, $m)) {
            $xpath .= "[@{$m[1]}='{$m[2]}']";
        }

        return $xpath;
    }

    // ── Tool Implementations ──────────────────────────────────────────────────

    /**
     * Navigate to a URL and capture page content.
     * Uses URL-keyed cache to avoid re-fetching recently visited pages.
     */
    public function navigate(string $url): array
    {
        if (empty($url)) {
            return ['success' => false, 'error' => 'No URL provided.'];
        }

        // Check cache first
        $cacheKey = $this->normalizeCacheKey($url);
        $cached = $this->getFromCache($cacheKey);

        if ($cached !== null) {
            // Restore from cache — no fetch needed
            $this->restoreFromCache($cached);
            $this->cacheHits++;

            return [
                'success' => true,
                'url'     => $this->currentPageUrl,
                'title'   => $this->pageMeta['title'],
                'summary' => $this->pageMeta['summary'],
                'links'   => $this->pageMeta['top_links'],
                'forms'   => $this->pageMeta['forms'],
                'cached'  => true,
            ];
        }

        $this->cacheMisses++;
        $result = $this->proxy->fetch($url);

        if ($result['statusCode'] >= 400) {
            return [
                'success' => false,
                'error'   => "Failed to load page (HTTP {$result['statusCode']}).",
            ];
        }

        $this->currentPageHtml  = $result['content'];
        $this->currentPageUrl   = $result['url'];
        $this->dom = null;       // Reset DOM cache
        $this->xpath = null;
        $this->domAvailable = false;
        $this->initDom();
        $this->pageMeta         = $this->extractPageMetaDom($result['content'], $result['title']);

        // Eagerly populate cache with all extracted data
        $this->storeInCache($result['url'], $result['content'], $result['title']);

        return [
            'success' => true,
            'url'     => $this->currentPageUrl,
            'title'   => $this->pageMeta['title'],
            'summary' => $this->pageMeta['summary'],
            'links'   => $this->pageMeta['top_links'],
            'forms'   => $this->pageMeta['forms'],
            'cached'  => false,
        ];
    }

    /**
     * Simulate clicking an element. Returns context around the element.
     */
    public function click(string $selector): array
    {
        if (empty($this->currentPageHtml)) {
            return ['success' => false, 'error' => 'No page loaded. Use navigate first.'];
        }

        if (empty($selector)) {
            return ['success' => false, 'error' => 'No selector provided.'];
        }

        $elementHtml = $this->findElementHtmlDom($selector);

        if ($elementHtml === null) {
            return [
                'success' => false,
                'error'   => "Element '{$selector}' not found on the current page.",
            ];
        }

        $linkUrl = $this->extractLinkHref($elementHtml);

        return [
            'success'       => true,
            'selector'      => $selector,
            'element_html'  => $this->truncateHtml($elementHtml, 500),
            'is_link'       => $linkUrl !== null,
            'link_url'      => $linkUrl,
            'suggestion'    => $linkUrl
                ? "This is a link to '{$linkUrl}'. Use [[TOOL:navigate|url={$linkUrl}]] to follow it."
                : 'This element was found. Use extract to get more details.',
        ];
    }

    /**
     * Type text into a form field.
     */
    public function type(string $selector, string $text): array
    {
        if (empty($this->currentPageHtml)) {
            return ['success' => false, 'error' => 'No page loaded. Use navigate first.'];
        }

        if (empty($selector)) {
            return ['success' => false, 'error' => 'No selector provided.'];
        }

        $elementHtml = $this->findElementHtmlDom($selector);

        if ($elementHtml === null) {
            return [
                'success' => false,
                'error'   => "Form field '{$selector}' not found.",
            ];
        }

        $formHtml = $this->findParentFormDom($selector);

        return [
            'success'     => true,
            'selector'    => $selector,
            'typed_text'  => $text,
            'form_html'   => $formHtml
                ? $this->truncateHtml($formHtml, 600)
                : 'No parent form found.',
            'form_action' => $this->extractFormAction($formHtml),
            'suggestion'  => $formHtml
                ? "Text '{$text}' typed into '{$selector}'. The form submits to '{$this->extractFormAction($formHtml)}'. You may want to tell the user how to submit, or use click on the submit button."
                : "Text '{$text}' typed into '{$selector}'.",
        ];
    }

    /**
     * Extract text content from elements matching a CSS selector.
     * Uses DOMXPath with regex fallback.
     */
    public function extract(string $selector): array
    {
        if (empty($this->currentPageHtml)) {
            return ['success' => false, 'error' => 'No page loaded. Use navigate first.'];
        }

        $texts = $this->extractTextBySelectorDom($selector);

        if (empty($texts)) {
            return [
                'success'  => false,
                'error'    => "No content found for selector '{$selector}'.",
                'selector' => $selector,
            ];
        }

        $combined = implode("\n---\n", array_map('trim', $texts));
        if (strlen($combined) > 3000) {
            $combined = substr($combined, 0, 3000) . "\n... (truncated, " . count($texts) . " elements found)";
        }

        return [
            'success'      => true,
            'selector'     => $selector,
            'count'        => count($texts),
            'content'      => $combined,
        ];
    }

    /**
     * Extract ALL structured data from the page as JSON.
     * Uses cache for tables/links/products/prices when available.
     */
    public function extractStructured(): array
    {
        if (empty($this->currentPageHtml)) {
            return ['success' => false, 'error' => 'No page loaded. Use navigate first.'];
        }

        $cacheKey = $this->normalizeCacheKey($this->currentPageUrl);
        $cached = $this->pageCache[$cacheKey] ?? null;

        return [
            'success'   => true,
            'url'       => $this->currentPageUrl,
            'meta'      => $this->pageMeta,
            'tables'    => $cached['tables'] ?? $this->extractTables()['tables'] ?? [],
            'links'     => $cached['links'] ?? $this->extractAllLinksDom(),
            'products'  => $cached['products'] ?? $this->detectProducts(),
            'prices'    => $cached['prices'] ?? $this->detectPrices(),
            'from_cache'=> $cached !== null,
        ];
    }

    /**
     * Extract ALL tables from the current page as JSON.
     * Uses cache when available.
     */
    public function extractTables(): array
    {
        if (empty($this->currentPageHtml)) {
            return ['success' => false, 'error' => 'No page loaded. Use navigate first.'];
        }

        // Check cache first
        $cacheKey = $this->normalizeCacheKey($this->currentPageUrl);
        $cached = $this->pageCache[$cacheKey] ?? null;
        if ($cached !== null && isset($cached['tables'])) {
            return [
                'success'   => true,
                'count'     => count($cached['tables']),
                'tables'    => $cached['tables'],
                'from_cache'=> true,
            ];
        }

        $this->initDom();
        $tables = [];

        if ($this->domAvailable) {
            $tableNodes = $this->xpath->query('//table');
            foreach ($tableNodes as $idx => $table) {
                $tableData = $this->parseTableDom($table);
                if (!empty($tableData['headers']) || !empty($tableData['rows'])) {
                    $tables[] = $tableData;
                }
            }
        }

        // Fallback: regex-based table extraction
        if (empty($tables)) {
            $tables = $this->extractTablesRegex();
        }

        return [
            'success'   => true,
            'count'     => count($tables),
            'tables'    => $tables,
        ];
    }

    /**
     * Extract all links from the page, optionally filtered.
     * Uses cache when no filter is applied.
     */
    public function extractLinks(string $filter = ''): array
    {
        if (empty($this->currentPageHtml)) {
            return ['success' => false, 'error' => 'No page loaded. Use navigate first.'];
        }

        // Use cache if no filter (cache stores all links)
        $cacheKey = $this->normalizeCacheKey($this->currentPageUrl);
        $cached = $this->pageCache[$cacheKey] ?? null;
        $links = empty($filter) && $cached !== null
            ? ($cached['links'] ?? $this->extractAllLinksDom())
            : $this->extractAllLinksDom();

        if (!empty($filter)) {
            $filterLower = strtolower($filter);
            $links = array_filter($links, function ($link) use ($filterLower) {
                return str_contains(strtolower($link['text']), $filterLower)
                    || str_contains(strtolower($link['url']), $filterLower);
            });
        }

        return [
            'success' => true,
            'count'   => count($links),
            'links'   => array_values(array_slice($links, 0, 50)),
        ];
    }

    /**
     * Scroll the page. Since we're server-side, this returns an updated page excerpt.
     */
    public function scroll(string $direction): array
    {
        if (empty($this->currentPageHtml)) {
            return ['success' => false, 'error' => 'No page loaded. Use navigate first.'];
        }

        return [
            'success'   => true,
            'direction' => $direction,
            'note'      => 'Scrolling is simulated server-side. Use get_page_content for the current view.',
            'summary'   => $this->pageMeta['summary'] ?? 'No summary available.',
        ];
    }

    /**
     * Search the YG ecosystem (delegates to UnifiedSearchService).
     */
    public function search(string $query): array
    {
        if (empty($query)) {
            return ['success' => false, 'error' => 'No search query provided.'];
        }

        try {
            $searchService = app(UnifiedSearchService::class);
            $results = $searchService->search($query, ['type' => 'web', 'per_page' => 5]);

            $items = [];
            foreach ($results['web'] as $r) {
                $items[] = [
                    'title'       => $r['title'] ?? 'Untitled',
                    'url'         => $r['url'] ?? '',
                    'description' => substr(strip_tags($r['description'] ?? ''), 0, 200),
                ];
            }

            return [
                'success'     => true,
                'query'       => $query,
                'total'       => $results['meta']['total'] ?? count($items),
                'results'     => $items,
            ];
        } catch (\Exception $e) {
            Log::error("Agent search failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Search service unavailable.'];
        }
    }

    /**
     * Wait for a number of seconds.
     */
    public function wait(int $seconds): array
    {
        $seconds = max(0, min(10, $seconds));
        sleep($seconds);

        return [
            'success' => true,
            'waited'  => $seconds,
            'note'    => "Waited {$seconds} seconds. Page should be loaded now.",
        ];
    }

    /**
     * Get a summary of the current page.
     */
    public function getPageContent(): array
    {
        if (empty($this->currentPageHtml)) {
            return ['success' => false, 'error' => 'No page loaded. Use navigate first.'];
        }

        return [
            'success' => true,
            'url'     => $this->currentPageUrl,
            'meta'    => $this->pageMeta,
        ];
    }

    /**
     * Get the current page title.
     */
    public function getPageTitle(): array
    {
        return [
            'success' => true,
            'title'   => $this->pageMeta['title'] ?? 'Unknown',
            'url'     => $this->currentPageUrl,
        ];
    }

    // ── DOM-Based Page Metadata ───────────────────────────────────────────────

    /**
     * Extract page metadata using DOMDocument (primary) with regex fallback.
     */
    private function extractPageMetaDom(string $html, ?string $title = null): array
    {
        $this->initDom();

        if ($this->domAvailable) {
            return $this->extractPageMetaWithDom($title);
        }

        return $this->extractPageMetaRegex($html, $title);
    }

    /**
     * Extract page metadata using DOMDocument/XPath.
     */
    private function extractPageMetaWithDom(?string $fallbackTitle = null): array
    {
        // Title
        $titleNodes = $this->xpath->query('//title');
        $title = $fallbackTitle;
        if ($titleNodes->length > 0) {
            $title = trim($titleNodes->item(0)->textContent);
        }

        // Headings
        $headings = [];
        foreach (['h1', 'h2', 'h3'] as $tag) {
            $nodes = $this->xpath->query("//{$tag}");
            foreach ($nodes as $node) {
                $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
                if (!empty($text)) {
                    $headings[] = $text;
                    if (count($headings) >= 10) break 2;
                }
            }
        }

        // Links
        $linkNodes = $this->xpath->query('//a[@href]');
        $topLinks = [];
        $count = 0;
        foreach ($linkNodes as $link) {
            $text = trim(preg_replace('/\s+/', ' ', $link->textContent));
            $href = $link->getAttribute('href');
            if (!empty($text) && strlen($text) < 200 && !empty($href)) {
                $topLinks[] = ['text' => $text, 'url' => $href];
                $count++;
                if ($count >= 15) break;
            }
        }

        // Forms
        $formNodes = $this->xpath->query('//form');
        $forms = [];
        foreach ($formNodes as $form) {
            $action = $form->getAttribute('action') ?: '(current page)';
            $method = strtoupper($form->getAttribute('method') ?: 'GET');
            $formId = $form->getAttribute('id') ?: '';

            // Input fields
            $fields = [];
            $inputNodes = $this->xpath->query('.//input[@name]', $form);
            foreach ($inputNodes as $input) {
                $name = $input->getAttribute('name');
                $type = $input->getAttribute('type') ?: 'text';
                $fields[] = "{$name} [{$type}]";
            }
            $selectNodes = $this->xpath->query('.//select[@name]', $form);
            foreach ($selectNodes as $sel) {
                $fields[] = $sel->getAttribute('name') . ' [select]';
            }

            // Buttons
            $buttons = [];
            $btnNodes = $this->xpath->query('.//button | .//input[@type="submit"]', $form);
            foreach ($btnNodes as $btn) {
                $btnText = trim($btn->textContent) ?: $btn->getAttribute('value');
                if (!empty($btnText)) $buttons[] = $btnText;
            }

            $forms[] = [
                'id'      => $formId,
                'action'  => $action,
                'method'  => $method,
                'fields'  => $fields,
                'buttons' => $buttons,
            ];
        }

        // Text excerpt
        $bodyNodes = $this->xpath->query('//body');
        $excerpt = '';
        if ($bodyNodes->length > 0) {
            $text = preg_replace('/\s+/', ' ', trim($bodyNodes->item(0)->textContent));
            $excerpt = substr($text, 0, 1500);
        }

        return [
            'title'     => $title ?: 'Unknown',
            'headings'  => $headings,
            'top_links' => $topLinks,
            'forms'     => $forms,
            'summary'   => $excerpt ?: '(no readable text content)',
        ];
    }

    /**
     * Fallback regex-based page metadata extraction.
     */
    private function extractPageMetaRegex(string $html, ?string $title = null): array
    {
        if (!$title) {
            preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m);
            $title = trim(strip_tags($m[1] ?? ''));
        }

        preg_match_all('/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $html, $headings);
        $headingTexts = array_map(function ($h) {
            return trim(strip_tags($h));
        }, array_slice($headings[1] ?? [], 0, 10));

        preg_match_all('/<a\b[^>]*?\bhref\s*=\s*["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $links);
        $topLinks = [];
        for ($i = 0; $i < min(15, count($links[0])); $i++) {
            $linkText = trim(strip_tags($links[2][$i]));
            if (!empty($linkText) && strlen($linkText) < 200) {
                $topLinks[] = ['text' => $linkText, 'url' => $links[1][$i]];
            }
        }

        preg_match_all('/<form\b[^>]*>(.*?)<\/form>/is', $html, $forms);
        $formSummaries = [];
        foreach ($forms[0] as $f) {
            preg_match('/action\s*=\s*["\']([^"\']+)["\']/is', $f, $action);
            preg_match('/method\s*=\s*["\']([^"\']+)["\']/is', $f, $method);
            preg_match_all('/<input\b[^>]*name\s*=\s*["\']([^"\']+)["\'][^>]*>/is', $f, $inputs);
            preg_match_all('/<button\b[^>]*>(.*?)<\/button>/is', $f, $buttons);
            $buttonTexts = array_map(fn($b) => trim(strip_tags($b)), $buttons[1] ?? []);
            $formId = '';
            preg_match('/id\s*=\s*["\']([^"\']+)["\']/is', $f, $idMatch);
            $formId = $idMatch[1] ?? '';

            $formSummaries[] = [
                'id'      => $formId,
                'action'  => $action[1] ?? '',
                'method'  => strtoupper($method[1] ?? 'GET'),
                'fields'  => $inputs[1] ?? [],
                'buttons' => $buttonTexts,
            ];
        }

        $textContent = strip_tags($html);
        $textContent = preg_replace('/\s+/', ' ', $textContent);
        $excerpt = substr(trim($textContent), 0, 1500);

        return [
            'title'     => $title,
            'headings'  => $headingTexts,
            'top_links' => $topLinks,
            'forms'     => $formSummaries,
            'summary'   => $excerpt ?: '(no readable text content)',
        ];
    }

    // ── DOM-Based Element Finding ─────────────────────────────────────────────

    /**
     * Find an element's outer HTML using DOMXPath (primary) with regex fallback.
     */
    private function findElementHtmlDom(string $selector): ?string
    {
        $this->initDom();

        if ($this->domAvailable) {
            $xpath = $this->cssToXPath($selector);
            $nodes = $this->xpath->query($xpath);
            if ($nodes && $nodes->length > 0) {
                $html = $this->dom->saveHTML($nodes->item(0));
                if (!empty($html)) return $html;
            }
        }

        return $this->findElementHtmlRegex($selector);
    }

    /**
     * Fallback regex-based element finding.
     */
    private function findElementHtmlRegex(string $selector): ?string
    {
        if (str_starts_with($selector, '#')) {
            $id = substr($selector, 1);
            if (preg_match('/<[^>]*\bid\s*=\s*["\']' . preg_quote($id, '/') . '["\'][^>]*>.*?<\/\w+>/is', $this->currentPageHtml, $m)) {
                return $m[0];
            }
            if (preg_match('/<[^>]*\bid\s*=\s*["\']' . preg_quote($id, '/') . '["\'][^>]*\/?>/is', $this->currentPageHtml, $m)) {
                return $m[0];
            }
        }

        if (str_starts_with($selector, '.')) {
            $class = substr($selector, 1);
            if (preg_match('/<[^>]*\bclass\s*=\s*["\'][^"\']*\b' . preg_quote($class, '/') . '\b[^"\']*["\'][^>]*>.*?<\/\w+>/is', $this->currentPageHtml, $m)) {
                return $m[0];
            }
        }

        if (preg_match('/<' . preg_quote($selector, '/') . '\b[^>]*>.*?<\/' . preg_quote($selector, '/') . '>/is', $this->currentPageHtml, $m)) {
            return $m[0];
        }

        $clean = ltrim($selector, '#.');
        if (preg_match('/<[^>]*\b(?:id|class|name)\s*=\s*["\'][^"\']*' . preg_quote($clean, '/') . '[^"\']*["\'][^>]*>.*?<\/\w+>/is', $this->currentPageHtml, $m)) {
            return $m[0];
        }

        return null;
    }

    /**
     * Extract text by selector using DOMXPath (primary) with regex fallback.
     */
    private function extractTextBySelectorDom(string $selector): array
    {
        $this->initDom();

        if ($this->domAvailable && $selector !== 'body' && $selector !== '*') {
            $xpath = $this->cssToXPath($selector);
            $nodes = $this->xpath->query($xpath);
            $results = [];
            foreach ($nodes as $node) {
                $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
                if (!empty($text)) {
                    $results[] = $text;
                }
                if (count($results) >= 20) break;
            }
            if (!empty($results)) return $results;
        }

        return $this->extractTextBySelectorRegex($selector);
    }

    /**
     * Fallback regex-based text extraction.
     */
    private function extractTextBySelectorRegex(string $selector): array
    {
        if ($selector === 'body' || $selector === '*') {
            $body = '';
            if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $this->currentPageHtml, $m)) {
                $body = strip_tags($m[1]);
            } else {
                $body = strip_tags($this->currentPageHtml);
            }
            $body = preg_replace('/\s+/', ' ', trim($body));
            return [substr($body, 0, 5000)];
        }

        $results = [];
        $clean = ltrim($selector, '#.');
        $pattern = '/<(?:div|span|p|section|article|li|td|th|h\d|a|button|label|strong|em)\b[^>]*\b(?:id|class)\s*=\s*["\']([^"\']*' . preg_quote($clean, '/') . '[^"\']*)["\'][^>]*>(.*?)<\/(?:div|span|p|section|article|li|td|th|h\d|a|button|label|strong|em)>/is';

        if (preg_match_all($pattern, $this->currentPageHtml, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $text = trim(strip_tags($m[2]));
                if (!empty($text)) $results[] = $text;
            }
        }

        return array_slice($results, 0, 20);
    }

    /**
     * Find parent form using DOMXPath (primary) with regex fallback.
     */
    private function findParentFormDom(string $selector): ?string
    {
        $this->initDom();

        if ($this->domAvailable) {
            $xpath = $this->cssToXPath($selector);
            $nodes = $this->xpath->query($xpath);
            if ($nodes && $nodes->length > 0) {
                $node = $nodes->item(0);
                // Walk up to find ancestor form
                while ($node && $node->nodeName !== 'form') {
                    $node = $node->parentNode;
                }
                if ($node && $node->nodeName === 'form') {
                    return $this->dom->saveHTML($node);
                }
            }
        }

        return $this->findParentFormRegex($selector);
    }

    /**
     * Fallback regex for finding parent form.
     */
    private function findParentFormRegex(string $selector): ?string
    {
        $clean = ltrim($selector, '#.');
        $pos = false;
        if (preg_match('/<(?:input|select|textarea|button)\b[^>]*\b(?:id|class|name)\s*=\s*["\']' . preg_quote($clean, '/') . '["\'][^>]*>/is', $this->currentPageHtml, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1];
        }

        if ($pos === false) return null;

        $before = substr($this->currentPageHtml, 0, $pos);
        $formStart = strrpos($before, '<form');
        if ($formStart === false) return null;

        $formEnd = stripos($this->currentPageHtml, '</form>', $pos);
        if ($formEnd === false) $formEnd = strlen($this->currentPageHtml);

        return substr($this->currentPageHtml, $formStart, $formEnd - $formStart + 7);
    }

    // ── Structured Data Extraction ────────────────────────────────────────────

    /**
     * Parse a DOM table node into a structured array.
     */
    private function parseTableDom(\DOMElement $table): array
    {
        $headers = [];
        $rows = [];

        // Try thead first
        $theadRows = $this->xpath->query('.//thead//tr', $table);
        if ($theadRows->length > 0) {
            $thNodes = $this->xpath->query('.//th', $theadRows->item(0));
            foreach ($thNodes as $th) {
                $headers[] = trim(preg_replace('/\s+/', ' ', $th->textContent));
            }
        }

        // If no thead, try first row with th
        if (empty($headers)) {
            $firstRowTh = $this->xpath->query('.//tr[1]/th', $table);
            if ($firstRowTh->length > 0) {
                foreach ($firstRowTh as $th) {
                    $headers[] = trim(preg_replace('/\s+/', ' ', $th->textContent));
                }
            }
        }

        // Body rows
        $bodyRows = $this->xpath->query('.//tbody//tr | .//tr[td]', $table);
        foreach ($bodyRows as $tr) {
            $cells = [];
            $tdNodes = $this->xpath->query('.//td | .//th', $tr);
            foreach ($tdNodes as $td) {
                $cells[] = trim(preg_replace('/\s+/', ' ', $td->textContent));
            }
            if (!empty($cells)) {
                $rows[] = $cells;
            }
            if (count($rows) >= 50) break;
        }

        // If no headers but we have rows, use first row as headers
        if (empty($headers) && !empty($rows)) {
            $headers = array_shift($rows);
        }

        return [
            'headers'  => $headers,
            'row_count'=> count($rows),
            'rows'     => array_slice($rows, 0, 25), // Limit to 25 rows
        ];
    }

    /**
     * Fallback regex table extraction.
     */
    private function extractTablesRegex(): array
    {
        $tables = [];
        preg_match_all('/<table\b[^>]*>(.*?)<\/table>/is', $this->currentPageHtml, $tableMatches, PREG_SET_ORDER);

        foreach ($tableMatches as $tableMatch) {
            $tableHtml = $tableMatch[0];
            $headers = [];
            $rows = [];

            // Extract headers
            if (preg_match_all('/<th[^>]*>(.*?)<\/th>/is', $tableHtml, $thMatches)) {
                foreach ($thMatches[1] as $th) {
                    $headers[] = trim(strip_tags($th));
                }
            }

            // Extract rows
            preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $tableHtml, $trMatches);
            foreach ($trMatches[1] as $trHtml) {
                // Skip if this is a header row
                if (preg_match('/<th[^>]*>/i', $trHtml) && empty($rows)) continue;

                preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $trHtml, $tdMatches);
                $cells = array_map(fn($c) => trim(strip_tags($c)), $tdMatches[1] ?? []);
                if (!empty($cells)) $rows[] = $cells;
            }

            if (!empty($headers) || !empty($rows)) {
                $tables[] = [
                    'headers'  => $headers,
                    'row_count'=> count($rows),
                    'rows'     => array_slice($rows, 0, 25),
                ];
            }
            if (count($tables) >= 5) break;
        }

        return $tables;
    }

    /**
     * Extract all links using DOMDocument.
     */
    private function extractAllLinksDom(): array
    {
        $links = [];

        if ($this->domAvailable) {
            $nodes = $this->xpath->query('//a[@href]');
            foreach ($nodes as $link) {
                $text = trim(preg_replace('/\s+/', ' ', $link->textContent));
                $href = $link->getAttribute('href');
                if (!empty($href) && !str_starts_with($href, 'javascript:')) {
                    $links[] = ['text' => $text, 'url' => $href];
                }
                if (count($links) >= 100) break;
            }
            return $links;
        }

        // Regex fallback
        preg_match_all('/<a\b[^>]*?\bhref\s*=\s*["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $this->currentPageHtml, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $text = trim(strip_tags($m[2]));
            $href = $m[1];
            if (!empty($href) && !str_starts_with($href, 'javascript:')) {
                $links[] = ['text' => $text, 'url' => $href];
            }
            if (count($links) >= 100) break;
        }

        return $links;
    }

    /**
     * Detect product-like patterns in the page.
     * Looks for product cards, items with prices, etc.
     */
    private function detectProducts(): array
    {
        $products = [];

        // Look for common product card patterns
        $patterns = [
            // Price patterns: $XX.XX, €XX.XX, etc.
            '/\$[\d,]+\.?\d*/',
            '/€[\d,]+\.?\d*/',
            '/£[\d,]+\.?\d*/',
            '/\d+\.?\d*\s*(?:USD|EUR|GBP)/',
        ];

        $pricePattern = implode('|', array_map(fn($p) => '(' . trim($p, '/') . ')', $patterns));

        // Try to find product containers
        $this->initDom();
        if ($this->domAvailable) {
            // Common product selectors
            $productSelectors = [
                '[class*="product"]', '[class*="item"]', '[class*="card"]',
                'li[class*="result"]', 'div[data-product]',
                '[class*="listing"]', '[class*="search-result"]',
            ];

            foreach ($productSelectors as $sel) {
                try {
                    $xpath = $this->cssToXPath($sel);
                    $nodes = $this->xpath->query($xpath);
                    foreach ($nodes as $node) {
                        $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
                        if (strlen($text) > 20 && strlen($text) < 1000) {
                            // Check if it contains a price
                            if (preg_match('/' . $pricePattern . '/i', $text)) {
                                $products[] = [
                                    'text'  => substr($text, 0, 300),
                                    'has_price' => true,
                                ];
                            }
                        }
                        if (count($products) >= 10) break 2;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return $products;
    }

    /**
     * Detect price values throughout the page.
     */
    private function detectPrices(): array
    {
        $prices = [];
        $patterns = [
            '/\$\s*([\d,]+\.?\d*)/',
            '/€\s*([\d,]+\.?\d*)/',
            '/£\s*([\d,]+\.?\d*)/',
            '/([\d,]+\.?\d*)\s*USD/',
            '/([\d,]+\.?\d*)\s*EUR/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $this->currentPageHtml, $matches)) {
                foreach ($matches[0] as $m) {
                    $price = trim($m);
                    if (!in_array($price, $prices)) {
                        $prices[] = $price;
                    }
                }
            }
        }

        return array_slice(array_unique($prices), 0, 20);
    }

    // ── Disk-Based Page Cache ──────────────────────────────────────────────────

    /** Disk cache directory */
    private string $diskCacheDir;

    /** Max disk cache entries */
    private int $maxDiskCacheEntries = 50;

    /** Disk cache TTL in seconds (10 min) */
    private int $diskCacheTtl = 600;

    /** Initialize disk cache directory */
    private function initDiskCache(): void
    {
        $this->diskCacheDir = storage_path('app/agent-cache/pages');
        if (!is_dir($this->diskCacheDir)) {
            mkdir($this->diskCacheDir, 0755, true);
        }
    }

    /**
     * Store page data to disk cache (persists beyond session).
     */
    private function storeToDiskCache(string $url, array $data): void
    {
        $this->initDiskCache();
        $key = $this->normalizeCacheKey($url);
        $file = $this->diskCacheDir . '/' . md5($key) . '.json';

        $entry = [
            'url'       => $data['url'] ?? $url,
            'html'      => $data['html'] ?? '',
            'title'     => $data['title'] ?? null,
            'meta'      => $data['meta'] ?? [],
            'tables'    => $data['tables'] ?? [],
            'links'     => $data['links'] ?? [],
            'products'  => $data['products'] ?? [],
            'prices'    => $data['prices'] ?? [],
            'timestamp' => time(),
        ];

        file_put_contents($file, json_encode($entry, JSON_UNESCAPED_SLASHES), LOCK_EX);

        // Evict old entries if over limit
        $this->evictDiskCache();
    }

    /**
     * Get page data from disk cache.
     */
    private function getFromDiskCache(string $url): ?array
    {
        $this->initDiskCache();
        $key = $this->normalizeCacheKey($url);
        $file = $this->diskCacheDir . '/' . md5($key) . '.json';

        if (!file_exists($file)) {
            return null;
        }

        // Check TTL
        if ((time() - filemtime($file)) > $this->diskCacheTtl) {
            @unlink($file);
            return null;
        }

        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : null;
    }

    /**
     * Evict oldest disk cache entries when over limit.
     */
    private function evictDiskCache(): void
    {
        $files = glob($this->diskCacheDir . '/*.json');
        if (count($files) <= $this->maxDiskCacheEntries) {
            return;
        }

        // Sort by modification time (oldest first)
        usort($files, fn($a, $b) => filemtime($a) - filemtime($b));

        // Delete oldest entries
        $toDelete = array_slice($files, 0, count($files) - $this->maxDiskCacheEntries);
        foreach ($toDelete as $f) {
            @unlink($f);
        }
    }

    /**
     * Clear all disk cache entries.
     */
    public function clearDiskCache(): void
    {
        $this->initDiskCache();
        foreach (glob($this->diskCacheDir . '/*.json') as $f) {
            @unlink($f);
        }
    }

    /**
     * Get disk cache statistics.
     */
    public function getDiskCacheStats(): array
    {
        $this->initDiskCache();
        $files = glob($this->diskCacheDir . '/*.json') ?: [];
        $totalSize = 0;
        $oldest = null;
        $newest = null;

        foreach ($files as $f) {
            $totalSize += filesize($f);
            $mtime = filemtime($f);
            if ($oldest === null || $mtime < $oldest) $oldest = $mtime;
            if ($newest === null || $mtime > $newest) $newest = $mtime;
        }

        return [
            'entries' => count($files),
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1048576, 2),
            'oldest_entry' => $oldest ? date('Y-m-d H:i:s', $oldest) : null,
            'newest_entry' => $newest ? date('Y-m-d H:i:s', $newest) : null,
            'cache_dir' => $this->diskCacheDir,
        ];
    }

    // ── In-Memory Page Cache (LRU) ────────────────────────────────────────────

    /**
     * Normalize a URL for cache key purposes.
     * Strips fragments, trailing slashes, and www prefix for dedup.
     */
    private function normalizeCacheKey(string $url): string
    {
        $parsed = parse_url($url);
        $host = strtolower($parsed['host'] ?? '');
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }
        $path = rtrim($parsed['path'] ?? '/', '/');
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        return ($parsed['scheme'] ?? 'https') . '://' . $host . $path . $query;
    }

    /**
     * Store page data in the in-memory cache.
     */
    private function storeInCache(string $url, string $html, ?string $title = null): void
    {
        $key = $this->normalizeCacheKey($url);

        if (count($this->pageCache) >= $this->maxCacheEntries) {
            $oldestKey = null;
            $oldestTime = PHP_INT_MAX;
            foreach ($this->pageCache as $k => $entry) {
                if ($entry['timestamp'] < $oldestTime) {
                    $oldestTime = $entry['timestamp'];
                    $oldestKey = $k;
                }
            }
            if ($oldestKey !== null) {
                unset($this->pageCache[$oldestKey]);
            }
        }

        $meta = $this->extractPageMetaDom($html, $title);
        $tables = $this->extractTables()['tables'] ?? [];
        $links = $this->extractAllLinksDom();
        $products = $this->detectProducts();
        $prices = $this->detectPrices();

        $this->pageCache[$key] = [
            'url'       => $url,
            'html'      => $html,
            'title'     => $title,
            'meta'      => $meta,
            'tables'    => $tables,
            'links'     => $links,
            'products'  => $products,
            'prices'    => $prices,
            'timestamp' => time(),
        ];

        // Also persist to disk cache
        $this->storeToDiskCache($url, [
            'url' => $url, 'html' => $html, 'title' => $title,
            'meta' => $meta, 'tables' => $tables, 'links' => $links,
            'products' => $products, 'prices' => $prices,
        ]);
    }

    /**
     * Get from in-memory cache first, then disk cache fallback.
     */
    private function getFromCache(string $cacheKey): ?array
    {
        if (isset($this->pageCache[$cacheKey])) {
            $entry = $this->pageCache[$cacheKey];
            if ((time() - $entry['timestamp']) > $this->cacheTtl) {
                unset($this->pageCache[$cacheKey]);
                return null;
            }
            $this->pageCache[$cacheKey]['timestamp'] = time();
            return $entry;
        }

        // Fallback to disk cache (deserialize without re-fetching)
        $diskData = $this->getFromDiskCache($cacheKey);
        if ($diskData !== null) {
            // Promote to in-memory cache
            $this->pageCache[$cacheKey] = $diskData;
            $this->pageCache[$cacheKey]['timestamp'] = time();
            return $this->pageCache[$cacheKey];
        }

        return null;
    }

    /**
     * Restore agent state from a cached entry.
     */
    private function restoreFromCache(array $cached): void
    {
        $this->currentPageHtml  = $cached['html'];
        $this->currentPageUrl   = $cached['url'];
        $this->pageMeta         = $cached['meta'];
        $this->dom = null;
        $this->xpath = null;
        $this->domAvailable = false;
        $this->initDom();
    }

    /**
     * Get cache statistics (in-memory + disk).
     */
    public function getCacheStats(): array
    {
        $diskStats = $this->getDiskCacheStats();
        return [
            'memory' => [
                'entries' => count($this->pageCache),
                'hits'    => $this->cacheHits,
                'misses'  => $this->cacheMisses,
                'ratio'   => ($this->cacheHits + $this->cacheMisses) > 0
                    ? round(($this->cacheHits / ($this->cacheHits + $this->cacheMisses)) * 100, 1)
                    : 0,
            ],
            'disk' => $diskStats,
        ];
    }

    /**
     * Clear both in-memory and disk cache.
     */
    public function clearCache(): void
    {
        $this->pageCache = [];
        $this->cacheHits = 0;
        $this->cacheMisses = 0;
        $this->clearDiskCache();
    }

    // ── Helpers Shared with Old Code ──────────────────────────────────────────

    /**
     * Extract the href from a link element.
     */
    private function extractLinkHref(string $elementHtml): ?string
    {
        if (preg_match('/href\s*=\s*["\']([^"\']+)["\']/is', $elementHtml, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Extract the form action URL.
     */
    private function extractFormAction(?string $formHtml): string
    {
        if (!$formHtml) return '(no form)';
        if (preg_match('/action\s*=\s*["\']([^"\']+)["\']/is', $formHtml, $m)) {
            return $m[1];
        }
        return '(current page)';
    }

    /**
     * Truncate HTML for display.
     */
    private function truncateHtml(string $html, int $maxLen): string
    {
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', trim($text));
        if (strlen($text) > $maxLen) {
            return substr($text, 0, $maxLen) . '...';
        }
        return $text;
    }
}
