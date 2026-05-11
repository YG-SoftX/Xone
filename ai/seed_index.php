<?php
/**
 * YGXONE Search — Index Seeder (v2 — uses GlobalCrawler.crawlQuery)
 * Run this ONCE to populate the search index so searches return results.
 *
 * Browser: http://localhost/yg-ai/seed_index.php?key=YOUR_ADMIN_KEY
 * CLI:     php seed_index.php
 *
 * Seeds the BM25 index with content from trusted sources so searches
 * return results immediately. The index grows further with every search.
 */
define('YUGA_ROOT', __DIR__);

// ── Auth (simple) ──────────────────────────────────────────────────────────
$config   = file_exists(YUGA_ROOT . '/config.php') ? require YUGA_ROOT . '/config.php' : [];
$isCli    = PHP_SAPI === 'cli';
$adminPwd = $config['admin_password'] ?? '';

if (!$isCli) {
    // Require ?key= param matching admin password or api_key
    $supplied = $_GET['key'] ?? '';
    $validKey = !empty($config['api_key']) && hash_equals($config['api_key'], $supplied);
    $validPwd = !empty($adminPwd)          && hash_equals($adminPwd, $supplied);
    if (!$validKey && !$validPwd) {
        http_response_code(403);
        die('Forbidden. Pass ?key=YOUR_ADMIN_KEY or ?key=YOUR_API_KEY');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

set_time_limit(600);
ini_set('memory_limit', '256M');

require_once YUGA_ROOT . '/core/SearchIndex.php';
require_once YUGA_ROOT . '/core/GlobalCrawler.php';

$dataDir = YUGA_ROOT . '/data';
$index   = new SearchIndex($dataDir);

function emit(string $msg): void {
    echo $msg . PHP_EOL;
    if (PHP_SAPI !== 'cli') { ob_flush(); flush(); }
}

emit("YGXONE Search Index Seeder");
emit("========================================");

$before = $index->stats();
emit("Current index: {$before['indexed_pages']} pages, {$before['total_terms']} terms");
emit("");

// ── GlobalCrawler setup ───────────────────────────────────────────────────
$gc = new GlobalCrawler($dataDir);
$gc->indexInstance        = $index;
$gc->max_pages_per_run    = 100;
$gc->max_pages_per_domain = 15;
$gc->delay                = 0.3;
$gc->timeout              = 10;

// ── Seed topic queries — covers a broad range of useful content ───────────
$seedQueries = [
    'artificial intelligence machine learning',
    'Nepal culture tourism Kathmandu',
    'search engine technology information retrieval',
    'PHP Laravel web development',
    'world news technology 2025',
    'climate environment science',
    'startup business entrepreneurship',
    'programming computer science',
];

emit("Seeding " . count($seedQueries) . " topic areas (~100 pages total)...");
emit("");

$totalCrawled = 0;
foreach ($seedQueries as $q) {
    emit("→ \"$q\"");
    try {
        $n = $gc->crawlQuery($q, 13);
        $totalCrawled += $n;
        emit("  $n pages indexed");
    } catch (Throwable $e) {
        emit("  SKIP: " . $e->getMessage());
    }
    sleep(1);
}

// ── Final stats ───────────────────────────────────────────────────────────
$stats = $index->stats();
emit("");
emit("========================================");
emit("Seeding complete!");
emit("Pages indexed : {$stats['indexed_pages']}  (was {$before['indexed_pages']})");
emit("Unique terms  : {$stats['total_terms']}");
emit("Total postings: {$stats['total_postings']}");
emit("========================================");

if ($stats['indexed_pages'] > 10) {
    emit("SUCCESS — search engine ready!");
    emit("Try: search.php?q=artificial+intelligence");
} else {
    emit("WARNING: Very few pages indexed.");
    emit("Make sure your server can make outbound HTTP requests.");
    emit("Or run Admin → Deep Crawl with a custom URL.");
}
