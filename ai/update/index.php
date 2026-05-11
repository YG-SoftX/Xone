<?php
/**
 * Yuga — Web Updater
 *
 * Password-protected update panel for cPanel users without SSH.
 * Handles:
 *   1. Database migrations (schema updates)
 *   2. File replacement (upload a new yuga-update.zip)
 *   3. Cache/temp clearing
 *
 * Access: https://yourdomain.com/yuga/update/
 * Protect with the admin password from config.php
 */

define('YUGA_ROOT', dirname(__DIR__));

$config = require YUGA_ROOT . '/config.php';

session_start();

// ── Auth ─────────────────────────────────────────────────────────────────────

$adminPass = $config['admin_password'] ?? '';

if (empty($adminPass)) {
    die('<h2 style="font-family:sans-serif;color:red">Update panel disabled: set admin_password in config.php first.</h2>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $adminPass) {
        $_SESSION['yuga_update_auth'] = true;
    } else {
        $authError = 'Wrong password.';
    }
}

if (isset($_POST['logout'])) {
    unset($_SESSION['yuga_update_auth']);
}

$authed = !empty($_SESSION['yuga_update_auth']);

// ── Actions (authenticated) ───────────────────────────────────────────────────

$messages = [];
$errors   = [];

if ($authed && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $act = $_POST['act'] ?? '';

    // ── Run migrations ───────────────────────────────────────────────────────
    if ($act === 'migrate') {
        require_once YUGA_ROOT . '/core/Migrator.php';
        $migrator = new Migrator(
            YUGA_ROOT . '/data',
            YUGA_ROOT . '/update/migrations'
        );
        if (!$migrator->hasPending()) {
            $messages[] = 'Nothing to do — all migrations are already applied.';
        } else {
            $applied = $migrator->run(function ($msg) use (&$messages) {
                $messages[] = $msg;
            });
            $messages[] = 'Done. Applied ' . count($applied) . ' migration(s).';
        }
    }

    // ── Apply zip update ─────────────────────────────────────────────────────
    elseif ($act === 'apply_zip') {
        if (empty($_FILES['zip_file']['tmp_name'])) {
            $errors[] = 'No zip file uploaded.';
        } else {
            $result = applyZipUpdate($_FILES['zip_file']['tmp_name'], $errors);
            if ($result) {
                $messages[] = 'Files updated successfully. Running migrations...';
                require_once YUGA_ROOT . '/core/Migrator.php';
                $migrator = new Migrator(YUGA_ROOT . '/data', YUGA_ROOT . '/update/migrations');
                $migrator->run(function ($msg) use (&$messages) { $messages[] = $msg; });
                $messages[] = 'Update complete!';
            }
        }
    }

    // ── Clear temp files ─────────────────────────────────────────────────────
    elseif ($act === 'clear_temp') {
        $cleared = clearTemp();
        $messages[] = "Cleared $cleared temporary file(s).";
    }
}

// ── Load migration state ──────────────────────────────────────────────────────

$migrationHistory = [];
$pendingMigrations = [];
if ($authed) {
    require_once YUGA_ROOT . '/core/Migrator.php';
    try {
        $migrator = new Migrator(YUGA_ROOT . '/data', YUGA_ROOT . '/update/migrations');
        $migrationHistory  = $migrator->history();
        $pendingMigrations = array_map('basename', $migrator->pending());
    } catch (Exception $e) {
        $errors[] = 'Could not load migration state: ' . $e->getMessage();
    }
}

// ── Helper functions ──────────────────────────────────────────────────────────

/**
 * Apply a yuga-update.zip:
 *  - Extracts to a temp dir
 *  - Skips: data/, config.php, update/
 *  - Replaces everything else
 */
function applyZipUpdate(string $tmpPath, array &$errors): bool {
    if (!class_exists('ZipArchive')) {
        $errors[] = 'ZipArchive PHP extension not available on this server.';
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($tmpPath) !== true) {
        $errors[] = 'Could not open zip file.';
        return false;
    }

    // Protected paths — never overwritten
    $protected = [
        'data/',
        'config.php',
        'update/',
        '.htaccess',
    ];

    $extracted = 0;
    $skipped   = 0;

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);

        // Strip leading directory (e.g. yuga-1.1/ → just the path within)
        $name = preg_replace('#^[^/]+/#', '', $name);
        if (empty($name)) continue;

        // Check protection
        $skip = false;
        foreach ($protected as $p) {
            if (str_starts_with($name, $p) || $name === rtrim($p, '/')) {
                $skip = true;
                break;
            }
        }
        if ($skip) { $skipped++; continue; }

        $dest = YUGA_ROOT . '/' . $name;

        if (str_ends_with($name, '/')) {
            // Directory
            if (!is_dir($dest)) mkdir($dest, 0755, true);
        } else {
            // File
            $dir = dirname($dest);
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            file_put_contents($dest, $zip->getFromIndex($i));
            $extracted++;
        }
    }

    $zip->close();

    if ($extracted === 0) {
        $errors[] = "No files were extracted (skipped: $skipped). Check zip structure.";
        return false;
    }

    return true;
}

function clearTemp(): int {
    $count = 0;
    $dirs  = [YUGA_ROOT . '/data/tmp', sys_get_temp_dir()];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) continue;
        foreach (glob($dir . '/yuga_*') ?: [] as $f) {
            if (is_file($f) && unlink($f)) $count++;
        }
    }
    return $count;
}

function yuga_version(): string {
    $vf = YUGA_ROOT . '/VERSION';
    return file_exists($vf) ? trim(file_get_contents($vf)) : '1.0';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Yuga Updater</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0 }
  body { font-family: system-ui, sans-serif; background: #0f1117; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
  .card { background: #1a1d27; border: 1px solid #2d3148; border-radius: 12px; padding: 2rem; width: 100%; max-width: 640px; }
  h1 { font-size: 1.4rem; margin-bottom: .25rem; }
  .sub { color: #64748b; font-size: .85rem; margin-bottom: 1.5rem; }
  label { display: block; font-size: .85rem; color: #94a3b8; margin-bottom: .3rem; margin-top: 1rem; }
  input[type=password], input[type=file] { width: 100%; padding: .55rem .75rem; background: #0f1117; border: 1px solid #2d3148; border-radius: 6px; color: #e2e8f0; font-size: .9rem; }
  .btn { display: inline-block; padding: .55rem 1.2rem; background: #6366f1; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: .9rem; margin-top: .75rem; text-decoration: none; }
  .btn:hover { background: #4f46e5; }
  .btn-sm { padding: .35rem .8rem; font-size: .8rem; }
  .btn-danger { background: #dc2626; }
  .btn-danger:hover { background: #b91c1c; }
  .btn-gray { background: #374151; }
  .btn-gray:hover { background: #4b5563; }
  .section { margin-top: 1.5rem; border-top: 1px solid #2d3148; padding-top: 1.25rem; }
  .section h2 { font-size: 1rem; margin-bottom: .5rem; }
  .msg { padding: .55rem .85rem; border-radius: 6px; font-size: .85rem; margin-bottom: .4rem; }
  .msg-ok  { background: #14532d; color: #86efac; }
  .msg-err { background: #450a0a; color: #fca5a5; }
  table { width: 100%; border-collapse: collapse; font-size: .82rem; margin-top: .5rem; }
  th, td { text-align: left; padding: .4rem .6rem; border-bottom: 1px solid #2d3148; }
  th { color: #94a3b8; font-weight: 500; }
  .badge { display: inline-block; padding: .15rem .5rem; border-radius: 999px; font-size: .75rem; }
  .badge-ok { background: #14532d; color: #86efac; }
  .badge-pending { background: #451a03; color: #fdba74; }
  .badge-none { background: #1e293b; color: #64748b; }
  .logout { float: right; font-size: .8rem; color: #64748b; cursor: pointer; background: none; border: none; text-decoration: underline; }
  .version { font-size: .75rem; color: #4b5563; margin-top: .3rem; }
</style>
</head>
<body>
<div class="card">
  <h1>🔄 Yuga Updater</h1>
  <p class="sub">Safe update tool — never touches your data or config</p>
  <p class="version">Current version: <?= htmlspecialchars(yuga_version()) ?></p>

<?php if (!$authed): ?>
  <!-- ── Login ─────────────────────────────────────────────── -->
  <?php if (!empty($authError)): ?>
    <div class="msg msg-err"><?= htmlspecialchars($authError) ?></div>
  <?php endif; ?>
  <form method="POST">
    <label>Admin Password</label>
    <input type="password" name="password" autofocus required placeholder="Enter admin password">
    <button type="submit" class="btn">Unlock</button>
  </form>

<?php else: ?>
  <!-- ── Authenticated ─────────────────────────────────────── -->
  <form method="POST" style="display:inline">
    <button name="logout" value="1" class="logout">Log out</button>
  </form>

  <?php foreach ($messages as $m): ?>
    <div class="msg msg-ok"><?= htmlspecialchars($m) ?></div>
  <?php endforeach; ?>
  <?php foreach ($errors as $e): ?>
    <div class="msg msg-err"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <!-- ── Migration status ──────────────────────────────────── -->
  <div class="section">
    <h2>Database Migrations
      <?php if (count($pendingMigrations) > 0): ?>
        <span class="badge badge-pending"><?= count($pendingMigrations) ?> pending</span>
      <?php else: ?>
        <span class="badge badge-ok">up to date</span>
      <?php endif; ?>
    </h2>

    <?php if (!empty($pendingMigrations)): ?>
      <p style="font-size:.82rem;color:#fdba74;margin:.5rem 0">
        Pending: <?= implode(', ', array_map('htmlspecialchars', $pendingMigrations)) ?>
      </p>
      <form method="POST">
        <input type="hidden" name="act" value="migrate">
        <button type="submit" class="btn btn-sm">Run Migrations</button>
      </form>
    <?php else: ?>
      <p style="font-size:.82rem;color:#64748b;margin:.4rem 0">All migrations applied.</p>
    <?php endif; ?>

    <?php if (!empty($migrationHistory)): ?>
      <table>
        <tr><th>Migration</th><th>Applied</th></tr>
        <?php foreach ($migrationHistory as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= date('Y-m-d H:i', $row['applied_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>

  <!-- ── File update via zip ───────────────────────────────── -->
  <div class="section">
    <h2>Apply Update Package</h2>
    <p style="font-size:.82rem;color:#64748b;margin-bottom:.75rem">
      Upload a <code>yuga-update.zip</code> to replace core files.
      Your <strong>data/</strong>, <strong>config.php</strong>, and
      <strong>update/</strong> are <em>never</em> overwritten.
    </p>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="act" value="apply_zip">
      <label>Update Zip File</label>
      <input type="file" name="zip_file" accept=".zip" required>
      <button type="submit" class="btn" onclick="return confirm('Apply update? Existing PHP files will be replaced (data/ and config.php are safe).')">
        Apply Update
      </button>
    </form>
  </div>

  <!-- ── Manual steps ─────────────────────────────────────── -->
  <div class="section">
    <h2>Manual / SSH Update</h2>
    <p style="font-size:.82rem;color:#64748b;margin-bottom:.5rem">
      If you prefer SSH, run these commands inside your Yuga directory:
    </p>
    <pre style="background:#0f1117;padding:.75rem;border-radius:6px;font-size:.78rem;overflow-x:auto;color:#94a3b8"># 1. Back up data (optional but recommended)
cp -r data/ data_backup_$(date +%Y%m%d)/

# 2. Unzip update (skipping data/ and config.php)
unzip -o yuga-update.zip -x "*/data/*" -x "*/config.php"

# 3. Run migrations
php update/migrate.php</pre>
  </div>

  <!-- ── Utilities ─────────────────────────────────────────── -->
  <div class="section">
    <h2>Utilities</h2>
    <form method="POST" style="display:inline">
      <input type="hidden" name="act" value="clear_temp">
      <button type="submit" class="btn btn-gray btn-sm">Clear Temp Files</button>
    </form>
    <a href="../admin/" class="btn btn-gray btn-sm" style="margin-left:.5rem">Back to Admin</a>
  </div>

  <!-- ── What is safe to update ────────────────────────────── -->
  <div class="section" style="font-size:.8rem;color:#64748b">
    <strong style="color:#94a3b8">What gets replaced during a zip update:</strong><br>
    core/*.php &nbsp;·&nbsp; api/ &nbsp;·&nbsp; admin/ &nbsp;·&nbsp; portal/ &nbsp;·&nbsp;
    payments/ &nbsp;·&nbsp; assistant/<br><br>
    <strong style="color:#94a3b8">Never touched:</strong><br>
    data/ (models + DB) &nbsp;·&nbsp; config.php &nbsp;·&nbsp; update/ &nbsp;·&nbsp; .htaccess
  </div>

<?php endif; ?>
</div>
</body>
</html>
