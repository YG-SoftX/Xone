<?php
/**
 * Yuga Admin CLI — Web-based terminal for full control
 *
 * GET  → renders the terminal UI
 * POST ?run=1        → execute a command (JSON response)
 * GET  ?stream=1&cmd → Server-Sent Events stream for long jobs
 */
define('YUGA_ROOT', dirname(__DIR__));
require_once YUGA_ROOT . '/core/YugaLM.php';
require_once YUGA_ROOT . '/core/ModelStore.php';
require_once YUGA_ROOT . '/core/Tokenizer.php';
require_once YUGA_ROOT . '/core/Transformer.php';
require_once YUGA_ROOT . '/core/Retriever.php';
require_once YUGA_ROOT . '/core/Brain.php';
require_once YUGA_ROOT . '/core/SelfLearner.php';
require_once YUGA_ROOT . '/core/Ingester.php';
require_once YUGA_ROOT . '/core/ModelPresets.php';
require_once YUGA_ROOT . '/subscriptions/Plans.php';
require_once YUGA_ROOT . '/subscriptions/APIKeyManager.php';
require_once YUGA_ROOT . '/core/ConfigWriter.php';
require_once YUGA_ROOT . '/core/Mailer.php';

$config    = file_exists(YUGA_ROOT.'/config.php') ? require YUGA_ROOT.'/config.php' : [];
$admin_pw  = $config['admin_password'] ?? '';

session_start();

// ── Auth ──────────────────────────────────────────────────────────────
if ($admin_pw && !($_SESSION['yuga_admin'] ?? false)) {
    header('Location: index.php');
    exit;
}

$store = new ModelStore(YUGA_ROOT . '/data');
$akm   = new APIKeyManager(YUGA_ROOT . '/data');

// ═════════════════════════════════════════════════════════════════════
// SSE STREAM MODE — long-running commands (crawl, train)
// ═════════════════════════════════════════════════════════════════════
if (isset($_GET['stream'])) {
    set_time_limit(600);
    ignore_user_abort(false);
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');

    $cmd_raw = trim($_GET['cmd'] ?? '');
    $argv    = str_getcsv($cmd_raw, ' ');
    $cmd     = strtolower($argv[0] ?? '');

    function sse(string $type, string $text): void {
        echo "data: " . json_encode(['type' => $type, 'text' => $text]) . "\n\n";
        if (ob_get_level()) { ob_flush(); }
        flush();
    }
    function sse_done(): void {
        echo "data: " . json_encode(['done' => true]) . "\n\n";
        if (ob_get_level()) { ob_flush(); }
        flush();
    }

    if ($cmd === 'crawl') {
        $model = $argv[1] ?? 'default';
        $url   = $argv[2] ?? '';
        $pages = (int)($argv[3] ?? 30);
        if (!$url) { sse('error','Usage: crawl <model> <url> [max_pages]'); sse_done(); exit; }

        sse('info', "Starting crawl of {$url} (max {$pages} pages) on model [{$model}]…");

        try {
            $sl = new SelfLearner($model, $store);
            $sl->max_pages   = $pages;
            $sl->train_steps = 15000;

            $result = $sl->learnFromSite($url, function($event, $data) {
                if ($event === 'page') {
                    sse('output', "  Fetched [{$data['status']}] {$data['url']}");
                } elseif ($event === 'train') {
                    sse('success', "  Trained step {$data['step']} — loss {$data['loss']}");
                } elseif ($event === 'error') {
                    sse('warn', "  Skip: {$data['message']}");
                }
            });

            sse('success', "Done. Pages: {$result['pages']} | Chars: " . number_format($result['chars']) . " | Loss: {$result['loss']}");
            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $e) sse('warn', "  ! {$e}");
            }
        } catch (Throwable $e) {
            sse('error', 'Error: ' . $e->getMessage());
        }
        sse_done();
        exit;
    }

    if ($cmd === 'train') {
        $model = $argv[1] ?? 'default';
        $steps = (int)($argv[2] ?? 10000);
        sse('info', "Training model [{$model}] for {$steps} steps…");
        try {
            $brain = new Brain($model, $store);
            $st    = $brain->status();
            sse('info', "Current: steps={$st['steps']} loss={$st['loss']} vocab={$st['vocab']}");

            // SelfLearner can also re-train from stored corpus if available
            $sl = new SelfLearner($model, $store);
            $sl->trainSteps = $steps;
            $result = $sl->retrainFromCorpus(function($step, $loss) {
                if ($step % 1000 === 0) {
                    sse('output', "  step {$step} — loss {$loss}");
                }
            });
            sse('success', "Training complete. Loss: {$result['loss']} | Steps: {$result['steps']}");
        } catch (Throwable $e) {
            sse('error', 'Error: ' . $e->getMessage());
        }
        sse_done();
        exit;
    }

    sse('error', "Command '{$cmd}' does not support streaming mode.");
    sse_done();
    exit;
}

// ═════════════════════════════════════════════════════════════════════
// AJAX COMMAND EXECUTION
// ═════════════════════════════════════════════════════════════════════
if (isset($_POST['run'])) {
    header('Content-Type: application/json');
    set_time_limit(300);

    $cmd_raw = trim($_POST['cmd'] ?? '');
    $lines   = [];

    // Helper to add output lines
    $out  = fn(string $t) => $lines[] = ['type' => 'output',  'text' => $t];
    $ok   = fn(string $t) => $lines[] = ['type' => 'success', 'text' => $t];
    $err  = fn(string $t) => $lines[] = ['type' => 'error',   'text' => $t];
    $info = fn(string $t) => $lines[] = ['type' => 'info',    'text' => $t];
    $warn = fn(string $t) => $lines[] = ['type' => 'warn',    'text' => $t];
    $sep  = fn() => $lines[] = ['type' => 'sep', 'text' => ''];

    // Parse command + arguments (respects quoted strings)
    preg_match_all('/\'[^\']*\'|"[^"]*"|\S+/', $cmd_raw, $m);
    $argv = array_map(fn($a) => trim($a, '"\''), $m[0]);
    $cmd  = strtolower($argv[0] ?? '');

    try {
        switch ($cmd) {

            // ── help ──────────────────────────────────────────────────
            case 'help':
            case '?':
                $info('Yuga CLI — available commands:');
                $sep();
                $out('  help                    Show this help');
                $out('  version                 Yuga + PHP version info');
                $out('  sysinfo                 PHP system info & extensions');
                $out('  clear                   Clear the terminal');
                $sep();
                $out('  models                  List all models');
                $out('  status [model]          Show model training status');
                $out('  create <name> [size]    Create model (nano/small/medium/large)');
                $out('  delete <name>           Delete a model');
                $sep();
                $out('  train <model> [steps]   Re-train from stored corpus  [STREAMS]');
                $out('  crawl <model> <url> [n] Crawl website + train         [STREAMS]');
                $out('  learn <model> <text>    Train on inline text');
                $out('  learn-url <model> <url> Train on single URL');
                $sep();
                $out('  subs                    List subscribers');
                $out('  sub <id|email>          Subscriber details');
                $out('  suspend <id>            Suspend subscriber');
                $out('  activate <id>           Activate subscriber');
                $out('  keys <sub_id>           List API keys for subscriber');
                $sep();
                $out('  config                  Show config (secrets masked)');
                $out('  config-set <key> <val>  Update a config value');
                $sep();
                $out('  stats                   Platform statistics');
                $out('  cron                    Trigger scheduled jobs now');
                $out('  shell <cmd>             Run shell command (if allowed)');
                $out('  php <expr>              Evaluate PHP expression');
                break;

            // ── version ───────────────────────────────────────────────
            case 'version':
                $info('Yuga v1.0 — Self-learning transformer LM');
                $out('PHP        : ' . PHP_VERSION . ' (' . PHP_SAPI . ')');
                $out('OS         : ' . PHP_OS . ' ' . php_uname('r'));
                $out('Server     : ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown'));
                $out('Memory     : ' . ini_get('memory_limit'));
                $out('Max exec   : ' . ini_get('max_execution_time') . 's');
                $out('Data dir   : ' . YUGA_ROOT . '/data');
                break;

            // ── sysinfo ───────────────────────────────────────────────
            case 'sysinfo':
                $info('PHP System Information');
                $sep();
                $out('PHP Version    : ' . PHP_VERSION);
                $out('Memory limit   : ' . ini_get('memory_limit'));
                $out('Memory used    : ' . round(memory_get_usage(true)/1024/1024, 2) . ' MB');
                $out('Peak memory    : ' . round(memory_get_peak_usage(true)/1024/1024, 2) . ' MB');
                $out('Max exec time  : ' . ini_get('max_execution_time') . 's');
                $out('Upload max     : ' . ini_get('upload_max_filesize'));
                $out('Post max       : ' . ini_get('post_max_size'));
                $out('Disk free      : ' . round(disk_free_space(YUGA_ROOT)/1024/1024/1024, 2) . ' GB');
                $out('Disk total     : ' . round(disk_total_space(YUGA_ROOT)/1024/1024/1024, 2) . ' GB');
                $sep();
                $exts = ['pdo_sqlite','curl','mbstring','json','fileinfo','opcache','zip'];
                $out('Extensions:');
                foreach ($exts as $ext) {
                    $loaded = extension_loaded($ext);
                    $lines[] = ['type' => $loaded ? 'success' : 'warn',
                                'text' => '  ' . ($loaded ? '✓' : '✗') . ' ' . $ext];
                }
                $sep();
                $out('Functions:');
                foreach (['exec','shell_exec','proc_open','passthru'] as $fn) {
                    $avail = function_exists($fn) && !in_array($fn, explode(',', ini_get('disable_functions')));
                    $lines[] = ['type' => $avail ? 'success' : 'warn',
                                'text' => '  ' . ($avail ? '✓' : '✗') . ' ' . $fn . '()'];
                }
                break;

            // ── models ────────────────────────────────────────────────
            case 'models':
                $ml = $store->listModels();
                if (empty($ml)) { $warn('No models found. Use: create <name> [size]'); break; }
                $info(count($ml) . ' model(s):');
                $sep();
                foreach ($ml as $mn) {
                    try {
                        $bs = (new Brain($mn, $store))->status();
                        $ready = $bs['ready'] ? '✓ ready  ' : '○ untrained';
                        $lines[] = ['type' => $bs['ready'] ? 'success' : 'warn',
                                    'text' => sprintf('  %-20s %s  steps=%-8s loss=%-6s vocab=%s',
                                        $mn, $ready,
                                        number_format($bs['steps'] ?? 0),
                                        round($bs['loss'] ?? 0, 4),
                                        $bs['vocab'] ?? 0)];
                    } catch (Throwable $e) {
                        $err("  {$mn} — ERROR: " . $e->getMessage());
                    }
                }
                break;

            // ── status ────────────────────────────────────────────────
            case 'status':
                $model = $argv[1] ?? 'default';
                try {
                    $bs = (new Brain($model, $store))->status();
                    $info("Model: {$model}");
                    $sep();
                    $out('  Ready      : ' . ($bs['ready'] ? 'Yes' : 'No'));
                    $out('  Steps      : ' . number_format($bs['steps'] ?? 0));
                    $out('  Loss       : ' . round($bs['loss'] ?? 0, 6));
                    $out('  Vocab size : ' . ($bs['vocab'] ?? 0));
                    $out('  Sentences  : ' . ($bs['sentences'] ?? 0));
                    $out('  Params     : ' . number_format($bs['params'] ?? 0));
                    $out('  Arch       : ' . ($bs['arch'] ?? '—'));
                    if (!empty($bs['last_crawl'])) $out('  Last crawl : ' . $bs['last_crawl']);
                    if (!empty($bs['pages_seen'])) $out('  Pages seen : ' . $bs['pages_seen']);
                } catch (Throwable $e) {
                    $err("Model '{$model}' not found or error: " . $e->getMessage());
                }
                break;

            // ── create ────────────────────────────────────────────────
            case 'create':
                $nm   = preg_replace('/[^a-z0-9_-]/', '', strtolower($argv[1] ?? ''));
                $size = in_array($argv[2] ?? 'nano', ['nano','small','medium','large']) ? $argv[2] : 'nano';
                if (!$nm) { $err('Usage: create <name> [nano|small|medium|large]'); break; }
                if ($store->loadModel($nm)) { $err("Model '{$nm}' already exists."); break; }
                $preset = ModelPresets::get($size);
                $m      = new YugaLM();
                $saved  = $m->save();
                $saved['_preset'] = $size;
                $store->saveModel($nm, $saved);
                $store->saveMeta($nm, ['preset' => $size, 'preset_name' => $preset['name'],
                    'approx_params' => $preset['approx_params'], 'created_at' => time()]);
                $ok("Model '{$nm}' created. Size: {$preset['emoji']} {$preset['name']} ({$preset['approx_params']} params)");
                $out("Next: crawl {$nm} https://yoursite.com");
                break;

            // ── delete ────────────────────────────────────────────────
            case 'delete':
                $nm = $argv[1] ?? '';
                if (!$nm) { $err('Usage: delete <model_name>'); break; }
                if (!$store->loadModel($nm)) { $err("Model '{$nm}' not found."); break; }
                $store->deleteModel($nm);
                $ok("Model '{$nm}' deleted.");
                break;

            // ── learn (inline text) ───────────────────────────────────
            case 'learn':
                $model = $argv[1] ?? 'default';
                $text  = implode(' ', array_slice($argv, 2));
                if (strlen($text) < 10) { $err('Usage: learn <model> <text...>  (min 10 chars)'); break; }
                $info("Training model [{$model}] on " . strlen($text) . " chars…");
                $ing = new Ingester(new Brain($model, $store));
                $r   = $ing->learnText($text, 'cli');
                $ok("Done. Loss: {$r['loss']} | Steps: {$r['steps']} | Vocab: {$r['vocab']}");
                break;

            // ── learn-url ─────────────────────────────────────────────
            case 'learn-url':
            case 'learn_url':
                $model = $argv[1] ?? 'default';
                $url   = $argv[2] ?? '';
                if (!$url) { $err('Usage: learn-url <model> <url>'); break; }
                $info("Fetching {$url}…");
                $ing = new Ingester(new Brain($model, $store));
                $r   = $ing->learnURL($url);
                $ok("Done. Loss: {$r['loss']} | Steps: {$r['steps']}");
                break;

            // ── subs ──────────────────────────────────────────────────
            case 'subs':
            case 'subscribers':
                $subs = $akm->listSubscribers(200);
                if (empty($subs)) { $warn('No subscribers yet.'); break; }
                $info(count($subs) . ' subscriber(s):');
                $sep();
                $out(sprintf('  %-36s %-20s %-10s %-10s %s', 'ID', 'Name', 'Plan', 'Status', 'Joined'));
                $out('  ' . str_repeat('─', 90));
                foreach ($subs as $s) {
                    $out(sprintf('  %-36s %-20s %-10s %-10s %s',
                        $s['id'],
                        mb_substr($s['name'], 0, 18),
                        $s['plan'],
                        $s['status'] ?? 'active',
                        date('Y-m-d', $s['created_at'])));
                }
                break;

            // ── sub <id|email> ────────────────────────────────────────
            case 'sub':
                $q = $argv[1] ?? '';
                if (!$q) { $err('Usage: sub <id_or_email>'); break; }
                $s = str_contains($q, '@') ? $akm->getSubscriberByEmail($q) : $akm->getSubscriber($q);
                if (!$s) { $err("Subscriber not found: {$q}"); break; }
                $info("Subscriber: {$s['name']}");
                $sep();
                $out('  ID      : ' . $s['id']);
                $out('  Email   : ' . $s['email']);
                $out('  Plan    : ' . $s['plan']);
                $out('  Status  : ' . ($s['status'] ?? 'active'));
                $out('  Created : ' . date('Y-m-d H:i', $s['created_at']));
                $sep();
                $keys = $akm->listKeys($s['id']);
                $out('  Keys (' . count($keys) . '):');
                foreach ($keys as $k) {
                    $out('    ' . substr($k['key_prefix'] ?? $k['id'], 0, 20) . '…  active=' . ($k['active'] ? 'yes' : 'no'));
                }
                break;

            // ── suspend / activate ────────────────────────────────────
            case 'suspend':
                $id = $argv[1] ?? '';
                if (!$id) { $err('Usage: suspend <subscriber_id>'); break; }
                $akm->setSubscriberStatus($id, 'suspended');
                $warn("Subscriber {$id} suspended.");
                break;

            case 'activate':
                $id = $argv[1] ?? '';
                if (!$id) { $err('Usage: activate <subscriber_id>'); break; }
                $akm->setSubscriberStatus($id, 'active');
                $ok("Subscriber {$id} activated.");
                break;

            // ── keys ──────────────────────────────────────────────────
            case 'keys':
                $sid  = $argv[1] ?? '';
                if (!$sid) { $err('Usage: keys <subscriber_id>'); break; }
                $keys = $akm->listKeys($sid);
                if (empty($keys)) { $warn('No keys found.'); break; }
                $info(count($keys) . ' key(s) for subscriber ' . $sid . ':');
                foreach ($keys as $k) {
                    $lines[] = ['type' => $k['active'] ? 'success' : 'warn',
                                'text' => sprintf('  %-40s active=%-5s created=%s',
                                    $k['id'], $k['active'] ? 'yes' : 'no',
                                    date('Y-m-d', $k['created_at']))];
                }
                break;

            // ── config ────────────────────────────────────────────────
            case 'config':
                $info('Current config (secrets masked):');
                $sep();
                $mask = fn($v) => is_string($v) && strlen($v) > 6 ? substr($v,0,4).'****' : $v;
                foreach ($config as $k => $v) {
                    if (is_array($v)) {
                        $out("  {$k}:");
                        foreach ($v as $sk => $sv) {
                            $display = in_array($sk, ['password','secret','key','token','api_key'])
                                ? $mask($sv) : $sv;
                            $out("    {$sk}: " . (is_array($display) ? json_encode($display) : $display));
                        }
                    } else {
                        $display = in_array($k, ['admin_password','api_key'])
                            ? $mask($v) : $v;
                        $out("  {$k}: {$display}");
                    }
                }
                break;

            // ── config-set ────────────────────────────────────────────
            case 'config-set':
            case 'config_set':
                $key = $argv[1] ?? '';
                $val = $argv[2] ?? '';
                if (!$key) { $err('Usage: config-set <key> <value>'); break; }
                $blocked = ['admin_password'];  // require UI to change password
                if (in_array($key, $blocked)) { $err("Use the Settings UI to change '{$key}'."); break; }
                (new ConfigWriter(YUGA_ROOT . '/config.php'))->update([$key => $val]);
                $ok("Config updated: {$key} = {$val}");
                break;

            // ── stats ─────────────────────────────────────────────────
            case 'stats':
                $subs   = $akm->listSubscribers(9999);
                $models = $store->listModels();
                $plans  = ['free'=>0,'starter'=>0,'pro'=>0,'enterprise'=>0];
                $total_keys = 0;
                foreach ($subs as $s) {
                    $plans[$s['plan']] = ($plans[$s['plan']] ?? 0) + 1;
                    $total_keys += count($akm->listKeys($s['id']));
                }
                $prices = ['free'=>0,'starter'=>9,'pro'=>29,'enterprise'=>99];
                $mrr = 0;
                foreach ($plans as $p => $c) $mrr += ($prices[$p] ?? 0) * $c;

                $info('Platform Statistics');
                $sep();
                $out('  Total subscribers : ' . count($subs));
                $out('  Total API keys    : ' . $total_keys);
                $out('  Total models      : ' . count($models));
                $out('  MRR (est.)        : $' . number_format($mrr));
                $sep();
                foreach ($plans as $p => $c) {
                    $bar = str_repeat('█', min($c * 2, 40));
                    $out(sprintf('  %-12s %3d  %s', ucfirst($p), $c, $bar));
                }
                break;

            // ── cron ──────────────────────────────────────────────────
            case 'cron':
                $info('Triggering scheduled jobs…');
                require_once YUGA_ROOT . '/core/Scheduler.php';
                $sch  = new Scheduler(YUGA_ROOT . '/data');
                $jobs = $sch->getDueJobs();
                if (empty($jobs)) { $warn('No jobs are due right now.'); break; }
                foreach ($jobs as $job) {
                    $out("  Running: {$job['type']} — {$job['target']}");
                    try {
                        $sch->runJob($job, $store);
                        $ok("  ✓ Done");
                    } catch (Throwable $e) {
                        $err("  ✗ " . $e->getMessage());
                    }
                }
                $ok('Cron complete.');
                break;

            // ── php eval ──────────────────────────────────────────────
            case 'php':
                $expr = implode(' ', array_slice($argv, 1));
                if (!$expr) { $err('Usage: php <expression>'); break; }
                $warn('Evaluating PHP (admin only)…');
                ob_start();
                try {
                    $result = eval("return ({$expr});");
                    $buffered = ob_get_clean();
                    if ($buffered) $out($buffered);
                    $out(var_export($result, true));
                } catch (Throwable $e) {
                    ob_get_clean();
                    $err($e->getMessage());
                }
                break;

            // ── shell ─────────────────────────────────────────────────
            case 'shell':
            case 'sh':
            case '$':
                $shell_cmd = implode(' ', array_slice($argv, 1));
                if (!$shell_cmd) { $err('Usage: shell <command>'); break; }
                $disabled = explode(',', str_replace(' ','',ini_get('disable_functions')));
                if (in_array('exec', $disabled) && in_array('shell_exec', $disabled)) {
                    $err('shell_exec() and exec() are disabled on this server.');
                    $warn('Ask your host to enable exec() for CLI access.');
                    break;
                }
                $warn("$ {$shell_cmd}");
                if (function_exists('shell_exec') && !in_array('shell_exec', $disabled)) {
                    $output = shell_exec($shell_cmd . ' 2>&1');
                    foreach (explode("\n", trim($output ?? '(no output)')) as $line) {
                        $out($line);
                    }
                } else {
                    exec($shell_cmd . ' 2>&1', $output_arr);
                    foreach ($output_arr as $line) $out($line);
                }
                break;

            // ── train / crawl → redirect to SSE ──────────────────────
            case 'train':
            case 'crawl':
                // These are handled via SSE stream — tell frontend to switch
                echo json_encode(['stream' => true, 'cmd' => $cmd_raw]);
                exit;

            // ── clear ─────────────────────────────────────────────────
            case 'clear':
            case 'cls':
                echo json_encode(['clear' => true]);
                exit;

            // ── empty ─────────────────────────────────────────────────
            case '':
                echo json_encode(['lines' => []]);
                exit;

            // ── unknown ───────────────────────────────────────────────
            default:
                $err("Unknown command: '{$cmd}'. Type 'help' for a list of commands.");
        }

    } catch (Throwable $e) {
        $err('Fatal error: ' . $e->getMessage());
        $err('  in ' . $e->getFile() . ':' . $e->getLine());
    }

    echo json_encode(['lines' => $lines]);
    exit;
}

// ═════════════════════════════════════════════════════════════════════
// RENDER TERMINAL UI
// ═════════════════════════════════════════════════════════════════════
$models_list = $store->listModels();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Yuga CLI</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;background:#0a0d12;font-family:'SF Mono','Cascadia Code','Fira Code','Courier New',monospace;color:#e2e8f0;overflow:hidden}

/* ── Layout ── */
.shell{display:flex;flex-direction:column;height:100vh}
.topbar{background:rgba(8,14,28,.97);border-bottom:1px solid rgba(255,255,255,.06);
  padding:10px 20px;display:flex;align-items:center;gap:14px;flex-shrink:0}
.tb-dots{display:flex;gap:6px}
.tb-dot{width:12px;height:12px;border-radius:50%}
.tb-dot.r{background:#ff5f57}.tb-dot.y{background:#febc2e}.tb-dot.g{background:#28c840}
.tb-title{font-size:13px;color:#6b7280;flex:1;text-align:center;letter-spacing:.04em}
.tb-back{font-size:12px;color:#475569;text-decoration:none;border:1px solid rgba(255,255,255,.08);
  border-radius:6px;padding:4px 12px;transition:.2s}
.tb-back:hover{color:#a5b4fc;border-color:rgba(99,102,241,.4)}

/* ── Output area ── */
.output{flex:1;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:2px;
  scroll-behavior:smooth}
.output::-webkit-scrollbar{width:4px}
.output::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:4px}

.line{font-size:13px;line-height:1.65;white-space:pre-wrap;word-break:break-all;display:flex;gap:8px;align-items:flex-start}
.line.output .ltext {color:#d1d5db}
.line.success .ltext{color:#6ee7b7}
.line.error   .ltext{color:#fda4af}
.line.info    .ltext{color:#93c5fd}
.line.warn    .ltext{color:#fcd34d}
.line.sep     .ltext{color:transparent;user-select:none}
.line.cmd     .ltext{color:#a5b4fc;font-weight:600}
.line.stream  .ltext{color:#67e8f9}
.line .ltime{font-size:10px;color:#374151;flex-shrink:0;margin-top:3px;min-width:54px;letter-spacing:.02em}

/* ── Input row ── */
.input-row{background:rgba(8,14,28,.97);border-top:1px solid rgba(255,255,255,.07);
  padding:12px 20px;display:flex;align-items:center;gap:10px;flex-shrink:0}
.prompt{color:#6366f1;font-size:13px;font-weight:700;white-space:nowrap}
.prompt .host{color:#14b8a6}
.prompt .sep{color:#475569}
#inp{flex:1;background:none;border:none;outline:none;color:#f1f5f9;font-size:13px;
  font-family:inherit;caret-color:#6366f1;letter-spacing:.01em}
.cursor-blink{display:inline-block;width:8px;height:14px;background:#6366f1;
  animation:blink 1s step-end infinite;vertical-align:text-bottom}
@keyframes blink{0%,100%{opacity:1}50%{opacity:0}}

/* ── Status bar ── */
.statusbar{background:#0d1526;border-top:1px solid rgba(255,255,255,.05);
  padding:5px 20px;display:flex;align-items:center;gap:16px;font-size:11px;
  color:#374151;flex-shrink:0}
.sb-item{display:flex;align-items:center;gap:5px}
.sb-dot{width:6px;height:6px;border-radius:50%;background:#6366f1}
.sb-dot.g{background:#10b981}
#sb-status{color:#6b7280}

/* ── Autocomplete ── */
.autocomplete{position:absolute;bottom:60px;left:0;right:0;background:#0d1526;
  border-top:1px solid rgba(99,102,241,.3);padding:6px 0;max-height:160px;overflow-y:auto;z-index:50}
.ac-item{padding:5px 70px;font-size:12px;color:#6b7280;cursor:pointer;display:flex;gap:10px}
.ac-item:hover,.ac-item.sel{background:rgba(99,102,241,.12);color:#a5b4fc}
.ac-item .ac-cmd{color:#e2e8f0;font-weight:600;min-width:130px}
.ac-item .ac-desc{color:#4b5563}

/* ── Scrollbar ── */
*::-webkit-scrollbar{width:4px}
*::-webkit-scrollbar-track{background:transparent}
*::-webkit-scrollbar-thumb{background:rgba(255,255,255,.08);border-radius:4px}
</style>
</head>
<body>

<div class="shell" id="shell">
  <!-- Top bar -->
  <div class="topbar">
    <div class="tb-dots">
      <div class="tb-dot r"></div>
      <div class="tb-dot y"></div>
      <div class="tb-dot g"></div>
    </div>
    <div class="tb-title">yuga-admin@<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') ?> — CLI</div>
    <a href="index.php" class="tb-back">← Admin</a>
  </div>

  <!-- Output -->
  <div class="output" id="out"></div>

  <!-- Autocomplete -->
  <div class="autocomplete" id="ac" style="display:none;position:relative;bottom:auto"></div>

  <!-- Input row -->
  <div class="input-row">
    <span class="prompt">
      <span class="host">yuga</span><span class="sep">:</span>~<span class="sep">$</span>
    </span>
    <input id="inp" type="text" autocomplete="off" autocorrect="off" spellcheck="false"
      placeholder="Type a command… (help to start)" autofocus>
  </div>

  <!-- Status bar -->
  <div class="statusbar">
    <div class="sb-item"><div class="sb-dot g"></div><span>PHP <?= PHP_VERSION ?></span></div>
    <div class="sb-item"><div class="sb-dot"></div><span><?= count($models_list) ?> models</span></div>
    <div class="sb-item"><div class="sb-dot"></div><span id="sb-mem"><?= round(memory_get_usage(true)/1024/1024,1) ?> MB</span></div>
    <div class="sb-item" style="margin-left:auto"><span id="sb-status">ready</span></div>
  </div>
</div>

<script>
const out    = document.getElementById('out');
const inp    = document.getElementById('inp');
const ac     = document.getElementById('ac');
const sbStat = document.getElementById('sb-status');

let history  = JSON.parse(sessionStorage.getItem('yuga_cli_hist') || '[]');
let histIdx  = -1;
let streaming = false;
let streamSource = null;

// ── Command definitions (for autocomplete) ────────────────────────
const COMMANDS = [
  ['help',           'Show all commands'],
  ['version',        'Yuga + PHP version info'],
  ['sysinfo',        'PHP system info & extensions'],
  ['clear',          'Clear terminal'],
  ['models',         'List all models'],
  ['status',         'status [model] — show training status'],
  ['create',         'create <name> [nano|small|medium|large]'],
  ['delete',         'delete <model>'],
  ['train',          'train <model> [steps] — re-train from corpus [STREAM]'],
  ['crawl',          'crawl <model> <url> [pages] — crawl + train [STREAM]'],
  ['learn',          'learn <model> <text>'],
  ['learn-url',      'learn-url <model> <url>'],
  ['subs',           'List all subscribers'],
  ['sub',            'sub <id|email> — subscriber details'],
  ['suspend',        'suspend <subscriber_id>'],
  ['activate',       'activate <subscriber_id>'],
  ['keys',           'keys <subscriber_id>'],
  ['stats',          'Platform statistics'],
  ['config',         'Show config (masked)'],
  ['config-set',     'config-set <key> <value>'],
  ['cron',           'Run scheduled jobs now'],
  ['shell',          'shell <cmd> — run shell command'],
  ['php',            'php <expr> — evaluate PHP'],
];
const MODEL_NAMES = <?= json_encode($models_list) ?>;

// ── Helpers ──────────────────────────────────────────────────────
function now() {
  return new Date().toLocaleTimeString('en', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
}

function addLine(type, text, time = true) {
  const div  = document.createElement('div');
  div.className = 'line ' + type;
  const ts = time ? `<span class="ltime">${now()}</span>` : '';
  div.innerHTML = ts + `<span class="ltext">${escHtml(text)}</span>`;
  out.appendChild(div);
  out.scrollTop = out.scrollHeight;
  return div;
}

function addRawLine(type, html) {
  const div = document.createElement('div');
  div.className = 'line ' + type;
  div.innerHTML = `<span class="ltime">${now()}</span><span class="ltext">${html}</span>`;
  out.appendChild(div);
  out.scrollTop = out.scrollHeight;
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function setStatus(s, color = '#6b7280') {
  sbStat.textContent = s;
  sbStat.style.color = color;
}

// ── Print banner on load ──────────────────────────────────────────
function showBanner() {
  addRawLine('info', '<span style="color:#6366f1;font-size:15px;font-weight:700">Yuga CLI v1.0</span>  <span style="color:#374151">— Self-learning LM Admin Terminal</span>');
  addLine('output', 'Type  help  to see all commands.  Use ↑/↓ for history.  Tab to autocomplete.');
  addLine('sep', '');
}
showBanner();

// ── Run a command ─────────────────────────────────────────────────
async function runCmd(raw) {
  const cmd = raw.trim();
  if (!cmd) return;

  // Save to history
  history = [cmd, ...history.filter(h => h !== cmd)].slice(0, 100);
  histIdx = -1;
  sessionStorage.setItem('yuga_cli_hist', JSON.stringify(history));

  addLine('cmd', '$ ' + cmd);

  if (streaming) {
    addLine('warn', 'A streaming job is already running. Wait for it to complete.');
    return;
  }

  setStatus('running…', '#f59e0b');
  inp.disabled = true;

  try {
    const fd = new FormData();
    fd.append('run', '1');
    fd.append('cmd', cmd);
    const r = await fetch('cli.php', { method: 'POST', body: fd });
    const d = await r.json();

    if (d.clear) {
      out.innerHTML = '';
      showBanner();
    } else if (d.stream) {
      // Switch to SSE for long-running jobs
      runStream(cmd);
      return; // don't re-enable input yet
    } else {
      (d.lines || []).forEach(l => addLine(l.type, l.text));
    }
  } catch (e) {
    addLine('error', 'Request failed: ' + e.message);
  }

  addLine('sep', '');
  setStatus('ready', '#6b7280');
  inp.disabled = false;
  inp.focus();
}

// ── SSE streaming for crawl / train ──────────────────────────────
function runStream(cmd) {
  streaming = true;
  setStatus('streaming…', '#06b6d4');
  addLine('stream', '⟳ Streaming job started — press Ctrl+C to cancel');

  const url = 'cli.php?stream=1&cmd=' + encodeURIComponent(cmd);
  streamSource = new EventSource(url);

  streamSource.onmessage = (e) => {
    const d = JSON.parse(e.data);
    if (d.done) {
      streamSource.close();
      streaming = false;
      streamSource = null;
      addLine('sep', '');
      setStatus('ready', '#6b7280');
      inp.disabled = false;
      inp.focus();
      return;
    }
    addLine(d.type || 'stream', d.text || '');
    out.scrollTop = out.scrollHeight;
  };

  streamSource.onerror = () => {
    streamSource.close();
    streaming = false;
    addLine('error', 'Stream connection error or job complete.');
    addLine('sep', '');
    setStatus('ready', '#6b7280');
    inp.disabled = false;
    inp.focus();
  };
}

// ── Keyboard handling ─────────────────────────────────────────────
inp.addEventListener('keydown', (e) => {
  if (e.key === 'Enter') {
    const val = inp.value;
    inp.value = '';
    hideAC();
    runCmd(val);
    return;
  }
  if (e.key === 'ArrowUp') {
    e.preventDefault();
    if (histIdx < history.length - 1) {
      histIdx++;
      inp.value = history[histIdx];
    }
    return;
  }
  if (e.key === 'ArrowDown') {
    e.preventDefault();
    if (histIdx > 0) { histIdx--; inp.value = history[histIdx]; }
    else { histIdx = -1; inp.value = ''; }
    return;
  }
  if (e.key === 'Tab') {
    e.preventDefault();
    tabComplete();
    return;
  }
  if (e.key === 'Escape') {
    hideAC();
    return;
  }
  if (e.ctrlKey && e.key === 'l') {
    e.preventDefault();
    out.innerHTML = '';
    showBanner();
    return;
  }
  if (e.ctrlKey && e.key === 'c') {
    if (streaming && streamSource) {
      streamSource.close();
      streaming = false;
      addLine('warn', '^C — streaming job cancelled');
      addLine('sep', '');
      setStatus('ready', '#6b7280');
      inp.disabled = false;
      inp.focus();
    } else {
      inp.value = '';
      addLine('warn', '^C');
    }
    return;
  }
});

inp.addEventListener('input', () => {
  showAC(inp.value);
});

// ── Autocomplete ──────────────────────────────────────────────────
let acItems = [], acSel = -1;

function showAC(val) {
  if (!val) { hideAC(); return; }
  const parts = val.split(/\s+/);
  const word  = parts[0].toLowerCase();

  let matches = [];
  if (parts.length === 1) {
    // Complete command names
    matches = COMMANDS.filter(([c]) => c.startsWith(word) && c !== word);
  } else if (['status','train','delete','learn','learn-url','crawl'].includes(word) && parts.length === 2) {
    // Complete model names
    matches = MODEL_NAMES.filter(m => m.startsWith(parts[1])).map(m => [m, '(model)']);
  }

  if (!matches.length) { hideAC(); return; }

  acItems = matches;
  acSel   = -1;
  ac.innerHTML = matches.map((m, i) =>
    `<div class="ac-item" onclick="applyAC(${i})"><span class="ac-cmd">${escHtml(m[0])}</span><span class="ac-desc">${escHtml(m[1] || '')}</span></div>`
  ).join('');
  ac.style.display = 'block';
}

function hideAC() { ac.style.display = 'none'; acItems = []; acSel = -1; }

function applyAC(i) {
  const parts = inp.value.split(/\s+/);
  if (parts.length === 1) {
    inp.value = acItems[i][0] + ' ';
  } else {
    parts[parts.length - 1] = acItems[i][0];
    inp.value = parts.join(' ') + ' ';
  }
  hideAC();
  inp.focus();
}

function tabComplete() {
  if (!acItems.length) { showAC(inp.value); return; }
  acSel = (acSel + 1) % acItems.length;
  document.querySelectorAll('.ac-item').forEach((el, i) =>
    el.classList.toggle('sel', i === acSel));
  applyAC(acSel);
}

// Keep focus on input
document.addEventListener('click', () => inp.focus());
inp.focus();
</script>
</body>
</html>
