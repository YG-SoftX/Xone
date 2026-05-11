<?php
define('YUGA_ROOT', dirname(__DIR__));
require_once YUGA_ROOT . '/core/YugaLM.php';
require_once YUGA_ROOT . '/core/ModelStore.php';
require_once YUGA_ROOT . '/core/Tokenizer.php';
require_once YUGA_ROOT . '/core/Transformer.php';
require_once YUGA_ROOT . '/core/Retriever.php';
require_once YUGA_ROOT . '/core/Brain.php';
require_once YUGA_ROOT . '/core/SelfLearner.php';
require_once YUGA_ROOT . '/core/Ingester.php';
require_once YUGA_ROOT . '/subscriptions/Plans.php';
require_once YUGA_ROOT . '/subscriptions/APIKeyManager.php';
require_once YUGA_ROOT . '/core/Mailer.php';

$config = file_exists(YUGA_ROOT . '/config.php') ? require YUGA_ROOT . '/config.php' : [];
session_start();

// ── SECURITY: Rate limiting ──────────────────────────────────────────
// TODO: Implement server-side rate limiting (e.g., via nginx limit_req,
// Redis-based counter, or a middleware) to protect the admin panel from
// brute-force login attempts and form abuse. Currently relies only on
// session-based authentication and CSRF tokens.
$admin_pw = $config['admin_password'] ?? '';
$admin_slug = $config['admin_slug'] ?? 'admin';

// ── Dynamic admin URL: block if accessed via wrong slug ───────────────
if ($admin_slug !== 'admin') {
  // REQUEST_URI still holds original URI before .htaccess rewrite
  $req_uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
  $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
  // Derive base from SCRIPT_NAME: /yuga/admin → /yuga
  $yuga_base = dirname($script);
  $ok_via_slug = str_contains($req_uri, '/' . $admin_slug);
  $ok_via_admin = str_contains($req_uri, '/admin');
  // If accessed directly via /admin/ (not through the custom slug), block
  if ($ok_via_admin && !$ok_via_slug) {
    http_response_code(404);
    exit('Not found.');
  }
}

// ── Block access if no admin password is configured ───────────────────
if (!$admin_pw) {
  http_response_code(403);
  echo '<!doctype html><html><head><title>Yuga — Setup Required</title><style>*{box-sizing:border-box}body{font-family:system-ui;background:#0a0f1e;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;color:#e2e8f0}.box{background:#0d1526;border:1px solid #7f1d1d;border-radius:16px;padding:40px;width:420px;text-align:center}h2{color:#f87171;margin:0 0 16px;font-size:20px}p{color:#94a3b8;font-size:14px;line-height:1.6;margin:0 0 20px}code{background:#1e293b;padding:4px 10px;border-radius:6px;color:#f59e0b;font-size:13px}</style></head><body><div class="box"><h2>&#9888; Setup Required</h2><p>The admin panel is not secured.<br>Open <code>config.php</code> and set <code>admin_password</code> and <code>api_key</code> before use.</p><p style="font-size:12px;color:#475569">This message is shown to everyone until a password is configured.</p></div></body></html>';
  exit;
}

// ── CSRF token ─────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
function csrf_field_admin(): string
{
  global $csrf;
  return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($csrf) . '">';
}

// ── YG Account SSO Check ──────────────────────────────────────────────
if (isset($_GET['token'])) {
  $token = $_GET['token'];
  $parts = explode('.', $token);
  if (count($parts) === 3) {
    $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0])), true);
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
    $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[2]));

    if ($payload && isset($payload['email']) && isset($payload['exp'])) {
      // Verify JWT expiration
      if ($payload['exp'] < time()) {
        http_response_code(401);
        exit('SSO token expired.');
      }

      // Verify JWT signature using YG_ACCOUNT_API_SECRET
      $yg_account_secret = $config['yg_account_api_secret'] ?? '';
      if ($yg_account_secret) {
        $signingInput = $parts[0] . '.' . $parts[1];
        $expectedSignature = hash_hmac('sha256', $signingInput, $yg_account_secret, true);
        if (!hash_equals($expectedSignature, $signature)) {
          http_response_code(401);
          exit('Invalid SSO token signature.');
        }
      }

      // Verify the email is an admin
      $admin_emails = $config['admin_emails'] ?? ['admin@ygxone.com'];
      if (!in_array($payload['email'], $admin_emails, true)) {
        http_response_code(403);
        exit('Access denied. You are not an authorized admin.');
      }

      $_SESSION['yuga_admin'] = true;
      $_SESSION['user_email'] = $payload['email'];
    }
  }
}

// ── Local login form handler ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'local_login') {
  if (($_POST['_csrf'] ?? '') === $csrf) {
    $submitted_pw = $_POST['admin_password'] ?? '';
    if ($submitted_pw !== '' && $submitted_pw === ($config['admin_password'] ?? '')) {
      $_SESSION['yuga_admin'] = true;
      $_SESSION['admin_user'] = 'local';
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    } else {
      $login_error = 'Incorrect password. Please try again.';
    }
  } else {
    $login_error = 'Invalid CSRF token. Please refresh and try again.';
  }
}

// ── Already authenticated? ────────────────────────────────────────────
$is_authenticated = $_SESSION['yuga_admin'] ?? false;

// ── Show login page if not authenticated ───────────────────────────────
if (!$is_authenticated) {
  $sso_login_url = "https://account.ygxone.com/login?service=yuga";
  $login_error_html = isset($login_error)
    ? '<div style="background:#1e1b4b;border:1px solid #7f1d1d;border-radius:8px;padding:12px;color:#fca5a5;font-size:13px;margin-bottom:16px;">' . htmlspecialchars($login_error) . '</div>'
    : '';
  http_response_code(200);
  echo '<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Yuga Admin — Login</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,sans-serif;background:#0a0f1e;display:flex;align-items:center;justify-content:center;min-height:100vh;color:#e2e8f0}
.card{background:#0d1526;border:1px solid #1e293b;border-radius:16px;padding:40px;width:400px;max-width:95vw}
.logo{text-align:center;margin-bottom:24px}
.logo h1{font-size:28px;font-weight:700;background:linear-gradient(135deg,#6366f1,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.logo p{color:#64748b;font-size:13px;margin-top:4px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;color:#94a3b8;margin-bottom:6px}
.form-group input{width:100%;padding:10px 14px;background:#0f172a;border:1px solid #1e293b;border-radius:8px;color:#e2e8f0;font-size:14px;outline:none;transition:border-color .2s}
.form-group input:focus{border-color:#6366f1}
.btn{display:block;width:100%;padding:12px;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;text-align:center;text-decoration:none;transition:opacity .2s}
.btn:active{opacity:.85}
.btn-primary{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff}
.btn-sso{background:#1e293b;color:#e2e8f0;border:1px solid #334155}
.divider{display:flex;align-items:center;gap:12px;margin:20px 0;color:#475569;font-size:12px}
.divider::before,.divider::after{content:"";flex:1;height:1px;background:#1e293b}
.footer{text-align:center;margin-top:20px;font-size:12px;color:#334155}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <h1>Yuga Admin</h1>
    <p>Sign in to the admin panel</p>
  </div>
  ' . $login_error_html . '
  <form method="post" action="">
    ' . csrf_field_admin() . '
    <input type="hidden" name="act" value="local_login">
    <div class="form-group">
      <label for="pw">Admin Password</label>
      <input type="password" id="pw" name="admin_password" placeholder="Enter admin password" required autofocus>
    </div>
    <button type="submit" class="btn btn-primary">Sign In Locally</button>
  </form>
  <div class="divider">or</div>
  <a href="' . htmlspecialchars($sso_login_url) . '" class="btn btn-sso">Login with YG Account (SSO)</a>
  <div class="footer">&copy; YG Ecosystem &mdash; Yuga Admin Panel</div>
</div>
</body>
</html>';
  exit;
}

$store = new ModelStore(YUGA_ROOT . '/data');
$akm = new APIKeyManager(YUGA_ROOT . '/data');
$models = $store->listModels();
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF check — reject any POST that doesn't carry the session token
  if (($_POST['_csrf'] ?? '') !== $csrf) {
    http_response_code(403);
    die('Invalid or missing CSRF token. Please refresh the page and try again.');
  }
  $act = $_POST['act'] ?? '';
  set_time_limit(600);
  if ($act === 'create_model') {
    $nm = preg_replace('/[^a-z0-9_-]/', '', strtolower($_POST['model_name'] ?? ''));
    $size = in_array($_POST['model_size'] ?? '', ['nano', 'small', 'medium', 'large']) ? $_POST['model_size'] : 'nano';
    if ($nm && !$store->loadModel($nm)) {
      require_once YUGA_ROOT . '/core/ModelPresets.php';
      $preset = ModelPresets::get($size);
      $m = new YugaLM();
      $saved = $m->save();
      $saved['_preset'] = $size;
      $store->saveModel($nm, $saved);
      $store->saveMeta($nm, ['preset' => $size, 'preset_name' => $preset['name'], 'approx_params' => $preset['approx_params'], 'created_at' => time()]);
      $flash = "Model '{$nm}' created — {$preset['emoji']} {$preset['name']} ({$preset['approx_params']} params).";
    }
  } elseif ($act === 'delete_model') {
    $store->deleteModel($_POST['model'] ?? '');
    $flash = "Model deleted.";
  } elseif ($act === 'bulk_upload') {
    $brain = new Brain($_POST['model'] ?? 'default', $store);
    $ingester = new Ingester($brain);
    $ingester->trainSteps = (int) ($_POST['steps'] ?? 10000);
    $files = $_FILES['bulk_files'] ?? [];
    $results = [];
    $ok = 0;
    $fail = 0;
    if (!empty($files['name'])) {
      $count = count($files['name']);
      for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
          $results[] = "✗ {$files['name'][$i]}: upload error";
          $fail++;
          continue;
        }
        $r = $ingester->ingestUpload([
          'name' => $files['name'][$i],
          'tmp_name' => $files['tmp_name'][$i],
          'error' => $files['error'][$i],
        ]);
        if (isset($r['error'])) {
          $results[] = "✗ {$files['name'][$i]}: {$r['error']}";
          $fail++;
        } else {
          $results[] = "✓ {$files['name'][$i]}: {$r['chars']} chars — loss {$r['loss']}";
          $ok++;
        }
      }
    }
    // Bulk URLs — one per line
    $urlList = array_filter(array_map('trim', explode("\n", $_POST['bulk_urls'] ?? '')));
    foreach ($urlList as $url) {
      if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $results[] = "✗ $url: invalid URL";
        $fail++;
        continue;
      }
      $r = $ingester->ingestUrl($url);
      if (isset($r['error'])) {
        $results[] = "✗ $url: {$r['error']}";
        $fail++;
      } else {
        $results[] = "✓ $url: ingested";
        $ok++;
      }
    }
    $flash = "Bulk import done — $ok succeeded, $fail failed.<br><small style='font-family:monospace;line-height:1.8'>" . implode('<br>', array_map('htmlspecialchars', $results)) . "</small>";
  } elseif ($act === 'learn_text') {
    $brain = new Brain($_POST['model'] ?? 'default', $store);
    $r = $brain->learn($_POST['text'] ?? '', (int) ($_POST['steps'] ?? 10000));
    $flash = "Trained! Loss: {$r['loss']} — Steps: {$r['steps']} — Vocab: {$r['vocab']}";
    if (!empty($config['push']['app_id'])) {
      require_once YUGA_ROOT . '/core/PushNotifier.php';
      (new PushNotifier($config['push']))->notifyTrainingComplete($_POST['model'] ?? 'default', (float) ($r['loss'] ?? 0));
    }
  } elseif ($act === 'learn_site') {
    $brain = new Brain($_POST['model'] ?? 'default', $store);
    $r = $brain->learnFromSite($_POST['url'] ?? '', (int) ($_POST['max_pages'] ?? 20));
    $flash = "Crawled {$r['pages']} pages. Loss: {$r['loss']}";
    if (!empty($config['push']['app_id'])) {
      require_once YUGA_ROOT . '/core/PushNotifier.php';
      (new PushNotifier($config['push']))->notifyTrainingComplete($_POST['model'] ?? 'default', (float) ($r['loss'] ?? 0));
    }
  } elseif ($act === 'learn_url') {
    $brain = new Brain($_POST['model'] ?? 'default', $store);
    $r = $brain->learnFromURL($_POST['url'] ?? '');
    if (isset($r['error'])) {
      $flash = "Error: {$r['error']}";
    } else {
      $flash = "Learned from URL. Loss: {$r['loss']} — Steps: {$r['steps']} — Vocab: {$r['vocab']}";
    }
  } elseif ($act === 'create_subscriber') {
    try {
      $sub = $akm->createSubscriber($_POST['sub_name'], $_POST['sub_email'], $_POST['sub_plan'] ?? 'free');
      $key = $akm->createKey($sub['id'], 'default');
      $flash = "Subscriber created! Key: <code>{$key['key']}</code> — shown once.";
      try {
        (new Mailer($config))->sendWelcome($_POST['sub_email'], $_POST['sub_name'], $key['key'], $_POST['sub_plan'] ?? 'free');
      } catch (Exception $e) {
      }
      try {
        require_once YUGA_ROOT . '/core/Webhooks.php';
        (new Webhooks(YUGA_ROOT . '/data'))->fire('subscriber.created', ['name' => $_POST['sub_name'], 'email' => $_POST['sub_email'], 'plan' => $_POST['sub_plan'] ?? 'free']);
        if (!empty($config['slack']['webhook_url'])) {
          require_once YUGA_ROOT . '/integrations/Slack.php';
          (new Slack($config['slack']['webhook_url']))->notifySubscriber($_POST['sub_name'], $_POST['sub_email'], $_POST['sub_plan'] ?? 'free');
        }
        if (!empty($config['push']['app_id'])) {
          require_once YUGA_ROOT . '/core/PushNotifier.php';
          (new PushNotifier($config['push']))->notifyNewSubscriber($_POST['sub_name'], $_POST['sub_plan'] ?? 'free');
        }
      } catch (Exception $e) {
      }
    } catch (Exception $e) {
      $flash = "Error: " . htmlspecialchars($e->getMessage());
    }
  } elseif ($act === 'update_plan') {
    $old = $akm->getSubscriber($_POST['sub_id']);
    $akm->updatePlan($_POST['sub_id'], $_POST['plan']);
    $flash = "Plan updated.";
    try {
      require_once YUGA_ROOT . '/core/Webhooks.php';
      (new Webhooks(YUGA_ROOT . '/data'))->fire('subscriber.upgraded', ['sub_id' => $_POST['sub_id'], 'old_plan' => $old['plan'] ?? '', 'new_plan' => $_POST['plan']]);
    } catch (Exception $e) {
    }
  } elseif ($act === 'suspend_sub') {
    $sid = $_POST['sub_id'];
    $akm->suspendSubscriber($sid);
    $who = $akm->getSubscriber($sid);
    if ($who)
      try {
        (new Mailer($config))->sendSuspended($who['email'], $who['name']);
      } catch (Exception $e) {
      }
    try {
      require_once YUGA_ROOT . '/core/Webhooks.php';
      (new Webhooks(YUGA_ROOT . '/data'))->fire('subscriber.suspended', ['sub_id' => $sid, 'email' => $who['email'] ?? '']);
    } catch (Exception $e) {
    }
    $flash = "Suspended.";
  } elseif ($act === 'revoke_key') {
    $akm->revokeKey($_POST['key_id'], $_POST['sub_id']);
    $flash = "Key revoked.";
  } elseif ($act === 'webhook_add') {
    require_once YUGA_ROOT . '/core/Webhooks.php';
    try {
      $wh = new Webhooks(YUGA_ROOT . '/data');
      $wh->register($_POST['wh_url'], $_POST['wh_events'] ?? [], $_POST['wh_label'] ?? '');
      $flash = "Webhook registered.";
    } catch (Exception $e) {
      $flash = "Error: " . htmlspecialchars($e->getMessage());
    }
  } elseif ($act === 'webhook_delete') {
    require_once YUGA_ROOT . '/core/Webhooks.php';
    (new Webhooks(YUGA_ROOT . '/data'))->delete($_POST['hook_id']);
    $flash = "Webhook deleted.";
  } elseif ($act === 'webhook_test') {
    require_once YUGA_ROOT . '/core/Webhooks.php';
    $r = (new Webhooks(YUGA_ROOT . '/data'))->test($_POST['hook_id']);
    $flash = $r['ok'] ? "Test delivered (HTTP {$r['http_status']})." : "Test failed (HTTP {$r['http_status']}).";
  } elseif ($act === 'scheduler_add') {
    require_once YUGA_ROOT . '/core/Scheduler.php';
    (new Scheduler(YUGA_ROOT . '/data'))->create($_POST);
    $flash = "Task scheduled.";
  } elseif ($act === 'scheduler_delete') {
    require_once YUGA_ROOT . '/core/Scheduler.php';
    (new Scheduler(YUGA_ROOT . '/data'))->delete($_POST['task_id']);
    $flash = "Task deleted.";
  } elseif ($act === 'scheduler_toggle') {
    require_once YUGA_ROOT . '/core/Scheduler.php';
    (new Scheduler(YUGA_ROOT . '/data'))->toggle($_POST['task_id'], (bool) ($_POST['active'] ?? 0));
    $flash = "Task updated.";
  } elseif ($act === 'whitelabel_save') {
    require_once YUGA_ROOT . '/core/WhiteLabel.php';
    $sid = trim($_POST['sub_id'] ?? '');
    if ($sid) {
      (new WhiteLabel(YUGA_ROOT . '/data'))->saveTenant($sid, [
        'widget_name' => substr(trim($_POST['widget_name'] ?? 'AI Assistant'), 0, 80),
        'widget_greeting' => substr(trim($_POST['widget_greeting'] ?? 'Hi! How can I help?'), 0, 200),
        'widget_theme' => in_array($_POST['widget_theme'] ?? '', ['dark', 'light']) ? $_POST['widget_theme'] : 'dark',
        'primary_color' => preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['primary_color'] ?? '') ? $_POST['primary_color'] : '#6366f1',
        'logo_url' => filter_var(trim($_POST['logo_url'] ?? ''), FILTER_VALIDATE_URL) ? trim($_POST['logo_url']) : '',
        'avatar_initials' => substr(preg_replace('/[^A-Za-z0-9]/', '', trim($_POST['avatar_initials'] ?? 'AI')), 0, 3) ?: 'AI',
        'persona' => substr(trim($_POST['persona'] ?? 'helpful and friendly'), 0, 200),
        'hide_branding' => (int) !empty($_POST['hide_branding']),
        'custom_domain' => trim($_POST['custom_domain'] ?? ''),
        'allowed_origins' => trim($_POST['allowed_origins'] ?? '*'),
      ]);
      $flash = "White-label settings saved.";
    }
  } elseif ($act === 'model_import') {
    require_once YUGA_ROOT . '/core/ModelPorter.php';
    if (!empty($_FILES['yuga_file']['tmp_name']) && $_FILES['yuga_file']['error'] === UPLOAD_ERR_OK) {
      try {
        $nm = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($_POST['import_name'] ?? '')));
        $r = (new ModelPorter(YUGA_ROOT . '/data', $store))->import($_FILES['yuga_file']['tmp_name'], $nm);
        $flash = "Model '{$r['model']}' imported (originally '{$r['source_name']}', exported {$r['exported_at']}).";
        $models = $store->listModels();
      } catch (Exception $e) {
        $flash = "Import failed: " . htmlspecialchars($e->getMessage());
      }
    } else {
      $flash = "Please upload a .yuga file.";
    }
  } elseif ($act === 'version_save') {
    require_once YUGA_ROOT . '/core/Versioning.php';
    $mn = trim($_POST['model'] ?? 'default');
    $vn = preg_replace('/[^a-zA-Z0-9._-]/', '', trim($_POST['version'] ?? ''));
    if ($mn && $vn) {
      try {
        $bs2 = (new Brain($mn, $store))->status();
      } catch (Exception $e) {
        $bs2 = [];
      }
      $ok = (new Versioning(YUGA_ROOT . '/data'))->saveVersion($mn, $vn, $bs2, substr(trim($_POST['notes'] ?? ''), 0, 200));
      $flash = $ok ? "Version '$vn' saved for '$mn'." : "Save failed — no trained weights yet.";
    }
  } elseif ($act === 'version_activate') {
    require_once YUGA_ROOT . '/core/Versioning.php';
    $ok = (new Versioning(YUGA_ROOT . '/data'))->setActive(trim($_POST['model'] ?? ''), trim($_POST['version'] ?? ''));
    $flash = $ok ? "Version '{$_POST['version']}' is now active." : "Activation failed — checkpoint file missing.";
  } elseif ($act === 'version_delete') {
    require_once YUGA_ROOT . '/core/Versioning.php';
    (new Versioning(YUGA_ROOT . '/data'))->deleteVersion(trim($_POST['model'] ?? ''), trim($_POST['version'] ?? ''));
    $flash = "Version deleted.";
  } elseif ($act === 'ab_start') {
    require_once YUGA_ROOT . '/core/Versioning.php';
    (new Versioning(YUGA_ROOT . '/data'))->startAB(trim($_POST['model'] ?? ''), trim($_POST['va'] ?? ''), trim($_POST['vb'] ?? ''), (int) ($_POST['traffic_b'] ?? 50));
    $flash = "A/B test started.";
  } elseif ($act === 'ab_end') {
    require_once YUGA_ROOT . '/core/Versioning.php';
    (new Versioning(YUGA_ROOT . '/data'))->endAB(trim($_POST['model'] ?? ''), trim($_POST['winner'] ?? ''));
    $flash = "A/B test ended. Winner activated.";
  } elseif ($act === 'save_web_search') {
    require_once YUGA_ROOT . '/core/ConfigWriter.php';
    $ws_provider = in_array($_POST['ws_provider'] ?? '', ['duckduckgo', 'brave', 'serpapi']) ? $_POST['ws_provider'] : 'duckduckgo';
    $ws_key = trim($_POST['ws_key'] ?? '');
    (new ConfigWriter(YUGA_ROOT . '/config.php'))->setSection('web_search', ['provider' => $ws_provider, 'api_key' => $ws_key]);
    $config['web_search'] = ['provider' => $ws_provider, 'api_key' => $ws_key];
    $flash = "Web search settings saved.";

  } elseif ($act === 'save_sso_quick') {
    require_once YUGA_ROOT . '/core/ConfigWriter.php';
    $secret = trim($_POST['yg_account_api_secret'] ?? '');
    $emails = array_filter(array_map('trim', explode(',', trim($_POST['admin_emails'] ?? ''))));
    if (empty($emails))
      $emails = ['admin@ygxone.com'];
    (new ConfigWriter(YUGA_ROOT . '/config.php'))->update([
      'yg_account_api_secret' => $secret,
      'admin_emails' => $emails,
    ]);
    $config['yg_account_api_secret'] = $secret;
    $config['admin_emails'] = $emails;
    $flash = "SSO configuration saved.";

  } elseif ($act === 'save_platform') {
    require_once YUGA_ROOT . '/core/ConfigWriter.php';
    $changes = [
      'platform_name' => substr(trim($_POST['platform_name'] ?? 'Yuga'), 0, 60),
      'site_description' => substr(trim($_POST['site_description'] ?? ''), 0, 200),
      'site_url' => trim($_POST['site_url'] ?? ''),
      'default_model' => preg_replace('/[^a-z0-9_-]/', '', trim($_POST['default_model'] ?? 'default')),
      'assistant_name' => substr(trim($_POST['assistant_name'] ?? 'Yuga'), 0, 60),
    ];
    (new ConfigWriter(YUGA_ROOT . '/config.php'))->update($changes);
    foreach ($changes as $k => $v)
      $config[$k] = $v;
    $flash = "Platform settings saved.";

  } elseif ($act === 'save_security') {
    require_once YUGA_ROOT . '/core/ConfigWriter.php';
    $cw = new ConfigWriter(YUGA_ROOT . '/config.php');
    $changes = [];
    $new_pw = trim($_POST['new_password'] ?? '');
    if ($new_pw && strlen($new_pw) >= 8)
      $changes['admin_password'] = $new_pw;
    $new_key = trim($_POST['new_api_key'] ?? '');
    if ($new_key && strlen($new_key) >= 16)
      $changes['api_key'] = $new_key;
    $new_slug = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($_POST['admin_slug'] ?? '')));
    if ($new_slug)
      $changes['admin_slug'] = $new_slug;
    if ($changes) {
      $cw->update($changes);
      foreach ($changes as $k => $v)
        $config[$k] = $v;
      $flash = "Security settings saved.";
    } else {
      $flash = "Nothing changed. Password needs 8+ chars, API key 16+ chars.";
    }

  } elseif ($act === 'save_smtp') {
    require_once YUGA_ROOT . '/core/ConfigWriter.php';
    $smtp = [
      'host' => trim($_POST['smtp_host'] ?? ''),
      'port' => (int) ($_POST['smtp_port'] ?? 587),
      'user' => trim($_POST['smtp_user'] ?? ''),
      'pass' => trim($_POST['smtp_pass'] ?? ''),
      'from_email' => trim($_POST['from_email'] ?? ''),
      'from_name' => trim($_POST['from_name'] ?? 'Yuga'),
      'encryption' => in_array($_POST['smtp_enc'] ?? 'tls', ['tls', 'ssl', 'none']) ? $_POST['smtp_enc'] : 'tls',
    ];
    (new ConfigWriter(YUGA_ROOT . '/config.php'))->setSection('smtp', $smtp);
    $config['smtp'] = $smtp;
    $flash = "SMTP settings saved.";

  } elseif ($act === 'test_smtp') {
    $to = trim($_POST['test_email'] ?? '');
    if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
      try {
        (new Mailer($config))->sendRaw($to, 'Yuga SMTP Test', '<p>Your SMTP configuration is working correctly! This email was sent from Yuga admin.</p>');
        $flash = "Test email sent to $to — check your inbox.";
      } catch (Exception $e) {
        $flash = "SMTP test failed: " . htmlspecialchars($e->getMessage());
      }
    } else {
      $flash = "Enter a valid test email address.";
    }

  } elseif ($act === 'save_payments') {
    require_once YUGA_ROOT . '/core/ConfigWriter.php';
    $payments = [
      'stripe' => ['enabled' => !empty($_POST['stripe_enabled']), 'secret_key' => trim($_POST['stripe_secret'] ?? ''), 'public_key' => trim($_POST['stripe_public'] ?? ''), 'webhook_secret' => trim($_POST['stripe_webhook'] ?? '')],
      'paypal' => ['enabled' => !empty($_POST['paypal_enabled']), 'client_id' => trim($_POST['paypal_client'] ?? ''), 'secret' => trim($_POST['paypal_secret'] ?? ''), 'mode' => in_array($_POST['paypal_mode'] ?? '', ['sandbox', 'live']) ? $_POST['paypal_mode'] : 'sandbox'],
      'esewa' => ['enabled' => !empty($_POST['esewa_enabled']), 'merchant_id' => trim($_POST['esewa_merchant'] ?? ''), 'secret' => trim($_POST['esewa_secret'] ?? '')],
      'fonepay' => ['enabled' => !empty($_POST['fonepay_enabled']), 'merchant_code' => trim($_POST['fonepay_merchant'] ?? ''), 'secret' => trim($_POST['fonepay_secret'] ?? '')],
      'imepay' => ['enabled' => !empty($_POST['imepay_enabled']), 'merchant_code' => trim($_POST['imepay_merchant'] ?? ''), 'token' => trim($_POST['imepay_token'] ?? '')],
    ];
    (new ConfigWriter(YUGA_ROOT . '/config.php'))->setSection('payments', $payments);
    $config['payments'] = $payments;
    $flash = "Payment gateway settings saved.";

  } elseif ($act === 'save_email_template') {
    require_once YUGA_ROOT . '/core/EmailTemplates.php';
    $tpl = new EmailTemplates(YUGA_ROOT . '/data');
    $name = trim($_POST['tpl_name'] ?? '');
    $subj = trim($_POST['tpl_subject'] ?? '');
    $body = trim($_POST['tpl_body'] ?? '');
    if ($name && $subj && $body) {
      $tpl->save($name, $subj, $body);
      $flash = "Template '" . htmlspecialchars($name) . "' saved.";
    } else {
      $flash = "All fields are required.";
    }
  } elseif ($act === 'reset_email_template') {
    require_once YUGA_ROOT . '/core/EmailTemplates.php';
    $tpl = new EmailTemplates(YUGA_ROOT . '/data');
    $name = trim($_POST['tpl_name'] ?? '');
    if ($name) {
      $tpl->reset($name);
      $flash = "Template reset to default.";
    }
  } elseif ($act === 'save_push_settings') {
    require_once YUGA_ROOT . '/core/ConfigWriter.php';
    $events = [];
    foreach (array_keys(['new_subscriber' => 1, 'payment' => 1, 'training_complete' => 1, 'api_limit_warning' => 1, 'api_limit_exceeded' => 1]) as $ev) {
      $events[$ev] = !empty($_POST['push_event_' . $ev]);
    }
    $push = ['app_id' => trim($_POST['push_app_id'] ?? ''), 'api_key' => trim($_POST['push_api_key'] ?? ''), 'events' => $events];
    (new ConfigWriter(YUGA_ROOT . '/config.php'))->setSection('push', $push);
    $config['push'] = $push;
    $flash = "Push notification settings saved.";
  } elseif ($act === 'send_push_broadcast') {
    require_once YUGA_ROOT . '/core/PushNotifier.php';
    $pn = new PushNotifier($config['push'] ?? []);
    $title = trim($_POST['push_title'] ?? '');
    $msg = trim($_POST['push_message'] ?? '');
    $url = trim($_POST['push_url'] ?? '');
    if (!$title || !$msg) {
      $flash = "Title and message are required.";
    } elseif (!$pn->ready()) {
      $flash = "OneSignal not configured — add App ID and REST API Key first.";
    } else {
      $r = $pn->sendToAll($title, $msg, $url);
      $flash = isset($r['error']) ? "Push failed: {$r['error']}" : "Push sent to {$r['recipients']} subscriber(s).";
    }
  } elseif ($act === 'blog_save') {
    require_once YUGA_ROOT . '/core/Blog.php';
    $blog = new Blog(YUGA_ROOT . '/data');
    $id = trim($_POST['post_id'] ?? '');
    $data = [
      'title' => substr(trim($_POST['title'] ?? ''), 0, 200),
      'slug' => substr(strtolower(preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', trim($_POST['slug'] ?? '')))), 0, 200),
      'excerpt' => substr(trim($_POST['excerpt'] ?? ''), 0, 500),
      'body' => trim($_POST['body'] ?? ''),
      'tags' => array_filter(array_map('trim', explode(',', $_POST['tags'] ?? ''))),
      'status' => ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft',
      'author' => substr(trim($_POST['author'] ?? 'Admin'), 0, 100),
    ];
    if ($id && $blog->get($id)) {
      $blog->update($id, $data);
      $flash = "Post updated.";
    } else {
      $id = $blog->create($data);
      $flash = "Post created. ID: $id";
    }
    header('Location: ?page=blog&edit=' . $id);
    exit;
  } elseif ($act === 'blog_delete') {
    require_once YUGA_ROOT . '/core/Blog.php';
    (new Blog(YUGA_ROOT . '/data'))->delete(trim($_POST['post_id'] ?? ''));
    $flash = "Post deleted.";
  } elseif ($act === 'ticket_reply') {
    require_once YUGA_ROOT . '/core/Tickets.php';
    $tk = new Tickets(YUGA_ROOT . '/data');
    $tid = trim($_POST['ticket_id'] ?? '');
    $msg = trim($_POST['reply_message'] ?? '');
    if ($tid && $msg) {
      $tk->addMessage($tid, 'Admin', $config['mail']['from_email'] ?? 'admin@yuga', 'admin', $msg);
      $tk->updateStatus($tid, 'in_progress');
      // Email the user
      $ticket = $tk->get($tid);
      if ($ticket) {
        try {
          (new Mailer($config))->sendRaw($ticket['email'], 'Re: [' . $tid . '] ' . $ticket['subject'], '<p>Hello ' . $ticket['name'] . ',</p><p>' . nl2br(htmlspecialchars($msg)) . '</p><p>— Support Team</p>');
        } catch (Exception $e) {
        }
      }
      $flash = "Reply sent.";
    }
    header('Location: ?page=tickets&view=' . urlencode($tid));
    exit;
  } elseif ($act === 'ticket_status') {
    require_once YUGA_ROOT . '/core/Tickets.php';
    (new Tickets(YUGA_ROOT . '/data'))->updateStatus(trim($_POST['ticket_id'] ?? ''), trim($_POST['status'] ?? 'open'));
    $flash = "Ticket status updated.";
  } elseif ($act === 'ticket_delete') {
    require_once YUGA_ROOT . '/core/Tickets.php';
    (new Tickets(YUGA_ROOT . '/data'))->delete(trim($_POST['ticket_id'] ?? ''));
    $flash = "Ticket deleted.";
  } elseif ($act === 'contact_delete') {
    require_once YUGA_ROOT . '/core/ContactStore.php';
    (new ContactStore(YUGA_ROOT . '/data'))->delete(trim($_POST['contact_id'] ?? ''));
    $flash = "Contact submission deleted.";
  } elseif ($act === 'contact_read') {
    require_once YUGA_ROOT . '/core/ContactStore.php';
    (new ContactStore(YUGA_ROOT . '/data'))->markRead(trim($_POST['contact_id'] ?? ''));
    $flash = "Marked as read.";
  } elseif ($act === 'workflow_create') {
    require_once YUGA_ROOT . '/core/WorkflowEngine.php';
    $wfe = new WorkflowEngine(YUGA_ROOT . '/data');
    $steps = json_decode($_POST['steps_json'] ?? '[]', true) ?: [];
    $id = $wfe->create(trim($_POST['wf_name'] ?? 'Workflow'), trim($_POST['wf_model'] ?? 'default'), $steps, ['schedule' => trim($_POST['wf_schedule'] ?? '')]);
    $flash = "Workflow created. ID: $id";
    header('Location: ?page=workflows&edit=' . $id);
    exit;
  } elseif ($act === 'workflow_delete') {
    require_once YUGA_ROOT . '/core/WorkflowEngine.php';
    (new WorkflowEngine(YUGA_ROOT . '/data'))->delete(trim($_POST['wf_id'] ?? ''));
    $flash = "Workflow deleted.";
  } elseif ($act === 'workflow_toggle') {
    require_once YUGA_ROOT . '/core/WorkflowEngine.php';
    (new WorkflowEngine(YUGA_ROOT . '/data'))->toggle(trim($_POST['wf_id'] ?? ''), (bool) (int) ($_POST['enabled'] ?? 1));
    $flash = "Workflow updated.";
  } elseif ($act === 'save_ai_backends') {
    require_once YUGA_ROOT . '/core/ConfigWriter.php';
    $cw = new ConfigWriter(YUGA_ROOT . '/config.php');
    $backend = trim($_POST['llm_backend'] ?? 'none');
    $changes = [
      'llm_backend' => in_array($backend, ['openai', 'anthropic', 'groq', 'ollama', 'together', 'none']) ? $backend : 'none',
      'llm_api_key' => trim($_POST['llm_api_key'] ?? ''),
      'llm_model' => trim($_POST['llm_model'] ?? ''),
      'llm_endpoint' => trim($_POST['llm_endpoint'] ?? ''),
      'ai_backends' => [
        'openai' => ['api_key' => trim($_POST['openai_key'] ?? ''), 'model' => trim($_POST['openai_model'] ?? 'gpt-4o-mini')],
        'anthropic' => ['api_key' => trim($_POST['anthropic_key'] ?? ''), 'model' => trim($_POST['anthropic_model'] ?? 'claude-haiku-4-5-20251001')],
        'groq' => ['api_key' => trim($_POST['groq_key'] ?? ''), 'model' => trim($_POST['groq_model'] ?? 'llama-3.3-70b-versatile')],
        'ollama' => ['endpoint' => trim($_POST['ollama_endpoint'] ?? 'http://localhost:11434'), 'model' => trim($_POST['ollama_model'] ?? 'llama3.2')],
        'together' => ['api_key' => trim($_POST['together_key'] ?? ''), 'model' => trim($_POST['together_model'] ?? 'meta-llama/Meta-Llama-3.1-70B-Instruct-Turbo')],
      ],
    ];
    $cw->update($changes);
    foreach ($changes as $k => $v)
      $config[$k] = $v;
    $flash = "AI backend settings saved. Active: " . $changes['llm_backend'];
  }
  $models = $store->listModels();
}

$page = $_GET['page'] ?? 'dashboard';

// ── SSE streaming for self-learning (crawl site) ─────────────────
if ($page === 'train' && ($_GET['stream'] ?? '') === '1') {
  session_write_close(); // release session lock so other tabs don't get 500
  @ini_set('max_execution_time', 0);
  set_time_limit(0);
  ignore_user_abort(true);
  header('Content-Type: text/event-stream');
  header('Cache-Control: no-cache');
  header('X-Accel-Buffering: no');
  header('Connection: keep-alive');
  if (ob_get_level())
    ob_end_clean();

  $sse = function (array $d) {
    echo 'data: ' . json_encode($d) . "\n\n";
    @ob_flush();
    flush();
  };

  $model = preg_replace('/[^a-z0-9_-]/', '', $_GET['model'] ?? 'default');
  $url = filter_var(urldecode($_GET['url'] ?? ''), FILTER_VALIDATE_URL);
  $max_pages = min(max((int) ($_GET['max_pages'] ?? 20), 1), 100);
  $steps = min(max((int) ($_GET['steps'] ?? 20000), 100), 500000);

  if (!$url) {
    $sse(['type' => 'error', 'msg' => 'Invalid URL']);
    exit;
  }

  $sse(['type' => 'info', 'msg' => "Starting crawl of {$url} (max {$max_pages} pages)..."]);

  try {
    $brain = new Brain($model, $store);
    $r = $brain->learnFromSite($url, $max_pages, function ($p) use ($sse) {
      $sse(['type' => 'page', 'msg' => "Fetching page {$p['page']}: {$p['url']}", 'page' => $p['page']]);
    });
    if (isset($r['error'])) {
      $sse(['type' => 'error', 'msg' => "Error: {$r['error']}"]);
      exit;
    }
    if (!empty($config['push']['app_id'])) {
      require_once YUGA_ROOT . '/core/PushNotifier.php';
      (new PushNotifier($config['push']))->notifyTrainingComplete($model, (float) ($r['loss'] ?? 0));
    }
    $sse([
      'type' => 'done',
      'msg' => "Done! Crawled {$r['pages']} pages · Loss: {$r['loss']} · Steps: {$r['steps']} · Vocab: {$r['vocab']}",
      'pages' => $r['pages'],
      'loss' => $r['loss'],
      'steps' => $r['steps'],
      'vocab' => $r['vocab']
    ]);
  } catch (Throwable $e) {
    $sse(['type' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
  }
  exit;
}

// ── SSE streaming for Nepali pipeline ────────────────────────────
if ($page === 'nepali' && ($_GET['stream'] ?? '') === '1') {
  session_write_close();
  @ini_set('max_execution_time', 0);
  set_time_limit(0);
  ignore_user_abort(true);
  header('Content-Type: text/event-stream');
  header('Cache-Control: no-cache');
  header('X-Accel-Buffering: no');
  if (ob_get_level())
    ob_end_clean();
  $sse = function (array $d) {
    echo 'data: ' . json_encode($d) . "\n\n";
    @ob_flush();
    flush();
  };
  require_once YUGA_ROOT . '/core/NepaliPipeline.php';
  $model = preg_replace('/[^a-z0-9_-]/', '', $_GET['model'] ?? 'default');
  $mode = $_GET['mode'] ?? 'source';  // source | group
  $key = $_GET['key'] ?? '';
  $brain = null;
  try {
    $brain = new Brain($model, $store);
  } catch (Throwable $e) {
    $sse(['type' => 'error', 'msg' => $e->getMessage()]);
    exit;
  }
  $pl = new NepaliPipeline($brain, function ($e) use ($sse) {
    $sse($e);
  });
  try {
    if ($mode === 'group')
      $r = $pl->runGroup($key);
    else
      $r = $pl->runSource($key);
    $sse(['type' => 'done', 'msg' => 'Pipeline complete.', 'result' => $r]);
  } catch (Throwable $e) {
    $sse(['type' => 'error', 'msg' => $e->getMessage()]);
  }
  exit;
}

// ── SSE streaming for Workflow execution ─────────────────────────
if ($page === 'workflows' && ($_GET['stream'] ?? '') === '1') {
  session_write_close();
  @ini_set('max_execution_time', 0);
  set_time_limit(0);
  ignore_user_abort(true);
  header('Content-Type: text/event-stream');
  header('Cache-Control: no-cache');
  header('X-Accel-Buffering: no');
  if (ob_get_level())
    ob_end_clean();
  $sse = function (array $d) {
    echo 'data: ' . json_encode($d) . "\n\n";
    @ob_flush();
    flush();
  };
  require_once YUGA_ROOT . '/core/WorkflowEngine.php';
  $wf_id = $_GET['wf_id'] ?? '';
  $wfe = new WorkflowEngine(YUGA_ROOT . '/data');
  $wf = $wfe->get($wf_id);
  if (!$wf) {
    $sse(['type' => 'error', 'msg' => "Workflow not found: $wf_id"]);
    exit;
  }
  $brain = null;
  try {
    $brain = new Brain($wf['model'], $store);
  } catch (Throwable $e) {
    $sse(['type' => 'error', 'msg' => $e->getMessage()]);
    exit;
  }
  try {
    $wfe->run($wf_id, $brain, function ($e) use ($sse) {
      $sse($e);
    });
  } catch (Throwable $e) {
    $sse(['type' => 'error', 'msg' => $e->getMessage()]);
  }
  exit;
}

$stats = $akm->getGlobalStats();
$plans = Plans::all();
$subs = $akm->listSubscribers(200);
$sel_model = $_GET['m'] ?? ($models[0] ?? 'default');
$bs = ['ready' => false, 'vocab' => 0, 'steps' => 0, 'loss' => 0, 'sentences' => 0, 'params' => 0, 'arch' => ''];
try {
  $bs = (new Brain($sel_model, $store))->status();
} catch (Exception $e) {
}
$api_base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . str_replace('/admin/index.php', '/api/', $_SERVER['SCRIPT_NAME']);
$portal_base = str_replace('/api/', '/portal/', $api_base);

// ── Early-exit: model export download (must run before any HTML output) ─
if (($_GET['page'] ?? '') === 'porter' && isset($_GET['download'])) {
  require_once YUGA_ROOT . '/core/ModelPorter.php';
  $dl_store = new ModelStore(YUGA_ROOT . '/data');
  $porter_dl = new ModelPorter(YUGA_ROOT . '/data', $dl_store);
  try {
    $porter_dl->download($_GET['download']);
  } catch (Exception $e) {
    http_response_code(400);
    die('Export failed: ' . htmlspecialchars($e->getMessage()));
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Yuga Admin</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
  <style>
    /* ═══════════════════════════════════════════════════
   YUGA COSMOS ADMIN — Design System v2
   ═══════════════════════════════════════════════════ */
    :root {
      --bg: #030712;
      --bg2: #080e1c;
      --bg3: #0a1020;
      --bg4: #0f1929;
      --bd: rgba(255, 255, 255, .07);
      --bd2: rgba(255, 255, 255, .13);
      --tx: #f1f5f9;
      --mu: #8b9ab5;
      --di: #445168;
      --pu: #6366f1;
      --pu2: #4f46e5;
      --vi: #8b5cf6;
      --cy: #06b6d4;
      --te: #14b8a6;
      --am: #f59e0b;
      --gr: #10b981;
      --re: #f43f5e;
      --pl: #a5b4fc;
      --cv: #c4b5fd;
      --cg: #67e8f9;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Inter', system-ui, sans-serif;
      background: var(--bg);
      color: var(--tx);
      min-height: 100vh;
      display: flex;
      background-image: radial-gradient(ellipse 60% 50% at 0% 0%, rgba(99, 102, 241, .09) 0%, transparent 55%),
        radial-gradient(ellipse 50% 40% at 100% 100%, rgba(139, 92, 246, .07) 0%, transparent 55%)
    }

    a {
      color: inherit;
      text-decoration: none
    }

    button {
      cursor: pointer;
      font-family: inherit
    }

    /* ── Sidebar ── */
    .sb {
      width: 234px;
      background: rgba(8, 14, 28, .96);
      border-right: 1px solid var(--bd);
      display: flex;
      flex-direction: column;
      height: 100vh;
      position: sticky;
      top: 0;
      flex-shrink: 0;
      backdrop-filter: blur(20px)
    }

    .sb-logo {
      padding: 22px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      border-bottom: 1px solid var(--bd);
      position: relative
    }

    .sb-logo::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 20px;
      right: 20px;
      height: 1px;
      background: linear-gradient(90deg, transparent, rgba(99, 102, 241, .4), transparent)
    }

    .lm {
      width: 34px;
      height: 34px;
      background: linear-gradient(135deg, var(--pu), var(--vi));
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: 900;
      font-size: 15px;
      box-shadow: 0 0 18px rgba(99, 102, 241, .45)
    }

    .lt {
      font-size: 16px;
      font-weight: 800;
      letter-spacing: -.2px
    }

    .lv {
      font-size: 10px;
      color: var(--di);
      margin-left: auto;
      background: rgba(255, 255, 255, .06);
      padding: 2px 8px;
      border-radius: 100px;
      letter-spacing: .02em
    }

    .sb nav {
      padding: 14px 0;
      flex: 1;
      overflow-y: auto
    }

    .sb nav::-webkit-scrollbar {
      width: 3px
    }

    .sb nav::-webkit-scrollbar-thumb {
      background: var(--bd);
      border-radius: 3px
    }

    .ns {
      padding: 8px 20px 5px;
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: .14em;
      color: var(--di);
      margin-top: 10px;
      font-weight: 700
    }

    .nl {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 20px;
      font-size: 13px;
      color: var(--mu);
      transition: .2s;
      position: relative;
      border-radius: 0;
      font-weight: 500
    }

    .nl:hover {
      background: rgba(255, 255, 255, .05);
      color: var(--tx)
    }

    .nl.active {
      background: rgba(99, 102, 241, .14);
      color: var(--pl)
    }

    .nl.active::before {
      content: '';
      position: absolute;
      left: 0;
      top: 4px;
      bottom: 4px;
      width: 3px;
      background: linear-gradient(var(--pu), var(--vi));
      border-radius: 0 3px 3px 0
    }

    .nd {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: currentColor;
      flex-shrink: 0
    }

    .nd.g {
      background: var(--gr)
    }

    .nd.a {
      background: var(--am)
    }

    .nd.p {
      background: var(--pu)
    }

    .nd.t {
      background: var(--te)
    }

    .nd.r {
      background: var(--re)
    }

    .nd.d {
      background: var(--di)
    }

    /* ── Main area ── */
    .main {
      flex: 1;
      overflow-y: auto;
      min-width: 0
    }

    .topbar {
      background: rgba(8, 14, 28, .9);
      backdrop-filter: blur(16px);
      border-bottom: 1px solid var(--bd);
      padding: 14px 28px;
      display: flex;
      align-items: center;
      gap: 12px;
      position: sticky;
      top: 0;
      z-index: 10
    }

    .tb-title {
      font-size: 15px;
      font-weight: 700;
      letter-spacing: -.2px
    }

    .tb-right {
      margin-left: auto;
      display: flex;
      gap: 8px;
      align-items: center
    }

    .badge {
      font-size: 11px;
      padding: 4px 11px;
      border-radius: 100px;
      font-weight: 600;
      letter-spacing: .01em
    }

    .bg-pu {
      background: rgba(99, 102, 241, .18);
      color: var(--pl)
    }

    .bg-gr {
      background: rgba(16, 185, 129, .14);
      color: #6ee7b7
    }

    .bg-am {
      background: rgba(245, 158, 11, .14);
      color: #fcd34d
    }

    .content {
      padding: 28px
    }

    /* ── Flash message ── */
    .flash {
      background: rgba(16, 185, 129, .1);
      border: 1px solid rgba(16, 185, 129, .28);
      border-radius: 12px;
      padding: 13px 18px;
      font-size: 13px;
      color: #6ee7b7;
      margin-bottom: 22px
    }

    /* ── Stat grid ── */
    .sg {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(155px, 1fr));
      gap: 16px;
      margin-bottom: 28px
    }

    .sc {
      background: rgba(10, 16, 32, .8);
      border: 1px solid var(--bd);
      border-radius: 16px;
      padding: 20px;
      backdrop-filter: blur(8px);
      transition: .25s;
      position: relative;
      overflow: hidden
    }

    .sc::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 1px;
      background: linear-gradient(90deg, transparent 0%, rgba(99, 102, 241, .4) 50%, transparent 100%)
    }

    .sc:hover {
      border-color: rgba(255, 255, 255, .14);
      transform: translateY(-2px);
      box-shadow: 0 12px 28px rgba(0, 0, 0, .4)
    }

    .sv {
      font-size: 30px;
      font-weight: 800;
      letter-spacing: -.8px
    }

    .sv.p {
      color: var(--pl)
    }

    .sv.t {
      color: var(--cg)
    }

    .sv.a {
      color: var(--am)
    }

    .sv.g {
      color: #6ee7b7
    }

    .sl {
      font-size: 11px;
      color: var(--di);
      margin-top: 6px;
      text-transform: uppercase;
      letter-spacing: .06em;
      font-weight: 600
    }

    /* ── Cards ── */
    .card {
      background: rgba(10, 16, 32, .75);
      border: 1px solid var(--bd);
      border-radius: 18px;
      padding: 24px;
      margin-bottom: 22px;
      backdrop-filter: blur(8px)
    }

    .ct {
      font-size: 13px;
      font-weight: 700;
      color: var(--pl);
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
      text-transform: uppercase;
      letter-spacing: .04em
    }

    .ct .ex {
      margin-left: auto;
      font-size: 12px;
      font-weight: 400;
      color: var(--di);
      text-transform: none;
      letter-spacing: 0
    }

    .tc {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px
    }

    /* ── Tables ── */
    .tbl {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px
    }

    .tbl th {
      text-align: left;
      padding: 8px 14px;
      color: var(--di);
      font-weight: 700;
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: .08em;
      border-bottom: 1px solid var(--bd)
    }

    .tbl td {
      padding: 11px 14px;
      border-bottom: 1px solid rgba(255, 255, 255, .04)
    }

    .tbl tr:last-child td {
      border-bottom: none
    }

    .tbl tr:hover td {
      background: rgba(255, 255, 255, .025)
    }

    /* ── Form elements ── */
    label {
      display: block;
      font-size: 11px;
      color: var(--di);
      font-weight: 700;
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: .04em
    }

    input[type=text],
    input[type=email],
    input[type=url],
    input[type=number],
    input[type=password],
    textarea,
    select {
      width: 100%;
      background: rgba(255, 255, 255, .04);
      border: 1px solid rgba(255, 255, 255, .1);
      border-radius: 10px;
      padding: 9px 13px;
      color: var(--tx);
      font-size: 13px;
      outline: none;
      transition: .2s;
      font-family: inherit
    }

    input:focus,
    textarea:focus,
    select:focus {
      border-color: var(--pu);
      background: rgba(99, 102, 241, .07);
      box-shadow: 0 0 0 3px rgba(99, 102, 241, .14)
    }

    textarea {
      resize: vertical
    }

    .fr {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      margin-bottom: 14px
    }

    .fg {
      margin-bottom: 14px
    }

    /* ── Buttons ── */
    .btn {
      border: none;
      border-radius: 9px;
      padding: 9px 18px;
      font-size: 13px;
      font-weight: 600;
      transition: .2s;
      font-family: inherit
    }

    .bp {
      background: linear-gradient(135deg, var(--pu), var(--vi));
      color: #fff;
      box-shadow: 0 4px 14px rgba(99, 102, 241, .3)
    }

    .bp:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(99, 102, 241, .45)
    }

    .bs {
      padding: 5px 12px;
      font-size: 12px
    }

    .bd {
      background: rgba(244, 63, 94, .15);
      color: #fda4af;
      border: 1px solid rgba(244, 63, 94, .25)
    }

    .bd:hover {
      background: rgba(244, 63, 94, .25)
    }

    .bg {
      background: rgba(255, 255, 255, .05);
      border: 1px solid var(--bd2);
      color: var(--mu)
    }

    .bg:hover {
      border-color: rgba(165, 180, 252, .4);
      color: var(--pl);
      background: rgba(99, 102, 241, .08)
    }

    .bt {
      background: rgba(20, 184, 166, .15);
      color: var(--cg);
      border: 1px solid rgba(20, 184, 166, .28)
    }

    .bt:hover {
      background: rgba(20, 184, 166, .25)
    }

    /* ── Tabs ── */
    .tabs {
      display: flex;
      border-bottom: 1px solid var(--bd);
      margin-bottom: 22px;
      gap: 4px
    }

    .tab {
      padding: 9px 18px;
      font-size: 13px;
      color: var(--di);
      cursor: pointer;
      border-bottom: 2px solid transparent;
      transition: .2s;
      border-radius: 8px 8px 0 0;
      font-weight: 500
    }

    .tab:hover {
      color: var(--tx);
      background: rgba(255, 255, 255, .04)
    }

    .tab.active {
      color: var(--pl);
      border-bottom-color: var(--pu);
      background: rgba(99, 102, 241, .07)
    }

    .tp {
      display: none
    }

    .tp.active {
      display: block
    }

    /* ── Pills / badges ── */
    .pill {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 100px;
      font-size: 11px;
      font-weight: 600
    }

    .pg {
      background: rgba(16, 185, 129, .14);
      color: #6ee7b7
    }

    .pa {
      background: rgba(245, 158, 11, .14);
      color: #fcd34d
    }

    .pr {
      background: rgba(244, 63, 94, .14);
      color: #fda4af
    }

    .pp {
      background: rgba(99, 102, 241, .15);
      color: var(--pl)
    }

    .pz {
      background: rgba(255, 255, 255, .07);
      color: var(--mu)
    }

    /* ── Chat box ── */
    .chat-box {
      background: rgba(3, 7, 18, .65);
      border-radius: 12px;
      height: 270px;
      overflow-y: auto;
      padding: 13px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-bottom: 13px;
      border: 1px solid var(--bd)
    }

    .cm {
      max-width: 82%;
      padding: 10px 14px;
      border-radius: 13px;
      font-size: 13px;
      line-height: 1.6
    }

    .cm.user {
      align-self: flex-end;
      background: linear-gradient(135deg, var(--pu), var(--vi));
      color: #fff
    }

    .cm.bot {
      align-self: flex-start;
      background: rgba(255, 255, 255, .07);
      border: 1px solid var(--bd)
    }

    .ci {
      display: flex;
      gap: 8px
    }

    /* ── Plan cards (admin billing view) ── */
    .pc {
      border: 1px solid var(--bd);
      border-radius: 16px;
      padding: 22px;
      position: relative;
      background: rgba(10, 16, 32, .6)
    }

    .pc.ft {
      border-color: rgba(99, 102, 241, .45);
      box-shadow: 0 0 30px rgba(99, 102, 241, .12)
    }

    .pn {
      font-size: 15px;
      font-weight: 700;
      margin-bottom: 6px;
      letter-spacing: -.2px
    }

    .pp2 {
      font-size: 32px;
      font-weight: 900;
      color: var(--pl);
      letter-spacing: -1px
    }

    .pp2 span {
      font-size: 14px;
      color: var(--di);
      font-weight: 400;
      letter-spacing: 0
    }

    .pf {
      list-style: none;
      margin-top: 14px;
      display: flex;
      flex-direction: column;
      gap: 7px
    }

    .pf li {
      font-size: 12px;
      color: var(--mu);
      display: flex;
      align-items: center;
      gap: 8px
    }

    .pf li::before {
      content: '✓';
      color: var(--gr);
      font-size: 11px;
      font-weight: 700;
      flex-shrink: 0
    }

    .pbadge {
      position: absolute;
      top: -11px;
      left: 50%;
      transform: translateX(-50%);
      background: linear-gradient(135deg, var(--pu), var(--vi));
      color: #fff;
      font-size: 10px;
      padding: 3px 14px;
      border-radius: 100px;
      white-space: nowrap;
      font-weight: 700
    }

    /* ── API key display ── */
    .kd {
      font-family: 'SF Mono', 'Cascadia Code', monospace;
      background: rgba(3, 7, 18, .8);
      padding: 11px 15px;
      border-radius: 10px;
      font-size: 12px;
      color: var(--cg);
      border: 1px solid rgba(6, 182, 212, .2);
      word-break: break-all;
      line-height: 1.6
    }

    /* ── Model list ── */
    .ml {
      display: flex;
      flex-direction: column;
      gap: 5px;
      margin-bottom: 14px
    }

    .mi {
      display: flex;
      align-items: center;
      gap: 9px;
      padding: 9px 12px;
      border-radius: 10px;
      font-size: 13px;
      transition: .2s;
      border: 1px solid transparent;
      font-weight: 500
    }

    .mi:hover {
      background: rgba(255, 255, 255, .05);
      border-color: var(--bd)
    }

    .mi.active {
      background: rgba(99, 102, 241, .14);
      border-color: rgba(99, 102, 241, .3);
      color: var(--pl)
    }

    .mdot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--gr);
      flex-shrink: 0
    }

    .mdot.u {
      background: var(--di)
    }

    /* ── Scrollbars ── */
    ::-webkit-scrollbar {
      width: 5px;
      height: 5px
    }

    ::-webkit-scrollbar-track {
      background: transparent
    }

    ::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, .1);
      border-radius: 10px
    }

    ::-webkit-scrollbar-thumb:hover {
      background: rgba(255, 255, 255, .18)
    }

    @media(max-width:900px) {
      .tc {
        grid-template-columns: 1fr
      }

      .sb {
        display: none
      }
    }
  </style>
</head>

<body>
  <div class="sb">
    <div class="sb-logo">
      <div class="lm">Y</div>
      <div class="lt">Yuga</div>
      <div class="lv">v1.0</div>
    </div>
    <nav>
      <div class="ns">Overview</div>
      <a class="nl <?= $page === 'dashboard' ? 'active' : '' ?>" href="?page=dashboard"><span
          class="nd p"></span>Dashboard</a>
      <a class="nl <?= $page === 'analytics' ? 'active' : '' ?>" href="?page=analytics"><span
          class="nd t"></span>Analytics</a>
      <div class="ns">Models</div>
      <a class="nl <?= $page === 'models' ? 'active' : '' ?>" href="?page=models"><span class="nd g"></span>Model
        manager</a>
      <a class="nl <?= $page === 'train' ? 'active' : '' ?>" href="?page=train"><span class="nd a"></span>Train /
        crawl</a>
      <a class="nl <?= $page === 'nepali' ? 'active' : '' ?>" href="?page=nepali"><span class="nd p"></span>Nepali
        training</a>
      <a class="nl <?= $page === 'playground' ? 'active' : '' ?>" href="?page=playground"><span class="nd p"></span>API
        playground</a>
      <div class="ns">Subscriptions</div>
      <a class="nl <?= $page === 'subscribers' ? 'active' : '' ?>" href="?page=subscribers"><span
          class="nd t"></span>Subscribers</a>
      <a class="nl <?= $page === 'plans' ? 'active' : '' ?>" href="?page=plans"><span class="nd g"></span>Plans &amp;
        pricing</a>
      <div class="ns">Content</div>
      <a class="nl <?= $page === 'blog' ? 'active' : '' ?>" href="?page=blog"><span class="nd p"></span>Blog</a>
      <a class="nl <?= $page === 'tickets' ? 'active' : '' ?>" href="?page=tickets"><span class="nd a"></span>Support
        tickets</a>
      <a class="nl <?= $page === 'contacts' ? 'active' : '' ?>" href="?page=contacts"><span class="nd t"></span>Contact
        inbox</a>
      <div class="ns">Notifications</div>
      <a class="nl <?= $page === 'email_templates' ? 'active' : '' ?>" href="?page=email_templates"><span
          class="nd p"></span>Email templates</a>
      <a class="nl <?= $page === 'push' ? 'active' : '' ?>" href="?page=push"><span class="nd a"></span>Push
        notifications</a>
      <div class="ns">Automation</div>
      <a class="nl <?= $page === 'workflows' ? 'active' : '' ?>" href="?page=workflows"><span
          class="nd g"></span>Workflows</a>
      <a class="nl <?= $page === 'webhooks' ? 'active' : '' ?>" href="?page=webhooks"><span
          class="nd t"></span>Webhooks</a>
      <a class="nl <?= $page === 'scheduler' ? 'active' : '' ?>" href="?page=scheduler"><span
          class="nd a"></span>Scheduler</a>
      <a class="nl <?= $page === 'integrations' ? 'active' : '' ?>" href="?page=integrations"><span
          class="nd p"></span>Integrations</a>
      <a class="nl <?= $page === 'whitelabel' ? 'active' : '' ?>" href="?page=whitelabel"><span
          class="nd p"></span>White-label</a>
      <div class="ns">Advanced</div>
      <a class="nl <?= $page === 'versions' ? 'active' : '' ?>" href="?page=versions"><span class="nd g"></span>Version
        history</a>
      <a class="nl <?= $page === 'porter' ? 'active' : '' ?>" href="?page=porter"><span class="nd t"></span>Export /
        Import</a>
      <div class="ns">Developer</div>
      <a class="nl" href="../portal/" target="_blank"><span class="nd a"></span>Dev portal ↗</a>
      <a class="nl" href="guide.php" target="_blank"><span class="nd p"></span>Knowledge base ↗</a>
      <a class="nl" href="cli.php" target="_blank"><span class="nd t"></span>CLI terminal ↗</a>
      <a class="nl <?= $page === 'settings' ? 'active' : '' ?>" href="?page=settings"><span
          class="nd d"></span>Settings</a>
      <a class="nl <?= $page === 'production' ? 'active' : '' ?>" href="production-setup.php"><span
          class="nd y"></span>🚀 Production Setup</a>
      <div class="ns">System</div>
      <a class="nl <?= $page === 'update' ? 'active' : '' ?>" href="?page=update"><span class="nd g"></span>Update</a>
    </nav>
  </div>
  <div class="main">
    <div class="topbar">
      <div class="tb-title">
        <?= match ($page) { 'dashboard' => 'Dashboard', 'analytics' => 'Analytics', 'models' => 'Model manager', 'train' => 'Train / crawl', 'playground' => 'API playground', 'subscribers' => 'Subscribers', 'plans' => 'Plans &amp; pricing', 'blog' => 'Blog', 'tickets' => 'Support tickets', 'contacts' => 'Contact inbox', 'settings' => 'Settings', 'update' => 'Update', 'webhooks' => 'Webhooks', 'scheduler' => 'Scheduler', 'integrations' => 'Integrations', 'whitelabel' => 'White-label branding', 'versions' => 'Version history', 'porter' => 'Export / Import', 'email_templates' => 'Email templates', 'push' => 'Push notifications', 'nepali' => 'Nepali training', 'workflows' => 'Workflows & automation', default => 'Yuga Admin'} ?>
      </div>
      <div class="tb-right">
        <span class="badge bg-pu"><?= count($models) ?> models</span>
        <span class="badge bg-pu"><?= $stats['total_subscribers'] ?> subscribers</span>
        <span class="badge bg-gr">$<?= number_format($stats['mrr']) ?>/mo</span>
      </div>
    </div>
    <div class="content">
      <?php if ($flash): ?>
        <div class="flash"><?= $flash ?></div><?php endif; ?>

      <?php if ($page === 'dashboard'): ?>
        <div class="sg">
          <div class="sc">
            <div class="sv p"><?= $stats['total_subscribers'] ?></div>
            <div class="sl">Subscribers</div>
          </div>
          <div class="sc">
            <div class="sv t"><?= $stats['active_keys'] ?></div>
            <div class="sl">Active API keys</div>
          </div>
          <div class="sc">
            <div class="sv a"><?= number_format($stats['calls_today']) ?></div>
            <div class="sl">API calls today</div>
          </div>
          <div class="sc">
            <div class="sv g"><?= number_format($stats['calls_total']) ?></div>
            <div class="sl">Total API calls</div>
          </div>
          <div class="sc">
            <div class="sv p">$<?= number_format($stats['mrr']) ?></div>
            <div class="sl">MRR</div>
          </div>
          <div class="sc">
            <div class="sv t"><?= count($models) ?></div>
            <div class="sl">Models</div>
          </div>
        </div>
        <div class="tc">
          <div class="card">
            <div class="ct">Plan distribution</div>
            <?php $pc = $stats['plan_counts'];
            foreach (['free', 'starter', 'pro', 'enterprise'] as $p):
              $cnt = $pc[$p] ?? 0;
              $tot = max($stats['total_subscribers'], 1);
              $pct = round($cnt / $tot * 100); ?>
              <div style="margin-bottom:10px">
                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
                  <span><?= ucfirst($p) ?></span><span style="color:var(--di)"><?= $cnt ?> &middot; <?= $pct ?>%</span>
                </div>
                <div style="background:var(--bd);border-radius:4px;height:6px">
                  <div style="height:100%;border-radius:4px;background:var(--pu);width:<?= $pct ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="card">
            <div class="ct">Recent subscribers</div>
            <table class="tbl">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Plan</th>
                  <th>Joined</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($subs, 0, 8) as $s): ?>
                  <tr>
                    <td><?= htmlspecialchars($s['name']) ?><br><small
                        style="color:var(--di)"><?= htmlspecialchars($s['email']) ?></small></td>
                    <td><span
                        class="pill <?= $s['plan'] === 'free' ? 'pz' : ($s['plan'] === 'enterprise' ? 'pa' : 'pp') ?>"><?= $s['plan'] ?></span>
                    </td>
                    <td style="color:var(--di)"><?= date('M j', $s['created_at']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card">
          <div class="ct">Models overview <span class="ex"><a href="?page=models" style="color:var(--pl)">Manage
                →</a></span></div>
          <table class="tbl">
            <thead>
              <tr>
                <th>Model</th>
                <th>Status</th>
                <th>Steps</th>
                <th>Loss</th>
                <th>Vocab</th>
                <th>Sentences</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($models as $mn):
                try {
                  $bss = (new Brain($mn, $store))->status();
                } catch (Exception $e) {
                  $bss = ['ready' => false, 'steps' => 0, 'loss' => 0, 'vocab' => 0, 'sentences' => 0];
                } ?>
                <tr>
                  <td style="font-weight:500"><?= htmlspecialchars($mn) ?></td>
                  <td><span
                      class="pill <?= $bss['ready'] ? 'pg' : 'pz' ?>"><?= $bss['ready'] ? 'Ready' : 'Untrained' ?></span>
                  </td>
                  <td><?= number_format($bss['steps']) ?></td>
                  <td><?= $bss['loss'] ?? '—' ?></td>
                  <td><?= $bss['vocab'] ?? '—' ?></td>
                  <td><?= $bss['sentences'] ?? '—' ?></td>
                  <td><a href="?page=train&m=<?= urlencode($mn) ?>" class="btn bs bg">Train</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php elseif ($page === 'analytics'):
        $daily = [];
        for ($i = 13; $i >= 0; $i--) {
          $d = date('Y-m-d', strtotime("-{$i} days"));
          $daily[$d] = 0;
        }
        foreach ($subs as $sub) {
          foreach ($akm->getUsageStats($sub['id'], 14) as $u) {
            if (isset($daily[$u['day']]))
              $daily[$u['day']] += (int) $u['calls'];
          }
        }
        $labels = array_map(fn($d) => date('M j', strtotime($d)), array_keys($daily));
        $values = array_values($daily);
        ?>
        <div class="sg">
          <div class="sc">
            <div class="sv p"><?= number_format(array_sum($values)) ?></div>
            <div class="sl">Calls last 14 days</div>
          </div>
          <div class="sc">
            <div class="sv t"><?= number_format(max($values)) ?></div>
            <div class="sl">Peak day</div>
          </div>
          <div class="sc">
            <div class="sv a"><?= $stats['calls_today'] ?></div>
            <div class="sl">Today</div>
          </div>
          <div class="sc">
            <div class="sv g">$<?= number_format($stats['mrr'] * 12) ?></div>
            <div class="sl">ARR (est.)</div>
          </div>
        </div>
        <div class="card">
          <div class="ct">API call volume — last 14 days</div><canvas id="uc" style="max-height:200px"></canvas>
        </div>
        <div class="tc">
          <div class="card">
            <div class="ct">Revenue by plan</div>
            <?php $pr2 = ['free' => 0, 'starter' => 9, 'pro' => 29, 'enterprise' => 99];
            $pc = $stats['plan_counts'];
            $tm = max($stats['mrr'], 1);
            foreach (['enterprise', 'pro', 'starter', 'free'] as $p):
              $rv = ($pr2[$p] ?? 0) * ($pc[$p] ?? 0);
              $pt = round($rv / $tm * 100); ?>
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;font-size:13px">
                <div style="flex:1"><?= ucfirst($p) ?></div>
                <div style="color:var(--di)">$<?= number_format($rv) ?>/mo</div>
                <div style="width:100px;background:var(--bd);border-radius:4px;height:6px">
                  <div style="height:100%;border-radius:4px;background:var(--te);width:<?= $pt ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="card">
            <div class="ct">Top by usage today</div>
            <table class="tbl">
              <thead>
                <tr>
                  <th>Subscriber</th>
                  <th>Plan</th>
                  <th>Calls</th>
                </tr>
              </thead>
              <tbody>
                <?php $su2 = [];
                foreach ($subs as $sub)
                  $su2[] = ['s' => $sub, 'c' => $akm->getCallsToday($sub['id'])];
                usort($su2, fn($a, $b) => $b['c'] - $a['c']);
                foreach (array_slice($su2, 0, 8) as $row): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['s']['name']) ?></td>
                    <td><span class="pill pp"><?= $row['s']['plan'] ?></span></td>
                    <td><?= $row['c'] ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <script>new Chart(document.getElementById('uc'), { type: 'bar', data: { labels: <?= json_encode($labels) ?>, datasets: [{ data: <?= json_encode($values) ?>, backgroundColor: 'rgba(99,102,241,.5)', borderColor: '#6366f1', borderWidth: 1, borderRadius: 4 }] }, options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#94a3b8', font: { size: 11 } } }, y: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#94a3b8', font: { size: 11 } } } } } });</script>

      <?php elseif ($page === 'models'): ?>
        <div class="tc">
          <div>
            <?php foreach ($models as $mn):
              try {
                $bss = (new Brain($mn, $store))->status();
              } catch (Exception $e) {
                $bss = ['ready' => false, 'steps' => 0, 'loss' => 0, 'vocab' => 0, 'sentences' => 0, 'params' => 0, 'arch' => ''];
              } ?>
              <div class="card" style="margin-bottom:14px">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                  <div style="font-size:16px;font-weight:600"><?= htmlspecialchars($mn) ?></div>
                  <span class="pill <?= $bss['ready'] ? 'pg' : 'pz' ?>"><?= $bss['ready'] ? 'Ready' : 'Untrained' ?></span>
                  <div style="margin-left:auto;display:flex;gap:8px">
                    <a href="?page=train&m=<?= urlencode($mn) ?>" class="btn bs bp">Train</a>
                    <form method="POST" onsubmit="return confirm('Delete?')" style="display:inline"><input type="hidden"
                        name="act" value="delete_model"><input type="hidden" name="model"
                        value="<?= htmlspecialchars($mn) ?>"><button class="btn bs bd">Delete</button></form>
                  </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
                  <?php foreach (['steps' => ['sv p', number_format($bss['steps']), 'Train steps'], 'vocab' => ['sv t', $bss['vocab'] ?? '—', 'Vocab'], 'loss' => ['sv a', $bss['loss'] ?? '—', 'Loss']] as $k => $inf): ?>
                    <div style="background:var(--bg3);border-radius:8px;padding:12px;text-align:center">
                      <div class="<?= $inf[0] ?>"><?= $inf[1] ?></div>
                      <div style="font-size:11px;color:var(--di)"><?= $inf[2] ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <?php if ($bss['arch']): ?>
                  <div style="margin-top:8px;font-size:11px;color:var(--di);font-family:monospace">Arch:
                    <?= htmlspecialchars($bss['arch']) ?> &middot; Params: <?= number_format($bss['params']) ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="card" style="align-self:start">
            <div class="ct">Create model</div>
            <form method="POST"><input type="hidden" name="act" value="create_model">
              <div class="fg"><label>Model name</label><input type="text" name="model_name" placeholder="my-model"
                  pattern="[a-z0-9_-]+"></div>
              <div class="fg"><label>Size preset</label>
                <select name="model_size">
                  <option value="nano">⚡ Nano — 65K params · shared hosting · fast</option>
                  <option value="small" selected>🔹 Small — 2M params · shared hosting · better quality</option>
                  <option value="medium">🔷 Medium — 20M params · VPS 512MB+</option>
                  <option value="large">💎 Large — 130M params · VPS 2GB+</option>
                </select>
              </div>
              <div style="font-size:11px;color:var(--di);margin-bottom:10px">Larger = better quality answers, more RAM
                &amp; training time needed.</div>
              <button class="btn bp" style="width:100%">Create</button>
            </form>
          </div>
        </div>

      <?php elseif ($page === 'train'): ?>
        <div class="tc">
          <div>
            <div class="card">
              <div class="ct">Select model</div>
              <div class="ml">
                <?php foreach ($models as $mn):
                  try {
                    $bss = (new Brain($mn, $store))->status();
                  } catch (Exception $e) {
                    $bss = ['ready' => false];
                  } ?>
                  <a href="?page=train&m=<?= urlencode($mn) ?>" class="mi <?= $mn === $sel_model ? 'active' : '' ?>">
                    <span class="mdot <?= $bss['ready'] ? '' : 'u' ?>"></span><?= htmlspecialchars($mn) ?></a>
                <?php endforeach; ?>
              </div>
              <div style="background:var(--bg3);border-radius:8px;padding:14px;font-size:12px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                  <div style="color:var(--di)">Status</div>
                  <div>
                    <?= $bs['ready'] ? '<span style="color:var(--gr)">Ready</span>' : '<span style="color:var(--di)">Untrained</span>' ?>
                  </div>
                  <div style="color:var(--di)">Steps</div>
                  <div><?= number_format($bs['steps']) ?></div>
                  <div style="color:var(--di)">Loss</div>
                  <div><?= $bs['loss'] ?? '—' ?></div>
                  <div style="color:var(--di)">Vocab</div>
                  <div><?= $bs['vocab'] ?? '—' ?></div>
                  <div style="color:var(--di)">Sentences</div>
                  <div><?= $bs['sentences'] ?? '—' ?></div>
                  <div style="color:var(--di)">Params</div>
                  <div><?= $bs['params'] ? number_format($bs['params']) : '—' ?></div>
                </div>
              </div>
            </div>
          </div>
          <div>
            <div class="card">
              <div class="ct">Training</div>
              <div class="tabs">
                <div class="tab active" onclick="st('site')">Crawl site</div>
                <div class="tab" onclick="st('url')">Single URL</div>
                <div class="tab" onclick="st('text')">Paste text</div>
                <div class="tab" onclick="st('bulk')" style="color:var(--am)">Bulk upload</div>
                <div class="tab" onclick="st('nanogpt')" style="color:var(--te)">YugaGen LM</div>
              </div>
              <div id="tab-site" class="tp active">
                <div class="fg"><label>Platform URL</label><input type="url" id="sl-url"
                    placeholder="https://yourplatform.com" value=""></div>
                <div class="fr">
                  <div><label>Max pages</label><input type="number" id="sl-pages" value="20"></div>
                  <div><label>Steps</label><input type="number" id="sl-steps" value="20000"></div>
                </div>
                <button class="btn bp" style="width:100%" id="sl-btn" onclick="startSelfLearn()">Start
                  self-learning</button>
                <div id="sl-output"
                  style="display:none;margin-top:14px;background:#000;border-radius:8px;padding:14px;font-family:monospace;font-size:12px;max-height:280px;overflow-y:auto;border:1px solid var(--bd)">
                </div>
              </div>
              <div id="tab-url" class="tp">
                <form method="POST">
                  <?= csrf_field_admin() ?>
                  <input type="hidden" name="act" value="learn_url"><input type="hidden" name="model"
                    value="<?= htmlspecialchars($sel_model) ?>">
                  <div class="fg"><label>URL</label><input type="url" name="url"></div>
                  <button class="btn bp" style="width:100%">Learn from URL</button>
                </form>
              </div>
              <div id="tab-text" class="tp">
                <form method="POST">
                  <?= csrf_field_admin() ?>
                  <input type="hidden" name="act" value="learn_text"><input type="hidden" name="model"
                    value="<?= htmlspecialchars($sel_model) ?>">
                  <div class="fg"><label>Text</label><textarea name="text" rows="5"
                      placeholder="Paste platform content..."></textarea></div>
                  <div class="fg"><label>Steps</label><input type="number" name="steps" value="20000"></div>
                  <button class="btn bp" style="width:100%">Train</button>
                </form>
              </div>
              <div id="tab-bulk" class="tp">
                <form method="POST" enctype="multipart/form-data">
                  <?= csrf_field_admin() ?>
                  <input type="hidden" name="act" value="bulk_upload">
                  <input type="hidden" name="model" value="<?= htmlspecialchars($sel_model) ?>">

                  <!-- Drop zone -->
                  <div id="drop-zone" onclick="document.getElementById('bulk-input').click()"
                    style="border:2px dashed var(--bd2);border-radius:10px;padding:32px;text-align:center;cursor:pointer;transition:.2s;margin-bottom:14px"
                    ondragover="event.preventDefault();this.style.borderColor='var(--am)'"
                    ondragleave="this.style.borderColor='var(--bd2)'" ondrop="handleDrop(event)">
                    <div style="font-size:28px;margin-bottom:8px">📂</div>
                    <div style="font-size:14px;font-weight:600;color:var(--tx);margin-bottom:4px">Drop files here or click
                      to browse</div>
                    <div style="font-size:12px;color:var(--di)">PDF, DOCX, TXT, MD, CSV, JSON, HTML, ODT — multiple files
                      supported</div>
                  </div>
                  <input type="file" id="bulk-input" name="bulk_files[]" multiple
                    accept=".pdf,.docx,.odt,.txt,.md,.csv,.json,.html,.htm,.xml,.rtf" style="display:none"
                    onchange="showFiles(this.files)">

                  <!-- File list preview -->
                  <div id="file-list"
                    style="display:none;background:var(--bg3);border-radius:8px;padding:10px;margin-bottom:14px;max-height:160px;overflow-y:auto;font-size:12px;color:var(--mu)">
                  </div>

                  <!-- URL list -->
                  <div class="fg" style="margin-top:4px">
                    <label>Or paste URLs (one per line)</label>
                    <textarea name="bulk_urls" rows="4"
                      placeholder="https://docs.example.com/page1&#10;https://docs.example.com/page2&#10;https://yoursite.com/faq"
                      style="font-family:monospace;font-size:12px"></textarea>
                  </div>

                  <div class="fg">
                    <label>Train steps per document</label>
                    <input type="number" name="steps" value="10000">
                  </div>
                  <button class="btn bp" style="width:100%" id="bulk-btn">Upload &amp; Train</button>
                </form>

                <div
                  style="margin-top:14px;background:var(--bg3);border-radius:8px;padding:12px;font-size:12px;color:var(--di)">
                  <strong style="color:var(--tx)">Supported formats</strong><br>
                  <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;margin-top:8px">
                    <span>📄 PDF — text extraction</span>
                    <span>📝 DOCX / ODT — Word docs</span>
                    <span>📊 CSV — table → sentences</span>
                    <span>🔧 JSON — flat to text</span>
                    <span>📋 TXT / MD — plain text</span>
                    <span>🌐 HTML — web pages</span>
                  </div>
                </div>

                <script>
                    function showFiles(fil                        es)                     {
                      var list = document.getElementById('file-list');
                      if (!files.length) { list.style.display = 'none'; return; }
                      list.style.display = 'block';
                      var html = '';
                      var total = 0;
                      for (var i = 0; i < files.length; i++) {
                        var kb = Math.round(files[i].size / 1024);
                        total += files[i].size;
                        html += '<div style="display:flex;justify-content:space-between;padding:3px 0;border-bottom:1px solid var(--bd)"><span>' + files[i].name + '</span><span style="color:var(--di)">' + kb + 'KB</span></div>';
                      }
                      html += '<div style="padding-top:6px;color:var(--am);font-weight:600">' + files.length + ' file(s) — ' + Math.round(total / 1024) + 'KB total</div>';
                      list.innerHTML = html;
                    }
                    function handleDrop(e) {
                      e.preventDefault();
                      document.getElementById('drop-zone').style.borderColor = 'var(--bd2)';
                      var input = document.getElementById('bulk-input');
                      var dt = e.dataTransfer;
                      // Assign dropped files to the input via DataTransfer
                      try { input.files = dt.files; } catch (ex) { }
                      showFiles(dt.files);
                    }
                  </script>
                </div>

                <div id="tab-nanogpt" class="tp">
                  <div
                    style="background:rgba(20,184,166,.08);border:1px solid rgba(20,184,166,.25);border-radius:8px;padding:14px;margin-bottom:14px;font-size:13px;color:var(--te)">
                    <strong>Real Language Model</strong> &mdash; YugaGen generates new text from scratch using a trained GPT
                    transformer.
                    Training runs via SSH or cron job. The model saves checkpoints and resumes automatically.
                  </div>
                  <div class="fg"><label>Model name</label><input type="text" id="ng-name"
                      value="<?= htmlspecialchars($sel_model) ?>"></div>
                  <div class="fr">
                    <div><label>Generate from seed</label><input type="text" id="ng-seed" placeholder="Our pricing"></div>
                    <div><label>Temperature</label><input type="number" id="ng-temp" value="0.8" step="0.1" min="0.1"
                        max="1.5"></div>
                  </div>
                  <button class="btn bt" onclick="ngGenerate()" style="width:100%;margin-bottom:12px">Generate text</button>
                  <div id="ng-out"
                    style="background:var(--bg3);border-radius:8px;padding:14px;font-size:13px;color:var(--tx);min-height:80px;white-space:pre-wrap;font-family:var(--font-mono,monospace)">
                    Output appears here...</div>
                  <div
                    style="margin-top:14px;padding-top:14px;border-top:1px solid var(--bd);font-size:12px;color:var(--di)">
                    <strong style="color:var(--tx)">How to train (SSH or cPanel terminal):</strong><br>
                    <code style="color:var(--te);display:block;margin-top:6px;line-height:2">
                    php training/build_corpus.php --url=https://yoursite.com --pages=50<br>
                    php training/train.php --model=<?= htmlspecialchars($sel_model) ?> --size=nano --steps=5000
                    </code>
                    <strong style="color:var(--tx);display:block;margin-top:10px">cPanel cron (runs every 30 min, trains
                      overnight):</strong><br>
                    <code
                      style="color:var(--te);display:block;margin-top:6px">*/30 * * * * php <?= htmlspecialchars(YUGA_ROOT) ?>/training/train.php --model=<?= htmlspecialchars($sel_model) ?> --steps=3000</code>
                  </div>
                </div>
              </div>
            </div>
          </div>

      <?php elseif ($page === 'playground'): ?>
          <div class="tc">
            <div class="card">
              <div class="ct">Chat test</div>
              <div class="chat-box" id="chat-box">
                <div class="cm bot">Hi! I am Yuga. Ask me something.</div>
              </div>
              <div class="ci"><select id="cm" style="width:130px"><?php foreach ($models as $mn): ?>
                      <option value="<?= htmlspecialchars($mn) ?>"><?= htmlspecialchars($mn) ?></option><?php endforeach; ?>
                </select>
                <input type="text" id="ci2" placeholder="Ask something..." style="flex:1">
                <button class="btn bp" onclick="sc()">Send</button>
              </div>
            </div>

            <div class="card">
              <div class="ct">Endpoints</div>
              <div style="font-size:12px;color:var(--di);margin-bottom:10px">Base: <code
                  style="color:var(--te)"><?= htmlspecialchars($api_base) ?></code></div>
              <?php $eps = [['POST', 'brain_chat', 'ChatGPT-style answer', '{"model":"default","message":"pricing?"}'], ['POST', 'chat', 'LM completion', '{"model":"default","prompt":"pricing"}'], ['POST', 'learn_text', 'Train on text', '{"model":"default","text":"..."}'], ['POST', 'learn_site', 'Crawl site', '{"model":"default","url":"https://..."}'], ['GET', 'status', 'Model status', 'model=default'], ['GET', 'models', 'List models', '']];
              foreach ($eps as [$m, $a, $d, $pl]): ?>
                  <div style="background:var(--bg3);border-radius:8px;padding:10px;margin-bottom:6px;cursor:pointer"
                    onclick="fr('<?= $a ?>','<?= htmlspecialchars($pl, ENT_QUOTES) ?>')">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px">
                      <span
                        style="font-size:10px;font-weight:600;padding:2px 7px;border-radius:4px;background:<?= $m === 'POST' ? 'rgba(99,102,241,.3)' : 'rgba(20,184,166,.3)' ?>;color:<?= $m === 'POST' ? 'var(--pl)' : 'var(--te)' ?>"><?= $m ?></span>
                      <code style="font-size:12px">?action=<?= $a ?></code>
                    </div>
                    <div style="font-size:11px;color:var(--di)"><?= $d ?></div>
                  </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="card">
            <div class="ct">Request builder</div>
            <div class="tc">
              <div>
                <div class="fg"><label>Action</label><input type="text" id="ra" value="brain_chat"></div>
                <div class="fg"><label>Payload (JSON)</label><textarea id="rb"
                    rows="4">{"model":"default","message":"what is the pricing?"}</textarea></div>
                <div class="fg"><label>API key (optional)</label><input type="text" id="rk" placeholder="yuga_live_...">
                </div>
                <button class="btn bt" onclick="rr()" style="margin-right:8px">Run</button>
                <button class="btn bg" onclick="cc()">Copy cURL</button>
              </div>
              <div><label>Response</label>
                <div id="rr2"
                  style="background:var(--bg3);border-radius:8px;padding:14px;font-family:monospace;font-size:12px;color:var(--te);min-height:140px;white-space:pre-wrap;word-break:break-all">
                  —</div>
              </div>
            </div>
          </div>

      <?php elseif ($page === 'subscribers'): ?>
          <div class="tc" style="margin-bottom:20px;align-items:start">
            <div class="card">
              <div class="ct">Add subscriber</div>
              <form method="POST"><input type="hidden" name="act" value="create_subscriber">
                <div class="fr">
                  <div><label>Name</label><input type="text" name="sub_name" required></div>
                  <div><label>Email</label><input type="email" name="sub_email" required></div>
                </div>
                <div class="fg"><label>Plan</label><select name="sub_plan"><?php foreach ($plans as $pid => $p): ?>
                        <option value="<?= $pid ?>"><?= $p['name'] ?> — $<?= $p['price_month'] ?>/mo</option>
                    <?php endforeach; ?>
                  </select></div>
                <button class="btn bp" style="width:100%">Create + generate API key</button>
              </form>
            </div>
            <div class="card">
              <div class="ct">Subscriber stats</div>
              <?php foreach ($plans as $pid => $plan):
                $cnt = $stats['plan_counts'][$pid] ?? 0; ?>
                  <div
                    style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--bd);font-size:13px">
                    <span><?= $plan['name'] ?></span><span style="color:var(--mu)"><?= $cnt ?> subs &middot;
                      $<?= number_format(($plan['price_month'] ?? 0) * $cnt) ?>/mo</span>
                  </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="card">
            <div class="ct">All subscribers <span class="ex"><?= count($subs) ?> total</span></div>
            <table class="tbl">
              <thead>
                <tr>
                  <th>Name / Email</th>
                  <th>Plan</th>
                  <th>Status</th>
                  <th>Calls today</th>
                  <th>Keys</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($subs as $sub):
                  $keys = $akm->listKeys($sub['id']);
                  $calls = $akm->getCallsToday($sub['id']);
                  $plan = Plans::get($sub['plan']);
                  $over = $calls >= $plan['api_calls']; ?>
                    <tr>
                      <td>
                        <div style="font-weight:500"><?= htmlspecialchars($sub['name']) ?></div>
                        <div style="font-size:11px;color:var(--di)"><?= htmlspecialchars($sub['email']) ?></div>
                      </td>
                      <td>
                        <form method="POST" style="display:inline-flex;gap:4px;align-items:center"><input type="hidden"
                            name="act" value="update_plan"><input type="hidden" name="sub_id" value="<?= $sub['id'] ?>">
                          <select name="plan" style="font-size:11px;padding:3px 6px"
                            onchange="this.form.submit()"><?php foreach ($plans as $pid => $p): ?>
                                <option value="<?= $pid ?>" <?= $sub['plan'] === $pid ? 'selected' : '' ?>><?= $p['name'] ?></option>
                            <?php endforeach; ?>
                          </select>
                        </form>
                      </td>
                      <td><span class="pill <?= $sub['status'] === 'active' ? 'pg' : 'pr' ?>"><?= $sub['status'] ?></span></td>
                      <td style="color:<?= $over ? 'var(--re)' : 'inherit' ?>"><?= number_format($calls) ?> /
                        <?= $plan['api_calls'] === PHP_INT_MAX ? '&#8734;' : number_format($plan['api_calls']) ?>
                      </td>
                      <td><?= count($keys) ?></td>
                      <td style="color:var(--di);font-size:12px"><?= date('M j Y', $sub['created_at']) ?></td>
                      <td style="display:flex;gap:4px">
                        <button class="btn bs bg" onclick="sk('<?= $sub['id'] ?>')">Keys</button>
                        <?php if ($sub['status'] === 'active'): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Suspend?')"><input type="hidden"
                                name="act" value="suspend_sub"><input type="hidden" name="sub_id" value="<?= $sub['id'] ?>"><button
                                class="btn bs bd">Suspend</button></form><?php endif; ?>
                      </td>
                    </tr>
                    <tr id="keys-<?= $sub['id'] ?>" style="display:none">
                      <td colspan="7" style="background:rgba(99,102,241,.05);padding:12px 16px">
                        <div style="font-size:12px;font-weight:600;color:var(--di);margin-bottom:8px">Keys for
                          <?= htmlspecialchars($sub['name']) ?>
                        </div>
                        <?php foreach ($keys as $k): ?>
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;font-size:12px">
                              <code style="color:var(--te)"><?= htmlspecialchars($k['key_prefix']) ?></code>
                              <span style="color:var(--di)"><?= $k['label'] ? htmlspecialchars($k['label']) : '(no label)' ?></span>
                              <span class="pill <?= $k['status'] === 'active' ? 'pg' : 'pr' ?>"><?= $k['status'] ?></span>
                              <span style="color:var(--di);margin-left:auto">Last used:
                                <?= $k['last_used'] ? date('M j', $k['last_used']) : 'Never' ?></span>
                              <?php if ($k['status'] === 'active'): ?>
                                  <form method="POST" style="display:inline"><input type="hidden" name="act" value="revoke_key"><input
                                      type="hidden" name="key_id" value="<?= $k['key_id'] ?>"><input type="hidden" name="sub_id"
                                      value="<?= $sub['id'] ?>"><button class="btn bs bd">Revoke</button></form><?php endif; ?>
                            </div><?php endforeach; ?>
                      </td>
                    </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

      <?php elseif ($page === 'plans'): ?>
          <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px">
            <?php foreach ($plans as $pid => $plan): ?>
                <div class="pc <?= $plan['badge'] === 'popular' ? 'ft' : '' ?>">
                  <?php if ($plan['badge'] === 'popular'): ?>
                      <div class="pbadge">Most popular</div><?php endif; ?>
                  <div class="pn"><?= $plan['name'] ?></div>
                  <div class="pp2">$<?= $plan['price_month'] ?><span>/mo</span></div>
                  <div style="font-size:11px;color:var(--di)">$<?= $plan['price_year'] ?>/yr</div>
                  <ul class="pf"><?php foreach ($plan['features'] as $f): ?>
                        <li><?= htmlspecialchars($f) ?></li><?php endforeach; ?>
                  </ul>
                  <div style="margin-top:14px;padding-top:10px;border-top:1px solid var(--bd);font-size:11px;color:var(--di)">
                    <?= $stats['plan_counts'][$pid] ?? 0 ?> subscribers &middot;
                    $<?= number_format(($plan['price_month']) * ($stats['plan_counts'][$pid] ?? 0)) ?>/mo
                  </div>
                </div><?php endforeach; ?>
          </div>
          <div class="card">
            <div class="ct">Embed pricing on your site</div>
            <p style="font-size:13px;color:var(--di);margin-bottom:10px">Let developers discover and sign up for API access
              directly from your website.</p>
            <div class="kd">&lt;script src="<?= htmlspecialchars($portal_base) ?>embed.js"&gt;&lt;/script&gt;</div>
          </div>

      <?php elseif ($page === 'settings'):
        $smtp_cfg = $config['smtp'] ?? [];
        $pay_cfg = $config['payments'] ?? [];
        $ai_cfg = $config['ai_backends'] ?? [];
        $ws_cfg = $config['web_search'] ?? [];
        $stab = $_GET['stab'] ?? 'platform';
        ?>
          <!-- Settings tabs -->
          <div class="tabs" style="margin-bottom:0">
            <?php foreach (['platform' => 'Platform', 'security' => 'Security', 'smtp' => 'Email / SMTP', 'payments' => 'Payments', 'ai' => 'AI Backends', 'search' => 'Web Search', 'production' => '🚀 Production Setup'] as $tid => $tname): ?>
                <a class="tab <?= $stab === $tid ? 'active' : '' ?>" href="?page=settings&stab=<?= $tid ?>"><?= $tname ?></a>
            <?php endforeach; ?>
          </div>

          <?php if ($stab === 'platform'): ?>
              <div class="card" style="margin-top:20px">
                <div class="ct">Platform branding</div>
                <form method="POST">
                  <?= csrf_field_admin() ?>
                  <input type="hidden" name="act" value="save_platform">
                  <div class="fr">
                    <div class="fg"><label>Platform name</label><input type="text" name="platform_name"
                        value="<?= htmlspecialchars($config['platform_name'] ?? 'Yuga') ?>" placeholder="Yuga"></div>
                    <div class="fg"><label>Assistant name</label><input type="text" name="assistant_name"
                        value="<?= htmlspecialchars($config['assistant_name'] ?? 'Yuga') ?>" placeholder="Yuga"></div>
                  </div>
                  <div class="fg"><label>Site description</label><input type="text" name="site_description"
                      value="<?= htmlspecialchars($config['site_description'] ?? '') ?>"
                      placeholder="Your AI platform description"></div>
                  <div class="fr">
                    <div class="fg"><label>Site URL</label><input type="url" name="site_url"
                        value="<?= htmlspecialchars($config['site_url'] ?? '') ?>" placeholder="https://yoursite.com"></div>
                    <div class="fg"><label>Default model</label>
                      <select name="default_model"><?php foreach ($models as $mn): ?>
                            <option value="<?= $mn ?>" <?= ($config['default_model'] ?? 'default') === $mn ? 'selected' : '' ?>>
                              <?= $mn ?>
                            </option><?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <button class="btn bp">Save platform settings</button>
                </form>
                <hr style="border:none;border-top:1px solid var(--bd);margin:20px 0">
                <div class="ct">Your URLs</div>
                <div class="fg"><label>API endpoint</label>
                  <div class="kd" style="font-family:monospace;font-size:12px"><?= htmlspecialchars($api_base) ?></div>
                </div>
                <div class="fg"><label>Developer portal</label>
                  <div class="kd" style="font-family:monospace;font-size:12px"><?= htmlspecialchars($portal_base) ?></div>
                </div>
                <div class="fg"><label>Widget embed snippet</label>
                  <div class="kd" style="font-family:monospace;font-size:11px;word-break:break-all">&lt;script
                    src="<?= htmlspecialchars(str_replace('/api/', '/widget/yuga-widget.js', $api_base)) ?>"
                    data-api="<?= htmlspecialchars($api_base) ?>" data-model="default" data-auto-learn="true"&gt;&lt;/script&gt;
                  </div>
                </div>
              </div>

          <?php elseif ($stab === 'security'): ?>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px">
                <div class="card">
                  <div class="ct">Change admin password</div>
                  <form method="POST">
                    <?= csrf_field_admin() ?>
                    <input type="hidden" name="act" value="save_security">
                    <div class="fg"><label>New admin password (8+ chars)</label><input type="password" name="new_password"
                        placeholder="Leave blank to keep current" autocomplete="new-password"></div>
                    <div class="fg"><label>New global API key (16+ chars)</label><input type="text" name="new_api_key"
                        placeholder="Leave blank to keep current"></div>
                    <div class="fg"><label>Admin panel slug (security through obscurity)</label>
                      <input type="text" name="admin_slug" value="<?= htmlspecialchars($config['admin_slug'] ?? 'admin') ?>"
                        placeholder="admin" pattern="[a-z0-9_-]+">
                      <div style="font-size:11px;color:var(--di);margin-top:4px">Access admin at <code>/yuga/{slug}/</code>
                        instead of <code>/yuga/admin/</code></div>
                    </div>
                    <button class="btn bp">Save security settings</button>
                  </form>
                </div>
                <div class="card">
                  <div class="ct">Current status</div>
                  <?php foreach (['admin_password' => 'Admin password', 'api_key' => 'Global API key', 'admin_slug' => 'Admin slug'] as $k => $lbl): ?>
                      <div
                        style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--bd);font-size:13px">
                        <span style="color:var(--di)"><?= $lbl ?></span>
                        <code
                          style="color:var(--te)"><?= isset($config[$k]) && $config[$k] !== '' ? ($k === 'admin_slug' ? htmlspecialchars($config[$k]) : str_repeat('•', min(strlen((string) $config[$k]), 10))) : '<span style="color:var(--re)">not set</span>' ?></code>
                      </div>
                  <?php endforeach; ?>
                  <div style="margin-top:16px;font-size:12px;color:var(--di);line-height:1.7">
                    <strong style="color:var(--am)">After changing admin slug:</strong> bookmark the new URL immediately. Old
                    /admin/ route gets blocked.
                  </div>
                </div>
              </div>

          <?php elseif ($stab === 'smtp'): ?>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px">
                <div class="card">
                  <div class="ct">SMTP configuration</div>
                  <form method="POST">
                    <?= csrf_field_admin() ?>
                    <input type="hidden" name="act" value="save_smtp">
                    <div class="fr">
                      <div class="fg"><label>SMTP host</label><input type="text" name="smtp_host"
                          value="<?= htmlspecialchars($smtp_cfg['host'] ?? '') ?>" placeholder="mail.yourhost.com"></div>
                      <div class="fg"><label>Port</label><input type="number" name="smtp_port"
                          value="<?= $smtp_cfg['port'] ?? 587 ?>" placeholder="587"></div>
                    </div>
                    <div class="fr">
                      <div class="fg"><label>Username / email</label><input type="text" name="smtp_user"
                          value="<?= htmlspecialchars($smtp_cfg['user'] ?? '') ?>" placeholder="noreply@yoursite.com"></div>
                      <div class="fg"><label>Password</label><input type="password" name="smtp_pass"
                          value="<?= htmlspecialchars($smtp_cfg['pass'] ?? '') ?>" autocomplete="new-password"></div>
                    </div>
                    <div class="fr">
                      <div class="fg"><label>From email</label><input type="email" name="from_email"
                          value="<?= htmlspecialchars($smtp_cfg['from_email'] ?? '') ?>" placeholder="noreply@yoursite.com">
                      </div>
                      <div class="fg"><label>From name</label><input type="text" name="from_name"
                          value="<?= htmlspecialchars($smtp_cfg['from_name'] ?? 'Yuga') ?>" placeholder="Yuga"></div>
                    </div>
                    <div class="fg"><label>Encryption</label>
                      <select name="smtp_enc">
                        <option value="tls" <?= ($smtp_cfg['encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (recommended,
                          port
                          587)</option>
                        <option value="ssl" <?= ($smtp_cfg['encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (port 465)
                        </option>
                        <option value="none" <?= ($smtp_cfg['encryption'] ?? '') === 'none' ? 'selected' : '' ?>>None (not
                          recommended)
                        </option>
                      </select>
                    </div>
                    <button class="btn bp">Save SMTP settings</button>
                  </form>
                </div>
                <div class="card">
                  <div class="ct">Send test email</div>
                  <form method="POST">
                    <?= csrf_field_admin() ?>
                    <input type="hidden" name="act" value="test_smtp">
                    <div class="fg"><label>Send test to</label><input type="email" name="test_email"
                        placeholder="your@email.com"></div>
                    <button class="btn bt">Send test email</button>
                  </form>
                  <hr style="border:none;border-top:1px solid var(--bd);margin:16px 0">
                  <div class="ct" style="margin-bottom:8px">Common providers</div>
                  <?php foreach ([
                    ['Gmail', 'smtp.gmail.com', '587', 'TLS', 'Use App Password (not your Gmail password)'],
                    ['Outlook/Office365', 'smtp.office365.com', '587', 'TLS', 'Use your Microsoft account password'],
                    ['Mailgun', 'smtp.mailgun.org', '587', 'TLS', 'Use SMTP credentials from Mailgun dashboard'],
                    ['cPanel', 'mail.yourdomain.com', '587', 'TLS', 'Same as your cPanel email password'],
                  ] as [$name, $host, $port, $enc, $note]): ?>
                      <div style="padding:8px 0;border-bottom:1px solid var(--bd);font-size:12px">
                        <div style="font-weight:600;color:var(--tx)"><?= $name ?></div>
                        <code style="color:var(--te)"><?= $host ?> : <?= $port ?> (<?= $enc ?>)</code>
                        <div style="color:var(--di);margin-top:2px"><?= $note ?></div>
                      </div>
                  <?php endforeach; ?>
                </div>
              </div>

          <?php elseif ($stab === 'payments'): ?>
              <div style="margin-top:20px">
                <form method="POST">
                  <?= csrf_field_admin() ?>
                  <input type="hidden" name="act" value="save_payments">
                  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

                    <!-- Stripe -->
                    <div class="card">
                      <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                        <div
                          style="width:36px;height:36px;background:#635bff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:14px">
                          S</div>
                        <div style="font-weight:600">Stripe</div>
                        <label style="margin-left:auto;display:flex;align-items:center;gap:6px;cursor:pointer">
                          <input type="checkbox" name="stripe_enabled" value="1" <?= !empty($pay_cfg['stripe']['enabled']) ? 'checked' : '' ?> style="accent-color:var(--pu)"> Enable
                        </label>
                      </div>
                      <div class="fg"><label>Publishable key (pk_live_...)</label><input type="text" name="stripe_public"
                          value="<?= htmlspecialchars($pay_cfg['stripe']['public_key'] ?? '') ?>" placeholder="pk_live_...">
                      </div>
                      <div class="fg"><label>Secret key (sk_live_...)</label><input type="password" name="stripe_secret"
                          value="<?= htmlspecialchars($pay_cfg['stripe']['secret_key'] ?? '') ?>" placeholder="sk_live_...">
                      </div>
                      <div class="fg"><label>Webhook secret (whsec_...)</label><input type="text" name="stripe_webhook"
                          value="<?= htmlspecialchars($pay_cfg['stripe']['webhook_secret'] ?? '') ?>" placeholder="whsec_...">
                      </div>
                      <div style="font-size:11px;color:var(--di)">Stripe webhook URL:
                        <code><?= htmlspecialchars($api_base) ?>../payments/callback.php?gw=stripe</code>
                      </div>
                    </div>

                    <!-- PayPal -->
                    <div class="card">
                      <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                        <div
                          style="width:36px;height:36px;background:#003087;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:11px">
                          PP</div>
                        <div style="font-weight:600">PayPal</div>
                        <label style="margin-left:auto;display:flex;align-items:center;gap:6px;cursor:pointer">
                          <input type="checkbox" name="paypal_enabled" value="1" <?= !empty($pay_cfg['paypal']['enabled']) ? 'checked' : '' ?> style="accent-color:var(--pu)"> Enable
                        </label>
                      </div>
                      <div class="fg"><label>Client ID</label><input type="text" name="paypal_client"
                          value="<?= htmlspecialchars($pay_cfg['paypal']['client_id'] ?? '') ?>"></div>
                      <div class="fg"><label>Client Secret</label><input type="password" name="paypal_secret"
                          value="<?= htmlspecialchars($pay_cfg['paypal']['secret'] ?? '') ?>"></div>
                      <div class="fg"><label>Mode</label>
                        <select name="paypal_mode">
                          <option value="sandbox" <?= ($pay_cfg['paypal']['mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>
                            Sandbox (testing)</option>
                          <option value="live" <?= ($pay_cfg['paypal']['mode'] ?? '') === 'live' ? 'selected' : '' ?>>Live
                            (production)
                          </option>
                        </select>
                      </div>
                    </div>

                    <!-- eSewa -->
                    <div class="card">
                      <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                        <div
                          style="width:36px;height:36px;background:#60BB46;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:11px">
                          eS</div>
                        <div style="font-weight:600">eSewa <span style="font-size:11px;color:var(--di)">(Nepal)</span></div>
                        <label style="margin-left:auto;display:flex;align-items:center;gap:6px;cursor:pointer">
                          <input type="checkbox" name="esewa_enabled" value="1" <?= !empty($pay_cfg['esewa']['enabled']) ? 'checked' : '' ?> style="accent-color:var(--pu)"> Enable
                        </label>
                      </div>
                      <div class="fg"><label>Merchant ID</label><input type="text" name="esewa_merchant"
                          value="<?= htmlspecialchars($pay_cfg['esewa']['merchant_id'] ?? '') ?>"></div>
                      <div class="fg"><label>Secret key</label><input type="password" name="esewa_secret"
                          value="<?= htmlspecialchars($pay_cfg['esewa']['secret'] ?? '') ?>"></div>
                    </div>

                    <!-- FonePay -->
                    <div class="card">
                      <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                        <div
                          style="width:36px;height:36px;background:#E31837;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:11px">
                          FP</div>
                        <div style="font-weight:600">FonePay <span style="font-size:11px;color:var(--di)">(Nepal)</span></div>
                        <label style="margin-left:auto;display:flex;align-items:center;gap:6px;cursor:pointer">
                          <input type="checkbox" name="fonepay_enabled" value="1" <?= !empty($pay_cfg['fonepay']['enabled']) ? 'checked' : '' ?> style="accent-color:var(--pu)"> Enable
                        </label>
                      </div>
                      <div class="fg"><label>Merchant code</label><input type="text" name="fonepay_merchant"
                          value="<?= htmlspecialchars($pay_cfg['fonepay']['merchant_code'] ?? '') ?>"></div>
                      <div class="fg"><label>Secret key</label><input type="password" name="fonepay_secret"
                          value="<?= htmlspecialchars($pay_cfg['fonepay']['secret'] ?? '') ?>"></div>
                    </div>

                    <!-- IMEPay -->
                    <div class="card">
                      <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                        <div
                          style="width:36px;height:36px;background:#FF6B00;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:11px">
                          IM</div>
                        <div style="font-weight:600">IMEPay <span style="font-size:11px;color:var(--di)">(Nepal)</span></div>
                        <label style="margin-left:auto;display:flex;align-items:center;gap:6px;cursor:pointer">
                          <input type="checkbox" name="imepay_enabled" value="1" <?= !empty($pay_cfg['imepay']['enabled']) ? 'checked' : '' ?> style="accent-color:var(--pu)"> Enable
                        </label>
                      </div>
                      <div class="fg"><label>Merchant code</label><input type="text" name="imepay_merchant"
                          value="<?= htmlspecialchars($pay_cfg['imepay']['merchant_code'] ?? '') ?>"></div>
                      <div class="fg"><label>Token</label><input type="password" name="imepay_token"
                          value="<?= htmlspecialchars($pay_cfg['imepay']['token'] ?? '') ?>"></div>
                    </div>

                  </div>
                  <div style="margin-top:20px"><button class="btn bp" style="padding:11px 28px">Save all payment
                      settings</button></div>
                </form>
              </div>

          <?php elseif ($stab === 'ai'): ?>
              <div style="margin-top:20px">
                <form method="POST">
                  <?= csrf_field_admin() ?>
                  <input type="hidden" name="act" value="save_ai_backends">

                  <div class="card" style="margin-bottom:16px">
                    <div class="ct">Active backend (used by default for all subscribers)</div>
                    <div class="fr">
                      <div class="fg"><label>Primary LLM backend</label>
                        <select name="llm_backend">
                          <option value="none" <?= ($config['llm_backend'] ?? 'none') === 'none' ? 'selected' : '' ?>>Yug1.0 only
                            (no
                            external LLM)</option>
                          <option value="openai" <?= ($config['llm_backend'] ?? '') === 'openai' ? 'selected' : '' ?>>OpenAI
                            (GPT-4o,
                            GPT-4o-mini)</option>
                          <option value="anthropic" <?= ($config['llm_backend'] ?? '') === 'anthropic' ? 'selected' : '' ?>>
                            Anthropic
                            (Claude)</option>
                          <option value="groq" <?= ($config['llm_backend'] ?? '') === 'groq' ? 'selected' : '' ?>>Groq (Llama 3 —
                            fastest)</option>
                          <option value="ollama" <?= ($config['llm_backend'] ?? '') === 'ollama' ? 'selected' : '' ?>>Ollama
                            (local,
                            private)</option>
                          <option value="together" <?= ($config['llm_backend'] ?? '') === 'together' ? 'selected' : '' ?>>Together
                            AI
                          </option>
                        </select>
                      </div>
                      <div class="fg"><label>Model name</label><input type="text" name="llm_model"
                          value="<?= htmlspecialchars($config['llm_model'] ?? '') ?>" placeholder="gpt-4o-mini"></div>
                    </div>
                    <div class="fr">
                      <div class="fg"><label>API key (for active backend)</label><input type="password" name="llm_api_key"
                          value="<?= htmlspecialchars($config['llm_api_key'] ?? '') ?>" placeholder="sk-..."></div>
                      <div class="fg"><label>Custom endpoint (Ollama / self-hosted)</label><input type="url" name="llm_endpoint"
                          value="<?= htmlspecialchars($config['llm_endpoint'] ?? '') ?>" placeholder="http://localhost:11434">
                      </div>
                    </div>
                  </div>

                  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <!-- OpenAI -->
                    <div class="card">
                      <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                        <div
                          style="width:28px;height:28px;background:#10a37f;border-radius:6px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:11px">
                          AI</div>
                        <span style="font-weight:600">OpenAI</span>
                      </div>
                      <div class="fg"><label>API key</label><input type="password" name="openai_key"
                          value="<?= htmlspecialchars($ai_cfg['openai']['api_key'] ?? '') ?>" placeholder="sk-..."></div>
                      <div class="fg"><label>Model</label><input type="text" name="openai_model"
                          value="<?= htmlspecialchars($ai_cfg['openai']['model'] ?? 'gpt-4o-mini') ?>"></div>
                    </div>
                    <!-- Anthropic -->
                    <div class="card">
                      <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                        <div
                          style="width:28px;height:28px;background:#d97706;border-radius:6px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:10px">
                          AN</div>
                        <span style="font-weight:600">Anthropic / Claude</span>
                      </div>
                      <div class="fg"><label>API key</label><input type="password" name="anthropic_key"
                          value="<?= htmlspecialchars($ai_cfg['anthropic']['api_key'] ?? '') ?>" placeholder="sk-ant-..."></div>
                      <div class="fg"><label>Model</label><input type="text" name="anthropic_model"
                          value="<?= htmlspecialchars($ai_cfg['anthropic']['model'] ?? 'claude-haiku-4-5-20251001') ?>"></div>
                    </div>
                    <!-- Groq -->
                    <div class="card">
                      <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                        <div
                          style="width:28px;height:28px;background:#f59e0b;border-radius:6px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:11px">
                          G</div>
                        <span style="font-weight:600">Groq <span style="font-size:11px;color:var(--di)">(fastest)</span></span>
                      </div>
                      <div class="fg"><label>API key</label><input type="password" name="groq_key"
                          value="<?= htmlspecialchars($ai_cfg['groq']['api_key'] ?? '') ?>" placeholder="gsk_..."></div>
                      <div class="fg"><label>Model</label><input type="text" name="groq_model"
                          value="<?= htmlspecialchars($ai_cfg['groq']['model'] ?? 'llama-3.3-70b-versatile') ?>"></div>
                    </div>
                    <!-- Ollama -->
                    <div class="card">
                      <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                        <div
                          style="width:28px;height:28px;background:#6366f1;border-radius:6px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:10px">
                          OL</div>
                        <span style="font-weight:600">Ollama <span style="font-size:11px;color:var(--di)">(local)</span></span>
                      </div>
                      <div class="fg"><label>Ollama endpoint</label><input type="url" name="ollama_endpoint"
                          value="<?= htmlspecialchars($ai_cfg['ollama']['endpoint'] ?? 'http://localhost:11434') ?>"></div>
                      <div class="fg"><label>Model name</label><input type="text" name="ollama_model"
                          value="<?= htmlspecialchars($ai_cfg['ollama']['model'] ?? 'llama3.2') ?>" placeholder="llama3.2">
                      </div>
                    </div>
                    <!-- Together AI -->
                    <div class="card">
                      <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                        <div
                          style="width:28px;height:28px;background:#8b5cf6;border-radius:6px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:10px">
                          TA</div>
                        <span style="font-weight:600">Together AI</span>
                      </div>
                      <div class="fg"><label>API key</label><input type="password" name="together_key"
                          value="<?= htmlspecialchars($ai_cfg['together']['api_key'] ?? '') ?>"></div>
                      <div class="fg"><label>Model</label><input type="text" name="together_model"
                          value="<?= htmlspecialchars($ai_cfg['together']['model'] ?? 'meta-llama/Meta-Llama-3.1-70B-Instruct-Turbo') ?>">
                      </div>
                    </div>
                  </div>
                  <div style="margin-top:20px"><button class="btn bp" style="padding:11px 28px">Save AI backend
                      settings</button></div>
                </form>
              </div>

          <?php elseif ($stab === 'search'): ?>
              <div class="card" style="margin-top:20px">
                <div class="ct">Web Search — Live RAG (Perplexity-style)</div>
                <p style="font-size:13px;color:var(--mu);margin-bottom:16px">Powers <code>?action=web_search</code> — searches
                  the internet live and generates answers with citations. DuckDuckGo works with no API key.</p>
                <form method="POST">
                  <?= csrf_field_admin() ?>
                  <input type="hidden" name="act" value="save_web_search">
                  <div class="fr">
                    <div class="fg"><label>Search provider</label>
                      <select name="ws_provider">
                        <option value="duckduckgo" <?= ($ws_cfg['provider'] ?? 'duckduckgo') === 'duckduckgo' ? 'selected' : '' ?>>
                          DuckDuckGo — free, no key needed</option>
                        <option value="brave" <?= ($ws_cfg['provider'] ?? '') === 'brave' ? 'selected' : '' ?>>Brave Search — free
                          2,000/mo</option>
                        <option value="serpapi" <?= ($ws_cfg['provider'] ?? '') === 'serpapi' ? 'selected' : '' ?>>SerpAPI — paid,
                          most
                          reliable</option>
                      </select>
                    </div>
                    <div class="fg"><label>API key (Brave or SerpAPI only)</label><input type="text" name="ws_key"
                        value="<?= htmlspecialchars($ws_cfg['api_key'] ?? '') ?>" placeholder="Leave blank for DuckDuckGo">
                    </div>
                  </div>
                  <button class="btn bp">Save search settings</button>
                </form>
                <hr style="border:none;border-top:1px solid var(--bd);margin:18px 0">
                <div style="font-size:12px;color:var(--di);line-height:1.8">
                  <strong style="color:var(--tx)">Brave Search:</strong> brave.com/search/api → free 2,000 queries/month<br>
                  <strong style="color:var(--tx)">SerpAPI:</strong> serpapi.com → 100 free/month then paid<br>
                  <strong style="color:var(--tx)">DuckDuckGo:</strong> free forever, no key — uses HTML scraping
                </div>
              </div>

          <?php elseif ($stab === 'production'): ?>
              <div class="card" style="margin-top:20px">
                <div class="ct">🚀 Production Setup Wizard</div>
                <p style="font-size:13px;color:var(--mu);margin-bottom:16px">
                  A dedicated wizard guides you through all production configuration steps including SSO integration, security
                  headers, admin access, and deployment readiness.
                </p>
                <a href="production-setup.php" class="btn bp"
                  style="display:inline-flex;align-items:center;gap:8px;text-decoration:none;padding:12px 28px">
                  🚀 Open Production Setup Wizard
                </a>
                <div style="margin-top:24px;font-size:12px;color:var(--di);line-height:1.8">
                  <strong style="color:var(--tx)">What the wizard configures:</strong><br>
                  • YG Account API Secret for JWT-verified SSO login<br>
                  • Admin email whitelist for restricted access<br>
                  • HSTS and Content-Security-Policy headers<br>
                  • Ecosystem node URLs for cross-service connectivity<br>
                  • Production readiness checklist with automated checks
                </div>
              </div>
              <div class="card" style="margin-top:16px">
                <div class="ct">Quick: YG Account API Secret</div>
                <p style="font-size:13px;color:var(--mu);margin-bottom:16px">Required to verify JWT tokens from YG Account SSO.
                </p>
                <form method="POST">
                  <?= csrf_field_admin() ?>
                  <input type="hidden" name="act" value="save_sso_quick">
                  <div class="fg"><label>API Secret</label>
                    <input type="password" name="yg_account_api_secret"
                      value="<?= htmlspecialchars($config['yg_account_api_secret'] ?? '') ?>"
                      placeholder="Your YG Account signing secret">
                  </div>
                  <div class="fg" style="margin-top:12px"><label>Admin Emails (comma-separated)</label>
                    <input type="text" name="admin_emails"
                      value="<?= htmlspecialchars(implode(', ', $config['admin_emails'] ?? ['admin@ygxone.com'])) ?>"
                      placeholder="admin@ygxone.com">
                  </div>
                  <button class="btn bp" style="margin-top:16px">Save SSO Config</button>
                </form>
              </div>
          <?php endif; ?>
      <?php elseif ($page === 'update'):
        require_once YUGA_ROOT . '/core/Migrator.php';
        $migrator = new Migrator(YUGA_ROOT . '/data', YUGA_ROOT . '/update/migrations');
        $pendingMig = array_map('basename', $migrator->pending());
        $migHistory = $migrator->history();
        $yugaVersion = file_exists(YUGA_ROOT . '/VERSION') ? trim(file_get_contents(YUGA_ROOT . '/VERSION')) : '1.0';

        // Handle actions posted from this page
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
          $act2 = $_POST['act'] ?? '';
          if ($act2 === 'run_migrations') {
            $applied = $migrator->run();
            $flash = 'Migrations applied: ' . (count($applied) ? implode(', ', $applied) : 'nothing to do');
            // Reload state
            $pendingMig = array_map('basename', $migrator->pending());
            $migHistory = $migrator->history();
          } elseif ($act2 === 'apply_zip') {
            if (!empty($_FILES['zip_file']['tmp_name'])) {
              require_once YUGA_ROOT . '/update/index.php'; // re-use applyZipUpdate()
              // Instead of including, inline the logic to avoid HTML output
              $zipErrors = [];
              if (!class_exists('ZipArchive')) {
                $flash = 'ERROR: ZipArchive not available on this server.';
              } else {
                $zip = new ZipArchive();
                if ($zip->open($_FILES['zip_file']['tmp_name']) === true) {
                  $protected = ['data/', 'config.php', 'update/', '.htaccess'];
                  $extracted = 0;
                  for ($zi = 0; $zi < $zip->numFiles; $zi++) {
                    $zn = preg_replace('#^[^/]+/#', '', $zip->getNameIndex($zi));
                    if (empty($zn))
                      continue;
                    $skip = false;
                    foreach ($protected as $p) {
                      if (str_starts_with($zn, $p) || $zn === rtrim($p, '/')) {
                        $skip = true;
                        break;
                      }
                    }
                    if ($skip)
                      continue;
                    $dest = YUGA_ROOT . '/' . $zn;
                    if (str_ends_with($zn, '/')) {
                      if (!is_dir($dest))
                        mkdir($dest, 0755, true);
                    } else {
                      $dir = dirname($dest);
                      if (!is_dir($dir))
                        mkdir($dir, 0755, true);
                      file_put_contents($dest, $zip->getFromIndex($zi));
                      $extracted++;
                    }
                  }
                  $zip->close();
                  if ($extracted > 0) {
                    // Auto-run migrations after file update
                    $applied2 = $migrator->run();
                    $flash = "Update applied — $extracted file(s) replaced. Migrations: " . (count($applied2) ? implode(', ', $applied2) : 'none pending');
                    $pendingMig = array_map('basename', $migrator->pending());
                    $migHistory = $migrator->history();
                  } else {
                    $flash = 'ERROR: No files extracted. Check zip structure.';
                  }
                } else {
                  $flash = 'ERROR: Could not open zip file.';
                }
              }
            } else {
              $flash = 'ERROR: No zip file uploaded.';
            }
          }
        }
        ?>
          <div class="tc">
            <div class="card">
              <div class="ct">System Version</div>
              <div style="display:flex;align-items:center;gap:12px;padding:8px 0">
                <span style="font-size:24px;font-weight:700;color:var(--pl)"><?= htmlspecialchars($yugaVersion) ?></span>
                <span style="font-size:12px;color:var(--di)">Current installed version</span>
              </div>
            </div>

            <div class="card">
              <div class="ct">Database Migrations
                <?php if (count($pendingMig) > 0): ?><span class="badge bg-am"
                      style="margin-left:8px"><?= count($pendingMig) ?> pending</span><?php else: ?><span class="badge bg-gr"
                      style="margin-left:8px">up to date</span><?php endif; ?>
              </div>
              <?php if (!empty($pendingMig)): ?>
                  <p style="font-size:13px;color:#fdba74;margin-bottom:10px">Pending:
                    <?= htmlspecialchars(implode(', ', $pendingMig)) ?>
                  </p>
                  <form method="POST"><input type="hidden" name="act" value="run_migrations">
                    <button class="btn bp" type="submit">Run Migrations</button>
                  </form>
              <?php else: ?>
                  <p style="font-size:13px;color:var(--di)">All migrations applied.</p>
              <?php endif; ?>
              <?php if (!empty($migHistory)): ?>
                  <table class="tbl" style="margin-top:14px">
                    <thead>
                      <tr>
                        <th>Migration</th>
                        <th>Applied at</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($migHistory as $row): ?>
                          <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td style="color:var(--di)"><?= date('Y-m-d H:i', $row['applied_at']) ?></td>
                          </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
              <?php endif; ?>
            </div>

            <div class="card">
              <div class="ct">Apply Update Package</div>
              <p style="font-size:13px;color:var(--di);margin-bottom:12px">Upload a <code>yuga-update.zip</code> to replace
                core files. Your <strong>data/</strong> and <strong>config.php</strong> are never overwritten.</p>
              <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="act" value="apply_zip">
                <div class="fg"><label>Zip file</label><input type="file" name="zip_file" accept=".zip" required
                    style="background:var(--bg3);border:1px solid var(--bd);border-radius:6px;padding:8px;color:var(--tx);width:100%">
                </div>
                <button class="btn bp" type="submit"
                  onclick="return confirm('Replace core files with zip contents? data/ and config.php are safe.')">Apply
                  Update</button>
              </form>
            </div>

            <div class="card">
              <div class="ct">Manual (SSH)</div>
              <pre
                style="background:var(--bg3);border-radius:6px;padding:12px;font-size:12px;color:var(--di);overflow-x:auto">cd /path/to/yuga
                    unzip -o yuga-update.zip -x "*/data/*" -x "*/config.php"
                    php update/migrate.php</pre>
              <p style="font-size:12px;color:var(--di);margin-top:8px">Protected from overwrite: <code>data/</code>
                &nbsp;·&nbsp; <code>config.php</code> &nbsp;·&nbsp; <code>update/</code> &nbsp;·&nbsp;
                <code>.htaccess</code>
              </p>
            </div>
          </div>

      <?php // ── WEBHOOKS ──────────────────────────────────────────────────────────
      elseif ($page === 'webhooks'):
        require_once YUGA_ROOT . '/core/Webhooks.php';
        $wh = new Webhooks(YUGA_ROOT . '/data');
        $hooks = $wh->list();
        $wh_log = $wh->recentLog(20);
        $all_evts = $wh->allEvents();
        ?>
          <div class="tc">
            <div>
              <div class="card">
                <div class="ct">Registered webhooks <span class="ex"><?= count($hooks) ?> total</span></div>
                <?php if (empty($hooks)): ?>
                    <p style="font-size:13px;color:var(--di)">No webhooks yet. Add one below.</p>
                <?php else: ?>
                    <table class="tbl">
                      <thead>
                        <tr>
                          <th>Label</th>
                          <th>URL</th>
                          <th>Events</th>
                          <th>Status</th>
                          <th>Deliveries</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($hooks as $h): ?>
                            <tr>
                              <td style="font-weight:500"><?= htmlspecialchars($h['label']) ?></td>
                              <td style="font-family:monospace;font-size:11px;color:var(--di)">
                                <?= htmlspecialchars(substr($h['url'], 0, 40)) ?>...
                              </td>
                              <td style="font-size:11px;color:var(--mu)"><?= implode(', ', $h['events']) ?></td>
                              <td><span
                                  class="pill <?= $h['active'] ? 'pg' : 'pr' ?>"><?= $h['active'] ? 'active' : 'disabled' ?></span>
                              </td>
                              <td style="font-size:12px;color:var(--di)">✓<?= $h['success_count'] ?> ✗<?= $h['fail_count'] ?></td>
                              <td style="display:flex;gap:4px">
                                <form method="POST" style="display:inline"><input type="hidden" name="act"
                                    value="webhook_test"><input type="hidden" name="hook_id" value="<?= $h['id'] ?>"><button
                                    class="btn bs bp" style="font-size:11px;padding:4px 8px">Test</button></form>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Delete webhook?')"><input
                                    type="hidden" name="act" value="webhook_delete"><input type="hidden" name="hook_id"
                                    value="<?= $h['id'] ?>"><button class="btn bs bd"
                                    style="font-size:11px;padding:4px 8px">Delete</button></form>
                              </td>
                            </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                <?php endif; ?>
              </div>

              <div class="card" style="margin-top:16px">
                <div class="ct">Delivery log</div>
                <?php if (empty($wh_log)): ?>
                    <p style="font-size:13px;color:var(--di)">No deliveries yet.</p>
                <?php else: ?>
                    <table class="tbl">
                      <thead>
                        <tr>
                          <th>Time</th>
                          <th>Hook</th>
                          <th>Event</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($wh_log as $l): ?>
                            <tr>
                              <td style="font-size:11px;color:var(--di)"><?= date('M j H:i', $l['ts']) ?></td>
                              <td style="font-size:12px"><?= htmlspecialchars($l['label']) ?></td>
                              <td style="font-size:11px;color:var(--mu)"><?= htmlspecialchars($l['event']) ?></td>
                              <td><span
                                  class="pill <?= $l['ok'] ? 'pg' : 'pr' ?>"><?= $l['ok'] ? '✓ ' . $l['status'] : '✗ ' . $l['status'] ?></span>
                              </td>
                            </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                <?php endif; ?>
              </div>
            </div>

            <div class="card" style="align-self:start">
              <div class="ct">Add webhook</div>
              <form method="POST">
                <div class="fg"><label>URL</label><input type="url" name="wh_url" required
                    placeholder="https://yourapp.com/webhook"></div>
                <div class="fg"><label>Label</label><input type="text" name="wh_label" placeholder="My app"></div>
                <div class="fg"><label>Events to send</label>
                  <div style="display:flex;flex-direction:column;gap:6px;margin-top:6px">
                    <?php foreach ($all_evts as $ev): ?>
                        <label
                          style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--tx);font-weight:400;cursor:pointer">
                          <input type="checkbox" name="wh_events[]" value="<?= $ev ?>" checked
                            style="width:auto;accent-color:var(--pu)"> <?= $ev ?>
                        </label>
                    <?php endforeach; ?>
                  </div>
                </div>
                <button class="btn bp" style="width:100%;margin-top:8px">Register webhook</button>
              </form>
              <div
                style="margin-top:16px;background:var(--bg3);border-radius:8px;padding:12px;font-size:12px;color:var(--di)">
                <strong style="color:var(--tx)">Verifying webhooks</strong><br><br>
                Every request includes an <code>X-Yuga-Signature</code> header:<br>
                <code style="color:var(--te)">sha256=HMAC_SHA256(body, secret)</code><br><br>
                The secret is shown once when you register. Store it securely.
              </div>
            </div>
          </div>

      <?php // ── SCHEDULER ─────────────────────────────────────────────────────────
      elseif ($page === 'scheduler'):
        require_once YUGA_ROOT . '/core/Scheduler.php';
        $sched = new Scheduler(YUGA_ROOT . '/data');
        $tasks = $sched->list();
        $sc_log = $sched->recentLog(20);
        ?>
          <div class="tc">
            <div>
              <div class="card">
                <div class="ct">Scheduled tasks <span class="ex"><?= count($tasks) ?> total</span></div>
                <?php if (empty($tasks)): ?>
                    <p style="font-size:13px;color:var(--di)">No tasks yet.</p>
                <?php else: ?>
                    <table class="tbl">
                      <thead>
                        <tr>
                          <th>Label</th>
                          <th>Type</th>
                          <th>Schedule</th>
                          <th>Last run</th>
                          <th>Next run</th>
                          <th>Status</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($tasks as $t): ?>
                            <tr>
                              <td style="font-weight:500"><?= htmlspecialchars($t['label']) ?></td>
                              <td><span class="pill pp"><?= $t['type'] ?></span></td>
                              <td style="font-size:12px;color:var(--mu)">
                                <?= $t['schedule'] === 'custom' ? htmlspecialchars($t['cron_expr']) : $t['schedule'] ?>
                              </td>
                              <td style="font-size:12px;color:var(--di)">
                                <?= $t['last_run'] ? date('M j H:i', $t['last_run']) : 'Never' ?>
                              </td>
                              <td style="font-size:12px;color:var(--di)">
                                <?= $t['next_run'] ? date('M j H:i', $t['next_run']) : '—' ?>
                              </td>
                              <td>
                                <?php if ($t['last_status']): ?><span
                                      class="pill <?= $t['last_status'] === 'ok' ? 'pg' : 'pr' ?>"><?= $t['last_status'] ?></span><?php endif; ?>
                                <span class="pill <?= $t['active'] ? 'pg' : 'pz' ?>"><?= $t['active'] ? 'on' : 'off' ?></span>
                              </td>
                              <td style="display:flex;gap:4px">
                                <form method="POST" style="display:inline"><input type="hidden" name="act"
                                    value="scheduler_toggle"><input type="hidden" name="task_id" value="<?= $t['id'] ?>"><input
                                    type="hidden" name="active" value="<?= $t['active'] ? 0 : 1 ?>"><button class="btn bs"
                                    style="font-size:11px;padding:4px 8px"><?= $t['active'] ? 'Pause' : 'Resume' ?></button></form>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Delete task?')"><input
                                    type="hidden" name="act" value="scheduler_delete"><input type="hidden" name="task_id"
                                    value="<?= $t['id'] ?>"><button class="btn bs bd"
                                    style="font-size:11px;padding:4px 8px">Delete</button></form>
                              </td>
                            </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                <?php endif; ?>
              </div>

              <div class="card" style="margin-top:16px">
                <div class="ct">Run log</div>
                <?php if (empty($sc_log)): ?>
                    <p style="font-size:13px;color:var(--di)">No runs yet.</p>
                <?php else: ?>
                    <table class="tbl">
                      <thead>
                        <tr>
                          <th>Time</th>
                          <th>Task</th>
                          <th>Result</th>
                          <th>Message</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($sc_log as $l): ?>
                            <tr>
                              <td style="font-size:11px;color:var(--di)"><?= date('M j H:i', $l['ts']) ?></td>
                              <td style="font-size:12px"><?= htmlspecialchars($l['task_id']) ?></td>
                              <td><span class="pill <?= $l['ok'] ? 'pg' : 'pr' ?>"><?= $l['ok'] ? 'OK' : 'FAIL' ?></span></td>
                              <td style="font-size:11px;color:var(--mu)"><?= htmlspecialchars($l['message']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                <?php endif; ?>
              </div>

              <div class="card" style="margin-top:16px;border-color:rgba(99,102,241,.3)">
                <div class="ct">Master cron (set once in cPanel)</div>
                <p style="font-size:13px;color:var(--di);margin-bottom:8px">Add this single cron job — it fires all
                  scheduled tasks automatically:</p>
                <div
                  style="background:var(--bg3);border-radius:8px;padding:12px;font-family:monospace;font-size:12px;color:var(--te)">
                  * * * * * php <?= htmlspecialchars(YUGA_ROOT) ?>/scheduler/run.php</div>
              </div>
            </div>

            <div class="card" style="align-self:start">
              <div class="ct">Create task</div>
              <form method="POST" id="sc-form">
                <input type="hidden" name="act" value="scheduler_add">
                <div class="fg"><label>Label</label><input type="text" name="label" required
                    placeholder="e.g. Nightly retrain"></div>
                <div class="fg"><label>Task type</label>
                  <select name="type" onchange="scType(this.value)" id="sc-type">
                    <option value="crawl_url">Crawl URL — retrain from website</option>
                    <option value="retrain">Retrain — use existing corpus</option>
                    <option value="ping_webhook">Ping URL — health check / trigger</option>
                    <option value="send_report">Send report email to admin</option>
                  </select>
                </div>
                <div id="sc-url" class="fg"><label>URL</label><input type="url" name="url"
                    placeholder="https://yoursite.com"></div>
                <div id="sc-pages" class="fg"><label>Max pages</label><input type="number" name="max_pages" value="20">
                </div>
                <div id="sc-model" class="fg"><label>Model</label>
                  <select name="model"><?php foreach ($models as $mn): ?>
                        <option value="<?= htmlspecialchars($mn) ?>"><?= htmlspecialchars($mn) ?></option><?php endforeach; ?>
                  </select>
                </div>
                <div id="sc-steps" class="fg" style="display:none"><label>Train steps</label><input type="number"
                    name="steps" value="5000"></div>
                <div class="fg"><label>Schedule</label>
                  <select name="schedule" onchange="scSched(this.value)" id="sc-sched">
                    <option value="hourly">Every hour</option>
                    <option value="daily" selected>Every day (midnight)</option>
                    <option value="weekly">Every week (Monday)</option>
                    <option value="monthly">Every month (1st)</option>
                    <option value="custom">Custom cron expression</option>
                  </select>
                </div>
                <div id="sc-cron" class="fg" style="display:none"><label>Cron expression</label><input type="text"
                    name="cron_expr" placeholder="0 3 * * 1  (3am every Monday)"></div>
                <button class="btn bp" style="width:100%">Create task</button>
              </form>
              <script>
                function scType(v) {
                  document.getElementById('sc-url').style.display = (v === 'crawl_url' || v === 'ping_webhook') ? 'block' : 'none';
                  document.getElementById('sc-pages').style.display = v === 'crawl_url' ? 'block' : 'none';
                  document.getElementById('sc-model').style.display = (v === 'crawl_url' || v === 'retrain') ? 'block' : 'none';
                  document.getElementById('sc-steps').style.display = v === 'retrain' ? 'block' : 'none';
                }
                function scSched(v) {
                  document.getElementById('sc-cron').style.display = v === 'custom' ? 'block' : 'none';
                }
              </script>
            </div>
          </div>

      <?php // ── INTEGRATIONS ─────────────────────────────────────────────────────
      elseif ($page === 'integrations'):
        $tg_cfg = $config['telegram'] ?? [];
        $sl_cfg = $config['slack'] ?? [];
        $wa_cfg = $config['whatsapp'] ?? [];
        $intBase = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . str_replace('/admin/index.php', '/', $_SERVER['SCRIPT_NAME']);
        ?>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:20px">

            <!-- Telegram -->
            <div class="card">
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                <div
                  style="width:36px;height:36px;background:#229ED9;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:20px">
                  ✈</div>
                <div>
                  <div style="font-weight:600">Telegram Bot</div>
                  <span
                    class="pill <?= !empty($tg_cfg['token']) ? 'pg' : 'pz' ?>"><?= !empty($tg_cfg['token']) ? 'configured' : 'not set' ?></span>
                </div>
              </div>
              <p style="font-size:12px;color:var(--di);margin-bottom:12px">Users chat with Yuga directly in Telegram.
                Supports conversation memory and /clear command.</p>
              <div style="font-size:12px;color:var(--di);margin-bottom:8px"><strong style="color:var(--tx)">Setup
                  steps:</strong></div>
              <ol style="font-size:12px;color:var(--mu);padding-left:16px;line-height:2">
                <li>Message @BotFather → /newbot</li>
                <li>Copy the token</li>
                <li>Add to config.php: <code
                    style="color:var(--te)">'telegram' =&gt; ['token' =&gt; '...', 'model' =&gt; 'default']</code></li>
                <li>Set webhook URL below</li>
              </ol>
              <div
                style="margin-top:12px;background:var(--bg3);border-radius:6px;padding:10px;font-size:11px;font-family:monospace;color:var(--te);word-break:break-all">
                <?= htmlspecialchars($intBase) ?>integrations/telegram_hook.php
              </div>
              <?php if (!empty($tg_cfg['token'])): ?>
                  <a href="https://api.telegram.org/bot<?= urlencode($tg_cfg['token']) ?>/setWebhook?url=<?= urlencode($intBase . 'integrations/telegram_hook.php') ?>"
                    target="_blank" class="btn bp"
                    style="display:block;text-align:center;margin-top:12px;font-size:12px">Auto-register webhook →</a>
              <?php endif; ?>
            </div>

            <!-- Slack -->
            <div class="card">
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                <div
                  style="width:36px;height:36px;background:#4A154B;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:20px">
                  #</div>
                <div>
                  <div style="font-weight:600">Slack</div>
                  <span
                    class="pill <?= !empty($sl_cfg['webhook_url']) ? 'pg' : 'pz' ?>"><?= !empty($sl_cfg['webhook_url']) ? 'configured' : 'not set' ?></span>
                </div>
              </div>
              <p style="font-size:12px;color:var(--di);margin-bottom:12px">Post notifications to Slack channels. Also
                supports <code>/yuga question</code> slash command.</p>
              <div style="font-size:12px;color:var(--di);margin-bottom:8px"><strong style="color:var(--tx)">Setup
                  steps:</strong></div>
              <ol style="font-size:12px;color:var(--mu);padding-left:16px;line-height:2">
                <li>api.slack.com/apps → Create app</li>
                <li>Incoming Webhooks → copy URL</li>
                <li>Slash Commands → /yuga → URL below</li>
                <li>Add to config.php: <code
                    style="color:var(--te)">'slack' =&gt; ['webhook_url' =&gt; '...', 'signing_secret' =&gt; '...']</code>
                </li>
              </ol>
              <div
                style="margin-top:12px;background:var(--bg3);border-radius:6px;padding:10px;font-size:11px;font-family:monospace;color:var(--te);word-break:break-all">
                <?= htmlspecialchars($intBase) ?>integrations/slack_hook.php
              </div>
            </div>

            <!-- WhatsApp -->
            <div class="card">
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                <div
                  style="width:36px;height:36px;background:#25D366;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:20px">
                  💬</div>
                <div>
                  <div style="font-weight:600">WhatsApp</div>
                  <span
                    class="pill <?= !empty($wa_cfg['account_sid']) ? 'pg' : 'pz' ?>"><?= !empty($wa_cfg['account_sid']) ? 'configured' : 'not set' ?></span>
                </div>
              </div>
              <p style="font-size:12px;color:var(--di);margin-bottom:12px">Chat with Yuga on WhatsApp via Twilio. Works on
                free Twilio sandbox for testing.</p>
              <div style="font-size:12px;color:var(--di);margin-bottom:8px"><strong style="color:var(--tx)">Setup
                  steps:</strong></div>
              <ol style="font-size:12px;color:var(--mu);padding-left:16px;line-height:2">
                <li>Sign up at twilio.com (free trial)</li>
                <li>Messaging → Try WhatsApp sandbox</li>
                <li>Set webhook URL to below</li>
                <li>Add to config.php: <code
                    style="color:var(--te)">'whatsapp' =&gt; ['account_sid'=&gt;'...', 'auth_token'=&gt;'...', 'from_number'=&gt;'whatsapp:+1...']</code>
                </li>
              </ol>
              <div
                style="margin-top:12px;background:var(--bg3);border-radius:6px;padding:10px;font-size:11px;font-family:monospace;color:var(--te);word-break:break-all">
                <?= htmlspecialchars($intBase) ?>integrations/whatsapp_hook.php
              </div>
            </div>
          </div>

          <!-- Config snippet -->
          <div class="card">
            <div class="ct">Add to config.php</div>
            <pre
              style="background:var(--bg3);border-radius:8px;padding:14px;font-size:12px;color:var(--te);overflow-x:auto;line-height:1.8"><?= htmlspecialchars("// Telegram
'telegram' => ['token' => 'BOT_TOKEN_HERE', 'model' => 'default', 'assistant_name' => 'Yuga'],

// Slack
'slack' => ['webhook_url' => 'https://hooks.slack.com/...', 'signing_secret' => '...', 'model' => 'default'],

// WhatsApp (Twilio)
'whatsapp' => [
    'account_sid' => 'ACxxx',
    'auth_token'  => '...',
    'from_number' => 'whatsapp:+14155238886',
    'model'       => 'default',
],") ?></pre>
          </div>

      <?php // ── WHITE-LABEL ───────────────────────────────────────────────────────
      elseif ($page === 'whitelabel'):
        require_once YUGA_ROOT . '/core/WhiteLabel.php';
        $wl = new WhiteLabel(YUGA_ROOT . '/data');
        $tenants = $wl->listTenants();
        $wl_sub = $_GET['sub_id'] ?? ($subs[0]['id'] ?? '');
        $tc = $wl_sub ? $wl->getTenant($wl_sub) : null;
        $wl_rec = $wl_sub ? $akm->getSubscriber($wl_sub) : null;
        $wl_plan = $wl_rec['plan'] ?? 'free';
        $def_model = $config['default_model'] ?? 'default';
        ?>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div>
              <div class="card">
                <div class="ct">Select subscriber to configure</div>
                <form method="GET">
                  <input type="hidden" name="page" value="whitelabel">
                  <select name="sub_id" onchange="this.form.submit()" style="margin-bottom:0">
                    <option value="">— choose subscriber —</option>
                    <?php foreach ($subs as $s): ?>
                        <option value="<?= htmlspecialchars($s['id']) ?>" <?= $s['id'] === $wl_sub ? 'selected' : '' ?>>
                          <?= htmlspecialchars($s['name']) ?> (<?= $s['plan'] ?>)
                        </option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </div>
              <?php if ($tc && $wl_rec): ?>
                  <div class="card">
                    <div class="ct">Widget settings — <?= htmlspecialchars($wl_rec['name']) ?> <span
                        class="pill <?= in_array($wl_plan, ['pro', 'enterprise']) ? 'pg' : 'pa' ?>"
                        style="margin-left:4px"><?= ucfirst($wl_plan) ?></span></div>
                    <form method="POST">
                      <input type="hidden" name="act" value="whitelabel_save">
                      <input type="hidden" name="sub_id" value="<?= htmlspecialchars($wl_sub) ?>">
                      <div class="fr">
                        <div class="fg"><label>Widget title</label><input type="text" name="widget_name"
                            value="<?= htmlspecialchars($tc['widget_name']) ?>" placeholder="AI Assistant"></div>
                        <div class="fg"><label>Avatar initials (1-3 chars)</label><input type="text" name="avatar_initials"
                            value="<?= htmlspecialchars($tc['avatar_initials']) ?>" maxlength="3" placeholder="AI"></div>
                      </div>
                      <div class="fg"><label>Opening greeting</label><input type="text" name="widget_greeting"
                          value="<?= htmlspecialchars($tc['widget_greeting']) ?>" placeholder="Hi! How can I help?"></div>
                      <div class="fg"><label>AI persona (tone)</label><input type="text" name="persona"
                          value="<?= htmlspecialchars($tc['persona']) ?>" placeholder="helpful and friendly"></div>
                      <div class="fr">
                        <div class="fg">
                          <label>Primary color</label>
                          <div style="display:flex;gap:8px;align-items:center">
                            <input type="color" name="primary_color" value="<?= htmlspecialchars($tc['primary_color']) ?>"
                              style="width:48px;height:36px;padding:2px;border-radius:6px;cursor:pointer;border:1px solid var(--bd2)">
                            <input type="text" value="<?= htmlspecialchars($tc['primary_color']) ?>" style="flex:1"
                              oninput="this.previousElementSibling.value=this.value">
                          </div>
                        </div>
                        <div class="fg"><label>Theme</label>
                          <select name="widget_theme">
                            <option value="dark" <?= $tc['widget_theme'] === 'dark' ? 'selected' : '' ?>>Dark</option>
                            <option value="light" <?= $tc['widget_theme'] === 'light' ? 'selected' : '' ?>>Light</option>
                          </select>
                        </div>
                      </div>
                      <div class="fg"><label>Logo URL (optional)</label><input type="url" name="logo_url"
                          value="<?= htmlspecialchars($tc['logo_url']) ?>" placeholder="https://yoursite.com/logo.png"></div>
                      <div class="fg"><label>Custom domain (optional)</label><input type="text" name="custom_domain"
                          value="<?= htmlspecialchars($tc['custom_domain']) ?>" placeholder="api.yourcompany.com"></div>
                      <div class="fg"><label>Allowed origins (comma-separated, * for all)</label><input type="text"
                          name="allowed_origins" value="<?= htmlspecialchars($tc['allowed_origins']) ?>"
                          placeholder="yoursite.com, app.yoursite.com"></div>
                      <?php if (in_array($wl_plan, ['pro', 'enterprise'])): ?>
                          <div
                            style="display:flex;align-items:center;gap:10px;padding:12px;background:var(--bg3);border-radius:8px;margin-bottom:12px">
                            <input type="checkbox" name="hide_branding" value="1" id="hb-cb" <?= $tc['hide_branding'] ? 'checked' : '' ?> style="width:16px;height:16px;accent-color:var(--pu)">
                            <label for="hb-cb" style="margin:0;font-size:13px;color:var(--tx);font-weight:400">Remove "Powered by
                              Yuga" branding</label>
                            <span class="pill pg" style="margin-left:auto">Pro+</span>
                          </div>
                      <?php else: ?>
                          <div
                            style="padding:12px;background:var(--bg3);border-radius:8px;font-size:12px;color:var(--di);margin-bottom:12px">
                            White-label branding removal requires Pro or Enterprise plan. <a href="?page=subscribers"
                              style="color:var(--pl)">Upgrade →</a></div>
                      <?php endif; ?>
                      <button type="submit" class="btn bp">Save settings</button>
                    </form>
                  </div>
              <?php endif; ?>
            </div>
            <div>
              <?php if ($tc && $wl_rec): ?>
                  <div class="card">
                    <div class="ct">Live widget preview</div>
                    <div style="background:var(--bg3);border-radius:10px;overflow:hidden">
                      <div
                        style="display:flex;align-items:center;gap:10px;padding:12px 14px;background:<?= htmlspecialchars($tc['primary_color']) ?>">
                        <div
                          style="width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:#fff;flex-shrink:0">
                          <?= htmlspecialchars($tc['avatar_initials']) ?>
                        </div>
                        <div style="font-weight:600;font-size:14px;color:#fff;flex:1">
                          <?= htmlspecialchars($tc['widget_name']) ?>
                        </div>
                        <div style="font-size:11px;color:rgba(255,255,255,.7)">● online</div>
                      </div>
                      <div style="padding:14px;display:flex;flex-direction:column;gap:10px">
                        <div
                          style="background:var(--bg2);border:1px solid var(--bd);border-radius:10px;padding:10px 13px;font-size:13px;color:var(--mu);align-self:flex-start;max-width:85%">
                          <?= htmlspecialchars($tc['widget_greeting']) ?>
                        </div>
                        <div style="display:flex;gap:8px;margin-top:4px">
                          <div
                            style="flex:1;background:var(--bg2);border:1px solid var(--bd2);border-radius:8px;padding:9px 12px;font-size:12px;color:var(--di)">
                            Ask anything...</div>
                          <div
                            style="width:36px;height:36px;background:<?= htmlspecialchars($tc['primary_color']) ?>;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff">
                            ➤</div>
                        </div>
                        <?php if (!$tc['hide_branding']): ?>
                            <div style="text-align:center;font-size:10px;color:var(--di)">Powered by Yuga</div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <div class="card">
                    <div class="ct">Embed snippet</div>
                    <p style="font-size:12px;color:var(--di);margin-bottom:10px">Paste into any HTML page to embed the branded
                      widget.</p>
                    <?php $embed = $wl->generateEmbed($wl_sub, $api_base, $def_model, $wl_plan); ?>
                    <pre id="wl-embed"
                      style="background:var(--bg3);border-radius:8px;padding:12px;font-size:11px;color:var(--te);overflow-x:auto;line-height:1.7;white-space:pre-wrap;word-break:break-all"><?= htmlspecialchars($embed) ?></pre>
                    <button class="btn bg" style="margin-top:8px;font-size:12px"
                      onclick="navigator.clipboard.writeText(document.getElementById('wl-embed').textContent);this.textContent='Copied!'">Copy
                      embed code</button>
                  </div>
              <?php endif; ?>
              <?php if ($tenants): ?>
                  <div class="card">
                    <div class="ct">Configured tenants <span class="ex"><?= count($tenants) ?></span></div>
                    <table class="tbl">
                      <tr>
                        <th>Subscriber</th>
                        <th>Widget name</th>
                        <th>Theme</th>
                        <th>Branding</th>
                      </tr>
                      <?php foreach ($tenants as $tt):
                        $tsub = $akm->getSubscriber($tt['sub_id']);
                        ?>
                          <tr>
                            <td><a href="?page=whitelabel&sub_id=<?= urlencode($tt['sub_id']) ?>"
                                style="color:var(--pl)"><?= htmlspecialchars($tsub['name'] ?? $tt['sub_id']) ?></a></td>
                            <td><?= htmlspecialchars($tt['widget_name']) ?></td>
                            <td><span class="pill pz"><?= $tt['widget_theme'] ?></span></td>
                            <td>
                              <?= $tt['hide_branding'] ? '<span class="pill pg">hidden</span>' : '<span class="pill pa">shown</span>' ?>
                            </td>
                          </tr>
                      <?php endforeach; ?>
                    </table>
                  </div>
              <?php endif; ?>
            </div>
          </div>

      <?php // ── VERSION HISTORY ───────────────────────────────────────────────────
      elseif ($page === 'versions'):
        require_once YUGA_ROOT . '/core/Versioning.php';
        $ver = new Versioning(YUGA_ROOT . '/data');
        $vm = $_GET['m'] ?? ($models[0] ?? 'default');
        $versions = $vm ? $ver->listVersions($vm) : [];
        ?>
          <div class="card">
            <div class="ct">Save checkpoint</div>
            <p style="font-size:13px;color:var(--mu);margin-bottom:14px">Snapshot the current trained weights as a named
              version. Roll back any time.</p>
            <form method="POST">
              <input type="hidden" name="act" value="version_save">
              <div class="fr">
                <div class="fg"><label>Model</label>
                  <select name="model" onchange="location='?page=versions&m='+this.value">
                    <?php foreach ($models as $mn): ?>
                        <option value="<?= htmlspecialchars($mn) ?>" <?= $mn === $vm ? 'selected' : '' ?>>
                          <?= htmlspecialchars($mn) ?>
                        </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="fg"><label>Version tag (e.g. v1.2, post-faq-training)</label><input type="text" name="version"
                    placeholder="v1.0" required></div>
              </div>
              <div class="fg"><label>Notes (optional)</label><input type="text" name="notes"
                  placeholder="Trained on 50 FAQ pages, loss 0.71"></div>
              <button type="submit" class="btn bp">Save checkpoint</button>
            </form>
          </div>
          <div class="card">
            <div class="ct">Version history — <code><?= htmlspecialchars($vm) ?></code> <span
                class="ex"><?= count($versions) ?> saved</span></div>
            <?php if (!$versions): ?>
                <p style="font-size:13px;color:var(--di);text-align:center;padding:24px 0">No checkpoints saved yet. Train the
                  model then save your first version above.</p>
            <?php else: ?>
                <table class="tbl">
                  <tr>
                    <th>Version</th>
                    <th>Steps</th>
                    <th>Loss</th>
                    <th>Size</th>
                    <th>Saved</th>
                    <th>Notes</th>
                    <th>Status</th>
                    <th></th>
                  </tr>
                  <?php foreach ($versions as $v): ?>
                      <tr>
                        <td><code style="color:var(--pl)"><?= htmlspecialchars($v['version']) ?></code></td>
                        <td><?= number_format($v['steps']) ?></td>
                        <td
                          style="color:<?= ($v['loss'] > 0 && $v['loss'] < 0.7) ? 'var(--gr)' : ($v['loss'] < 0.85 ? 'var(--am)' : 'var(--re)') ?>">
                          <?= $v['loss'] > 0 ? round($v['loss'], 4) : '—' ?>
                        </td>
                        <td style="color:var(--di)"><?= $v['size_kb'] ?>KB</td>
                        <td style="color:var(--di);font-size:12px"><?= date('M j, Y', $v['created_at']) ?></td>
                        <td
                          style="color:var(--mu);font-size:12px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                          <?= htmlspecialchars($v['notes']) ?>
                        </td>
                        <td>
                          <?= $v['is_active'] ? '<span class="pill pg">active</span>' : ($v['is_ab'] ? '<span class="pill pa">A/B</span>' : '<span class="pill pz">saved</span>') ?>
                        </td>
                        <td>
                          <div style="display:flex;gap:5px">
                            <?php if (!$v['is_active']): ?>
                                <form method="POST" style="display:inline">
                                  <input type="hidden" name="act" value="version_activate">
                                  <input type="hidden" name="model" value="<?= htmlspecialchars($vm) ?>">
                                  <input type="hidden" name="version" value="<?= htmlspecialchars($v['version']) ?>">
                                  <button class="btn bt bs"
                                    onclick="return confirm('Activate <?= htmlspecialchars($v['version']) ?>? Replaces the live model.')">Activate</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline">
                              <input type="hidden" name="act" value="version_delete">
                              <input type="hidden" name="model" value="<?= htmlspecialchars($vm) ?>">
                              <input type="hidden" name="version" value="<?= htmlspecialchars($v['version']) ?>">
                              <button class="btn bd bs" onclick="return confirm('Delete this version?')">Del</button>
                            </form>
                          </div>
                        </td>
                      </tr>
                  <?php endforeach; ?>
                </table>
            <?php endif; ?>
          </div>
          <?php if (count($versions) >= 2): ?>
              <div class="card">
                <div class="ct">A/B Testing</div>
                <p style="font-size:13px;color:var(--mu);margin-bottom:16px">Split live traffic between two versions. Declare a
                  winner when you have enough data.</p>
                <form method="POST">
                  <input type="hidden" name="act" value="ab_start">
                  <input type="hidden" name="model" value="<?= htmlspecialchars($vm) ?>">
                  <div class="fr">
                    <div class="fg"><label>Version A (control)</label>
                      <select name="va"><?php foreach ($versions as $v): ?>
                            <option value="<?= htmlspecialchars($v['version']) ?>">
                              <?= htmlspecialchars($v['version']) ?>             <?= $v['is_active'] ? ' (active)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="fg"><label>Version B (challenger)</label>
                      <select name="vb"><?php foreach (array_reverse($versions) as $v): ?>
                            <option value="<?= htmlspecialchars($v['version']) ?>"><?= htmlspecialchars($v['version']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="fg">
                    <label>Traffic to Version B: <strong id="ab-pct">50</strong>%</label>
                    <input type="range" name="traffic_b" min="10" max="90" value="50"
                      oninput="document.getElementById('ab-pct').textContent=this.value"
                      style="width:100%;accent-color:var(--pu);margin-top:6px">
                  </div>
                  <button type="submit" class="btn bp">Start A/B test</button>
                </form>
                <?php $ab_active = array_values(array_filter($versions, fn($v) => $v['is_ab']));
                if ($ab_active): ?>
                    <hr style="border:none;border-top:1px solid var(--bd);margin:20px 0">
                    <div style="font-size:13px;font-weight:600;color:var(--am);margin-bottom:12px">Active A/B test — declare winner:
                    </div>
                    <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap">
                      <input type="hidden" name="act" value="ab_end">
                      <input type="hidden" name="model" value="<?= htmlspecialchars($vm) ?>">
                      <?php foreach ($ab_active as $abv): ?>
                          <button class="btn bt" name="winner" value="<?= htmlspecialchars($abv['version']) ?>">Declare
                            <?= htmlspecialchars($abv['version']) ?> winner</button>
                      <?php endforeach; ?>
                    </form>
                <?php endif; ?>
              </div>
          <?php endif; ?>

      <?php // ── EXPORT / IMPORT ───────────────────────────────────────────────────
      elseif ($page === 'porter'):
        require_once YUGA_ROOT . '/core/ModelPorter.php';
        $porter = new ModelPorter(YUGA_ROOT . '/data', $store);
        $exports = $porter->listExports();
        ?>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
            <div class="card">
              <div class="ct">Export model</div>
              <p style="font-size:13px;color:var(--mu);margin-bottom:16px">Download any model as a portable
                <code>.yuga</code> bundle — weights, BM25 index, metadata, YugaGen checkpoint.
              </p>
              <table class="tbl">
                <tr>
                  <th>Model</th>
                  <th>Training</th>
                  <th></th>
                </tr>
                <?php foreach ($models as $mn):
                  try {
                    $mst = (new Brain($mn, $store))->status();
                  } catch (Exception $e) {
                    $mst = ['ready' => false, 'steps' => 0, 'loss' => 0];
                  }
                  ?>
                    <tr>
                      <td><code><?= htmlspecialchars($mn) ?></code></td>
                      <td><?= $mst['ready'] ? '<span class="pill pg">trained</span>' : '<span class="pill pz">empty</span>' ?>
                        <?php if (!empty($mst['steps'])): ?><span
                              style="font-size:11px;color:var(--di);margin-left:5px"><?= number_format($mst['steps']) ?>
                              steps</span><?php endif; ?>
                      </td>
                      <td><a href="?page=porter&download=<?= urlencode($mn) ?>" class="btn bt bs"
                          onclick="this.textContent='Exporting…';this.style.pointerEvents='none'">Download .yuga</a></td>
                    </tr>
                <?php endforeach; ?>
              </table>
            </div>
            <div class="card">
              <div class="ct">Import model</div>
              <p style="font-size:13px;color:var(--mu);margin-bottom:16px">Upload a <code>.yuga</code> file from any Yuga
                instance. Restores all weights, index, and metadata.</p>
              <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="act" value="model_import">
                <div class="fg"><label>Import as model name (blank = use original name from file)</label><input type="text"
                    name="import_name" placeholder="e.g. production-backup"></div>
                <div class="fg">
                  <label>Upload .yuga file</label>
                  <div style="border:2px dashed var(--bd2);border-radius:8px;padding:24px;text-align:center;cursor:pointer"
                    onclick="document.getElementById('yf').click()">
                    <div style="font-size:28px;margin-bottom:8px">📦</div>
                    <div style="font-size:13px;color:var(--mu)" id="yf-lbl">Click to browse or drag a .yuga file</div>
                    <div style="font-size:11px;color:var(--di);margin-top:4px">Max: <?= ini_get('upload_max_filesize') ?>
                    </div>
                  </div>
                  <input type="file" id="yf" name="yuga_file" accept=".yuga" style="display:none"
                    onchange="document.getElementById('yf-lbl').textContent=this.files[0]?.name||'No file chosen'">
                </div>
                <button type="submit" class="btn bp">Import model</button>
              </form>
            </div>
          </div>
          <?php if ($exports): ?>
              <div class="card">
                <div class="ct">Saved exports in /data/ <span class="ex"><?= count($exports) ?> files</span></div>
                <p style="font-size:12px;color:var(--di);margin-bottom:12px">Download via FTP/cPanel File Manager or delete to
                  free disk space.</p>
                <table class="tbl">
                  <tr>
                    <th>File</th>
                    <th>Model</th>
                    <th>Exported</th>
                    <th>Size</th>
                  </tr>
                  <?php foreach ($exports as $ex): ?>
                      <tr>
                        <td><code style="font-size:11px;color:var(--te)"><?= htmlspecialchars($ex['file']) ?></code></td>
                        <td><?= htmlspecialchars($ex['model_name']) ?></td>
                        <td style="color:var(--di)"><?= $ex['exported_at'] ?></td>
                        <td style="color:var(--di)"><?= $ex['size_kb'] ?> KB</td>
                      </tr>
                  <?php endforeach; ?>
                </table>
              </div>
          <?php endif; ?>

      <?php // ── EMAIL TEMPLATES ───────────────────────────────────────────────────
      elseif ($page === 'email_templates'):
        require_once YUGA_ROOT . '/core/EmailTemplates.php';
        $tpl = new EmailTemplates(YUGA_ROOT . '/data');
        $tpl_names = $tpl->names();
        $editing = $_GET['t'] ?? $tpl_names[0];
        if (!in_array($editing, $tpl_names))
          $editing = $tpl_names[0];
        $platform = $config['platform_name'] ?? 'Yuga';
        ?>
          <div class="tc" style="grid-template-columns:240px 1fr">
            <!-- Left: template list -->
            <div>
              <div class="card" style="padding:0;overflow:hidden">
                <?php foreach ($tpl_names as $tn):
                  $label = \EmailTemplates::LABELS[$tn];
                  $custom = $tpl->isCustom($tn); ?>
                    <a href="?page=email_templates&t=<?= urlencode($tn) ?>"
                      style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid var(--bd);text-decoration:none;background:<?= $editing === $tn ? 'var(--bg3)' : 'transparent' ?>;transition:.15s">
                      <span
                        style="width:8px;height:8px;border-radius:50%;background:<?= $custom ? 'var(--am)' : 'var(--di)' ?>;flex-shrink:0"></span>
                      <div>
                        <div style="font-size:13px;font-weight:500;color:var(--tx)"><?= htmlspecialchars($label) ?></div>
                        <div style="font-size:11px;color:var(--di)">
                          <?= $custom ? 'Custom · ' . date('M j', $tpl->getUpdatedAt($tn)) : 'Default' ?>
                        </div>
                      </div>
                    </a>
                <?php endforeach; ?>
              </div>
              <div class="card" style="margin-top:14px">
                <div class="ct" style="margin-bottom:10px">Variables</div>
                <div style="font-size:12px;color:var(--di);line-height:2">
                  <?php foreach (\EmailTemplates::VARS[$editing] as $v): ?>
                      <code
                        style="background:var(--bg3);padding:2px 6px;border-radius:4px;margin:2px;display:inline-block;color:var(--am)"><?= htmlspecialchars($v) ?></code>
                  <?php endforeach; ?>
                </div>
                <div style="font-size:11px;color:var(--di);margin-top:8px">Use these in subject and body. They are replaced
                  when the email is sent.</div>
              </div>
            </div>

            <!-- Right: editor -->
            <div>
              <div class="card">
                <div class="ct" style="margin-bottom:16px">
                  Editing: <?= htmlspecialchars(\EmailTemplates::LABELS[$editing]) ?>
                  <?php if ($tpl->isCustom($editing)): ?>
                      <span class="badge bg-am" style="margin-left:8px">Custom</span>
                  <?php else: ?>
                      <span class="badge" style="margin-left:8px;background:var(--bg3);color:var(--di)">Default</span>
                  <?php endif; ?>
                </div>
                <form method="POST">
                  <?= csrf_field_admin() ?>
                  <input type="hidden" name="act" value="save_email_template">
                  <input type="hidden" name="tpl_name" value="<?= htmlspecialchars($editing) ?>">
                  <div class="fg">
                    <label>Subject line</label>
                    <input type="text" name="tpl_subject" value="<?= htmlspecialchars($tpl->getSubject($editing)) ?>"
                      required>
                  </div>
                  <div class="fg">
                    <label>Email body HTML <span style="font-size:11px;color:var(--di)">(inner content only — the
                        header/footer wrapper is added automatically)</span></label>
                    <textarea name="tpl_body" rows="14" style="font-family:monospace;font-size:12px;resize:vertical"
                      required><?= htmlspecialchars($tpl->getBody($editing)) ?></textarea>
                  </div>
                  <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <button class="btn bp" type="submit">Save template</button>
                    <button class="btn bt" type="button" onclick="previewTpl()">Preview email</button>
                    <?php if ($tpl->isCustom($editing)): ?>
                        <form method="POST" style="display:inline">
                          <?= csrf_field_admin() ?>
                          <input type="hidden" name="act" value="reset_email_template">
                          <input type="hidden" name="tpl_name" value="<?= htmlspecialchars($editing) ?>">
                          <button class="btn bs" type="submit" onclick="return confirm('Reset to default template?')"
                            style="color:var(--er)">Reset to default</button>
                        </form>
                    <?php endif; ?>
                  </div>
                </form>
              </div>

              <!-- Live preview -->
              <div class="card" style="margin-top:14px">
                <div class="ct" style="margin-bottom:12px">Preview <span style="font-size:11px;color:var(--di)">(uses sample
                    data)</span></div>
                <iframe id="tpl-preview" src="?page=email_templates&t=<?= urlencode($editing) ?>&preview=1"
                  style="width:100%;height:480px;border:none;border-radius:8px;background:#f8fafc"></iframe>
              </div>
            </div>
          </div>

          <?php if (($_GET['preview'] ?? '') === '1'):
            require_once YUGA_ROOT . '/core/EmailTemplates.php';
            $tpl2 = new EmailTemplates(YUGA_ROOT . '/data');
            $t = $_GET['t'] ?? 'welcome';
            if (!in_array($t, $tpl2->names()))
              $t = 'welcome';
            // If POST with inline preview, use the submitted body instead of saved one
            if (!empty($_POST['tpl_body']) && !empty($_POST['tpl_name'])) {
              // Temporarily use submitted body for preview
              $plat = $config['platform_name'] ?? 'Yuga';
              $samples = [
                'welcome' => ['name' => 'Jane Smith', 'platform' => $plat, 'plan' => 'Pro', 'api_key' => 'yk_live_xxxxxxxxxxxxxxxxxxx', 'portal' => '#', 'docs' => '#'],
                'usage_warning' => ['name' => 'Jane Smith', 'platform' => $plat, 'used' => '8,200', 'limit' => '10,000', 'pct' => '82', 'plan' => 'Starter', 'upgrade' => '#'],
                'training_complete' => ['name' => 'Jane Smith', 'platform' => $plat, 'model' => 'yug10', 'steps' => '50,000', 'loss' => '1.4823'],
                'receipt' => ['name' => 'Jane Smith', 'platform' => $plat, 'plan' => 'Pro', 'amount' => 'Rs 999.00', 'gateway' => 'eSewa', 'ref' => 'ESW-20260325-0042', 'date' => date('d M Y H:i')],
                'suspended' => ['name' => 'Jane Smith', 'platform' => $plat, 'reason' => 'Payment overdue. Please update your billing information.'],
              ];
              $body = trim($_POST['tpl_body']);
              foreach ($samples[$t] ?? [] as $k => $v) {
                $body = str_replace('{' . $k . '}', htmlspecialchars($v), $body);
              }
              // Build wrapper inline
              echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:system-ui,sans-serif;background:#f8fafc;margin:0;padding:20px}.wrap{max-width:520px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1)}.header{background:#0a0f1e;padding:20px 28px;display:flex;align-items:center;gap:10px}.logo{width:32px;height:32px;background:#6366f1;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px}.logo-text{color:#fff;font-size:16px;font-weight:700}.body{padding:28px;color:#1e293b;line-height:1.6;font-size:14px}.footer{padding:16px 28px;background:#f8fafc;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0}h2{margin:0 0 14px;color:#0f172a}a{color:#6366f1}</style></head><body><div class="wrap"><div class="header"><div class="logo">Y</div><div class="logo-text">' . htmlspecialchars($plat) . '</div></div><div class="body">' . $body . '</div><div class="footer">' . htmlspecialchars($plat) . ' · Powered by Yuga v1.0</div></div></body></html>';
            } else {
              echo $tpl2->preview($t, $config['platform_name'] ?? 'Yuga');
            }
            exit;
          endif; ?>

          <script>
            function previewTpl() {
              const subj = document.querySelector('[name=tpl_subject]').value;
              const body = document.querySelector('[name=tpl_body]').value;
              const name = document.querySelector('[name=tpl_name]').value;
              const f = document.createElement('form');
              f.method = 'POST'; f.action = '?page=email_templates&t=' + encodeURIComponent(name) + '&preview=1&inline=1';
              f.target = 'tpl-preview';
              const csrf = document.createElement('input'); csrf.type = 'hidden'; csrf.name = '_csrf'; csrf.value = '<?= htmlspecialchars($csrf, ENT_QUOTES) ?>'; f.appendChild(csrf);
              const sb = document.createElement('input'); sb.type = 'hidden'; sb.name = 'tpl_subject'; sb.value = subj; f.appendChild(sb);
              const bd = document.createElement('input'); bd.type = 'hidden'; bd.name = 'tpl_body'; bd.value = body; f.appendChild(bd);
              const nm = document.createElement('input'); nm.type = 'hidden'; nm.name = 'tpl_name'; nm.value = name; f.appendChild(nm);
              document.body.appendChild(f); f.submit(); document.body.removeChild(f);
            }
          </script>

      <?php // ── PUSH NOTIFICATIONS ─────────────────────────────────────────────────
      elseif ($page === 'push'):
        require_once YUGA_ROOT . '/core/PushNotifier.php';
        $push_cfg = $config['push'] ?? [];
        $pn = new PushNotifier($push_cfg);
        $pn_stats = $pn->ready() ? $pn->getStats() : null;
        ?>
          <div class="tc">
            <!-- Settings + Broadcast -->
            <div>

              <div class="card">
                <div class="ct">OneSignal configuration</div>
                <p style="font-size:13px;color:var(--di);margin-bottom:16px">
                  OneSignal is free (unlimited web push, up to 10,000 subscribers).
                  <a href="https://onesignal.com" target="_blank" style="color:var(--pl)">Sign up at onesignal.com →</a>
                </p>
                <form method="POST">
                  <?= csrf_field_admin() ?>
                  <input type="hidden" name="act" value="save_push_settings">
                  <div class="fg"><label>OneSignal App ID</label><input type="text" name="push_app_id"
                      value="<?= htmlspecialchars($push_cfg['app_id'] ?? '') ?>"
                      placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"></div>
                  <div class="fg"><label>REST API Key</label><input type="password" name="push_api_key"
                      value="<?= htmlspecialchars($push_cfg['api_key'] ?? '') ?>" placeholder="Paste your REST API Key">
                  </div>
                  <div style="margin-top:8px;margin-bottom:16px">
                    <div style="font-size:13px;font-weight:600;color:var(--tx);margin-bottom:10px">Trigger push on:</div>
                    <?php foreach (\PushNotifier::EVENTS as $ev => $label):
                      $checked = !empty($push_cfg['events'][$ev]); ?>
                        <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;cursor:pointer;font-size:13px">
                          <input type="checkbox" name="push_event_<?= $ev ?>" <?= $checked ? 'checked' : '' ?>
                            style="accent-color:var(--pu)">
                          <?= htmlspecialchars($label) ?>
                        </label>
                    <?php endforeach; ?>
                  </div>
                  <button class="btn bp">Save push settings</button>
                </form>
              </div>

              <div class="card" style="margin-top:14px">
                <div class="ct">Send broadcast</div>
                <p style="font-size:13px;color:var(--di);margin-bottom:14px">Send a push notification to all subscribed
                  users right now.</p>
                <?php if (!$pn->ready()): ?>
                    <div
                      style="background:rgba(245,158,11,.1);border:1px solid var(--am);border-radius:8px;padding:12px;font-size:13px;color:var(--am)">
                      Configure OneSignal credentials above to enable broadcasting.
                    </div>
                <?php else: ?>
                    <form method="POST">
                      <?= csrf_field_admin() ?>
                      <input type="hidden" name="act" value="send_push_broadcast">
                      <div class="fg"><label>Title</label><input type="text" name="push_title"
                          placeholder="e.g. New feature available!" required></div>
                      <div class="fg"><label>Message</label><textarea name="push_message" rows="3"
                          placeholder="e.g. We just launched something awesome..." required></textarea></div>
                      <div class="fg"><label>Click URL <span
                            style="font-size:11px;color:var(--di)">(optional)</span></label><input type="url" name="push_url"
                          placeholder="https://yoursite.com/..."></div>
                      <button class="btn bp">Send to all subscribers</button>
                    </form>
                <?php endif; ?>
              </div>

            </div>

            <!-- Setup guide + stats -->
            <div>

              <?php if ($pn_stats && empty($pn_stats['error'])): ?>
                  <div class="card" style="margin-bottom:14px">
                    <div class="ct">Subscriber stats</div>
                    <div class="sg" style="grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
                      <div class="sc">
                        <div class="sv p"><?= number_format($pn_stats['players'] ?? 0) ?></div>
                        <div class="sl">Total subscribers</div>
                      </div>
                      <div class="sc">
                        <div class="sv t"><?= number_format($pn_stats['messagable_players'] ?? 0) ?></div>
                        <div class="sl">Reachable now</div>
                      </div>
                    </div>
                  </div>
              <?php endif; ?>

              <div class="card">
                <div class="ct">Setup guide</div>
                <div style="font-size:13px;color:var(--tx);line-height:1.8">
                  <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:14px">
                    <div
                      style="width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,var(--pu),var(--vi));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0">
                      1</div>
                    <div><strong>Create a OneSignal account</strong><br><span style="color:var(--di)">Go to onesignal.com →
                        New App → Web Push → enter your site URL.</span></div>
                  </div>
                  <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:14px">
                    <div
                      style="width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,var(--pu),var(--vi));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0">
                      2</div>
                    <div><strong>Copy App ID &amp; REST API Key</strong><br><span style="color:var(--di)">Settings → Keys
                        &amp; IDs → copy both and paste above.</span></div>
                  </div>
                  <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:14px">
                    <div
                      style="width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,var(--pu),var(--vi));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0">
                      3</div>
                    <div><strong>Add the SDK to your portal</strong><br><span style="color:var(--di)">In OneSignal dashboard
                        → Setup → Web → copy the SDK snippet and add it inside <code>&lt;head&gt;</code> of
                        <code>portal/index.php</code>.</span></div>
                  </div>
                  <div style="display:flex;gap:10px;align-items:flex-start">
                    <div
                      style="width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,var(--pu),var(--vi));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0">
                      4</div>
                    <div><strong>Users subscribe automatically</strong><br><span style="color:var(--di)">OneSignal shows a
                        native browser permission prompt when users visit your portal. No extra code needed.</span></div>
                  </div>
                </div>
              </div>

              <div class="card" style="margin-top:14px">
                <div class="ct">Event triggers</div>
                <p style="font-size:13px;color:var(--di);margin-bottom:12px">These events automatically send a push to all
                  subscribers when they fire — no manual action needed.</p>
                <table class="tbl">
                  <thead>
                    <tr>
                      <th>Event</th>
                      <th>Fires when</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach (\PushNotifier::EVENTS as $ev => $label):
                      $on = !empty($push_cfg['events'][$ev]); ?>
                        <tr>
                          <td style="font-weight:500"><?= htmlspecialchars($label) ?></td>
                          <td style="color:var(--di);font-size:12px"><?= match ($ev) {
                            'new_subscriber' => 'Subscriber signs up',
                            'payment' => 'Payment is confirmed',
                            'training_complete' => 'Model training finishes',
                            'api_limit_warning' => 'Subscriber hits 80% quota',
                            'api_limit_exceeded' => 'Subscriber hits 100% quota',
                            default => ''
                          } ?></td>
                          <td><span class="pill <?= $on ? 'pg' : 'pz' ?>"><?= $on ? 'On' : 'Off' ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

            </div>
          </div>

      <?php // ── BLOG ──────────────────────────────────────────────────────────────
      elseif ($page === 'blog'):
        require_once YUGA_ROOT . '/core/Blog.php';
        $blog = new Blog(YUGA_ROOT . '/data');
        $editing = isset($_GET['edit']) ? $blog->get($_GET['edit']) : null;
        if (isset($_GET['new']))
          $editing = [];
        $all_posts = $blog->list();
        ?>
          <div class="tc">
            <div>
              <div class="card">
                <div class="ct">Posts <span class="ex"><a href="?page=blog&new=1" class="btn bp bs">+ New post</a></span>
                </div>
                <?php if (empty($all_posts)): ?>
                    <p style="font-size:13px;color:var(--di)">No posts yet. <a href="?page=blog&new=1"
                        style="color:var(--pl)">Create your first post →</a></p>
                <?php else: ?>
                    <table class="tbl">
                      <thead>
                        <tr>
                          <th>Title</th>
                          <th>Status</th>
                          <th>Date</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($all_posts as $p): ?>
                            <tr>
                              <td><a href="?page=blog&edit=<?= urlencode($p['id']) ?>"
                                  style="color:var(--pl);font-weight:500"><?= htmlspecialchars($p['title'] ?: '(untitled)') ?></a>
                                <?php if ($p['tags']): ?><br><small
                                      style="color:var(--di)"><?= htmlspecialchars(implode(', ', $p['tags'])) ?></small><?php endif; ?>
                              </td>
                              <td><span class="pill <?= $p['status'] === 'published' ? 'pg' : 'pz' ?>"><?= $p['status'] ?></span>
                              </td>
                              <td style="color:var(--di)"><?= date('M j, Y', $p['created_at']) ?></td>
                              <td style="display:flex;gap:6px">
                                <a href="?page=blog&edit=<?= urlencode($p['id']) ?>" class="btn bt bs">Edit</a>
                                <form method="POST" style="display:inline"><input type="hidden" name="act"
                                    value="blog_delete"><input type="hidden" name="post_id"
                                    value="<?= htmlspecialchars($p['id']) ?>"><button class="btn bs" style="color:var(--er)"
                                    onclick="return confirm('Delete this post?')">Del</button></form>
                              </td>
                            </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                <?php endif; ?>
              </div>
            </div>

            <div>
              <?php if ($editing !== null): ?>
                  <div class="card">
                    <div class="ct">
                      <?= empty($editing['id']) ? 'New post' : 'Edit: ' . htmlspecialchars($editing['title'] ?? '') ?>
                    </div>
                    <form method="POST">
                      <?= csrf_field_admin() ?>
                      <input type="hidden" name="act" value="blog_save">
                      <input type="hidden" name="post_id" value="<?= htmlspecialchars($editing['id'] ?? '') ?>">
                      <div class="fg"><label>Title</label><input type="text" name="title"
                          value="<?= htmlspecialchars($editing['title'] ?? '') ?>" required></div>
                      <div class="fr">
                        <div class="fg"><label>Slug (auto-generated if blank)</label><input type="text" name="slug"
                            value="<?= htmlspecialchars($editing['slug'] ?? '') ?>" placeholder="my-post-title"></div>
                        <div class="fg"><label>Author</label><input type="text" name="author"
                            value="<?= htmlspecialchars($editing['author'] ?? 'Admin') ?>"></div>
                      </div>
                      <div class="fg"><label>Excerpt <span style="font-size:11px;color:var(--di)">(shown in
                            listing)</span></label>
                        <textarea name="excerpt" rows="2"><?= htmlspecialchars($editing['excerpt'] ?? '') ?></textarea>
                      </div>
                      <div class="fg"><label>Body (HTML supported)</label>
                        <textarea name="body" rows="16"
                          style="font-family:monospace;font-size:12px;resize:vertical"><?= htmlspecialchars($editing['body'] ?? '') ?></textarea>
                      </div>
                      <div class="fr">
                        <div class="fg"><label>Tags <span style="font-size:11px;color:var(--di)">(comma
                              separated)</span></label>
                          <input type="text" name="tags" value="<?= htmlspecialchars(implode(', ', $editing['tags'] ?? [])) ?>"
                            placeholder="AI, tutorial, news">
                        </div>
                        <div class="fg"><label>Status</label>
                          <select name="status">
                            <option value="draft" <?= ($editing['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= ($editing['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published
                            </option>
                          </select>
                        </div>
                      </div>
                      <button class="btn bp">Save post</button>
                      <a href="?page=blog" class="btn bt" style="margin-left:8px">Cancel</a>
                    </form>
                  </div>
              <?php else: ?>
                  <div class="card" style="text-align:center;padding:40px">
                    <div style="font-size:40px;margin-bottom:12px">✍️</div>
                    <div style="font-size:15px;font-weight:600;color:var(--tx);margin-bottom:8px">Select a post to edit</div>
                    <div style="font-size:13px;color:var(--di);margin-bottom:20px">or create a new one</div>
                    <a href="?page=blog&new=1" class="btn bp">+ New post</a>
                  </div>
              <?php endif; ?>
            </div>
          </div>

      <?php // ── TICKETS ───────────────────────────────────────────────────────────
      elseif ($page === 'tickets'):
        require_once YUGA_ROOT . '/core/Tickets.php';
        $tk = new Tickets(YUGA_ROOT . '/data');
        $view_id = $_GET['view'] ?? '';
        $view_ticket = $view_id ? $tk->get($view_id) : null;
        $view_msgs = $view_id ? $tk->getMessages($view_id) : [];
        $filter_status = $_GET['status'] ?? '';
        $ticket_list = $tk->list($filter_status);
        $counts = $tk->countByStatus();
        ?>
          <div class="tc">
            <div>
              <div class="card">
                <div class="ct">Support tickets</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
                  <a href="?page=tickets" class="btn bt bs <?= !$filter_status ? 'bg-pu' : '' ?>"
                    style="<?= !$filter_status ? 'color:#fff' : '' ?>">All (<?= array_sum($counts) ?>)</a>
                  <?php foreach (\Tickets::STATUSES as $s): ?>
                      <a href="?page=tickets&status=<?= $s ?>" class="btn bt bs"
                        style="<?= $filter_status === $s ? 'background:var(--pu);color:#fff' : '' ?>">
                        <?= ucfirst(str_replace('_', ' ', $s)) ?> (<?= $counts[$s] ?? 0 ?>)
                      </a>
                  <?php endforeach; ?>
                </div>
                <?php if (empty($ticket_list)): ?>
                    <p style="font-size:13px;color:var(--di)">No
                      tickets<?= $filter_status ? " with status '$filter_status'" : '' ?>.</p>
                <?php else: ?>
                    <table class="tbl">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Subject</th>
                          <th>From</th>
                          <th>Priority</th>
                          <th>Status</th>
                          <th>Updated</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($ticket_list as $t): ?>
                            <tr style="<?= ($t['id'] === $view_id) ? 'background:rgba(99,102,241,.08)' : '' ?>">
                              <td style="font-family:monospace;font-size:12px;color:var(--te)"><?= htmlspecialchars($t['id']) ?>
                              </td>
                              <td><a href="?page=tickets&view=<?= urlencode($t['id']) ?>"
                                  style="color:var(--pl)"><?= htmlspecialchars($t['subject']) ?></a>
                                <br><small style="color:var(--di)"><?= $t['replies'] ?>
                                  repl<?= $t['replies'] === 1 ? 'y' : 'ies' ?></small>
                              </td>
                              <td><?= htmlspecialchars($t['name']) ?><br><small
                                  style="color:var(--di)"><?= htmlspecialchars($t['email']) ?></small></td>
                              <td><span
                                  class="pill <?= match ($t['priority'] ?? 'normal') { 'urgent' => 'pa', 'high' => 'pa', 'normal' => 'pp', default => 'pz'} ?>"><?= $t['priority'] ?></span>
                              </td>
                              <td><span
                                  class="pill <?= match ($t['status']) { 'open' => 'pa', 'in_progress' => 'pp', 'resolved' => 'pg', default => 'pz'} ?>"><?= str_replace('_', ' ', $t['status']) ?></span>
                              </td>
                              <td style="color:var(--di);font-size:12px"><?= date('M j H:i', $t['updated_at']) ?></td>
                              <td><a href="?page=tickets&view=<?= urlencode($t['id']) ?>" class="btn bt bs">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                <?php endif; ?>
              </div>
            </div>

            <div>
              <?php if ($view_ticket): ?>
                  <div class="card">
                    <div class="ct">
                      [<?= htmlspecialchars($view_ticket['id']) ?>] <?= htmlspecialchars($view_ticket['subject']) ?>
                      <div style="float:right;display:flex;gap:8px">
                        <form method="POST" style="display:flex;gap:6px;align-items:center">
                          <?= csrf_field_admin() ?>
                          <input type="hidden" name="act" value="ticket_status">
                          <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($view_ticket['id']) ?>">
                          <select name="status" onchange="this.form.submit()"
                            style="background:var(--bg3);border:1px solid var(--bd);border-radius:6px;padding:4px 8px;color:var(--tx);font-size:12px">
                            <?php foreach (\Tickets::STATUSES as $s): ?>
                                <option value="<?= $s ?>" <?= $view_ticket['status'] === $s ? 'selected' : '' ?>>
                                  <?= ucfirst(str_replace('_', ' ', $s)) ?>
                                </option>
                            <?php endforeach; ?>
                          </select>
                        </form>
                        <form method="POST"><input type="hidden" name="act" value="ticket_delete"><input type="hidden"
                            name="ticket_id" value="<?= htmlspecialchars($view_ticket['id']) ?>"><button class="btn bs"
                            style="color:var(--er)" onclick="return confirm('Delete ticket?')">Delete</button></form>
                      </div>
                    </div>
                    <!-- Messages thread -->
                    <div style="max-height:340px;overflow-y:auto;margin-bottom:16px">
                      <?php foreach ($view_msgs as $msg): ?>
                          <div
                            style="margin-bottom:12px;padding:12px;border-radius:8px;background:<?= $msg['type'] === 'admin' ? 'rgba(99,102,241,.1)' : 'var(--bg3)' ?>;border:1px solid <?= $msg['type'] === 'admin' ? 'rgba(99,102,241,.2)' : 'var(--bd)' ?>">
                            <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                              <span
                                style="font-size:12px;font-weight:600;color:<?= $msg['type'] === 'admin' ? 'var(--pl)' : 'var(--te)' ?>">
                                <?= $msg['type'] === 'admin' ? 'Support Team' : '' . htmlspecialchars($msg['author_name']) ?>
                              </span>
                              <span style="font-size:11px;color:var(--di)"><?= date('M j H:i', $msg['created_at']) ?></span>
                            </div>
                            <div style="font-size:13px;color:var(--tx);white-space:pre-wrap">
                              <?= htmlspecialchars($msg['message']) ?>
                            </div>
                          </div>
                      <?php endforeach; ?>
                    </div>
                    <!-- Reply form -->
                    <form method="POST">
                      <?= csrf_field_admin() ?>
                      <input type="hidden" name="act" value="ticket_reply">
                      <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($view_ticket['id']) ?>">
                      <div class="fg"><label>Reply to <?= htmlspecialchars($view_ticket['name']) ?>
                          (<?= htmlspecialchars($view_ticket['email']) ?>)</label>
                        <textarea name="reply_message" rows="4" placeholder="Type your reply..." required></textarea>
                      </div>
                      <button class="btn bp">Send reply</button>
                    </form>
                  </div>
              <?php else: ?>
                  <div class="card" style="text-align:center;padding:40px">
                    <div style="font-size:40px;margin-bottom:12px">🎫</div>
                    <div style="font-size:15px;font-weight:600;color:var(--tx);margin-bottom:8px">Select a ticket</div>
                    <div style="font-size:13px;color:var(--di)">Click any ticket on the left to view and reply</div>
                  </div>
              <?php endif; ?>
            </div>
          </div>

      <?php // ── CONTACT INBOX ─────────────────────────────────────────────────────
      elseif ($page === 'contacts'):
        require_once YUGA_ROOT . '/core/ContactStore.php';
        $cs = new ContactStore(YUGA_ROOT . '/data');
        $contacts = $cs->list();
        $unread = $cs->unreadCount();
        $view_c = isset($_GET['view']) ? null : null;
        foreach ($contacts as $c) {
          if ($c['id'] === ($_GET['view'] ?? '')) {
            $view_c = $c;
            break;
          }
        }
        if ($view_c && !$view_c['read']) {
          $cs->markRead($view_c['id']);
          $view_c['read'] = true;
        }
        ?>
          <div class="tc">
            <div>
              <div class="card">
                <div class="ct">Contact inbox <?php if ($unread > 0): ?><span class="badge bg-am"
                        style="margin-left:8px"><?= $unread ?> unread</span><?php endif; ?></div>
                <?php if (empty($contacts)): ?>
                    <p style="font-size:13px;color:var(--di)">No contact submissions yet.</p>
                <?php else: ?>
                    <table class="tbl">
                      <thead>
                        <tr>
                          <th>From</th>
                          <th>Subject</th>
                          <th>Date</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($contacts as $c): ?>
                            <tr style="<?= !$c['read'] ? 'font-weight:600' : '' ?>">
                              <td><?= htmlspecialchars($c['name']) ?><br><small
                                  style="color:var(--di);font-weight:400"><?= htmlspecialchars($c['email']) ?></small></td>
                              <td><a href="?page=contacts&view=<?= urlencode($c['id']) ?>"
                                  style="color:var(--pl)"><?= htmlspecialchars($c['subject']) ?></a>
                                <?php if (!$c['read']): ?><span class="badge bg-am"
                                      style="margin-left:6px;font-size:10px">New</span><?php endif; ?></td>
                              <td style="color:var(--di)"><?= date('M j H:i', $c['created_at']) ?></td>
                              <td>
                                <form method="POST" style="display:inline"><input type="hidden" name="act"
                                    value="contact_delete"><input type="hidden" name="contact_id"
                                    value="<?= htmlspecialchars($c['id']) ?>"><button class="btn bs" style="color:var(--er)"
                                    onclick="return confirm('Delete?')">Del</button></form>
                              </td>
                            </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                <?php endif; ?>
              </div>
            </div>
            <div>
              <?php if ($view_c): ?>
                  <div class="card">
                    <div class="ct"><?= htmlspecialchars($view_c['subject']) ?></div>
                    <div
                      style="margin-bottom:14px;padding:12px;background:var(--bg3);border-radius:8px;font-size:13px;color:var(--di)">
                      <strong style="color:var(--tx)"><?= htmlspecialchars($view_c['name']) ?></strong> ·
                      <?= htmlspecialchars($view_c['email']) ?> · <?= date('d M Y H:i', $view_c['created_at']) ?>
                    </div>
                    <div style="font-size:14px;color:var(--tx);white-space:pre-wrap;line-height:1.7">
                      <?= htmlspecialchars($view_c['message']) ?>
                    </div>
                    <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--bd)">
                      <a href="mailto:<?= htmlspecialchars($view_c['email']) ?>?subject=Re: <?= rawurlencode($view_c['subject']) ?>"
                        class="btn bp">Reply via email</a>
                      <form method="POST" style="display:inline;margin-left:8px"><input type="hidden" name="act"
                          value="contact_delete"><input type="hidden" name="contact_id"
                          value="<?= htmlspecialchars($view_c['id']) ?>"><button class="btn bs" style="color:var(--er)"
                          onclick="return confirm('Delete?')">Delete</button></form>
                    </div>
                  </div>
              <?php else: ?>
                  <div class="card" style="text-align:center;padding:40px">
                    <div style="font-size:40px;margin-bottom:12px">📬</div>
                    <div style="font-size:15px;font-weight:600;color:var(--tx);margin-bottom:8px">Select a message</div>
                    <div style="font-size:13px;color:var(--di)">Click any contact submission to read it</div>
                  </div>
              <?php endif; ?>
            </div>
          </div>

      <?php // ── NEPALI TRAINING ───────────────────────────────────────────────────
      elseif ($page === 'nepali'):
        require_once YUGA_ROOT . '/core/NepaliPipeline.php';
        $sources = NepaliPipeline::listSources();
        $groups = NepaliPipeline::listGroups();
        ?>
          <div class="tc">
            <!-- Source catalog -->
            <div>
              <div class="card">
                <div class="ct">Nepali content sources
                  <span style="float:right;font-size:11px;color:var(--di)"><?= count($sources) ?> sources ·
                    <?= count($groups) ?> groups</span>
                </div>
                <p style="font-size:13px;color:var(--di);margin-bottom:14px">Curated Nepali + English sources. Select
                  sources or a group, pick your model, and start training.</p>

                <!-- Group quick-run -->
                <div style="margin-bottom:18px">
                  <div
                    style="font-size:12px;font-weight:600;color:var(--mu);margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em">
                    Quick groups</div>
                  <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <?php foreach ($groups as $gk => $g): ?>
                        <button onclick="runNepaliGroup('<?= htmlspecialchars($gk) ?>')"
                          style="background:rgba(99,102,241,.12);border:1px solid rgba(99,102,241,.25);border-radius:8px;padding:6px 12px;font-size:12px;color:var(--pl);cursor:pointer;transition:.15s"
                          onmouseover="this.style.background='rgba(99,102,241,.25)'"
                          onmouseout="this.style.background='rgba(99,102,241,.12)'">
                          <?= htmlspecialchars(str_replace('_', ' ', $gk)) ?> (<?= $g['count'] ?>)
                        </button>
                    <?php endforeach; ?>
                  </div>
                </div>

                <!-- Source table -->
                <table class="tbl">
                  <thead>
                    <tr>
                      <th>Source</th>
                      <th>Language</th>
                      <th>Pages</th>
                      <th>Tags</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($sources as $key => $src): ?>
                        <tr>
                          <td>
                            <div style="font-weight:500;color:var(--tx)"><?= htmlspecialchars($src['label']) ?></div>
                            <div style="font-size:11px;color:var(--di);font-family:monospace">
                              <?= htmlspecialchars($src['url']) ?>
                            </div>
                          </td>
                          <td><span class="pill <?= $src['lang'] === 'np' ? 'pp' : ($src['lang'] === 'en' ? 'pg' : 'pt') ?>">
                              <?= $src['lang'] === 'np' ? 'नेपाली' : ($src['lang'] === 'en' ? 'English' : 'Both') ?>
                            </span></td>
                          <td style="color:var(--di)"><?= $src['pages'] ?></td>
                          <td><?php foreach ($src['tags'] as $t): ?>
                                <span
                                  style="background:var(--bg3);color:var(--di);padding:1px 6px;border-radius:4px;font-size:10px;margin-right:2px"><?= htmlspecialchars($t) ?></span>
                            <?php endforeach; ?>
                          </td>
                          <td><button onclick="runNepaliSource('<?= htmlspecialchars($key) ?>')"
                              class="btn bt bs">Train</button></td>
                        </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Right panel: model selector + live output -->
            <div>
              <div class="card">
                <div class="ct">Model</div>
                <div class="fg">
                  <label>Train into model</label>
                  <select id="np-model"
                    style="width:100%;background:var(--bg3);border:1px solid var(--bd);border-radius:8px;padding:9px 12px;color:var(--tx);font-size:13px">
                    <?php foreach ($models as $mn): ?>
                        <option value="<?= htmlspecialchars($mn) ?>" <?= $mn === $sel_model ? 'selected' : '' ?>>
                          <?= htmlspecialchars($mn) ?>
                        </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <p style="font-size:12px;color:var(--di);margin-top:8px">Tip: create a dedicated model (e.g.
                  <code>nepal-ai</code>) for Nepali training to keep it separate from your English model.
                </p>
              </div>

              <div class="card" style="margin-top:14px">
                <div class="ct">Training output</div>
                <div id="np-output"
                  style="background:#000;border-radius:8px;padding:14px;font-family:monospace;font-size:12px;min-height:200px;max-height:480px;overflow-y:auto;border:1px solid var(--bd)">
                  <span style="color:var(--di)">Select a source or group above to begin training…</span>
                </div>
                <div style="margin-top:10px;display:flex;gap:8px">
                  <button id="np-stop-btn" onclick="stopNepali()" class="btn bs"
                    style="display:none;color:var(--re)">Stop</button>
                  <span id="np-status" style="font-size:12px;color:var(--di);align-self:center"></span>
                </div>
              </div>

              <div class="card" style="margin-top:14px">
                <div class="ct">Why Nepali training?</div>
                <div style="font-size:13px;color:var(--mu);line-height:1.8">
                  <p style="margin-bottom:8px">YugaLM uses a <strong style="color:var(--pl)">Unicode-aware word
                      tokenizer</strong> with full Devanagari support:</p>
                  <ul style="list-style:none;padding:0">
                    <li style="margin-bottom:6px"><span style="color:var(--te)">✓</span> Devanagari script (U+0900–U+097F)
                      handled natively</li>
                    <li style="margin-bottom:6px"><span style="color:var(--te)">✓</span> Nepali sentence stops (। ॥)
                      tokenized correctly</li>
                    <li style="margin-bottom:6px"><span style="color:var(--te)">✓</span> Bilingual vocab: 16,000 tokens
                      (Nepali + English)</li>
                    <li style="margin-bottom:6px"><span style="color:var(--te)">✓</span> Nepali + English stopwords filtered
                    </li>
                    <li style="margin-bottom:6px"><span style="color:var(--te)">✓</span> Devanagari numerals (०–९)
                      normalized to ASCII</li>
                    <li style="margin-bottom:6px"><span style="color:var(--am)">→</span> Train on both Nepali + English
                      sources for best bilingual results</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <script>
            let npSSE = null;
            function npLine(msg, color) {
              const out = document.getElementById('np-output');
              const d = document.createElement('div');
              d.style.cssText = 'margin-bottom:3px;color:' + (color || '#a5b4fc');
              d.textContent = '[' + new Date().toLocaleTimeString() + '] ' + msg;
              out.appendChild(d); out.scrollTop = out.scrollHeight;
            }
            function npStart(mode, key) {
              const model = document.getElementById('np-model').value;
              const out = document.getElementById('np-output');
              out.innerHTML = '';
              document.getElementById('np-stop-btn').style.display = 'inline-block';
              document.getElementById('np-status').textContent = 'Running…';
              if (npSSE) npSSE.close();
              const qs = '?page=nepali&stream=1&model=' + encodeURIComponent(model) + '&mode=' + encodeURIComponent(mode) + '&key=' + encodeURIComponent(key);
              npSSE = new EventSource(qs);
              const colors = { info: '#67e8f9', page: '#475569', ok: '#10b981', done: '#10b981', error: '#f43f5e', step: '#f59e0b' };
              npSSE.onmessage = function (e) {
                try {
                  const d = JSON.parse(e.data);
                  npLine(d.msg, colors[d.type] || '#a5b4fc');
                  if (d.type === 'done' || d.type === 'error') {
                    npSSE.close(); npSSE = null;
                    document.getElementById('np-stop-btn').style.display = 'none';
                    document.getElementById('np-status').textContent = d.type === 'done' ? 'Complete!' : 'Failed';
                  }
                } catch { }
              };
              npSSE.onerror = function () {
                npLine('Connection lost.', '#f43f5e');
                npSSE = null; document.getElementById('np-stop-btn').style.display = 'none';
                document.getElementById('np-status').textContent = 'Error';
              };
            }
            function runNepaliSource(key) { npStart('source', key); }
            function runNepaliGroup(key) { npStart('group', key); }
            function stopNepali() {
              if (npSSE) { npSSE.close(); npSSE = null; }
              document.getElementById('np-stop-btn').style.display = 'none';
              document.getElementById('np-status').textContent = 'Stopped';
              npLine('Training stopped.', '#f59e0b');
            }
          </script>

      <?php // ── WORKFLOWS ─────────────────────────────────────────────────────────
      elseif ($page === 'workflows'):
        require_once YUGA_ROOT . '/core/WorkflowEngine.php';
        require_once YUGA_ROOT . '/core/NepaliPipeline.php';
        $wfe = new WorkflowEngine(YUGA_ROOT . '/data');
        $wf_list = $wfe->list();
        $edit_wf = isset($_GET['edit']) ? $wfe->get($_GET['edit']) : null;
        $run_logs = $wfe->recentRuns('', 10);
        $step_types = WorkflowEngine::stepTypes();
        $np_sources = NepaliPipeline::listSources();
        $np_groups = NepaliPipeline::listGroups();
        ?>
          <div class="tc">
            <!-- Left: workflow list -->
            <div>
              <div class="card">
                <div class="ct">Workflows <span class="ex"><a href="?page=workflows&new=1" class="btn bp bs">+
                      New</a></span></div>
                <?php if (empty($wf_list)): ?>
                    <p style="font-size:13px;color:var(--di);margin-bottom:16px">No workflows yet. Start from a template or <a
                        href="?page=workflows&new=1" style="color:var(--pl)">build from scratch →</a></p>
                    <div
                      style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--di);margin-bottom:10px">
                      Starter templates</div>
                    <div style="display:flex;flex-direction:column;gap:8px">
                      <?php
                      $templates = [
                        ['name' => 'Daily site refresh', 'icon' => '🌐', 'desc' => 'Crawl your homepage daily and keep the model current', 'steps' => [['type' => 'crawl_url', 'label' => 'Crawl website', 'url' => '', 'max_pages' => 20], ['type' => 'set_default', 'label' => 'Set as default model']]],
                        ['name' => 'Nepal news daily', 'icon' => '📰', 'desc' => 'Train on Nepali news sources every morning', 'steps' => [['type' => 'crawl_group', 'label' => 'Crawl Nepali source group', 'group' => 'news'], ['type' => 'notify_email', 'label' => 'Send completion email', 'to' => '', 'subject' => 'Nepal model updated', 'message' => 'Your Yuga Nepal news training has completed.']]],
                        ['name' => 'Text knowledge base', 'icon' => '📄', 'desc' => 'Train on a fixed block of curated text then set as default', 'steps' => [['type' => 'train_text', 'label' => 'Train on pasted text', 'text' => 'Paste your knowledge base content here...', 'steps' => 30000], ['type' => 'set_default', 'label' => 'Set as default model']]],
                        ['name' => 'Full pipeline', 'icon' => '⚡', 'desc' => 'Crawl site + Nepali sources + email report', 'steps' => [['type' => 'crawl_url', 'label' => 'Crawl website', 'url' => '', 'max_pages' => 30], ['type' => 'sleep', 'label' => 'Wait (pause between steps)', 'seconds' => 5], ['type' => 'crawl_group', 'label' => 'Crawl Nepali source group', 'group' => 'news'], ['type' => 'notify_email', 'label' => 'Send completion email', 'to' => '', 'subject' => 'Full pipeline complete', 'message' => 'All training steps finished.']]],
                      ];
                      foreach ($templates as $tpl): ?>
                          <form method="POST" style="display:contents">
                            <?= csrf_field_admin() ?>
                            <input type="hidden" name="act" value="workflow_create">
                            <input type="hidden" name="wf_name" value="<?= htmlspecialchars($tpl['name']) ?>">
                            <input type="hidden" name="wf_model" value="<?= htmlspecialchars($sel_model) ?>">
                            <input type="hidden" name="steps_json" value="<?= htmlspecialchars(json_encode($tpl['steps'])) ?>">
                            <button type="submit"
                              style="display:flex;align-items:flex-start;gap:12px;background:var(--bg3);border:1px solid var(--bd);border-radius:10px;padding:12px 14px;text-align:left;width:100%;cursor:pointer;transition:.15s;color:inherit"
                              onmouseover="this.style.borderColor='rgba(99,102,241,.4)'"
                              onmouseout="this.style.borderColor='var(--bd)'">
                              <span style="font-size:22px;flex-shrink:0;margin-top:1px"><?= $tpl['icon'] ?></span>
                              <div>
                                <div style="font-size:13px;font-weight:600;color:var(--tx);margin-bottom:3px">
                                  <?= htmlspecialchars($tpl['name']) ?>
                                </div>
                                <div style="font-size:12px;color:var(--di)"><?= htmlspecialchars($tpl['desc']) ?> ·
                                  <?= count($tpl['steps']) ?> steps
                                </div>
                              </div>
                              <span style="margin-left:auto;font-size:11px;color:var(--di);align-self:center;flex-shrink:0">Use
                                →</span>
                            </button>
                          </form>
                      <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($wf_list as $wf): ?>
                        <div
                          style="padding:12px;border-bottom:1px solid var(--bd);<?= isset($_GET['edit']) && $_GET['edit'] === $wf['id'] ? 'background:rgba(99,102,241,.06)' : '' ?>">
                          <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                            <div>
                              <div style="font-weight:500;color:var(--tx);font-size:13px"><?= htmlspecialchars($wf['name']) ?></div>
                              <div style="font-size:11px;color:var(--di)">
                                Model: <?= htmlspecialchars($wf['model']) ?> · <?= count($wf['steps']) ?> steps
                                <?= $wf['last_run_at'] ? ' · Last run: ' . date('M j H:i', $wf['last_run_at']) : '' ?>
                              </div>
                            </div>
                            <div style="display:flex;gap:6px;flex-shrink:0">
                              <span class="pill <?= $wf['enabled'] ? 'pg' : 'pz' ?>"><?= $wf['enabled'] ? 'On' : 'Off' ?></span>
                              <a href="?page=workflows&edit=<?= urlencode($wf['id']) ?>" class="btn bt bs">Edit</a>
                              <button onclick="runWorkflow('<?= htmlspecialchars($wf['id'], ENT_QUOTES) ?>')"
                                class="btn bp bs">Run</button>
                              <form method="POST" style="display:inline"><?= csrf_field_admin() ?><input type="hidden" name="act"
                                  value="workflow_delete"><input type="hidden" name="wf_id"
                                  value="<?= htmlspecialchars($wf['id']) ?>"><button class="btn bs" style="color:var(--re)"
                                  onclick="return confirm('Delete this workflow?')">Del</button></form>
                            </div>
                          </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
              </div>

              <div class="card" style="margin-top:14px">
                <div class="ct">Recent runs</div>
                <?php if (empty($run_logs)): ?>
                    <p style="font-size:12px;color:var(--di)">No runs yet.</p>
                <?php else: ?>
                    <table class="tbl">
                      <thead>
                        <tr>
                          <th>Workflow</th>
                          <th>Status</th>
                          <th>When</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($run_logs as $r):
                          $rwf = $wfe->get($r['workflow_id']); ?>
                            <tr>
                              <td style="font-size:12px"><?= htmlspecialchars($rwf['name'] ?? $r['workflow_id']) ?></td>
                              <td><span class="pill <?= $r['status'] === 'completed' ? 'pg' : 'pa' ?>"><?= $r['status'] ?></span>
                              </td>
                              <td style="color:var(--di);font-size:11px"><?= date('M j H:i', $r['started_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                <?php endif; ?>
              </div>
            </div>

            <!-- Right: editor + live output -->
            <div>
              <?php if ($edit_wf !== null || isset($_GET['new'])): ?>
                  <div class="card">
                    <div class="ct"><?= $edit_wf ? 'Edit: ' . htmlspecialchars($edit_wf['name']) : 'New workflow' ?></div>
                    <form method="POST" id="wf-form">
                      <?= csrf_field_admin() ?>
                      <input type="hidden" name="act" value="workflow_create">
                      <input type="hidden" name="wf_id" value="<?= htmlspecialchars($edit_wf['id'] ?? '') ?>">
                      <div class="fr">
                        <div class="fg"><label>Workflow name</label><input type="text" name="wf_name"
                            value="<?= htmlspecialchars($edit_wf['name'] ?? '') ?>" required
                            placeholder="e.g. Nepal AI daily training"></div>
                        <div class="fg"><label>Model</label>
                          <select name="wf_model">
                            <?php foreach ($models as $mn): ?>
                                <option value="<?= $mn ?>" <?= ($edit_wf['model'] ?? $sel_model) === $mn ? 'selected' : '' ?>>
                                  <?= $mn ?>
                                </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>

                      <!-- Step builder -->
                      <div style="margin-bottom:16px">
                        <div style="font-size:13px;font-weight:600;color:var(--tx);margin-bottom:10px">Steps</div>
                        <div id="wf-steps"></div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
                          <?php foreach ($step_types as $st => $stdef): ?>
                              <button type="button" onclick="addStep('<?= $st ?>')"
                                style="background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.2);border-radius:6px;padding:5px 10px;font-size:11px;color:var(--pl);cursor:pointer">
                                + <?= htmlspecialchars($stdef['label']) ?>
                              </button>
                          <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="steps_json" id="steps-json"
                          value="<?= htmlspecialchars(json_encode($edit_wf['steps'] ?? [])) ?>">
                      </div>
                      <button class="btn bp" type="submit">Save workflow</button>
                      <a href="?page=workflows" class="btn bt" style="margin-left:8px">Cancel</a>
                    </form>
                  </div>
              <?php else: ?>
                  <div class="card" style="text-align:center;padding:40px">
                    <div style="font-size:36px;margin-bottom:12px">⚙️</div>
                    <div style="font-size:15px;font-weight:600;color:var(--tx);margin-bottom:8px">Select a workflow to edit
                    </div>
                    <div style="font-size:13px;color:var(--di);margin-bottom:20px">or create a new automation</div>
                    <a href="?page=workflows&new=1" class="btn bp">+ New workflow</a>
                  </div>
              <?php endif; ?>

              <!-- Live run output -->
              <div class="card" style="margin-top:14px">
                <div class="ct">Run output</div>
                <div id="wf-output"
                  style="background:#000;border-radius:8px;padding:14px;font-family:monospace;font-size:12px;min-height:160px;max-height:400px;overflow-y:auto;border:1px solid var(--bd)">
                  <span style="color:var(--di)">Click Run on any workflow to see live output…</span>
                </div>
                <div style="margin-top:8px;display:flex;gap:8px">
                  <button id="wf-stop-btn" onclick="stopWorkflow()" class="btn bs"
                    style="display:none;color:var(--re)">Stop</button>
                  <span id="wf-status" style="font-size:12px;color:var(--di);align-self:center"></span>
                </div>
              </div>
            </div>
          </div>

          <script>
            // ── Step builder ──────────────────────────────────────────────────
            const NP_SOURCES = <?= json_encode(array_map(fn($k, $s) => ['key' => $k, 'label' => $s['label']], array_keys($np_sources), $np_sources)) ?>;
            const NP_GROUPS = <?= json_encode(array_map(fn($k, $g) => ['key' => $k, 'label' => str_replace('_', ' ', $k)], array_keys($np_groups), $np_groups)) ?>;
            let wfSteps = <?= json_encode($edit_wf['steps'] ?? []) ?>;

            function renderSteps() {
              const cont = document.getElementById('wf-steps');
              cont.innerHTML = '';
              wfSteps.forEach((s, i) => {
                const d = document.createElement('div');
                d.style.cssText = 'background:var(--bg3);border:1px solid var(--bd);border-radius:8px;padding:10px;margin-bottom:8px;display:flex;gap:10px;align-items:flex-start';
                let fields = `<div style="flex:1"><div style="font-size:12px;font-weight:600;color:var(--pl);margin-bottom:6px">${i + 1}. ${s.label || s.type}</div>`;
                if (s.type === 'crawl_url') fields += `<input style="width:100%;background:var(--bg4);border:1px solid var(--bd);border-radius:5px;padding:5px 8px;color:var(--tx);font-size:11px" placeholder="URL" value="${s.url || ''}" oninput="wfSteps[${i}].url=this.value;syncJson()">`;
                if (s.type === 'crawl_nepali') fields += `<select style="background:var(--bg4);border:1px solid var(--bd);border-radius:5px;padding:5px 8px;color:var(--tx);font-size:11px" onchange="wfSteps[${i}].source=this.value;syncJson()">${NP_SOURCES.map(x => `<option value="${x.key}" ${s.source === x.key ? 'selected' : ''}>${x.label}</option>`).join('')}</select>`;
                if (s.type === 'crawl_group') fields += `<select style="background:var(--bg4);border:1px solid var(--bd);border-radius:5px;padding:5px 8px;color:var(--tx);font-size:11px" onchange="wfSteps[${i}].group=this.value;syncJson()">${NP_GROUPS.map(x => `<option value="${x.key}" ${s.group === x.key ? 'selected' : ''}>${x.label}</option>`).join('')}</select>`;
                if (s.type === 'train_text') fields += `<textarea style="width:100%;background:var(--bg4);border:1px solid var(--bd);border-radius:5px;padding:5px 8px;color:var(--tx);font-size:11px" rows="3" oninput="wfSteps[${i}].text=this.value;syncJson()">${s.text || ''}</textarea>`;
                if (s.type === 'sleep') fields += `<input type="number" style="width:80px;background:var(--bg4);border:1px solid var(--bd);border-radius:5px;padding:5px 8px;color:var(--tx);font-size:11px" placeholder="Seconds" value="${s.seconds || 5}" oninput="wfSteps[${i}].seconds=+this.value;syncJson()">`;
                if (s.type === 'notify_email') fields += `<input style="width:100%;background:var(--bg4);border:1px solid var(--bd);border-radius:5px;padding:5px 8px;color:var(--tx);font-size:11px;margin-bottom:4px" placeholder="Email" value="${s.to || ''}" oninput="wfSteps[${i}].to=this.value;syncJson()"><input style="width:100%;background:var(--bg4);border:1px solid var(--bd);border-radius:5px;padding:5px 8px;color:var(--tx);font-size:11px" placeholder="Subject" value="${s.subject || ''}" oninput="wfSteps[${i}].subject=this.value;syncJson()">`;
                fields += '</div>';
                fields += `<button type="button" onclick="removeStep(${i})" style="background:none;border:none;color:var(--di);cursor:pointer;font-size:16px;padding:0 4px;flex-shrink:0" title="Remove">×</button>`;
                d.innerHTML = fields; cont.appendChild(d);
              });
              syncJson();
            }
            function addStep(type) {
              const labels = <?= json_encode(array_map(fn($s) => $s['label'], $step_types)) ?>;
              wfSteps.push({ type, label: labels[type] || type });
              renderSteps();
            }
            function removeStep(i) { wfSteps.splice(i, 1); renderSteps(); }
            function syncJson() { document.getElementById('steps-json').value = JSON.stringify(wfSteps); }
            renderSteps();

            // ── Workflow SSE runner ───────────────────────────────────────────
            let wfSSE = null;
            function wfLine(msg, color) {
              const out = document.getElementById('wf-output');
              const d = document.createElement('div');
              d.style.cssText = 'margin-bottom:3px;color:' + (color || '#a5b4fc');
              d.textContent = '[' + new Date().toLocaleTimeString() + '] ' + msg;
              out.appendChild(d); out.scrollTop = out.scrollHeight;
            }
            function runWorkflow(wfId) {
              if (wfSSE) wfSSE.close();
              document.getElementById('wf-output').innerHTML = '';
              document.getElementById('wf-stop-btn').style.display = 'inline-block';
              document.getElementById('wf-status').textContent = 'Running…';
              const qs = '?page=workflows&stream=1&wf_id=' + encodeURIComponent(wfId);
              wfSSE = new EventSource(qs);
              const colors = { info: '#67e8f9', page: '#475569', ok: '#10b981', done: '#10b981', error: '#f43f5e', step: '#f59e0b' };
              wfSSE.onmessage = function (e) {
                try {
                  const d = JSON.parse(e.data);
                  wfLine(d.msg, colors[d.type] || '#a5b4fc');
                  if (d.type === 'done' || d.type === 'error') {
                    wfSSE.close(); wfSSE = null;
                    document.getElementById('wf-stop-btn').style.display = 'none';
                    document.getElementById('wf-status').textContent = d.type === 'done' ? 'Complete!' : 'Failed';
                  }
                } catch { }
              };
              wfSSE.onerror = function () {
                wfLine('Connection lost.', '#f43f5e');
                wfSSE = null; document.getElementById('wf-stop-btn').style.display = 'none';
                document.getElementById('wf-status').textContent = 'Error';
              };
            }
            function stopWorkflow() { if (wfSSE) { wfSSE.close(); wfSSE = null; } document.getElementById('wf-stop-btn').style.display = 'none'; document.getElementById('wf-status').textContent = 'Stopped'; wfLine('Stopped.', '#f59e0b'); }
          </script>

      <?php endif; ?>

    </div>
  </div>
  <script>
    function st(t) { document.querySelectorAll('.tp').forEach(p => p.classList.remove('active')); document.querySelectorAll('.tab').forEach(b => b.classList.remove('active')); document.getElementById('tab-' + t).classList.add('active'); event.target.classList.add('active'); }
    async function ngGenerate() {
      const name = document.getElementById('ng-name').value || 'default';
      const seed = document.getElementById('ng-seed').value || '';
      const temp = parseFloat(document.getElementById('ng-temp').value) || 0.8;
      const out = document.getElementById('ng-out');
      out.textContent = 'Generating...';
      try {
        const r = await fetch(location.pathname.replace('/admin/', '') + '/api/?action=yugagen_chat', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ model: name, prompt: seed, max_chars: 250, temperature: temp, top_p: 0.9 })
        });
        const d = await r.json();
        if (d.ok) {
          out.textContent = d.reply + '\n\n[steps: ' + d.steps + ' | loss: ' + d.loss + ' | params: ' + d.params.toLocaleString() + ']';
        } else {
          out.textContent = 'Error: ' + (d.error || 'unknown');
        }
      } catch (e) { out.textContent = 'Error: ' + e.message; }
    }
    function sk(sid) { const r = document.getElementById('keys-' + sid); r.style.display = r.style.display === 'none' ? 'table-row' : 'none'; }
    async function sc() {
      const inp = document.getElementById('ci2'); const box = document.getElementById('chat-box'); const m = document.getElementById('cm')?.value || 'default'; const q = inp.value.trim(); if (!q) return; am('user', q); inp.value = ''; const t = am('bot', '...');
      try { const r = await fetch('<?= htmlspecialchars($api_base) ?>?action=brain_chat', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ model: m, message: q }) }); const d = await r.json(); t.textContent = d.reply || d.error || '(no response)'; } catch (e) { t.textContent = 'Error: ' + e.message; }
    }
    function am(tp, tx) { const b = document.getElementById('chat-box'); const d = document.createElement('div'); d.className = 'cm ' + tp; d.textContent = tx; b.appendChild(d); b.scrollTop = b.scrollHeight; return d; }
    document.getElementById('ci2')?.addEventListener('keydown', e => { if (e.key === 'Enter') sc(); });
    function fr(a, p) { document.getElementById('ra').value = a; if (p) document.getElementById('rb').value = p; }
    async function rr() {
      const a = document.getElementById('ra').value; const b = document.getElementById('rb').value; const k = document.getElementById('rk').value; const o = document.getElementById('rr2'); o.textContent = 'Loading...';
      const h = { 'Content-Type': 'application/json' }; if (k) h['X-API-Key'] = k;
      try { const r = await fetch('<?= htmlspecialchars($api_base) ?>?action=' + a, { method: 'POST', headers: h, body: b }); const d = await r.json(); o.textContent = JSON.stringify(d, null, 2); } catch (e) { o.textContent = 'Error: ' + e.message; }
    }
    function cc() { const a = document.getElementById('ra').value; const b = document.getElementById('rb').value; const k = document.getElementById('rk').value; const c = `curl -X POST '<?= htmlspecialchars($api_base) ?>?action=${a}' \\\n  -H 'Content-Type: application/json' \\${k ? `\n  -H 'X-API-Key: ${k}' \\` : ''}\n  -d '${b}'`; navigator.clipboard.writeText(c); }
    // Inject CSRF token into every POST form that doesn't already have it
    (function () { var t = '<?= htmlspecialchars($csrf, ENT_QUOTES) ?>'; document.querySelectorAll('form').forEach(function (f) { if (f.method.toLowerCase() === 'post' && !f.querySelector('[name="_csrf"]')) { var i = document.createElement('input'); i.type = 'hidden'; i.name = '_csrf'; i.value = t; f.appendChild(i); } }); })();
    let slSSE = null;
    function startSelfLearn() {
      const url = document.getElementById('sl-url').value.trim();
      const pages = document.getElementById('sl-pages').value || 20;
      const steps = document.getElementById('sl-steps').value || 20000;
      const model = '<?= htmlspecialchars($sel_model) ?>';
      const out = document.getElementById('sl-output');
      const btn = document.getElementById('sl-btn');
      if (!url) { alert('Enter a URL first'); return; }
      if (slSSE) { slSSE.close(); }
      out.style.display = 'block'; out.innerHTML = '';
      btn.disabled = true; btn.textContent = 'Running…';
      const addLine = (msg, color) => {
        const d = document.createElement('div');
        d.style.color = color || '#a5b4fc';
        d.style.marginBottom = '4px';
        d.textContent = '[' + new Date().toLocaleTimeString() + '] ' + msg;
        out.appendChild(d); out.scrollTop = out.scrollHeight;
      };
      addLine('Connecting…', '#94a3b8');
      const qs = '?page=train&stream=1&model=' + encodeURIComponent(model)
        + '&url=' + encodeURIComponent(url)
        + '&max_pages=' + pages + '&steps=' + steps;
      slSSE = new EventSource(qs);
      slSSE.onmessage = function (e) {
        try {
          const d = JSON.parse(e.data);
          const colors = { info: '#67e8f9', page: '#94a3b8', done: '#10b981', error: '#f43f5e' };
          addLine(d.msg, colors[d.type] || '#a5b4fc');
          if (d.type === 'done' || d.type === 'error') {
            slSSE.close(); slSSE = null;
            btn.disabled = false; btn.textContent = 'Start self-learning';
          }
        } catch { }
      };
      slSSE.onerror = function () {
        addLine('Connection lost or server error.', '#f43f5e');
        slSSE.close(); slSSE = null;
        btn.disabled = false; btn.textContent = 'Start self-learning';
      };
    }
  </script>
</body>

</html>