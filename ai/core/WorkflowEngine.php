<?php
/**
 * WorkflowEngine — Multi-step training automation for Yuga
 *
 * A workflow is an ordered list of steps. Each step has a type and config.
 * Workflows are stored in data/workflows.json.
 * Execution state is tracked per-run in data/workflow_runs.json.
 *
 * Step types:
 *   crawl_url   — crawl a website and train
 *   crawl_nepali — crawl a NepaliPipeline source by key
 *   crawl_group  — run a NepaliPipeline group
 *   train_text   — train on raw text
 *   sleep        — wait N seconds (polite crawling)
 *   notify_email — send an email when workflow completes
 *   set_default  — set this model as default after training
 *
 * Usage:
 *   $wf = new WorkflowEngine(YUGA_ROOT . '/data');
 *   $id = $wf->create('Nepal AI Daily', 'yug10', [...steps...]);
 *   $wf->run($id, $brain, $cb);  // $cb receives SSE-style progress events
 */
class WorkflowEngine {

    private string $file;
    private string $runs_file;
    private array  $workflows;

    public function __construct(string $data_dir) {
        $this->file      = rtrim($data_dir, '/') . '/workflows.json';
        $this->runs_file = rtrim($data_dir, '/') . '/workflow_runs.json';
        $raw             = file_exists($this->file) ? json_decode(file_get_contents($this->file), true) : null;
        $this->workflows = is_array($raw) ? $raw : [];
    }

    // ── CRUD ──────────────────────────────────────────────────────────

    public function create(string $name, string $model, array $steps, array $opts = []): string {
        $id = 'wf_' . uniqid();
        $this->workflows[$id] = [
            'id'          => $id,
            'name'        => substr(trim($name), 0, 100),
            'model'       => preg_replace('/[^a-z0-9_-]/', '', $model),
            'steps'       => $this->validateSteps($steps),
            'enabled'     => true,
            'schedule'    => $opts['schedule'] ?? '',   // cron string if auto-run
            'created_at'  => time(),
            'updated_at'  => time(),
            'last_run_at' => null,
            'last_status' => null,
        ];
        $this->save();
        return $id;
    }

    public function update(string $id, array $data): bool {
        if (!isset($this->workflows[$id])) return false;
        if (isset($data['steps'])) $data['steps'] = $this->validateSteps($data['steps']);
        $data['updated_at'] = time();
        $this->workflows[$id] = array_merge($this->workflows[$id], $data, ['id' => $id]);
        $this->save();
        return true;
    }

    public function delete(string $id): void {
        unset($this->workflows[$id]);
        $this->save();
    }

    public function get(string $id): ?array {
        return $this->workflows[$id] ?? null;
    }

    public function list(): array {
        $all = array_values($this->workflows);
        usort($all, fn($a, $b) => ($b['updated_at'] ?? 0) <=> ($a['updated_at'] ?? 0));
        return $all;
    }

    public function toggle(string $id, bool $enabled): void {
        if (isset($this->workflows[$id])) {
            $this->workflows[$id]['enabled'] = $enabled;
            $this->save();
        }
    }

    // ── Execution ─────────────────────────────────────────────────────

    /**
     * Run all steps of a workflow.
     * $brain — Brain instance for the workflow's model
     * $cb    — callable(array $event) for SSE progress
     */
    public function run(string $id, object $brain, callable $cb = null): array {
        $wf = $this->get($id);
        if (!$wf) return ['error' => "Workflow $id not found"];
        if (!$wf['enabled']) return ['error' => "Workflow is disabled"];

        $run_id    = 'run_' . uniqid();
        $started   = time();
        $results   = [];
        $emit      = function(string $type, string $msg, array $extra = []) use ($cb, $run_id) {
            $event = array_merge(['type' => $type, 'msg' => $msg, 'run_id' => $run_id], $extra);
            if ($cb) ($cb)($event);
        };

        $this->logRun($id, $run_id, 'running', $started);
        $total_steps = count($wf['steps']);
        $emit('info', "Workflow '{$wf['name']}' started — {$wf['model']} · {$total_steps} steps");

        $step_num = 0;
        foreach ($wf['steps'] as $step) {
            $step_num++;
            $type  = $step['type'] ?? 'unknown';
            $label = $step['label'] ?? $type;
            $emit('step', "Step {$step_num}/{$total_steps}: {$label}", ['step' => $step_num]);

            try {
                $r = $this->executeStep($step, $brain, $emit);
                $results[] = ['step' => $step_num, 'type' => $type, 'result' => $r];
                if (isset($r['error'])) {
                    $emit('error', "Step $step_num failed: {$r['error']}");
                } else {
                    $emit('ok', "Step $step_num done: " . ($r['summary'] ?? 'OK'));
                }
            } catch (Throwable $e) {
                $emit('error', "Step $step_num threw: " . $e->getMessage());
                $results[] = ['step' => $step_num, 'type' => $type, 'result' => ['error' => $e->getMessage()]];
            }
        }

        $elapsed = time() - $started;
        $this->workflows[$id]['last_run_at'] = time();
        $this->workflows[$id]['last_status'] = 'completed';
        $this->save();
        $this->logRun($id, $run_id, 'completed', $started, $results);

        $emit('done', "Workflow complete in {$elapsed}s.", ['elapsed' => $elapsed, 'steps' => count($results)]);
        return ['ok' => true, 'run_id' => $run_id, 'elapsed' => $elapsed, 'steps' => $results];
    }

    // ── Step executor ─────────────────────────────────────────────────

    private function executeStep(array $step, object $brain, callable $emit): array {
        $type = $step['type'] ?? '';

        switch ($type) {

            case 'crawl_url': {
                $url       = $step['url'] ?? '';
                $max_pages = (int)($step['max_pages'] ?? 20);
                if (!filter_var($url, FILTER_VALIDATE_URL)) return ['error' => "Invalid URL: $url"];
                $r = $brain->learnFromSite($url, $max_pages, function($p) use ($emit, $url) {
                    $emit('page', "Page {$p['page']}: {$p['url']}");
                });
                return array_merge($r, ['summary' => "Crawled {$r['pages']} pages · Loss: {$r['loss']}"]);
            }

            case 'crawl_nepali': {
                require_once __DIR__ . '/NepaliPipeline.php';
                $key = $step['source'] ?? '';
                $pl  = new NepaliPipeline($brain, function($e) use ($emit) {
                    $emit($e['type'] ?? 'info', $e['msg'] ?? '');
                });
                $r = $pl->runSource($key);
                return array_merge($r, ['summary' => "Source '$key': {$r['pages']} pages"]);
            }

            case 'crawl_group': {
                require_once __DIR__ . '/NepaliPipeline.php';
                $group = $step['group'] ?? '';
                $pl    = new NepaliPipeline($brain, function($e) use ($emit) {
                    $emit($e['type'] ?? 'info', $e['msg'] ?? '');
                });
                $r = $pl->runGroup($group);
                return array_merge($r, ['summary' => "Group '$group': {$r['total_pages']} total pages"]);
            }

            case 'train_text': {
                $text  = $step['text'] ?? '';
                $steps = (int)($step['steps'] ?? 20000);
                if (strlen($text) < 20) return ['error' => 'Text too short'];
                $r = $brain->learn($text, $steps);
                return array_merge($r, ['summary' => "Text trained · Loss: {$r['loss']} · Steps: {$r['steps']}"]);
            }

            case 'sleep': {
                $secs = min((int)($step['seconds'] ?? 5), 300);
                $emit('info', "Sleeping {$secs}s…");
                sleep($secs);
                return ['summary' => "Slept {$secs}s"];
            }

            case 'set_default': {
                // Write default_model to config via ConfigWriter
                $model = $brain->model ?? ($step['model'] ?? '');
                if ($model) {
                    require_once __DIR__ . '/ConfigWriter.php';
                    $cw = new ConfigWriter(dirname(__DIR__) . '/config.php');
                    $cw->update(['default_model' => $model]);
                    return ['summary' => "Default model set to '$model'"];
                }
                return ['error' => 'No model name'];
            }

            case 'notify_email': {
                $to      = $step['to'] ?? '';
                $subject = $step['subject'] ?? 'Workflow complete';
                $message = $step['message'] ?? 'Your Yuga training workflow has completed.';
                if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    require_once __DIR__ . '/Mailer.php';
                    $config = file_exists(dirname(__DIR__).'/config.php') ? require dirname(__DIR__).'/config.php' : [];
                    try {
                        (new Mailer($config))->sendRaw($to, $subject, '<p>' . nl2br(htmlspecialchars($message)) . '</p>');
                        return ['summary' => "Email sent to $to"];
                    } catch (Exception $e) {
                        return ['error' => $e->getMessage()];
                    }
                }
                return ['error' => "Invalid email: $to"];
            }

            default:
                return ['error' => "Unknown step type: $type"];
        }
    }

    // ── Step definitions (for UI) ─────────────────────────────────────

    public static function stepTypes(): array {
        return [
            'crawl_url'     => ['label' => 'Crawl website',             'fields' => ['url', 'max_pages']],
            'crawl_nepali'  => ['label' => 'Crawl Nepali source',       'fields' => ['source']],
            'crawl_group'   => ['label' => 'Crawl Nepali source group', 'fields' => ['group']],
            'train_text'    => ['label' => 'Train on pasted text',      'fields' => ['text', 'steps']],
            'sleep'         => ['label' => 'Wait (pause between steps)','fields' => ['seconds']],
            'set_default'   => ['label' => 'Set as default model',      'fields' => []],
            'notify_email'  => ['label' => 'Send completion email',     'fields' => ['to', 'subject', 'message']],
        ];
    }

    // ── Run log ───────────────────────────────────────────────────────

    public function recentRuns(string $wf_id = '', int $limit = 20): array {
        $raw  = file_exists($this->runs_file) ? json_decode(file_get_contents($this->runs_file), true) : [];
        $runs = is_array($raw) ? $raw : [];
        if ($wf_id) $runs = array_filter($runs, fn($r) => ($r['workflow_id'] ?? '') === $wf_id);
        $runs = array_values($runs);
        usort($runs, fn($a, $b) => ($b['started_at'] ?? 0) <=> ($a['started_at'] ?? 0));
        return array_slice($runs, 0, $limit);
    }

    private function logRun(string $wf_id, string $run_id, string $status, int $started, array $results = []): void {
        $raw  = file_exists($this->runs_file) ? json_decode(file_get_contents($this->runs_file), true) : [];
        $runs = is_array($raw) ? $raw : [];
        $runs[$run_id] = [
            'run_id'      => $run_id,
            'workflow_id' => $wf_id,
            'status'      => $status,
            'started_at'  => $started,
            'finished_at' => $status === 'running' ? null : time(),
            'steps'       => count($results),
        ];
        // Keep only last 100 runs
        if (count($runs) > 100) {
            uasort($runs, fn($a, $b) => ($b['started_at'] ?? 0) <=> ($a['started_at'] ?? 0));
            $runs = array_slice($runs, 0, 100, true);
        }
        file_put_contents($this->runs_file, json_encode($runs, JSON_PRETTY_PRINT));
    }

    // ── Validate + sanitize step array ────────────────────────────────

    private function validateSteps(array $steps): array {
        $valid_types = array_keys(self::stepTypes());
        $out = [];
        foreach ($steps as $s) {
            if (!is_array($s)) continue;
            $type = $s['type'] ?? '';
            if (!in_array($type, $valid_types)) continue;
            $clean = ['type' => $type, 'label' => substr(trim($s['label'] ?? $type), 0, 100)];
            // Copy allowed fields
            foreach (['url','max_pages','source','group','text','steps','seconds','to','subject','message','model'] as $f) {
                if (isset($s[$f])) $clean[$f] = $s[$f];
            }
            $out[] = $clean;
        }
        return $out;
    }

    private function save(): void {
        file_put_contents(
            $this->file,
            json_encode($this->workflows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
