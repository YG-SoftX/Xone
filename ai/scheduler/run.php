<?php
/**
 * Scheduler runner — called by cPanel master cron every minute
 *
 * cPanel cron (add this once):
 *   * * * * * php /home/username/public_html/yuga/scheduler/run.php >> /dev/null 2>&1
 */
define('YUGA_ROOT', dirname(__DIR__));
require_once YUGA_ROOT . '/core/Scheduler.php';
require_once YUGA_ROOT . '/core/YugaLM.php';
require_once YUGA_ROOT . '/core/ModelStore.php';
require_once YUGA_ROOT . '/core/Tokenizer.php';
require_once YUGA_ROOT . '/core/Transformer.php';
require_once YUGA_ROOT . '/core/Retriever.php';
require_once YUGA_ROOT . '/core/Brain.php';
require_once YUGA_ROOT . '/core/SelfLearner.php';
require_once YUGA_ROOT . '/core/Webhooks.php';

$config = file_exists(YUGA_ROOT . '/config.php') ? require YUGA_ROOT . '/config.php' : [];
$data   = YUGA_ROOT . '/data';

$scheduler = new Scheduler($data);
$store     = new ModelStore($data);
$webhooks  = new Webhooks($data);
$due       = $scheduler->due();

if (!$due) exit(0);

set_time_limit(600);
ini_set('memory_limit', $config['memory_limit'] ?? '128M');

foreach ($due as $task) {
    $ok  = false;
    $msg = '';
    try {
        switch ($task['type']) {

            case 'crawl_url':
                if (!$task['url']) throw new Exception('No URL configured');
                $learner = new SelfLearner($task['model'], $store);
                $learner->max_pages = $task['max_pages'] ?? 20;
                $r   = $learner->learnFromSite($task['url']);
                $ok  = true;
                $msg = "Crawled {$r['pages']} pages. Loss: {$r['loss']}";
                $webhooks->fire('training.complete', [
                    'model'  => $task['model'],
                    'source' => 'scheduler:crawl_url',
                    'pages'  => $r['pages'],
                    'loss'   => $r['loss'],
                ]);
                break;

            case 'retrain':
                $corpus = YUGA_ROOT . '/training/corpus.txt';
                if (!file_exists($corpus)) throw new Exception('No corpus.txt found');
                $text    = file_get_contents($corpus);
                $brain   = new Brain($task['model'], $store);
                $r       = $brain->learn($text, $task['steps'] ?? 5000);
                $ok      = true;
                $msg     = "Retrained. Loss: {$r['loss']} Steps: {$r['steps']}";
                $webhooks->fire('training.complete', [
                    'model' => $task['model'],
                    'source'=> 'scheduler:retrain',
                    'loss'  => $r['loss'],
                    'steps' => $r['steps'],
                ]);
                break;

            case 'ping_webhook':
                if (!$task['url']) throw new Exception('No URL configured');
                $ch = curl_init($task['url']);
                curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10,
                    CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>json_encode(['event'=>'scheduler.ping','ts'=>time()]),
                    CURLOPT_HTTPHEADER=>['Content-Type: application/json']]);
                curl_exec($ch);
                $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                $ok  = $status >= 200 && $status < 300;
                $msg = "HTTP $status";
                break;

            case 'send_report':
                // Load usage stats and email to admin
                require_once YUGA_ROOT . '/subscriptions/APIKeyManager.php';
                require_once YUGA_ROOT . '/subscriptions/Plans.php';
                require_once YUGA_ROOT . '/core/Mailer.php';
                $akm    = new APIKeyManager($data);
                $stats  = $akm->getGlobalStats();
                $admin_email = $config['mail']['from_email'] ?? '';
                if ($admin_email) {
                    $body = "<h2>Yuga Weekly Report</h2>
                        <p>Subscribers: {$stats['total_subscribers']}<br>
                        API calls today: {$stats['calls_today']}<br>
                        MRR: \${$stats['mrr']}</p>";
                    (new Mailer($config))->send($admin_email, 'Admin', 'Yuga Weekly Report', $body);
                }
                $ok  = true;
                $msg = "Report sent to $admin_email";
                break;

            case 'neural_evolution':
                $accountPath = $config['yg_account_path'] ?? dirname(YUGA_ROOT) . '/yg-account';
                
                // 1. Harvest Social Intelligence
                $harvestCmd = "php {$accountPath}/artisan yuga:harvest";
                exec($harvestCmd, $harvestOutput, $harvestRet);
                
                // 2. Harvest Financial Intelligence
                $finHarvestCmd = "php {$accountPath}/artisan yuga:harvest-financial";
                exec($finHarvestCmd, $finOutput, $finRet);

                // 3. Harvest Private Sovereignty
                $privHarvestCmd = "php {$accountPath}/artisan yuga:harvest-private";
                exec($privHarvestCmd, $privOutput, $privRet);

                // 4. Ingest All into Corpora
                $harvestDir = "{$accountPath}/storage/app/yuga";
                
                // Public Corpus
                exec("php " . YUGA_ROOT . "/training/build_corpus.php --append --harvest={$harvestDir}/training", $o1);
                // Financial Intelligence
                exec("php " . YUGA_ROOT . "/training/build_corpus.php --append --harvest-financial={$harvestDir}/financial", $o2);
                // Private Sovereignty (User-partitioned)
                exec("php " . YUGA_ROOT . "/training/build_corpus.php --append --harvest-private={$harvestDir}/private", $o3);

                // 5. Neural Training (Main Model)
                $modelName = $task['model'] ?? $config['default_model'];
                $steps = $task['steps'] ?? 5000;
                $trainCmd = "php " . YUGA_ROOT . "/training/train.php --model={$modelName} --steps={$steps}";
                exec($trainCmd, $trainOutput, $trainRet);

                $ok  = true;
                $msg = "Triple Evolution Complete: Social, Financial, and Private Intelligence updated.";
                $webhooks->fire('training.complete', [
                    'model'  => $modelName,
                    'source' => 'scheduler:triple_evolution',
                    'steps'  => $steps
                ]);
                break;

            default:
                throw new Exception("Unknown task type: {$task['type']}");
        }
    } catch (Exception $e) {
        $ok  = false;
        $msg = $e->getMessage();
    }

    $scheduler->markRun($task['id'], $ok, $msg);
    echo date('[Y-m-d H:i:s]') . " [{$task['type']}] {$task['label']}: " . ($ok ? 'OK' : 'FAIL') . " — $msg\n";
}
