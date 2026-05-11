<?php
/**
 * YG Search API Proxy
 * Keeps the real API key server-side; the browser calls this endpoint instead.
 * Rate-limited per IP: 60 searches / hour.
 */
define('YUGA_ROOT', __DIR__);

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

session_start();
$config = file_exists(YUGA_ROOT . '/config.php') ? require YUGA_ROOT . '/config.php' : [];

// ── Rate limiting (session-based, per IP) ─────────────────────────────────
$rateBucket = 'search_rl_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$rl = $_SESSION[$rateBucket] ?? ['count' => 0, 'reset_at' => time() + 3600];
if (time() > $rl['reset_at']) {
    $rl = ['count' => 0, 'reset_at' => time() + 3600];
}
if ($rl['count'] >= 60) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Rate limit exceeded. Please wait a minute.']);
    exit;
}
$rl['count']++;
$_SESSION[$rateBucket] = $rl;

// ── Only allow POST ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// ── Parse & validate input ─────────────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);
$query = trim($body['query'] ?? '');
$action = $body['action'] ?? 'web_search';
$tab = $body['tab'] ?? 'all';
$page = max(1, (int) ($body['page'] ?? 1));

if (strlen($query) < 2) {
    echo json_encode(['ok' => false, 'error' => 'Query too short']);
    exit;
}
if (strlen($query) > 500) {
    $query = substr($query, 0, 500);
}

// Allow only safe actions through the proxy
$allowedActions = ['web_search', 'related_questions', 'smart_chat'];
if (!in_array($action, $allowedActions, true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Action not permitted']);
    exit;
}

// ── Build internal API URL ─────────────────────────────────────────────────
$_script = str_replace(['/search_api.php'], '', $_SERVER['SCRIPT_NAME']);
$api_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . $_script . '/api/?action=' . urlencode($action);

$realApiKey = $config['api_key'] ?? '';
$aiEnabled  = (bool) ($config['ai_enabled'] ?? true);

// If AI is globally disabled, never ask the API to synthesize an answer.
// The client can also request generate=false explicitly — we honour whichever is stricter.
$clientWantsAI = isset($body['generate']) ? (bool) $body['generate'] : true;
$generate      = $aiEnabled && $clientWantsAI;

$payload = json_encode([
    'query'    => $query,
    'generate' => $generate,
    'sources'  => 8,
    'tab'      => $tab,
    'page'     => $page,
]);

// ── Proxy to internal API ──────────────────────────────────────────────────
$startTime = microtime(true);

$ch = curl_init($api_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'X-API-Key: ' . $realApiKey,
        'X-Forwarded-For: ' . ($_SERVER['REMOTE_ADDR'] ?? ''),
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

$elapsed = round(microtime(true) - $startTime, 3);

if ($curlErr || $httpCode < 200 || $httpCode >= 300) {
    // Fallback: return mock results so the UI still works even if API is down
    echo json_encode([
        'ok'      => false,
        'error'   => $curlErr ?: "API returned HTTP $httpCode",
        'elapsed' => $elapsed,
    ]);
    exit;
}

$data = json_decode($response, true);
if (!is_array($data)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid API response', 'elapsed' => $elapsed]);
    exit;
}

$data['elapsed'] = $elapsed;
$data['query']   = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');

echo json_encode($data);
