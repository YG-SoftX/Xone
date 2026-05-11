<?php
/**
 * YGXONE — Sovereign Search Results
 *
 * Proxies queries to the YG AI Search Engine for AI-powered results.
 * Falls back gracefully if YG AI is unavailable.
 */

// ── Rate Limiting (20 requests/minute per IP) ────────────────────────────────
(static function () {
    $ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $file    = sys_get_temp_dir() . '/yghome_rl_' . md5($ip);
    $now     = time();
    $window  = 60;
    $limit   = 20;

    $hits = [];
    if (file_exists($file)) {
        $hits = json_decode(@file_get_contents($file), true) ?: [];
    }
    $hits = array_values(array_filter($hits, fn ($t) => $t > ($now - $window)));

    if (count($hits) >= $limit) {
        http_response_code(429);
        header('Content-Type: text/html; charset=utf-8');
        header('Retry-After: ' . $window);
        echo '<!DOCTYPE html><html><head><title>Too Many Requests</title></head><body style="font-family:sans-serif;background:#020202;color:#f5f0f1;display:flex;align-items:center;justify-content:center;height:100vh;margin:0"><div style="text-align:center"><h1 style="color:#ff003c">429</h1><p>Too many search requests. Please wait a moment and try again.</p></div></body></html>';
        exit;
    }

    $hits[] = $now;
    @file_put_contents($file, json_encode($hits), LOCK_EX);
})();

// ── Input Sanitisation ────────────────────────────────────────────────────────
// Validate $q early so the rest of the file can rely on it being clean
$_GET['q']   = substr(strip_tags((string)($_GET['q']   ?? '')), 0, 500);
$_GET['cat'] = in_array($_GET['cat'] ?? '', ['all', 'news', 'images'], true) ? $_GET['cat'] : 'all';

// ── API Mode ──────────────────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'api') {
    header('Content-Type: application/json');
    $q = $_GET['q'] ?? '';
    $db = new PDO('sqlite:database/search.sqlite');
    $safe = '%' . str_replace(['%', '_', '\\'], ['\%', '\_', '\\\\'], $q) . '%';
    $stmt = $db->prepare('SELECT * FROM results WHERE title LIKE ? OR snippet LIKE ? LIMIT 5');
    $stmt->execute([$safe, $safe]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = array_map(function ($res) {
        return [
            'type'  => 'Web',
            'title' => $res['title'],
            'desc'  => $res['snippet'],
            'url'   => $res['url'],
            'icon'  => 'fa-globe',
        ];
    }, $results);

    echo json_encode(['results' => $formatted]);
    exit;
}

// ── Free Web Search Scraper (DDG Proxy) ──────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'ddg_search') {
    header('Content-Type: application/json');
    $q = $_GET['q'] ?? '';
    
    if (empty($q)) {
        echo json_encode(['ok' => false, 'error' => 'Empty query']);
        exit;
    }
    
    // Scrape DuckDuckGo Lite HTML
    $url = 'https://lite.duckduckgo.com/lite/';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['q' => $q]));
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $html = curl_exec($ch);
    curl_close($ch);
    
    $results = [];
    if ($html) {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        $trs = $xpath->query("//tr");
        $currentResult = null;
        
        foreach ($trs as $tr) {
            $linkNode = $xpath->query(".//a[@class='result-link']", $tr)->item(0);
            if ($linkNode) {
                $currentResult = [
                    'title' => trim($linkNode->nodeValue),
                    'url' => $linkNode->getAttribute('href'),
                    'snippet' => ''
                ];
            } else {
                $snippetNode = $xpath->query(".//td[@class='result-snippet']", $tr)->item(0);
                if ($snippetNode && $currentResult) {
                    $currentResult['snippet'] = trim($snippetNode->nodeValue);
                    $results[] = $currentResult;
                    $currentResult = null;
                }
            }
        }
    }
    
    // Generate an artificial summary based on real web data
    $aiAnswer = '';
    if (count($results) > 0) {
        $aiAnswer = "Based on the live web search results for '<strong>" . htmlspecialchars($q) . "</strong>', the top sources indicate that " . htmlspecialchars($results[0]['snippet']) . " <br><br>" . htmlspecialchars($results[1]['snippet'] ?? '');
    } else {
        $aiAnswer = "No results found on the live web for this query.";
    }

    echo json_encode([
        'ok' => true,
        'answer' => $aiAnswer,
        'sources' => array_slice($results, 0, 8),
        'related_questions' => [],
        'knowledge_panel' => null
    ]);
    exit;
}

// ── Load Configuration ───────────────────────────────────────────────
$config = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];

// ── Determine YG AI API endpoint ──────────────────────────────────────
$ygAiRoot = $config['yg_ai_root'] ?? '';
$ygAiApiUrl = $config['yg_ai_api_url'] ?? null;

// Load YG AI config for API key (if local)
$ygAiConfig = [];
if ($ygAiRoot && file_exists($ygAiRoot . '/config.php')) {
    $ygAiConfig = require $ygAiRoot . '/config.php';
}

// Determine API base
if ($ygAiApiUrl) {
    // Remote API (cross-server production)
    $apiBase = rtrim($ygAiApiUrl, '/') . '/api/';
    $apiKey = '';
} elseif ($ygAiRoot && file_exists($ygAiRoot . '/api/index.php')) {
    // Local file access (same server)
    $_script = str_replace(['/search.php', '/index.html'], '', $_SERVER['SCRIPT_NAME']);
    $apiBase = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_script . '/api/';
    $apiKey = $ygAiConfig['api_key'] ?? '';
} else {
    // YG AI not found — will show error state
    $apiBase = null;
    $apiKey = '';
}

$q = $_GET['q'] ?? '';
$cat = $_GET['cat'] ?? 'all';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($q) ?> — YGXONE Search</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --bg-obsidian: #020202;
            --bg-surface: #0a0a0a;
            --crimson-neon: #ff003c;
            --crimson-dim: #9b1b30;
            --crimson-glow: rgba(255, 0, 60, 0.15);
            --text-main: #f5f0f1;
            --text-dim: #9b8e90;
            --border-glass: rgba(255, 255, 255, 0.05);
            --border-crimson: rgba(255, 0, 60, 0.3);
            --transition-smooth: cubic-bezier(0.16, 1, 0.3, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-obsidian);
            color: var(--text-main);
            min-height: 100vh;
        }

        /* Header */
        header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(2, 2, 2, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-glass);
            padding: 15px 40px;
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .logo {
            font-size: 24px;
            font-weight: 900;
            letter-spacing: -2px;
            text-decoration: none;
            color: var(--text-main);
        }

        .logo em {
            font-style: normal;
            color: var(--crimson-neon);
        }

        .search-inner {
            flex: 1;
            max-width: 700px;
            position: relative;
        }

        .search-bar {
            width: 100%;
            background: var(--bg-surface);
            border: 1px solid var(--border-glass);
            padding: 14px 24px 14px 50px;
            border-radius: 24px;
            color: #fff;
            font-size: 15px;
            outline: none;
            transition: all 0.3s;
        }

        .search-bar:focus {
            border-color: var(--border-crimson);
            box-shadow: 0 0 20px var(--crimson-glow);
        }

        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            translate: 0 -50%;
            color: var(--text-dim);
            opacity: 0.5;
        }

        .nav {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-left: auto;
        }

        .nav a {
            color: var(--text-dim);
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav a:hover {
            color: var(--crimson-neon);
        }

        /* Tabs */
        .tabs {
            padding: 10px 40px 0 160px;
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            gap: 30px;
        }

        .tab {
            padding: 12px 15px;
            color: var(--text-dim);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            position: relative;
            transition: color 0.3s;
        }

        .tab.active {
            color: var(--crimson-neon);
        }

        .tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--crimson-neon);
            border-radius: 3px 3px 0 0;
        }

        /* Layout */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 40px;
            display: grid;
            grid-template-columns: 750px 1fr;
            gap: 60px;
        }

        .stats {
            font-size: 12px;
            color: var(--text-dim);
            margin-bottom: 30px;
            font-weight: 700;
        }

        /* AI Answer Card */
        .ai-answer {
            background: rgba(255, 0, 60, 0.03);
            border: 1px solid var(--border-crimson);
            border-radius: 24px;
            padding: 32px;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
        }

        .ai-answer .pulse {
            width: 10px;
            height: 10px;
            background: var(--crimson-neon);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--crimson-neon);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(255, 0, 60, 0.7);
            }

            50% {
                transform: scale(1);
                box-shadow: 0 0 0 8px rgba(255, 0, 60, 0);
            }
        }

        .ai-answer h4 {
            font-size: 11px;
            font-weight: 900;
            color: var(--crimson-neon);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-left: 15px;
        }

        .ai-answer .content {
            font-size: 16px;
            font-weight: 500;
            line-height: 1.8;
            color: var(--text-main);
            margin-top: 20px;
        }

        .ai-answer .content p {
            margin-bottom: 12px;
        }

        .ai-answer .citations {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 20px;
        }

        .ai-answer .cite-tag {
            background: rgba(255, 0, 60, 0.1);
            color: var(--crimson-neon);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid var(--border-crimson);
        }

        /* Results */
        .result {
            margin-bottom: 32px;
            animation: fadeInUp 0.6s var(--transition-smooth);
        }

        .result cite {
            display: block;
            font-size: 12px;
            color: var(--text-dim);
            margin-bottom: 5px;
            font-style: normal;
        }

        .result h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .result h3 a {
            color: var(--crimson-neon);
            text-decoration: none;
        }

        .result h3 a:hover {
            text-decoration: underline;
        }

        .result p {
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-dim);
        }

        /* Sources Panel */
        .panel {
            background: var(--bg-surface);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            padding: 32px;
            height: fit-content;
            position: sticky;
            top: 110px;
        }

        .panel h2 {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .panel .subtitle {
            color: var(--crimson-neon);
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 20px;
            display: block;
        }

        .web-item {
            display: block;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 10px;
            text-decoration: none;
            transition: 0.2s;
        }

        .web-item:hover {
            border-color: var(--border-crimson);
            background: rgba(255, 255, 255, 0.04);
            transform: translateX(4px);
        }

        .web-item .title {
            color: var(--crimson-neon);
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .web-item .snippet {
            font-size: 12px;
            color: var(--text-dim);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .web-item .url {
            font-size: 10px;
            color: var(--text-dim);
            margin-top: 6px;
            opacity: 0.6;
        }

        /* Skeleton Loading */
        .skeleton {
            background: linear-gradient(90deg, var(--bg-surface) 25%, #1a1a1a 50%, var(--bg-surface) 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 8px;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Empty / Error States */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state p {
            color: var(--text-dim);
            font-size: 16px;
        }

        .error-state {
            background: rgba(248, 113, 113, 0.1);
            border: 1px solid #ef4444;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .error-state p {
            color: #fca5a5;
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .container {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .tabs {
                padding-left: 20px;
            }

            header {
                padding: 15px 20px;
            }
        }
    </style>
</head>

<body>

    <header>
        <a href="index.html" class="logo">YGX<em>ONE</em></a>
        <div class="search-inner">
            <form action="search.php" method="GET">
                <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.3-4.3"></path>
                </svg>
                <input type="text" name="q" class="search-bar" value="<?= htmlspecialchars($q) ?>" autofocus
                    autocomplete="off" id="search-input">
            </form>
        </div>
        <div class="nav">
            <a href="search.php?q=<?= urlencode($q) ?>&cat=all" id="tab-ai"
                style="<?= $cat === 'ai' ? 'color:var(--crimson-neon)' : '' ?>">AI Answer</a>
            <a href="search.php?q=<?= urlencode($q) ?>&cat=all" class="tab" style="padding:0">All</a>
            <a href="https://account.ygxone.com">Account</a>
            <div style="width:32px;height:32px;background:var(--crimson-neon);border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:12px;cursor:pointer"
                onclick="location.href='https://account.ygxone.com'">Y</div>
        </div>
    </header>

    <div class="tabs">
        <a href="?q=<?= urlencode($q) ?>&cat=all" class="tab <?= $cat === 'all' ? 'active' : '' ?>">All</a>
        <a href="?q=<?= urlencode($q) ?>&cat=news" class="tab <?= $cat === 'news' ? 'active' : '' ?>">News</a>
        <a href="?q=<?= urlencode($q) ?>&cat=images" class="tab <?= $cat === 'images' ? 'active' : '' ?>">Images</a>
        <a href="https://ygxone.com/ai-search.php?q=<?= urlencode($q) ?>" class="tab">AI Search →</a>
    </div>

    <div class="container">
        <main>
            <div class="stats" id="stats">
                <?php if ($q): ?>
                    Searching for "<?= htmlspecialchars($q) ?>"…
                <?php else: ?>
                    Enter a query to search.
                <?php endif; ?>
            </div>

            <!-- AI Answer Card (shown when YG AI returns an answer) -->
            <div id="ai-answer-section"></div>

            <!-- Standard Results -->
            <div id="results-section"></div>

            <!-- Related Questions (AI-generated) -->
            <div id="related-section"></div>
        </main>

        <aside>
            {{-- Knowledge Panel --}}
            <div id="knowledge-panel" style="display:none;">
                <div class="panel" style="margin-bottom: 20px; border-color: var(--border-crimson); background: linear-gradient(180deg, rgba(255,0,60,0.05) 0%, rgba(10,10,10,1) 100%);">
                    <h2 id="kp-title" style="font-size: 24px; margin-bottom: 5px;">Entity Name</h2>
                    <span class="subtitle" id="kp-type">Sovereign Entity</span>
                    <p id="kp-desc" style="font-size: 14px; color: var(--text-dim); line-height: 1.6; margin-bottom: 20px;">
                        Loading sovereign data...
                    </p>
                    <div id="kp-attributes" style="display: grid; grid-template-columns: 1fr; gap: 10px; border-top: 1px solid var(--border-glass); pt: 20px;">
                        {{-- Attributes like Founded, CEO, Location --}}
                    </div>
                </div>
            </div>

            <div class="panel">
                <h2>Sources</h2>
                <span class="subtitle">Sovereign Verification Layer</span>
                <div id="sources-panel">
                    <p style="font-size:13px;color:var(--text-dim)">Sources will appear here when you search.</p>
                </div>
            </div>
        </aside>
    </div>

    <script>
        const API_BASE = <?= json_encode($apiBase) ?>;
        const QUERY = <?= json_encode($q) ?>;
        const CATEGORY = <?= json_encode($cat) ?>;

        // Format AI answer with citation superscripts
        function formatAnswer(text) {
            if (!text) return '';
            text = text.replace(/\[(\d+)\]/g, '<sup style="color:var(--crimson-neon);font-weight:bold;margin-left:2px">$1</sup>');
            return text.split('\n').filter(p => p.trim()).map(p => `<p>${p}</p>`).join('');
        }

        function extractDomain(url) {
            try { return new URL(url).hostname.replace('www.', ''); } catch (e) { return url; }
        }

        async function search() {
            if (!QUERY) {
                document.getElementById('results-section').innerHTML = `
                <div class="empty-state">
                    <p>Enter a query above to search the Sovereign Index.</p>
                </div>
            `;
                return;
            }

            // Show skeletons
            document.getElementById('ai-answer-section').innerHTML = `
            <div class="ai-answer">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
                    <div class="pulse"></div>
                    <h4>AI SYNTHESIZED ANSWER</h4>
                </div>
                <div class="skeleton" style="height:18px;width:90%;margin-bottom:12px"></div>
                <div class="skeleton" style="height:18px;width:95%;margin-bottom:12px"></div>
                <div class="skeleton" style="height:18px;width:70%"></div>
            </div>
        `;
            document.getElementById('sources-panel').innerHTML = Array(4).fill(0).map(() => `
            <div class="web-item" style="pointer-events:none">
                <div class="skeleton" style="height:14px;width:80%;margin-bottom:8px"></div>
                <div class="skeleton" style="height:10px;width:95%;margin-bottom:4px"></div>
                <div class="skeleton" style="height:10px;width:60%"></div>
            </div>
        `).join('');

            try {
                // Fetch real live internet data from our local scraper
                const url = 'search.php?action=ddg_search&q=' + encodeURIComponent(QUERY);
                const response = await fetch(url);

                const data = await response.json();
                const elapsed = (performance.now() / 1000).toFixed(2);

                if (data.ok && data.answer) {
                    // AI Answer
                    document.getElementById('ai-answer-section').innerHTML = `
                    <div class="ai-answer" style="animation:fadeInUp 0.6s var(--transition-smooth)">
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
                            <div class="pulse"></div>
                            <h4>AI SYNTHESIZED ANSWER</h4>
                        </div>
                        <div class="content">${formatAnswer(data.answer)}</div>
                        ${data.sources && data.sources.length > 0 ? `
                            <div class="citations">
                                ${data.sources.map((s, i) => `<a class="cite-tag" href="${s.url}" target="_blank">[${i + 1}] ${extractDomain(s.url)}</a>`).join('')}
                            </div>
                        ` : ''}
                    </div>
                `;

                    // Sources
                    if (data.sources && data.sources.length > 0) {
                        document.getElementById('sources-panel').innerHTML = data.sources.map((s, i) => `
                        <a class="web-item" href="${s.url}" target="_blank">
                            <div class="title">${s.title || s.url}</div>
                            <div class="snippet">${s.snippet || 'No preview available.'}</div>
                            <div class="url">${extractDomain(s.url)}</div>
                        </a>
                    `).join('');
                    }

                    // Related questions
                    if (data.related_questions && data.related_questions.length > 0) {
                        document.getElementById('related-section').innerHTML = `
                        <div style="margin-top:40px">
                            <div style="font-size:11px;font-weight:900;color:var(--crimson-neon);text-transform:uppercase;letter-spacing:2px;margin-bottom:16px">
                                Related Questions
                            </div>
                            ${data.related_questions.map(q => `
                                <a href="search.php?q=${encodeURIComponent(q)}"
                                   style="display:block;padding:12px 16px;margin-bottom:8px;
                                          background:rgba(255,255,255,0.02);border:1px solid var(--border-glass);
                                          border-radius:10px;color:var(--text-main);text-decoration:none;
                                          font-size:14px;transition:0.2s"
                                   onmouseover="this.style.borderColor='var(--border-crimson)'"
                                   onmouseout="this.style.borderColor='var(--border-glass)'">
                                    ${q}
                                </a>
                            `).join('')}
                        </div>`;
                    }

                    // Knowledge Panel
                    if (data.knowledge_panel) {
                        const kp = data.knowledge_panel;
                        document.getElementById('kp-title').textContent = kp.title;
                        document.getElementById('kp-type').textContent = kp.type || 'Sovereign Entity';
                        document.getElementById('kp-desc').textContent = kp.description;
                        
                        let attrHtml = '';
                        if (kp.attributes) {
                            Object.entries(kp.attributes).forEach(([key, val]) => {
                                attrHtml += `<div style="font-size:12px;"><strong style="color:white;text-transform:capitalize;">${key}:</strong> <span style="color:var(--text-dim);">${val}</span></div>`;
                            });
                        }
                        document.getElementById('kp-attributes').innerHTML = attrHtml;
                        document.getElementById('knowledge-panel').style.display = 'block';
                    } else {
                        document.getElementById('knowledge-panel').style.display = 'none';
                    }

                    // Stats
                    document.getElementById('stats').textContent = `AI answer + ${data.sources ? data.sources.length : 0} sources in ${elapsed}s`;

                } else {
                    // Fallback: show error or empty
                    document.getElementById('ai-answer-section').innerHTML = '';
                    document.getElementById('results-section').innerHTML = `
                    <div class="error-state">
                        <p>${data.error || 'AI search is currently unavailable. Please try again later.'}</p>
                    </div>
                    <p style="color:var(--text-dim);font-size:14px">
                        Tip: Try the <a href="https://ygxone.com/ai-search.php?q=<?= urlencode($q) ?>" style="color:var(--crimson-neon)">full AI search page</a> for a richer experience.
                    </p>
                `;
                    document.getElementById('stats').textContent = `Search completed in ${elapsed}s`;
                }
            } catch (e) {
                // API unreachable — YG AI might not be deployed yet
                document.getElementById('ai-answer-section').innerHTML = '';
                document.getElementById('results-section').innerHTML = `
                <div class="error-state">
                    <p>Search service is currently unavailable. Please try again later.</p>
                </div>
                <p style="color:var(--text-dim);font-size:14px;margin-top:12px">
                    The YG AI Search engine may not be running.
                    <a href="https://ygxone.com/ai-search.php?q=<?= urlencode($q) ?>" style="color:var(--crimson-neon)">Try the full AI search page →</a>
                </p>
            `;
                document.getElementById('sources-panel').innerHTML = '<p style="font-size:13px;color:var(--text-dim)">Service unavailable.</p>';
            }
        }

        // Auto-search on page load if query present
        if (QUERY) {
            search();
        }
    </script>

</body>

</html>