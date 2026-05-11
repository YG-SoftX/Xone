<?php
/**
 * Yuga API  — api/index.php
 * REST JSON API for all model operations
 */

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// CORS: production origins + any localhost / 127.0.0.1 port for dev
$allowedOrigins = [
    'https://ygxone.com',
    'https://www.ygxone.com',
    'https://account.ygxone.com',
    'https://mail.ygxone.com',
    'https://ai.ygxone.com',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Allow any localhost or 127.0.0.1 origin (any port) for local dev
$isLocalhost = (bool) preg_match(
    '/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/',
    $origin
);

if (in_array($origin, $allowedOrigins, true) || $isLocalhost) {
    header("Access-Control-Allow-Origin: {$origin}");
} elseif (empty($origin)) {
    // Same-origin / CLI requests — always allowed
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---- Bootstrap --------------------------------------------------------
define('YUGA_ROOT', dirname(__DIR__));
require_once YUGA_ROOT . '/core/YugaLM.php';
require_once YUGA_ROOT . '/core/ModelStore.php';
require_once YUGA_ROOT . '/core/Tokenizer.php';
require_once YUGA_ROOT . '/core/Transformer.php';
require_once YUGA_ROOT . '/core/Retriever.php';
require_once YUGA_ROOT . '/core/Brain.php';
require_once YUGA_ROOT . '/core/SelfLearner.php';
require_once YUGA_ROOT . '/core/SafetySentinel.php';
require_once YUGA_ROOT . '/subscriptions/Plans.php';
require_once YUGA_ROOT . '/subscriptions/APIKeyManager.php';

$config = file_exists(YUGA_ROOT . '/config.php')
    ? require YUGA_ROOT . '/config.php'
    : ['api_key' => '', 'default_model' => 'default'];

// ── Initialize Safety Sentinel ─────────────────────────────────────────
$sentinel = new \Yuga\Core\SafetySentinel($config, YUGA_ROOT . '/data');

// ── Webhook helper (lazy-loaded) ────────────────────────────────────────
function fireEvent(string $event, array $payload): void
{
    static $wh = null;
    if ($wh === null) {
        require_once YUGA_ROOT . '/core/Webhooks.php';
        $wh = new Webhooks(YUGA_ROOT . '/data');
    }
    try {
        $wh->fire($event, $payload);
    } catch (Exception $e) {
    }
}

$akm = new APIKeyManager(YUGA_ROOT . '/data');

// ---- Auth: support both global key AND subscriber API keys ------------
$raw_key    = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['key'] ?? '');
$sub_record = null;
$globalKey  = $config['api_key'] ?? '';

// Same-server proxy calls (from search_api.php) don't need a key when no key is configured
$isSameServer = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

if ($raw_key) {
    if (str_starts_with($raw_key, 'yuga_live_')) {
        // Subscriber key — validate via APIKeyManager
        $sub_record = $akm->validateKey($raw_key);
        if (!$sub_record) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Invalid or expired API key']);
            exit;
        }
    } elseif (!empty($globalKey)) {
        // Global admin/dev key — must match exactly (constant-time comparison prevents timing attacks)
        if (!hash_equals($globalKey, $raw_key)) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
            exit;
        }
    } else {
        // Key provided but no global key configured AND not a subscriber key — always reject
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'API not configured. Set YUGA_API_KEY in .env']);
        exit;
    }
} elseif (!empty($globalKey) && !$isSameServer) {
    // No key sent, global key is required, not a same-server request
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'API key required']);
    exit;
} elseif (empty($globalKey) && !$isSameServer) {
    // No key configured and external caller — lock down
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'API not configured. Set YUGA_API_KEY in .env']);
    exit;
}
// Same-server calls with no configured key are allowed (local proxy)

// ---- Rate limiting for subscriber keys --------------------------------
if ($sub_record) {
    $plan = Plans::get($sub_record['plan']);
    $calls_today = $akm->getCallsToday($sub_record['sub_id']);
    if ($calls_today >= $plan['api_calls']) {
        http_response_code(429);
        echo json_encode(['ok' => false, 'error' => 'Daily rate limit exceeded', 'limit' => $plan['api_calls'], 'plan' => $sub_record['plan']]);
        exit;
    }
    // Send usage warning at 80% (only once per day — check if exactly crossed 80%)
    $limit = $plan['api_calls'];
    if ($limit < PHP_INT_MAX && $calls_today === (int) floor($limit * 0.8)) {
        try {
            require_once YUGA_ROOT . '/core/Mailer.php';
            (new Mailer($config))->sendUsageWarning($sub_record['email'], $sub_record['name'], $calls_today, $limit, $sub_record['plan']);
        } catch (Exception $e) {
        }
    }
}

// ---- Route ------------------------------------------------------------
$action = $_GET['action'] ?? 'status';
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$store = new ModelStore(YUGA_ROOT . '/data');

set_time_limit(300); // allow longer for training

function ok(array $data): void
{
    echo json_encode(['ok' => true] + $data);
    exit;
}
// Log usage for subscriber keys
function logUsage(string $action, string $model = ''): void
{
    global $akm, $sub_record;
    if ($sub_record) {
        $akm->logUsage($sub_record['key_id'], $sub_record['sub_id'], $action, $model);
    }
}
function err(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

// ── Safety Interceptor ────────────────────────────────────────────────
function checkSafety(string $text): void
{
    global $sentinel;
    $verdict = $sentinel->analyze($text);
    if (!$verdict['safe']) {
        err("Yuga 1.0 Intelligence has blocked this request: " . $verdict['reason'], 403);
    }
}

// -----------------------------------------------------------------------
switch ($action) {

    // ── Status ──────────────────────────────────────────────────────────
    case 'status': {
        $name = $body['model'] ?? $_GET['model'] ?? $config['default_model'];
        $meta = $store->loadMeta($name);
        $saved = $store->loadModel($name);
        $model = $saved ? YugaLM::fromArray($saved) : new YugaLM();
        ok([
            'model' => $name,
            'vocab_size' => $model->vocab_size,
            'trained_steps' => $model->trained_steps,
            'last_loss' => $model->last_loss,
            'vocab_built' => $model->vocab_built,
            'total_chars' => $meta['total_chars'] ?? 0,
            'base_url' => $meta['base_url'] ?? null,
            'last_crawl' => $meta['last_crawl'] ?? null,
            'pages_seen' => count($meta['visited_urls'] ?? []),
        ]);
    }

    // ── List models ─────────────────────────────────────────────────────
    case 'models': {
        ok(['models' => $store->listModels()]);
    }

    // ── Create model ─────────────────────────────────────────────────────
    case 'create': {
        $name = trim($body['model'] ?? '');
        if (!$name)
            err('model name required');
        if ($store->loadModel($name))
            err("Model '$name' already exists");
        $m = new YugaLM();
        $store->saveModel($name, $m->save());
        ok(['model' => $name, 'message' => 'Model created']);
    }

    // ── Moderate Media (Visual Sentinel) ─────────────────────────────────
    case 'moderate_media': {
        require_once YUGA_ROOT . '/core/VisualSentinel.php';
        $vSentinel = new \Yuga\Core\VisualSentinel($config, YUGA_ROOT . '/data');
        
        if (empty($_FILES['file'])) {
            err('No media file uploaded for moderation');
        }

        $verdict = $vSentinel->analyze($_FILES['file']['tmp_name']);
        ok($verdict);
    }

    // ── Delete model ─────────────────────────────────────────────────────
    case 'delete': {
        $name = trim($body['model'] ?? '');
        if (!$name)
            err('model name required');
        $store->deleteModel($name);
        ok(['message' => "Model '$name' deleted"]);
    }

    // ── Chat (context-aware with Memory) ────────────────────────────────
    case 'chat': {
        require_once YUGA_ROOT . '/core/Tokenizer.php';
        require_once YUGA_ROOT . '/core/Transformer.php';
        require_once YUGA_ROOT . '/core/Retriever.php';
        require_once YUGA_ROOT . '/core/Brain.php';
        require_once YUGA_ROOT . '/core/Memory.php';

        $name = $body['model'] ?? $config['default_model'];
        $message = trim($body['message'] ?? ($body['prompt'] ?? ''));
        $session_id = $body['session_id'] ?? '';
        $sub_id = $sub_record['sub_id'] ?? 'guest';
        $temp = (float) ($body['temperature'] ?? 0.72);

        if (!$message)
            err('message is required');

        checkSafety($message);

        $brain = new Brain($name, $store);
        $memory = new Memory(YUGA_ROOT . '/data');

        // Auto-create session if not provided
        if (!$session_id) {
            $session_id = $memory->createSession($sub_id, $name);
        }

        $result = $brain->chat($message, $session_id, $memory, $temp);
        logUsage('chat', $name);
        fireEvent('chat.message', ['model' => $name, 'message' => $message, 'session_id' => $result['session_id'], 'sub_id' => $sub_id]);

        ok([
            'reply' => $result['answer'],
            'session_id' => $result['session_id'],
            'turns' => $result['turns'],
            'enriched_query' => $result['enriched_query'],
            'model' => $name,
        ]);
    }

    // ── Session management ───────────────────────────────────────────────
    case 'session_start': {
        require_once YUGA_ROOT . '/core/Memory.php';
        $name = $body['model'] ?? $config['default_model'];
        $sub_id = $sub_record['sub_id'] ?? 'guest';
        $memory = new Memory(YUGA_ROOT . '/data');
        $sid = $memory->createSession($sub_id, $name);
        ok(['session_id' => $sid, 'model' => $name]);
    }

    case 'session_history': {
        require_once YUGA_ROOT . '/core/Memory.php';
        $sid = $body['session_id'] ?? err('session_id required');
        $n = (int) ($body['last_n'] ?? 20);
        $memory = new Memory(YUGA_ROOT . '/data');
        ok(['history' => $memory->getHistory($sid, $n), 'session_id' => $sid]);
    }

    case 'session_end': {
        require_once YUGA_ROOT . '/core/Memory.php';
        $sid = $body['session_id'] ?? err('session_id required');
        $memory = new Memory(YUGA_ROOT . '/data');
        $memory->deleteSession($sid);
        ok(['message' => 'Session ended', 'session_id' => $sid]);
    }

    // ── Learn from raw text ─────────────────────────────────────────────
    case 'learn_text': {
        $name = $body['model'] ?? $config['default_model'];
        $text = $body['text'] ?? '';
        $source = $body['source'] ?? 'api';
        if (strlen($text) < 10)
            err('text too short (min 10 chars)');

        $learner = new SelfLearner($name, $store);
        $res = $learner->learnFromText($text, $source);
        fireEvent('training.complete', ['model' => $name, 'source' => $source, 'loss' => $res['loss'] ?? 0, 'steps' => $res['steps'] ?? 0]);
        ok($res + ['model' => $name]);
    }

    // ── Learn from a single URL ─────────────────────────────────────────
    case 'learn_url': {
        $name = $body['model'] ?? $config['default_model'];
        $url = $body['url'] ?? '';
        if (!filter_var($url, FILTER_VALIDATE_URL))
            err('invalid URL');

        $learner = new SelfLearner($name, $store);
        $res = $learner->learnFromURL($url);
        ok($res + ['model' => $name, 'url' => $url]);
    }

    // ── Crawl full site & learn ─────────────────────────────────────────
    case 'learn_site': {
        $name = $body['model'] ?? $config['default_model'];
        $base_url = $body['url'] ?? $body['base_url'] ?? '';
        $max_p = (int) ($body['max_pages'] ?? 30);

        if (!filter_var($base_url, FILTER_VALIDATE_URL))
            err('invalid base URL');

        $learner = new SelfLearner($name, $store);
        $learner->max_pages = min($max_p, 100);

        // Streaming progress (SSE or just wait)
        $res = $learner->learnFromSite($base_url);
        fireEvent('training.complete', ['model' => $name, 'source' => $base_url, 'pages' => $res['pages'] ?? 0, 'loss' => $res['loss'] ?? 0]);
        ok($res + ['model' => $name, 'base_url' => $base_url]);
    }

    // ── Reset (clear visited URLs so it re-crawls) ──────────────────────
    case 'reset_crawl': {
        $name = $body['model'] ?? $config['default_model'];
        $meta = $store->loadMeta($name);
        $meta['visited_urls'] = [];
        $store->saveMeta($name, $meta);
        ok(['message' => 'Crawl history cleared']);
    }

    // ── Smart Chat (ChatGPT-style) ───────────────────────────────────────
    case 'smart_chat': {
        require_once YUGA_ROOT . '/core/SmartChat.php';
        $name = $body['model'] ?? $config['default_model'];
        $msg = $body['message'] ?? ($body['prompt'] ?? '');
        $history = $body['history'] ?? [];
        if (!$msg)
            err('message required');

        checkSafety($msg);

        $chat = new SmartChat($name, $store, $config);
        $chat->setHistory($history);
        $result = $chat->chat($msg, [
            'max_tokens' => (int) ($body['max_tokens'] ?? 500),
            'model' => $body['llm_model'] ?? null,
        ]);
        ok($result + ['history' => $chat->getHistory()]);
    }

    // ── Add text to SmartChat knowledge base ─────────────────────────────
    case 'smart_learn': {
        require_once YUGA_ROOT . '/core/SmartChat.php';
        $name = $body['model'] ?? $config['default_model'];
        $text = $body['text'] ?? '';
        $source = $body['source'] ?? '';
        if (strlen($text) < 10)
            err('text too short');

        $chat = new SmartChat($name, $store, $config);
        $chat->addKnowledge($text, $source);

        // Also train the Yuga weights
        $learner = new SelfLearner($name, $store);
        $learner->learnFromText($text, $source);

        ok(['message' => 'Knowledge added', 'chars' => strlen($text)]);
    }

    // ── YugaGen: generate text ───────────────────────────────────────────
    case 'nanogpt_generate':
    case 'yugagen_chat': {
        require_once YUGA_ROOT . '/core/YugaGen.php';
        $model_name = $body['model'] ?? $config['default_model'];
        $prompt = $body['prompt'] ?? ($body['message'] ?? '');
        $max_chars = (int) ($body['max_chars'] ?? 200);
        $temp = (float) ($body['temperature'] ?? 0.8);
        $top_p = (float) ($body['top_p'] ?? 0.9);

        $ckpt_file = YUGA_ROOT . '/data/ckpt_' . preg_replace('/[^a-z0-9_-]/', '', $model_name) . '.json.gz';

        if (!file_exists($ckpt_file)) {
            err("No trained YugaGen checkpoint found for model '$model_name'. Train it first with train.php.");
        }

        $gz = file_get_contents($ckpt_file);
        $gpt = YugaGen::fromArray(json_decode(gzdecode($gz), true));

        if (!$gpt->ready)
            err('Model not trained yet.');

        $generated = $gpt->generate($prompt, $max_chars, $temp, $top_p);
        logUsage('yugagen_chat', $model_name);

        ok([
            'reply' => $generated,
            'prompt' => $prompt,
            'model' => $model_name,
            'steps' => $gpt->steps,
            'loss' => round($gpt->loss, 4),
            'params' => $gpt->paramCount(),
        ]);
    }

    // ── YugaGen: status ──────────────────────────────────────────────────
    case 'yugagen_status': {
        require_once YUGA_ROOT . '/core/YugaGen.php';
        $model_name = $body['model'] ?? $_GET['model'] ?? $config['default_model'];
        $ckpt_file = YUGA_ROOT . '/data/ckpt_' . preg_replace('/[^a-z0-9_-]/', '', $model_name) . '.json.gz';

        if (!file_exists($ckpt_file)) {
            ok(['ready' => false, 'model' => $model_name, 'message' => 'No checkpoint yet. Run train.php to start training.']);
        }

        $gz = file_get_contents($ckpt_file);
        $gpt = YugaGen::fromArray(json_decode(gzdecode($gz), true));

        ok([
            'ready' => $gpt->ready,
            'model' => $model_name,
            'steps' => $gpt->steps,
            'loss' => round($gpt->loss, 4),
            'best_loss' => round($gpt->best_loss, 4),
            'vocab_size' => $gpt->V,
            'params' => $gpt->paramCount(),
            'arch' => "D={$gpt->D} H={$gpt->H} L={$gpt->L} CTX={$gpt->CTX}",
            'ckpt_size' => round(filesize($ckpt_file) / 1024) . 'KB',
        ]);
    }

    // ── YugaGen: list trained checkpoints ────────────────────────────────
    case 'yugagen_models': {
        require_once YUGA_ROOT . '/core/YugaGen.php';
        $ckpts = glob(YUGA_ROOT . '/data/ckpt_*.json.gz') ?: [];
        $models = [];
        foreach ($ckpts as $f) {
            preg_match('/ckpt_(.+)\.json\.gz$/', $f, $m);
            $name = $m[1] ?? 'unknown';
            $gz = file_get_contents($f);
            $d = json_decode(gzdecode($gz), true);
            $gpt = YugaGen::fromArray($d);
            $models[] = [
                'name' => $name,
                'steps' => $gpt->steps,
                'loss' => round($gpt->loss, 4),
                'params' => $gpt->paramCount(),
                'size_kb' => round(filesize($f) / 1024),
            ];
        }
        ok(['models' => $models]);
    }


    // ── Document ingestion: PDF, DOCX, CSV, JSON, TXT, URL ──────────────
    case 'ingest': {
        require_once YUGA_ROOT . '/core/Tokenizer.php';
        require_once YUGA_ROOT . '/core/Transformer.php';
        require_once YUGA_ROOT . '/core/Retriever.php';
        require_once YUGA_ROOT . '/core/Brain.php';
        require_once YUGA_ROOT . '/core/Ingester.php';
        require_once YUGA_ROOT . '/core/VisualSentinel.php';

        $name = $body['model'] ?? $config['default_model'];
        $brain = new Brain($name, $store);
        $ingest = new Ingester($brain);
        $vSentinel = new \Yuga\Core\VisualSentinel($config, YUGA_ROOT . '/data');

        // 1. URL ingestion
        if (!empty($body['url'])) {
            $crawl = !empty($body['crawl']);
            $result = $ingest->ingestUrl($body['url'], $crawl);
            ok($result + ['model' => $name]);
        }

        // 2. Raw text ingestion
        if (!empty($body['text'])) {
            $result = $ingest->ingestText($body['text'], $body['source'] ?? 'api');
            ok($result + ['model' => $name]);
        }

        // 3. File upload (multipart)
        if (!empty($_FILES['file'])) {
            // Neural Visual Check (Zero-Tolerance)
            $vVerdict = $vSentinel->analyze($_FILES['file']['tmp_name']);
            if (!$vVerdict['safe']) {
                err("Visual Intelligence has blocked this file: " . $vVerdict['reason'], 403);
            }
            $result = $ingest->ingestUpload($_FILES['file']);
            ok($result + ['model' => $name]);
        }

        // File path on server
        if (!empty($body['file_path'])) {
            $path = YUGA_ROOT . '/' . ltrim($body['file_path'], '/');
            if (!str_starts_with(realpath($path) ?: '', YUGA_ROOT))
                err('Invalid file path');
            $result = $ingest->ingestFile($path, $body['source'] ?? basename($path));
            ok($result + ['model' => $name]);
        }

        err('Provide url, text, file upload, or file_path');
    }

    // ── ToolKit: register a tool ─────────────────────────────────────────
    case 'tool_register': {
        require_once YUGA_ROOT . '/core/ToolKit.php';
        $kit = new ToolKit(YUGA_ROOT . '/data');
        $result = $kit->register($body);
        ok($result);
    }

    // ── ToolKit: list tools ───────────────────────────────────────────────
    case 'tool_list': {
        require_once YUGA_ROOT . '/core/ToolKit.php';
        $kit = new ToolKit(YUGA_ROOT . '/data');
        $all = !empty($body['all']);
        ok(['tools' => $kit->listTools(!$all)]);
    }

    // ── ToolKit: call a specific tool ─────────────────────────────────────
    case 'tool_call': {
        require_once YUGA_ROOT . '/core/ToolKit.php';
        $name = $body['tool'] ?? err('tool name required');
        $params = $body['params'] ?? [];
        $kit = new ToolKit(YUGA_ROOT . '/data');
        $start = microtime(true);
        $result = $kit->call($name, $params);
        $ms = (int) ((microtime(true) - $start) * 1000);
        $kit->logCall($name, $params, $result, $ms);
        logUsage('tool_call', $name);
        ok($result + ['duration_ms' => $ms]);
    }

    // ── ToolKit: auto-select and call best tool for a query ───────────────
    case 'tool_match': {
        require_once YUGA_ROOT . '/core/ToolKit.php';
        $query = trim($body['query'] ?? err('query required'));
        $kit = new ToolKit(YUGA_ROOT . '/data');
        $name = $kit->selectTool($query);
        if (!$name)
            ok(['matched' => false, 'tools' => $kit->matchTools($query, 0.0)]);
        $result = $kit->call($name, $body['params'] ?? []);
        ok($result + ['matched_tool' => $name]);
    }

    // ── Pipeline: run a workflow ──────────────────────────────────────────
    case 'pipeline_run': {
        require_once YUGA_ROOT . '/core/Tokenizer.php';
        require_once YUGA_ROOT . '/core/Transformer.php';
        require_once YUGA_ROOT . '/core/Retriever.php';
        require_once YUGA_ROOT . '/core/Brain.php';
        require_once YUGA_ROOT . '/core/Reasoner.php';
        require_once YUGA_ROOT . '/core/TextGenerator.php';
        require_once YUGA_ROOT . '/core/Ingester.php';
        require_once YUGA_ROOT . '/core/ToolKit.php';
        require_once YUGA_ROOT . '/core/Pipeline.php';

        $name = $body['model'] ?? $config['default_model'];
        $input = trim($body['input'] ?? ($body['goal'] ?? ($body['prompt'] ?? '')));
        $steps = $body['steps'] ?? err('steps array required');
        $vars = $body['vars'] ?? [];

        if (!$input)
            err('input is required');
        if (!is_array($steps))
            err('steps must be an array');

        $brain = new Brain($name, $store);
        $kit = new ToolKit(YUGA_ROOT . '/data');
        $pipeline = new Pipeline($brain, $kit);
        $pipeline->temperature = (float) ($body['temperature'] ?? 0.7);

        $result = $pipeline->execute($steps, $input, $vars);
        logUsage('pipeline_run', $name);

        ok($result + ['model' => $name]);
    }

    // ── Chain-of-thought thinking ─────────────────────────────────────────
    case 'think': {
        require_once YUGA_ROOT . '/core/Tokenizer.php';
        require_once YUGA_ROOT . '/core/Transformer.php';
        require_once YUGA_ROOT . '/core/Retriever.php';
        require_once YUGA_ROOT . '/core/Brain.php';
        require_once YUGA_ROOT . '/core/Reasoner.php';
        require_once YUGA_ROOT . '/core/Thinker.php';

        $name = $body['model'] ?? $config['default_model'];
        $question = trim($body['question'] ?? ($body['prompt'] ?? ($body['message'] ?? '')));
        $trace = !empty($body['trace']); // include full thinking trace in response

        if (!$question)
            err('question is required');

        $brain = new Brain($name, $store);
        $thinker = new Thinker($brain);
        $thinker->temperature = (float) ($body['temperature'] ?? 0.65);

        $result = $thinker->think($question);
        logUsage('think', $name);

        $response = [
            'answer' => $result['answer'],
            'type' => $result['type'],
            'confidence' => $result['confidence'],
            'model' => $name,
        ];
        if ($trace)
            $response['thinking'] = $result['thinking'];

        ok($response);
    }

    // ── Beam search generation — higher quality output ────────────────────
    case 'beam_generate': {
        require_once YUGA_ROOT . '/core/Tokenizer.php';
        require_once YUGA_ROOT . '/core/Transformer.php';
        require_once YUGA_ROOT . '/core/Retriever.php';
        require_once YUGA_ROOT . '/core/Brain.php';

        $name = $body['model'] ?? $config['default_model'];
        $prompt = trim($body['prompt'] ?? ($body['seed'] ?? ''));
        $maxTok = (int) ($body['max_tokens'] ?? 80);
        $beams = (int) ($body['beam_width'] ?? 3);
        $alpha = (float) ($body['length_penalty'] ?? 0.9);

        if (!$prompt)
            err('prompt is required');

        $brain = new Brain($name, $store);
        $result = $brain->generateBeam($prompt, $maxTok, $beams, $alpha);
        logUsage('beam_generate', $name);

        ok([
            'text' => $result,
            'full' => trim($prompt . ' ' . $result),
            'prompt' => $prompt,
            'beam_width' => $beams,
            'length_penalty' => $alpha,
            'model' => $name,
        ]);
    }

    // ── Text generation: complete / expand / bestOf / fill ──────────────
    case 'generate': {
        require_once YUGA_ROOT . '/core/Tokenizer.php';
        require_once YUGA_ROOT . '/core/Transformer.php';
        require_once YUGA_ROOT . '/core/Retriever.php';
        require_once YUGA_ROOT . '/core/Brain.php';
        require_once YUGA_ROOT . '/core/TextGenerator.php';

        $name = $body['model'] ?? $config['default_model'];
        $mode = $body['mode'] ?? 'complete';   // complete|expand|best_of|fill
        $prompt = trim($body['prompt'] ?? ($body['text'] ?? ($body['topic'] ?? ($body['template'] ?? ''))));
        if (!$prompt)
            err('prompt/text/topic/template is required');

        $opts = [
            'temperature' => (float) ($body['temperature'] ?? 0.8),
            'top_p' => (float) ($body['top_p'] ?? 0.9),
            'rep_penalty' => (float) ($body['rep_penalty'] ?? 1.5),
            'max_tokens' => (int) ($body['max_tokens'] ?? 80),
        ];

        $brain = new Brain($name, $store);
        $gen = new TextGenerator($brain);

        $result = match ($mode) {
            'expand' => $gen->expand($prompt, $opts),
            'best_of', 'best' => $gen->bestOf($prompt, (int) ($body['n'] ?? 3), $opts),
            'fill' => $gen->fill($prompt, $opts),
            default => $gen->complete($prompt, $opts),
        };

        logUsage('generate', $name);
        ok($result + ['model' => $name]);
    }

    // ── Agent: goal-driven agentic loop (100% Yuga, no external LLM) ───
    case 'agent_run': {
        require_once YUGA_ROOT . '/core/Tokenizer.php';
        require_once YUGA_ROOT . '/core/Transformer.php';
        require_once YUGA_ROOT . '/core/Retriever.php';
        require_once YUGA_ROOT . '/core/Brain.php';
        require_once YUGA_ROOT . '/core/Reasoner.php';
        require_once YUGA_ROOT . '/core/Agent.php';

        $name = $body['model'] ?? $config['default_model'];
        $goal = trim($body['goal'] ?? ($body['prompt'] ?? ''));
        $steps = (int) ($body['max_steps'] ?? 5);
        $temp = (float) ($body['temperature'] ?? 0.7);

        if (!$goal)
            err('goal is required');

        $brain = new Brain($name, $store);
        $reasoner = new Reasoner($brain);
        $reasoner->max_hops = (int) ($body['max_hops'] ?? 3);
        $reasoner->iterative_refinement = (bool) ($body['refine'] ?? true);

        $agent = new Agent($brain, $reasoner);
        $agent->max_steps = min($steps, 10);
        $agent->temperature = max(0.1, min(1.0, $temp));

        $result = $agent->run($goal);
        logUsage('agent_run', $name);

        ok($result + ['model' => $name]);
    }

    // ── Reasoner: direct multi-hop reasoning without agent loop ─────────
    case 'reason': {
        require_once YUGA_ROOT . '/core/Tokenizer.php';
        require_once YUGA_ROOT . '/core/Transformer.php';
        require_once YUGA_ROOT . '/core/Retriever.php';
        require_once YUGA_ROOT . '/core/Brain.php';
        require_once YUGA_ROOT . '/core/Reasoner.php';

        $name = $body['model'] ?? $config['default_model'];
        $question = trim($body['question'] ?? ($body['prompt'] ?? ($body['goal'] ?? '')));
        $temp = (float) ($body['temperature'] ?? 0.7);

        if (!$question)
            err('question is required');

        $brain = new Brain($name, $store);
        $reasoner = new Reasoner($brain);
        $reasoner->temperature = max(0.1, min(1.0, $temp));
        $reasoner->max_hops = (int) ($body['max_hops'] ?? 3);
        $reasoner->iterative_refinement = (bool) ($body['refine'] ?? true);

        $result = $reasoner->reason($question);
        logUsage('reason', $name);

        ok($result + ['model' => $name]);
    }

    // ── Voice Command: device action + knowledge Q&A in one call ────────
    case 'command': {
        require_once YUGA_ROOT . '/core/Memory.php';
        require_once YUGA_ROOT . '/core/Commander.php';

        $name = $body['model'] ?? $config['default_model'];
        $message = trim($body['message'] ?? '');
        $sessionId = $body['session_id'] ?? ('cli_' . time());

        if (!$message)
            err('message is required');

        checkSafety($message);

        $brain = new Brain($name, $store);
        $memory = new Memory(YUGA_ROOT . '/data');
        $commander = new Commander($brain, $memory);
        $result = $commander->handle($message, $sessionId);

        logUsage('command', $name);
        ok(['reply' => $result['reply'], 'action' => $result['action'] ?? null, 'source' => $result['source'], 'session_id' => $sessionId]);
    }

    // ── Sovereign Web Search — own index + own AI ───────────────────
    case 'web_search': {
        require_once YUGA_ROOT . '/core/SearchIndex.php';
        require_once YUGA_ROOT . '/core/WebSearch.php';
        require_once YUGA_ROOT . '/core/LiveRAG.php';
        require_once YUGA_ROOT . '/core/Reasoner.php';

        $query   = trim($body['query'] ?? ($body['message'] ?? ($body['q'] ?? '')));
        $sources = min(max((int) ($body['sources'] ?? 5), 1), 10);
        $model   = $body['model'] ?? $config['default_model'];

        if (!$query) err('query is required');

        // Build own SearchIndex and inject into WebSearch.
        // Merge full $config so WebSearch can access fallback_provider, brave_api_key, etc.
        $idx = new SearchIndex(YUGA_ROOT . '/data');
        $ws  = new WebSearch(array_merge(
            $config,
            $config['web_search'] ?? [],
            ['data_dir' => YUGA_ROOT . '/data']
        ));
        $ws->index = $idx;   // share the same SQLite handle — no double open

        // Load local Brain + Reasoner for own AI synthesis
        $brain = null;
        try {
            $brain = new Brain($model, $store);
        } catch (Exception $e) {}

        // Run LiveRAG with own brain; generate flag defaults true
        $generate = isset($body['generate']) ? (bool) $body['generate'] : true;
        $rag = new LiveRAG($ws, $generate ? $brain : null);
        $rag->max_sources = $sources;

        $result = $rag->query($question = $query, [
            'sources'  => $sources,
            'generate' => $generate,
            'config'   => $config,
        ]);

        // Enhance with Reasoner if Brain is ready and index has content
        if ($brain && $result['ok'] && empty($result['related_questions'])) {
            try {
                $reasoner = new Reasoner($brain);
                $reasoned = $reasoner->reason($question);
                if (!empty($reasoned['answer']) && !str_contains($reasoned['answer'], "haven't learned")) {
                    // Prepend reasoned knowledge if different from RAG answer
                    if (strtolower(substr($reasoned['answer'], 0, 40)) !==
                        strtolower(substr($result['answer'], 0, 40))) {
                        $result['answer'] = $reasoned['answer'] . "\n\n" . $result['answer'];
                    }
                }
            } catch (Exception $e) {}
        }

        logUsage('web_search', $model);
        ok($result);
    }

    // ── Deep recursive BFS site crawl ────────────────────────────────
    case 'deep_crawl': {
        require_once YUGA_ROOT . '/core/DeepCrawler.php';

        $name = $body['model'] ?? $config['default_model'];
        $start_url = trim($body['url'] ?? '');
        $reset = !empty($body['reset']);

        if (!filter_var($start_url, FILTER_VALIDATE_URL))
            err('valid url required');

        $brain = new Brain($name, $store);
        $crawler = new DeepCrawler(YUGA_ROOT . '/data');
        $crawler->max_pages = min((int) ($body['max_pages'] ?? 100), 500);
        $crawler->max_depth = min((int) ($body['max_depth'] ?? 4), 8);
        $crawler->train_steps = (int) ($body['train_steps'] ?? 20000);

        if ($reset)
            $crawler->reset($start_url);

        $result = $crawler->crawl($start_url, $brain);
        fireEvent('training.complete', ['model' => $name, 'source' => $start_url, 'pages' => $result['pages'], 'loss' => $result['loss']]);
        logUsage('deep_crawl', $name);
        ok($result + ['model' => $name, 'start_url' => $start_url]);
    }

    // ── Global seed crawl (starts full web index build) ──────────────
    case 'seed_crawl': {
        require_once YUGA_ROOT . '/core/GlobalCrawler.php';
        
        $categories = $body['categories'] ?? [];
        if (!is_array($categories)) $categories = [];
        
        $gc = new GlobalCrawler(YUGA_ROOT . '/data');
        if (!empty($body['reset'])) {
            $gc->resetFrontier();
        }
        
        // High limit for cron tasks
        $gc->max_pages_per_run = min((int)($body['max_pages'] ?? 1000), 5000);
        
        $result = $gc->crawlSeeds($categories);
        $result['stats'] = $gc->stats();
        
        logUsage('seed_crawl', 'search_index');
        ok($result);
    }
    
    // ── Global crawler stats / status ────────────────────────────────
    case 'global_status': {
        require_once YUGA_ROOT . '/core/GlobalCrawler.php';
        $gc = new GlobalCrawler(YUGA_ROOT . '/data');
        ok($gc->stats());
    }

    // ── Deep crawl progress status ────────────────────────────────────
    case 'crawl_status': {
        require_once YUGA_ROOT . '/core/DeepCrawler.php';
        $url = trim($body['url'] ?? ($_GET['url'] ?? ''));
        if (!$url)
            err('url required');
        ok((new DeepCrawler(YUGA_ROOT . '/data'))->getProgress($url));
    }

    // ── Related questions — contextual follow-ups from query + answer ─────
    case 'related_questions': {
        $query  = trim($body['query']  ?? ($body['q'] ?? ''));
        $answer = trim($body['answer'] ?? '');
        $n      = min(max((int) ($body['n'] ?? 4), 1), 8);

        if (!$query) err('query is required');

        // Extract keywords from query and answer for better related Qs
        $stopWords = ['the','a','an','is','are','was','were','be','been','being',
                      'have','has','had','do','does','did','will','would','could',
                      'should','may','might','can','shall','of','in','on','at',
                      'to','for','and','or','but','not','with','from','by','this',
                      'that','it','its','as','i','you','he','she','we','they'];

        $allText  = strtolower($query . ' ' . $answer);
        $words    = preg_split('/\W+/', $allText) ?: [];
        $keywords = array_filter($words, fn($w) =>
            strlen($w) > 3 && !in_array($w, $stopWords, true)
        );
        $freq = array_count_values($keywords);
        arsort($freq);
        $topKeywords = array_slice(array_keys($freq), 0, 8);

        // Template expansions driven by top keywords
        $templates = [
            'How does %s work?',
            'What are the benefits of %s?',
            'What is the history of %s?',
            'How to get started with %s?',
            'What are the best alternatives to %s?',
            'Why is %s important?',
            'What are common problems with %s?',
            'How to improve %s?',
        ];

        $related = [];
        foreach ($topKeywords as $i => $kw) {
            if (count($related) >= $n) break;
            $tpl = $templates[$i % count($templates)];
            $q   = sprintf($tpl, $kw);
            // Skip if too similar to main query
            similar_text(strtolower($query), strtolower($q), $pct);
            if ($pct < 70) {
                $related[] = $q;
            }
        }

        // Fill remaining slots with generic expansions on the original query
        $generics = [
            "Tell me more about: $query",
            "What are the latest developments in $query?",
            "Explain $query in simple terms",
            "What experts say about $query",
        ];
        foreach ($generics as $g) {
            if (count($related) >= $n) break;
            $related[] = $g;
        }

        ok(['questions' => array_slice($related, 0, $n), 'query' => $query]);
    }

    // ── Admin Stats for Master Dashboard ────────────────────────────────
    case 'admin_stats': {
        require_once YUGA_ROOT . '/core/ModelStore.php';
        $store = new ModelStore(YUGA_ROOT . '/data');
        $models = $store->listModels();
        $total_chars = 0;
        foreach($models as $m) {
            $meta = $store->loadMeta($m);
            $total_chars += ($meta['total_chars'] ?? 0);
        }
        ok([
            'total_models' => count($models),
            'total_intelligence_chars' => $total_chars,
            'status' => 'operational',
            'last_update' => date('c'),
        ]);
    }

    case 'search': {
        $q = $_GET['q'] ?? '';
        ok([
            'results' => [
                [
                    'type' => 'AI Insight',
                    'title' => 'Query: ' . $q,
                    'desc' => 'Find related intelligence data in your chat history...',
                    'url' => 'https://ai.ygxone.com/chat',
                    'icon' => 'fa-robot'
                ]
            ]
        ]);
    }

    default:
        err("Unknown action: $action", 404);
}
