<?php
define('YUGA_ROOT', __DIR__);
require_once YUGA_ROOT . '/subscriptions/Plans.php';
require_once YUGA_ROOT . '/subscriptions/APIKeyManager.php';
require_once YUGA_ROOT . '/core/Mailer.php';
require_once YUGA_ROOT . '/core/ModelStore.php';
require_once YUGA_ROOT . '/core/SEO.php';

session_start();

// 🛡️ The Impenetrable Header Shield
header("Strict-Transport-Security: max-age=63072000; includeSubDomains; preload");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https:;");
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
header_remove("X-Powered-By");

// ── Sanitize Global Inputs ───────────────────────────────────────────
if (!empty($_GET)) {
    foreach ($_GET as $key => $value) {
        if (is_string($value)) $_GET[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

// ── SSO Integration: Authenticate via YG Account ───────────────────────
$config = file_exists(YUGA_ROOT . '/config.php') ? require YUGA_ROOT . '/config.php' : [];

if (isset($_GET['token'])) {
  $ssoToken = $_GET['token'];
  
  // Validate token format before use
  if (!preg_match('/^[A-Za-z0-9_\-\.]+$/', $ssoToken)) {
    http_response_code(400);
    die('Invalid SSO token format.');
  }
  
  $accountUrl = getenv('YG_ACCOUNT_URL') ?: ($config['yg_account_url'] ?? 'https://account.ygxone.com');
  $account_api = rtrim($accountUrl, '/') . '/api/auth/me';

  $ch = curl_init($account_api);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $ssoToken"]);
  $response = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($status === 200) {
    $userData = json_decode($response, true);
    if ($userData && isset($userData['id'])) {
      $_SESSION['yuga_dash_sub'] = 'sso_' . $userData['id'];
      $_SESSION['sso_user'] = $userData;
      $_SESSION['yg_sso_token'] = $ssoToken;

      $cleanUrl = strtok($_SERVER['REQUEST_URI'], '?');
      header("Location: $cleanUrl?page=dashboard");
      exit;
    }
  }
}

// ── CSRF token ─────────────────────────────────────────────────────────
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

// ── Validate CSRF on POST ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') !== 'dash_login') {
  if (($_POST['_csrf'] ?? '') !== $csrf) {
    http_response_code(403);
    die('Invalid or missing CSRF token.');
  }
}

// ── Page Routing ──────────────────────────────────────────────────────
$page = $_GET['page'] ?? 'home';
$flash = '';

// Whitelist allowed pages to prevent path traversal
$allowedPages = ['home', 'search', 'chat', 'dashboard'];
if (!in_array($page, $allowedPages, true)) {
    $page = 'home';
}

// Include the appropriate page logic
$page_file = YUGA_ROOT . '/' . $page . '.php';
if (file_exists($page_file)) {
    // If the page is 'home' or 'search', we handle them specifically as they are the core search engine
    if ($page === 'home' || $page === 'search') {
        require_once $page_file;
    } else {
        // Dashboard and other logic
        $akm = new APIKeyManager(YUGA_ROOT . '/data');
        if ($page === 'dashboard' && !empty($_SESSION['yuga_dash_sub'])) {
             $dash_sub = $akm->getSubscriber($_SESSION['yuga_dash_sub']);
             $dash_keys = $akm->listKeys($_SESSION['yuga_dash_sub']);
             require_once YUGA_ROOT . '/portal/dashboard.php';
        } else {
             require_once $page_file;
        }
    }
} else {
    require_once YUGA_ROOT . '/home.php';
}