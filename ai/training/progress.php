<?php
/**
 * Yuga YugaGen Training Dashboard
 * Shows live training progress, loss curve, and sample text from checkpoint.
 * Access: yoursite.com/yuga/training/progress.php
 */
define('YUGA_ROOT', dirname(__DIR__));
require_once YUGA_ROOT . '/core/YugaGen.php';

$config = file_exists(YUGA_ROOT . '/config.php') ? require YUGA_ROOT . '/config.php' : [];
session_start();
$pw = $config['admin_password'] ?? '';
if ($pw && ($_POST['pw'] ?? '') === $pw) $_SESSION['yuga_admin'] = true;
if ($pw && !($_SESSION['yuga_admin'] ?? false)) {
    echo '<form method="POST"><input type="password" name="pw" placeholder="Admin password">
    <button>Login</button></form>'; exit;
}

// Find all checkpoints
$ckpts = glob(YUGA_ROOT . '/data/ckpt_*.json.gz') ?: [];
$models = [];
foreach ($ckpts as $f) {
    preg_match('/ckpt_(.+)\.json\.gz$/', $f, $m);
    $name = $m[1] ?? 'unknown';
    $gz   = file_get_contents($f);
    $d    = json_decode(gzdecode($gz), true);
    $gpt  = YugaGen::fromArray($d);
    $models[$name] = [
        'gpt'    => $gpt,
        'file'   => $f,
        'mtime'  => filemtime($f),
        'size'   => round(filesize($f) / 1024),
    ];
}

$sel = $_GET['m'] ?? array_key_first($models);
$cur = $models[$sel] ?? null;

// Generate samples if model is ready
$samples = [];
if ($cur && $cur['gpt']->ready) {
    $gpt = $cur['gpt'];
    $seeds = ['', 'The ', 'Our ', 'You '];
    foreach ($seeds as $seed) {
        $samples[] = [
            'seed' => $seed ?: '(no seed)',
            'text' => $gpt->generate($seed, 180, 0.75, 0.9),
        ];
    }
}

// Corpus stats
$corpus_file = YUGA_ROOT . '/training/corpus.txt';
$corpus_size = file_exists($corpus_file) ? filesize($corpus_file) : 0;
$corpus_lines = $corpus_size ? substr_count(file_get_contents($corpus_file), "\n") : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="refresh" content="60"> <!-- auto-refresh every 60s -->
<title>Yuga Training Progress</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0a0f1e;color:#e2e8f0;min-height:100vh;padding:24px}
.top{display:flex;align-items:center;gap:16px;margin-bottom:24px}
.logo{font-size:22px;font-weight:700;color:#a5b4fc}
.auto-refresh{font-size:12px;color:#475569;margin-left:auto}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-bottom:24px}
.card{background:#0d1526;border:1px solid #1e293b;border-radius:12px;padding:18px}
.val{font-size:28px;font-weight:700}.val.p{color:#a5b4fc}.val.t{color:#14b8a6}.val.a{color:#f59e0b}.val.g{color:#10b981}
.lbl{font-size:12px;color:#475569;margin-top:4px}
h2{font-size:14px;font-weight:600;color:#a5b4fc;margin-bottom:14px}
.sample{background:#0d1526;border:1px solid #1e293b;border-radius:12px;padding:18px;margin-bottom:14px}
.sample-seed{font-size:11px;color:#475569;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px}
.sample-text{font-family:monospace;font-size:13px;line-height:1.7;color:#e2e8f0;white-space:pre-wrap;word-break:break-word}
.model-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.mtab{padding:7px 14px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none;border:1px solid #1e293b;color:#94a3b8;transition:.15s}
.mtab:hover,.mtab.active{border-color:#6366f1;color:#a5b4fc;background:rgba(99,102,241,.1)}
.two{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.cmd{font-family:monospace;background:#050a14;border-radius:8px;padding:14px;font-size:12px;color:#14b8a6;line-height:1.9;border:1px solid #1e293b}
.status-dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:6px}
.dot-g{background:#10b981}.dot-a{background:#f59e0b}.dot-r{background:#ef4444}
progress-bar-outer{background:#1e293b;border-radius:4px;height:8px;display:block;overflow:hidden;margin:6px 0}
.pbo{background:#1e293b;border-radius:4px;height:8px;overflow:hidden;margin:6px 0}
.pbi{height:100%;border-radius:4px;background:#6366f1;transition:width .3s}
@media(max-width:700px){.two{grid-template-columns:1fr}}
</style>
</head>
<body>

<div class="top">
  <div class="logo">&#129504; Yuga — Training Progress</div>
  <div class="auto-refresh">Auto-refreshes every 60s &bull; <?= date('H:i:s') ?></div>
</div>

<!-- Model selector -->
<?php if (count($models) > 1): ?>
<div class="model-tabs">
  <?php foreach ($models as $name => $md): ?>
  <a href="?m=<?= urlencode($name) ?>" class="mtab <?= $name===$sel?'active':'' ?>">
    <span class="status-dot <?= $md['gpt']->ready?'dot-g':'dot-a' ?>"></span>
    <?= htmlspecialchars($name) ?>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!$cur): ?>
<div class="card">
  <h2>No trained models yet</h2>
  <p style="color:#64748b;font-size:14px;margin-bottom:14px">Run the trainer to get started:</p>
  <div class="cmd">
php training/build_corpus.php --url=https://yoursite.com --pages=30
php training/train.php --model=default --size=nano --steps=5000
  </div>
</div>
<?php else: ?>

<?php $gpt = $cur['gpt']; ?>

<!-- Stats -->
<div class="grid">
  <div class="card"><div class="val p"><?= number_format($gpt->steps) ?></div><div class="lbl">Total steps trained</div></div>
  <div class="card"><div class="val t"><?= round($gpt->loss, 4) ?></div><div class="lbl">Current loss</div></div>
  <div class="card"><div class="val a"><?= round($gpt->best_loss, 4) ?></div><div class="lbl">Best loss ever</div></div>
  <div class="card"><div class="val g"><?= $gpt->V ?></div><div class="lbl">Vocab size</div></div>
  <div class="card"><div class="val p"><?= number_format($gpt->paramCount()) ?></div><div class="lbl">Parameters</div></div>
  <div class="card">
    <div class="val <?= $gpt->ready?'g':'a' ?>"><?= $gpt->ready?'&#10003;':'&#9711;' ?></div>
    <div class="lbl">Model ready</div>
  </div>
</div>

<!-- Loss progress bar (rough estimate) -->
<div class="card" style="margin-bottom:20px">
  <h2>Training progress</h2>
  <?php
  $initial = 3.5; $target = 0.8;
  $pct = max(0, min(100, round(($initial - $gpt->loss) / ($initial - $target) * 100)));
  ?>
  <div style="display:flex;justify-content:space-between;font-size:12px;color:#64748b;margin-bottom:6px">
    <span>Untrained (loss ~3.5)</span>
    <span><?= $pct ?>% toward target</span>
    <span>Good generation (loss ~0.8)</span>
  </div>
  <div class="pbo"><div class="pbi" style="width:<?= $pct ?>%"></div></div>
  <div style="margin-top:12px;font-size:12px;color:#64748b">
    Arch: <code style="color:#a5b4fc">D=<?= $gpt->D ?> H=<?= $gpt->H ?> L=<?= $gpt->L ?> CTX=<?= $gpt->CTX ?></code>
    &bull; Checkpoint: <code style="color:#14b8a6"><?= $cur['size'] ?>KB</code>
    &bull; Last update: <code style="color:#14b8a6"><?= date('M j H:i', $cur['mtime']) ?></code>
  </div>
  <?php
  $steps_to_good = max(0, 50000 - $gpt->steps);
  $mins = round($steps_to_good * 0.114 / 60);
  ?>
  <div style="margin-top:8px;font-size:12px;color:#64748b">
    <?php if ($gpt->loss < 1.0): ?>
      <span style="color:#10b981">&#10003; Loss below 1.0 — generation quality is good!</span>
    <?php elseif ($gpt->loss < 1.5): ?>
      <span style="color:#f59e0b">Getting there. ~<?= number_format($steps_to_good) ?> more steps for strong generation (~<?= $mins ?> min via cron).</span>
    <?php else: ?>
      <span style="color:#94a3b8">Need ~<?= number_format($steps_to_good) ?> more steps (~<?= $mins ?> minutes via cron) for coherent generation.</span>
    <?php endif; ?>
  </div>
</div>

<div class="two">
  <!-- Generation samples -->
  <div>
    <div class="card" style="margin-bottom:0">
      <h2>Live generation samples <span style="font-size:11px;color:#475569;font-weight:400">(temp=0.75)</span></h2>
      <?php if ($gpt->steps < 500): ?>
      <div style="color:#64748b;font-size:13px">Model needs more training before generating meaningful text. Check back after a cron run.</div>
      <?php else: ?>
      <?php foreach ($samples as $s): ?>
      <div class="sample">
        <div class="sample-seed">Seed: "<?= htmlspecialchars($s['seed']) ?>"</div>
        <div class="sample-text"><?= htmlspecialchars($s['text']) ?></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Corpus + cron setup -->
  <div>
    <div class="card" style="margin-bottom:14px">
      <h2>Corpus</h2>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;margin-bottom:12px">
        <div style="color:#64748b">Size</div><div><?= round($corpus_size/1024) ?>KB</div>
        <div style="color:#64748b">Lines</div><div><?= number_format($corpus_lines) ?></div>
        <div style="color:#64748b">File</div><div style="font-family:monospace;font-size:11px;color:#14b8a6">training/corpus.txt</div>
      </div>
      <?php if ($corpus_size < 100000): ?>
      <div style="background:rgba(245,158,11,.1);border-radius:6px;padding:10px;font-size:12px;color:#fcd34d">
        Corpus is small (&lt;100KB). Add more text for better generation quality.
      </div>
      <?php else: ?>
      <div style="background:rgba(16,185,129,.1);border-radius:6px;padding:10px;font-size:12px;color:#6ee7b7">
        Good corpus size. Training will produce meaningful results.
      </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>Cron setup (cPanel)</h2>
      <p style="font-size:12px;color:#64748b;margin-bottom:10px">Add this in cPanel &#8594; Cron Jobs to train automatically:</p>
      <div class="cmd">*/30 * * * * php <?= htmlspecialchars(YUGA_ROOT) ?>/training/train.php \
  --model=<?= htmlspecialchars($sel) ?> --steps=3000 \
  >> /tmp/yuga_train.log 2>&1</div>
      <p style="font-size:11px;color:#475569;margin-top:8px">Runs every 30 min &bull; 3000 steps/run &bull; ~6 min/run &bull; 144,000 steps/day</p>
    </div>
  </div>
</div>

<?php endif; ?>

<!-- No models: show quickstart -->
<?php if (empty($models)): ?>
<div class="card" style="margin-top:20px">
  <h2>Quickstart</h2>
  <div class="cmd">
# Step 1: Build a corpus from your website
php <?= YUGA_ROOT ?>/training/build_corpus.php \
  --url=https://yoursite.com --pages=30

# Step 2: Start training (first run)
php <?= YUGA_ROOT ?>/training/train.php \
  --model=default --size=nano --steps=5000

# Step 3: Come back here to see progress
  </div>
</div>
<?php endif; ?>

</body>
</html>
