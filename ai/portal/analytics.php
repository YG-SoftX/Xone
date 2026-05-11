<?php
/**
 * Yuga Subscriber Analytics Portal
 * Subscribers log in with their API key to see their own usage,
 * top questions, unanswered queries, feedback stats, model status.
 * yoursite.com/yuga/portal/analytics.php
 */
define('YUGA_ROOT', dirname(__DIR__));
require_once YUGA_ROOT . '/subscriptions/Plans.php';
require_once YUGA_ROOT . '/subscriptions/APIKeyManager.php';
require_once YUGA_ROOT . '/core/Feedback.php';
require_once YUGA_ROOT . '/core/Memory.php';
require_once YUGA_ROOT . '/core/Brain.php';
require_once YUGA_ROOT . '/core/ModelStore.php';

session_start();
$config = file_exists(YUGA_ROOT.'/config.php') ? require YUGA_ROOT.'/config.php' : [];
$akm    = new APIKeyManager(YUGA_ROOT . '/data');
$fb     = new Feedback(YUGA_ROOT . '/data');
$mem    = new Memory(YUGA_ROOT . '/data');

// ── Auth: login with API key ──────────────────────────────────────────
$sub = null;
if ($_POST['api_key'] ?? '') {
    $record = $akm->validateKey($_POST['api_key']);
    if ($record) {
        $_SESSION['yuga_sub_id'] = $record['sub_id'];
    }
}
if ($_SESSION['yuga_sub_id'] ?? '') {
    $sub = $akm->getSubscriber($_SESSION['yuga_sub_id']);
}
if ($_GET['logout'] ?? '') {
    session_destroy(); header('Location: analytics.php'); exit;
}

if (!$sub) {
    // Show login form
    ?>
    <!DOCTYPE html><html lang="en"><head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Yuga — Subscriber Portal</title>
    <style>
    *{box-sizing:border-box;margin:0;padding:0}body{font-family:system-ui,sans-serif;background:#0a0f1e;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
    .box{background:#0d1526;border:1px solid #1e293b;border-radius:16px;padding:36px;width:360px;text-align:center}
    .logo{width:44px;height:44px;background:#6366f1;border-radius:12px;font-size:20px;font-weight:800;color:#fff;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px}
    h2{color:#e2e8f0;margin-bottom:6px;font-size:20px}p{color:#64748b;font-size:13px;margin-bottom:22px}
    input{width:100%;background:#111827;border:1px solid #334155;border-radius:8px;padding:11px 14px;color:#e2e8f0;font-size:13px;margin-bottom:12px;outline:none;font-family:monospace}
    button{width:100%;background:#6366f1;color:#fff;border:none;border-radius:8px;padding:12px;font-size:14px;font-weight:600;cursor:pointer}
    </style></head><body>
    <div class="box">
        <div class="logo">Y</div>
        <h2>Subscriber Portal</h2>
        <p>Enter your API key to view your usage and analytics</p>
        <form method="POST">
            <input type="text" name="api_key" placeholder="yuga_live_..." required>
            <button>Sign in</button>
        </form>
    </div></body></html>
    <?php exit;
}

// ── Fetch data ────────────────────────────────────────────────────────
$plan       = Plans::get($sub['plan']);
$store      = new ModelStore(YUGA_ROOT . '/data');
$calls_today = $akm->getCallsToday($sub['id']);
$usage_stats = $akm->getUsageStats($sub['id'], 14);
$fb_stats    = $fb->getStats();
$mem_stats   = $mem->getStats();
$keys        = $akm->listKeys($sub['id']);
$sessions    = $mem->getSessions($sub['id'], 10);
$feedback    = $fb->list('', 1, 20);   // thumbs up
$unanswered  = $fb->listUnanswered('', 20);

// Brain status
$brain_status = ['ready'=>false,'vocab'=>0,'steps'=>0,'loss'=>0,'sentences'=>0];
try {
    $brain = new Brain($sub['id'] ?? 'default', $store);
    $brain_status = $brain->status();
} catch(Exception $e) {}

// Chart data
$chart_labels = array_map(fn($u) => date('M j', strtotime($u['day'])), $usage_stats);
$chart_values = array_column($usage_stats, 'calls');

$api_limit  = $plan['api_calls'];
$pct_used   = $api_limit > 0 ? round($calls_today / $api_limit * 100) : 0;
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Yuga Analytics — <?= htmlspecialchars($sub['name']) ?></title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
:root{--bg:#0a0f1e;--bg2:#0d1526;--bg3:#111827;--bd:#1e293b;--tx:#e2e8f0;--mu:#94a3b8;--di:#475569;--pu:#6366f1;--pl:#a5b4fc;--te:#14b8a6;--am:#f59e0b;--gr:#10b981}
*{box-sizing:border-box;margin:0;padding:0}body{font-family:system-ui,sans-serif;background:var(--bg);color:var(--tx);min-height:100vh}
.nav{background:var(--bg2);border-bottom:1px solid var(--bd);padding:14px 24px;display:flex;align-items:center;gap:12px}
.logo{width:30px;height:30px;background:var(--pu);border-radius:7px;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:13px}
.nav-title{font-size:15px;font-weight:600}.nav-sub{font-size:12px;color:var(--di);margin-left:4px}
.logout{margin-left:auto;font-size:12px;color:var(--di);text-decoration:none}.logout:hover{color:var(--tx)}
.content{padding:24px;max-width:1100px;margin:0 auto}
.sg{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:12px;margin-bottom:22px}
.sc{background:var(--bg2);border:1px solid var(--bd);border-radius:12px;padding:16px}
.sv{font-size:26px;font-weight:700}.sv.p{color:var(--pl)}.sv.t{color:var(--te)}.sv.a{color:var(--am)}.sv.g{color:var(--gr)}
.sl{font-size:12px;color:var(--di);margin-top:3px}
.card{background:var(--bg2);border:1px solid var(--bd);border-radius:12px;padding:20px;margin-bottom:18px}
.ct{font-size:14px;font-weight:600;color:var(--pl);margin-bottom:14px}
.tc{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.tbl{width:100%;border-collapse:collapse;font-size:12px}
.tbl th{text-align:left;padding:7px 10px;color:var(--di);font-size:10px;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid var(--bd)}
.tbl td{padding:8px 10px;border-bottom:1px solid var(--bd)}.tbl tr:last-child td{border-bottom:none}
.pbo{background:var(--bg3);border-radius:4px;height:6px;overflow:hidden;margin:8px 0}
.pbi{height:100%;border-radius:4px;background:var(--pu);transition:.3s}
.pill{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:500}
.pg{background:rgba(16,185,129,.15);color:#6ee7b7}.pr{background:rgba(239,68,68,.15);color:#fca5a5}
.pm{background:rgba(245,158,11,.15);color:#fcd34d}
.key-str{font-family:monospace;font-size:11px;color:var(--te)}
@media(max-width:700px){.tc{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="nav">
  <div class="logo">Y</div>
  <div>
    <div class="nav-title"><?= htmlspecialchars($sub['name']) ?></div>
    <div class="nav-sub"><?= htmlspecialchars($sub['email']) ?> &bull; <?= ucfirst($sub['plan']) ?> plan</div>
  </div>
  <a href="?logout=1" class="logout">Sign out</a>
</div>
<div class="content">

<!-- Stats -->
<div class="sg">
  <div class="sc"><div class="sv p"><?= number_format($calls_today) ?></div><div class="sl">API calls today</div></div>
  <div class="sc"><div class="sv t"><?= $api_limit === PHP_INT_MAX ? '&#8734;' : number_format($api_limit) ?></div><div class="sl">Daily limit</div></div>
  <div class="sc"><div class="sv a"><?= $pct_used ?>%</div><div class="sl">Limit used today</div></div>
  <div class="sc"><div class="sv g"><?= count($sessions) ?></div><div class="sl">Active sessions</div></div>
  <div class="sc"><div class="sv p"><?= $fb_stats['thumbs_up'] ?? 0 ?></div><div class="sl">Thumbs up</div></div>
  <div class="sc"><div class="sv <?= $brain_status['ready']?'g':'a' ?>"><?= $brain_status['ready']?'Ready':'Untrained' ?></div><div class="sl">Model status</div></div>
</div>

<!-- Usage chart -->
<div class="card">
  <div class="ct">API usage — last 14 days</div>
  <canvas id="uc" style="max-height:160px"></canvas>
</div>

<!-- Daily limit progress -->
<div class="card">
  <div class="ct">Today's usage</div>
  <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--di);margin-bottom:4px">
    <span><?= number_format($calls_today) ?> calls used</span>
    <span><?= $api_limit === PHP_INT_MAX ? 'Unlimited' : number_format($api_limit - $calls_today) . ' remaining' ?></span>
  </div>
  <div class="pbo"><div class="pbi" style="width:<?= min($pct_used, 100) ?>%;background:<?= $pct_used>90?'var(--am)':'var(--pu)' ?>"></div></div>
  <?php if ($pct_used > 80): ?>
  <div style="font-size:12px;color:var(--am);margin-top:6px">You are near your daily limit. <a href="../portal/?page=signup&plan=pro" style="color:var(--pu)">Upgrade for more calls →</a></div>
  <?php endif; ?>
</div>

<div class="tc">
  <!-- API keys -->
  <div class="card">
    <div class="ct">API keys</div>
    <table class="tbl">
      <thead><tr><th>Key</th><th>Status</th><th>Last used</th></tr></thead>
      <tbody>
      <?php foreach ($keys as $k): ?>
      <tr>
        <td><span class="key-str"><?= htmlspecialchars($k['key_prefix']) ?></span><br><span style="color:var(--di);font-size:10px"><?= htmlspecialchars($k['label']?:'(no label)') ?></span></td>
        <td><span class="pill <?= $k['status']==='active'?'pg':'pr' ?>"><?= $k['status'] ?></span></td>
        <td style="color:var(--di)"><?= $k['last_used'] ? date('M j', $k['last_used']) : 'Never' ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Model status -->
  <div class="card">
    <div class="ct">Model</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:12px">
      <div style="color:var(--di)">Status</div><div><?= $brain_status['ready']?'<span style="color:var(--gr)">Ready</span>':'<span style="color:var(--di)">Untrained</span>' ?></div>
      <div style="color:var(--di)">Train steps</div><div><?= number_format($brain_status['steps']) ?></div>
      <div style="color:var(--di)">Loss</div><div><?= $brain_status['loss'] ?: '—' ?></div>
      <div style="color:var(--di)">Vocab size</div><div><?= $brain_status['vocab'] ?: '—' ?></div>
      <div style="color:var(--di)">Sentences</div><div><?= $brain_status['sentences'] ?: '—' ?></div>
    </div>
    <div style="margin-top:14px">
      <a href="../admin/?page=train" style="font-size:12px;color:var(--pu)">Train your model →</a>
    </div>
  </div>
</div>

<!-- Unanswered questions -->
<?php if (!empty($unanswered)): ?>
<div class="card">
  <div class="ct">Questions your model can't answer yet <span style="font-size:12px;font-weight:400;color:var(--di);margin-left:8px"><?= count($unanswered) ?> total</span></div>
  <table class="tbl">
    <thead><tr><th>Question</th><th>Asked</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach (array_slice($unanswered, 0, 8) as $q): ?>
    <tr>
      <td><?= htmlspecialchars(substr($q['question'], 0, 100)) ?></td>
      <td style="color:var(--di)"><?= $q['count'] ?>x</td>
      <td><a href="../admin/?page=train" style="font-size:11px;color:var(--pu)">Add content →</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- Recent conversations -->
<div class="card">
  <div class="ct">Recent conversations</div>
  <?php if (empty($sessions)): ?>
  <div style="font-size:13px;color:var(--di)">No conversations yet. Start chatting with your widget!</div>
  <?php else: ?>
  <table class="tbl">
    <thead><tr><th>Session</th><th>Messages</th><th>Started</th><th>Last active</th></tr></thead>
    <tbody>
    <?php foreach ($sessions as $s): ?>
    <tr>
      <td style="font-family:monospace;font-size:11px;color:var(--te)"><?= substr($s['session_id'],0,16) ?>...</td>
      <td><?= $s['msg_count'] ?></td>
      <td style="color:var(--di)"><?= date('M j H:i', $s['created_at']) ?></td>
      <td style="color:var(--di)"><?= date('M j H:i', $s['updated_at']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<!-- Plan info -->
<div class="card">
  <div class="ct">Your plan — <?= ucfirst($sub['plan']) ?></div>
  <div style="display:flex;gap:12px;flex-wrap:wrap">
    <?php foreach ($plan['features'] as $f): ?>
    <div style="font-size:12px;color:var(--mu);display:flex;align-items:center;gap:5px">
      <span style="width:5px;height:5px;background:var(--gr);border-radius:50%;flex-shrink:0"></span><?= htmlspecialchars($f) ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if (!in_array($sub['plan'], ['pro','enterprise'])): ?>
  <div style="margin-top:14px">
    <a href="../portal/?page=signup&plan=pro&sub_id=<?= urlencode($sub['id']) ?>" style="font-size:12px;background:var(--pu);color:#fff;padding:7px 14px;border-radius:7px;text-decoration:none">Upgrade to Pro →</a>
  </div>
  <?php endif; ?>
</div>

</div>
<script>
new Chart(document.getElementById('uc'),{type:'bar',data:{labels:<?= json_encode($chart_labels) ?>,datasets:[{data:<?= json_encode($chart_values) ?>,backgroundColor:'rgba(99,102,241,.5)',borderColor:'#6366f1',borderWidth:1,borderRadius:4}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(255,255,255,.05)'},ticks:{color:'#94a3b8',font:{size:10}}},y:{grid:{color:'rgba(255,255,255,.05)'},ticks:{color:'#94a3b8',font:{size:10}}}}}});
</script>
</body></html>
