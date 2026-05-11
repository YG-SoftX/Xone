#!/usr/bin/env php
<?php
/**
 * Yuga Corpus Builder
 *
 * Assembles the training corpus. The bigger and better your corpus,
 * the better Yuga will generate text in that domain.
 *
 * Usage:
 *   php build_corpus.php --url=https://yoursite.com --pages=50
 *   php build_corpus.php --append --text="Your custom text here"
 *   php build_corpus.php --append --file=/path/to/docs.txt
 *   php build_corpus.php --stats
 *
 * For best results aim for at least:
 *   100KB text (nano model)
 *   500KB text (small model)
 */

define('YUGA_ROOT', dirname(__DIR__));

$args = [];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--')) {
        [$k, $v] = array_pad(explode('=', substr($arg, 2), 2), 2, true);
        $args[$k] = $v;
    }
}

$corpus_file = $args['output'] ?? YUGA_ROOT . '/training/corpus.txt';
$append      = isset($args['append']);

function log_c(string $msg): void { echo '[' . date('H:i:s') . '] ' . $msg . PHP_EOL; }

function fetch_url(string $url): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true, CURLOPT_USERAGENT => 'YugaCorpusBuilder/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $r = curl_exec($ch); curl_close($ch);
        return $r ?: null;
    }
    return @file_get_contents($url, false, stream_context_create(['http'=>['timeout'=>15]])) ?: null;
}

function html_to_text(string $html): string {
    $html = preg_replace('/<(script|style|nav|footer|header|aside)[^>]*>.*?<\/\1>/is', '', $html);
    $html = preg_replace('/<(p|div|h[1-6]|li|br|tr|blockquote)[^>]*>/i', "\n", $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim($text);
}

function clean_for_lm(string $text): string {
    // Keep only printable ASCII + newlines
    $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text);
    // Normalise line endings
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    // Collapse excess blank lines
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim($text) . "\n";
}

// ── Stats mode ───────────────────────────────────────────────────────
if (isset($args['stats'])) {
    if (!file_exists($corpus_file)) { log_c("No corpus file: $corpus_file"); exit(1); }
    $corpus = file_get_contents($corpus_file);
    $chars  = strlen($corpus);
    $unique = count(array_unique(str_split($corpus)));
    $lines  = substr_count($corpus, "\n");
    $words  = str_word_count($corpus);
    log_c("Corpus: $corpus_file");
    log_c("Size: " . number_format($chars) . " chars  (~" . round($chars/1024) . "KB)");
    log_c("Words: " . number_format($words));
    log_c("Lines: " . number_format($lines));
    log_c("Unique chars (vocab): $unique");
    log_c("Estimated training sequences (CTX=64, stride=32): " . intdiv(max(0, $chars - 65), 32));
    exit(0);
}

// ── Append raw text ──────────────────────────────────────────────────
if (isset($args['text'])) {
    $text = clean_for_lm($args['text']);
    $flag = $append ? FILE_APPEND : 0;
    file_put_contents($corpus_file, "\n\n" . $text . "\n", $flag);
    log_c("Added " . strlen($text) . " chars to corpus.");
    exit(0);
}

// ── Harvest JSON Datasets (Social Intelligence) ─────────────────────
if (isset($args['harvest'])) {
    $dir = $args['harvest'];
    if (!is_dir($dir)) { log_c("Directory not found: $dir"); exit(1); }
    
    $files = glob($dir . '/*.json');
    log_c("Found " . count($files) . " datasets for ingestion.");
    
    $combined_text = '';
    foreach ($files as $file) {
        $json = json_decode(file_get_contents($file), true);
        if (!$json || !isset($json['data'])) continue;
        
        log_c("  Processing: " . basename($file) . " [" . count($json['data']) . " samples]");
        foreach ($json['data'] as $sample) {
            $text = "### INSTRUCTION: " . ($sample['instruction'] ?? 'Analyze content') . "\n";
            $text .= "### INPUT: " . ($sample['input'] ?? '') . "\n";
            if (isset($sample['metadata'])) {
                $text .= "### CONTEXT: " . json_encode($sample['metadata']) . "\n";
            }
            $text .= "### RESPONSE: (Model Evaluation State)\n\n";
            $combined_text .= clean_for_lm($text);
        }
    }
    
    $flag = $append ? FILE_APPEND : 0;
    file_put_contents($corpus_file, "\n\n" . $combined_text . "\n", $flag);
    log_c("Added " . strlen($combined_text) . " chars of social intelligence to corpus.");
    exit(0);
}

// ── Harvest Financial Datasets (YG Pay Intelligence) ────────────────
if (isset($args['harvest-financial'])) {
    $dir = $args['harvest-financial'];
    if (!is_dir($dir)) { log_c("Directory not found: $dir"); exit(1); }

    require_once YUGA_ROOT . '/core/FinancialAnalyst.php';
    $analyst = new \Yuga\Core\FinancialAnalyst([], YUGA_ROOT . '/data');
    
    $files = glob($dir . '/*.json');
    $combined_text = '';
    foreach ($files as $file) {
        $json = json_decode(file_get_contents($file), true);
        if (!$json) continue;

        log_c("  Analyzing Financial Trends: " . basename($file));
        $analysis = $analyst->analyze($json);
        
        $text = "### FINANCIAL REPORT: " . date('Y-m-d') . "\n";
        foreach ($analysis['insights'] as $insight) $text .= "- " . $insight . "\n";
        foreach ($analysis['metrics'] as $k => $v) $text .= "- {$k}: " . (is_numeric($v) ? number_format($v, 2) : $v) . "\n";
        $text .= "\n";
        $combined_text .= clean_for_lm($text);
    }

    $flag = $append ? FILE_APPEND : 0;
    file_put_contents($corpus_file, "\n\n" . $combined_text . "\n", $flag);
    log_c("Added " . strlen($combined_text) . " chars of financial intelligence to corpus.");
    exit(0);
}

// ── Harvest Private Documents (YG Drive Sovereignty) ────────────────
if (isset($args['harvest-private'])) {
    $dir = $args['harvest-private']; // format: storage/yuga/private
    if (!is_dir($dir)) { log_c("Directory not found: $dir"); exit(1); }

    require_once YUGA_ROOT . '/core/Retriever.php';
    require_once YUGA_ROOT . '/core/Brain.php';
    require_once YUGA_ROOT . '/core/Ingester.php';
    
    // We mock the brain/store for corpus building
    $ingester = new \Ingester(new class { public function learn($t) { return []; } });

    $userDirs = glob($dir . '/*', GLOB_ONLYDIR);
    foreach ($userDirs as $userDir) {
        $userId = basename($userDir);
        $userCorpus = YUGA_ROOT . "/training/corpus_private_{$userId}.txt";
        log_c("Building Private Corpus for User: {$userId}");

        $files = glob($userDir . '/*');
        $userText = '';
        foreach ($files as $file) {
            log_c("  Ingesting Private Doc: " . basename($file));
            $res = $ingester->ingestFile($file);
            if (isset($res['chars'])) {
                $userText .= "### PRIVATE DOCUMENT: " . basename($file) . "\n" . $res['source'] . "\n\n";
            }
        }
        
        file_put_contents($userCorpus, clean_for_lm($userText), $append ? FILE_APPEND : 0);
        log_c("  Sovereign Corpus Updated: " . strlen($userText) . " chars.");
    }
    exit(0);
}

// ── Append from file ─────────────────────────────────────────────────
if (isset($args['file'])) {
    $src = $args['file'];
    if (!file_exists($src)) { log_c("File not found: $src"); exit(1); }
    $text = clean_for_lm(file_get_contents($src));
    $flag = $append ? FILE_APPEND : 0;
    file_put_contents($corpus_file, "\n\n" . $text . "\n", $flag);
    log_c("Added " . strlen($text) . " chars from $src");
    exit(0);
}

// ── Crawl URL / site ─────────────────────────────────────────────────
if (isset($args['url'])) {
    $base_url  = rtrim($args['url'], '/');
    $max_pages = (int)($args['pages'] ?? 30);
    $min_chars = (int)($args['min_chars'] ?? 200);  // skip very short pages

    log_c("Building corpus from: $base_url");
    log_c("Max pages: $max_pages");

    $queue   = [$base_url];
    $visited = [];
    $corpus  = '';
    $pages   = 0;

    // Try sitemap.xml first — much faster than crawling
    $sm = fetch_url($base_url . '/sitemap.xml');
    if ($sm && str_contains($sm, '<loc>')) {
        preg_match_all('/<loc>(.*?)<\/loc>/s', $sm, $m);
        foreach ($m[1] as $u) {
            $u = trim($u);
            if (parse_url($u, PHP_URL_HOST) === parse_url($base_url, PHP_URL_HOST)) {
                $queue[] = $u;
            }
        }
        log_c("Found " . count($queue) . " URLs from sitemap.xml");
    }

    foreach (array_unique($queue) as $url) {
        if ($pages >= $max_pages) break;
        if (isset($visited[$url])) continue;
        if (memory_get_usage(true) > 100 * 1024 * 1024) { log_c("Memory limit, stopping."); break; }

        $html = fetch_url($url);
        $visited[$url] = true;
        if (!$html) { log_c("  SKIP (fetch failed): $url"); continue; }

        // Extract links from this page
        if ($pages < 5) {  // Only follow links from first few pages
            preg_match_all('/href=["\']([^"\'#?]+)["\']/', $html, $lm);
            foreach ($lm[1] as $href) {
                if (str_starts_with($href, 'http')) $abs = $href;
                elseif (str_starts_with($href, '/')) { $p=parse_url($base_url); $abs=($p['scheme']??'https').'://'.($p['host']??'').$href; }
                else continue;
                if (parse_url($abs, PHP_URL_HOST) === parse_url($base_url, PHP_URL_HOST) && !isset($visited[$abs])) {
                    $queue[] = $abs;
                }
            }
        }

        $text = html_to_text($html);
        $text = clean_for_lm($text);
        if (strlen($text) < $min_chars) { log_c("  SKIP (too short): $url"); continue; }

        $corpus .= "\n\n" . $text;
        $pages++;
        log_c("  [{$pages}/{$max_pages}] " . strlen($text) . " chars — $url");
        usleep(300000);  // 300ms delay
    }

    // Write corpus
    $flag = $append ? FILE_APPEND : 0;
    $final = clean_for_lm($corpus);
    file_put_contents($corpus_file, $final, $flag);

    log_c("Done. $pages pages crawled.");
    log_c("Corpus: " . strlen($final) . " chars saved to $corpus_file");

    // Print quality guidance
    $sz = strlen($final);
    if ($sz < 50000)  log_c("WARNING: Corpus is small (<50KB). Add more content for better generation quality.");
    if ($sz < 100000) log_c("TIP: For nano model, aim for ≥100KB. Crawl more pages or add docs/FAQs manually.");
    if ($sz >= 100000) log_c("Good! Corpus is sufficient for nano model training.");
    if ($sz >= 500000) log_c("Excellent! Corpus is sufficient for small model training.");
    exit(0);
}

// ── Help ─────────────────────────────────────────────────────────────
echo "Yuga Corpus Builder\n\n";
echo "  php build_corpus.php --url=https://yoursite.com --pages=50\n";
echo "  php build_corpus.php --append --text=\"Your content here\"\n";
echo "  php build_corpus.php --append --file=docs.txt\n";
echo "  php build_corpus.php --stats\n\n";
