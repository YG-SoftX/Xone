<?php
/**
 * Yuga Configuration
 *
 * Credentials are loaded from environment variables (preferred for production)
 * or a .env file in the project root. Hardcoded credentials have been removed.
 *
 * For local development, secure random defaults are auto-generated.
 * For production, set the environment variables in your server config.
 */

// ── Load .env file if it exists (simple parser, no dependency) ────────
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#'))
            continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\v\"'");
            if ($key !== '' && getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

// ── Helper: read env var with fallback ────────────────────────────────
function env(string $key, $default = null)
{
    $val = getenv($key);
    if ($val === false)
        return $default;
    // Handle string booleans from .env
    if (strtolower($val) === 'true')
        return true;
    if (strtolower($val) === 'false')
        return false;
    return $val;
}

// ── Helper: generate secure random string ─────────────────────────────
function generateSecureKey(int $bytes = 24): string
{
    return 'yg_ai_live_' . bin2hex(random_bytes($bytes));
}

$config = [];

// ── Security ──────────────────────────────────────────────────────────
// Never hardcode credentials. Use env vars or generate secure defaults for dev.
$apiKey = env('YUGA_API_KEY', '');
if (empty($apiKey)) {
    // Auto-generate a secure key for development — not persisted to disk.
    // In production, always set YUGA_API_KEY in your environment.
    $apiKey = generateSecureKey();
}
$config['api_key'] = $apiKey;

$adminPassword = env('YUGA_ADMIN_PASSWORD', '');
$config['admin_password'] = $adminPassword;

// ── Model ─────────────────────────────────────────────────────────────
$config['default_model'] = env('YUGA_DEFAULT_MODEL', 'default');

// ── AI Features toggle ───────────────────────────────────────────────
// Set YUGA_AI_ENABLED=false in .env to serve pure BM25 results with no LLM calls.
// Useful when: no API key is configured, you want faster results, or lower costs.
$config['ai_enabled'] = env('YUGA_AI_ENABLED', true);

// ── Smart Chat backend ────────────────────────────────────────────────
$config['llm_backend'] = env('YUGA_LLM_BACKEND', 'claude');
$config['anthropic_api_key'] = env('YUGA_ANTHROPIC_API_KEY', '');
$config['openai_api_key'] = env('YUGA_OPENAI_API_KEY', '');
$config['ollama_url'] = env('YUGA_OLLAMA_URL', 'http://localhost:11434');
$config['ollama_model'] = env('YUGA_OLLAMA_MODEL', 'llama3.2');

// ── Smart Chat persona ────────────────────────────────────────────────
$config['assistant_name'] = env('YUGA_ASSISTANT_NAME', 'Yuga');
$config['platform_name'] = env('YUGA_PLATFORM_NAME', 'YG Ecosystem');
$config['tone'] = env('YUGA_TONE', 'professional, concise, and ecosystem-aware');

// ── Self-learning (Sovereign Ecosystem) ───────────────────────────────
$nodesStr = env('YUGA_ECOSYSTEM_NODES', 'http://localhost:5000,http://localhost:5001,http://localhost:5002');
$config['ecosystem_nodes'] = array_map('trim', explode(',', $nodesStr));

$config['auto_learn'] = env('YUGA_AUTO_LEARN', true);
$config['max_pages'] = (int) env('YUGA_MAX_PAGES', 50);
$config['train_steps'] = (int) env('YUGA_TRAIN_STEPS', 15000);
$config['time_limit'] = (int) env('YUGA_TIME_LIMIT', 300);
$config['memory_limit'] = env('YUGA_MEMORY_LIMIT', '128M');

// ── International Payment Gateways ────────────────────────────────────
$config['stripe'] = [
    'test_mode' => env('YUGA_STRIPE_TEST_MODE', true),
    'secret_key' => env('YUGA_STRIPE_SECRET_KEY', ''),
    'webhook_secret' => env('YUGA_STRIPE_WEBHOOK_SECRET', ''),
];
$config['paypal'] = [
    'test_mode' => env('YUGA_PAYPAL_TEST_MODE', true),
    'client_id' => env('YUGA_PAYPAL_CLIENT_ID', ''),
    'client_secret' => env('YUGA_PAYPAL_CLIENT_SECRET', ''),
];

// ── Email (SMTP) ──────────────────────────────────────────────────────
$config['mail'] = [
    'from_email' => env('YUGA_MAIL_FROM_EMAIL', 'noreply@yourdomain.com'),
    'from_name' => env('YUGA_MAIL_FROM_NAME', 'Yuga'),
    'portal_url' => env('YUGA_MAIL_PORTAL_URL', ''),
    'smtp' => [
        'host' => env('YUGA_SMTP_HOST', 'mail.yourdomain.com'),
        'port' => (int) env('YUGA_SMTP_PORT', 587),
        'username' => env('YUGA_SMTP_USERNAME', 'noreply@yourdomain.com'),
        'password' => env('YUGA_SMTP_PASSWORD', ''),
    ],
];

// ── Nepal Payment Gateways ────────────────────────────────────────────
$config['usd_to_npr_rate'] = (float) env('YUGA_USD_TO_NPR_RATE', 133.5);
$config['esewa'] = [
    'test_mode' => env('YUGA_ESEWA_TEST_MODE', true),
    'merchant_code' => env('YUGA_ESEWA_MERCHANT_CODE', 'EPAYTEST'),
    'secret_key' => env('YUGA_ESEWA_SECRET_KEY', ''),
];
$config['fonepay'] = [
    'test_mode' => env('YUGA_FONEPAY_TEST_MODE', true),
    'merchant_id' => env('YUGA_FONEPAY_MERCHANT_ID', 'NBQM'),
    'secret_key' => env('YUGA_FONEPAY_SECRET_KEY', ''),
];
$config['imepay'] = [
    'test_mode' => env('YUGA_IMEPAY_TEST_MODE', true),
    'merchant_code' => env('YUGA_IMEPAY_MERCHANT_CODE', ''),
    'merchant_name' => env('YUGA_IMEPAY_MERCHANT_NAME', 'Yuga'),
    'module' => env('YUGA_IMEPAY_MODULE', ''),
    'username' => env('YUGA_IMEPAY_USERNAME', ''),
    'password' => env('YUGA_IMEPAY_PASSWORD', ''),
];

// ── YG Pay Gateway (Ecosystem Native) ────────────────────────────────
$config['yg_pay'] = [
    'test_mode' => env('YUGA_YGPAY_TEST_MODE', true),
    'client_id' => env('YUGA_YGPAY_CLIENT_ID', ''),
];

// ── Web Search ────────────────────────────────────────────────────────
// Primary: sovereign SQLite BM25 index — grows with every search.
// Fallback: free public sources (Wikipedia, DuckDuckGo, HackerNews) —
//   no API key, no payment, results stored back into local index.
$config['web_search'] = [
    'provider' => env('YUGA_WEB_SEARCH_PROVIDER', 'index'),
    'data_dir' => env('YUGA_DATA_DIR', ''),
];

// ── Free Fallback — always on, no key needed ──────────────────────────
$config['fallback_enabled'] = env('YUGA_FALLBACK_ENABLED', true);

// ── YG Account SSO Integration ───────────────────────────────────────
$config['yg_account_api_secret'] = env('YUGA_YG_ACCOUNT_API_SECRET', '');
$config['admin_emails'] = array_map('trim', explode(
    ',',
    env('YUGA_ADMIN_EMAILS', 'admin@ygxone.com')
));

// ── YG Ecosystem App URLs (for navigation & quick links) ────────────
$config['yg_account_url'] = env('YG_ACCOUNT_URL', 'https://account.ygxone.com');
$config['yg_drive_url']   = env('YG_DRIVE_URL',   'https://drive.ygxone.com');
$config['yg_docx_url']    = env('YG_DOCX_URL',    'https://docx.ygxone.com');
$config['yg_mail_url']    = env('YG_MAIL_URL',     'https://mail.ygxone.com');
$config['yg_master_url']  = env('YG_MASTER_URL',   'https://master.ygxone.com');

// ── Security Headers ─────────────────────────────────────────────────
$config['security_headers'] = [
    'hsts_enabled' => (bool) env('YUGA_HSTS_ENABLED', false),
    'hsts_max_age' => (int) env('YUGA_HSTS_MAX_AGE', 31536000),
    'csp_enabled' => (bool) env('YUGA_CSP_ENABLED', false),
    'csp_policy' => env('YUGA_CSP_POLICY', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com;"),
];

return $config;
