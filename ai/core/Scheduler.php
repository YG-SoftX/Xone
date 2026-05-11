<?php
/**
 * Scheduler — cron-style task runner
 *
 * Task types:
 *   crawl_url    — crawl a URL and train the model
 *   retrain      — retrain model on existing corpus
 *   ping_webhook — call a URL (health check / trigger)
 *   send_report  — email usage report to admin
 *
 * Master cron (add once to cPanel):
 *   * * * * * php /path/to/yuga/scheduler/run.php
 */
class Scheduler {

    private string $db_path;

    // cron field values: minute, hour, day, month, weekday  (null = *)
    public function __construct(string $data_dir) {
        $this->db_path = $data_dir . '/scheduler.json';
        if (!file_exists($this->db_path)) {
            file_put_contents($this->db_path, json_encode(['tasks' => [], 'log' => []]));
        }
    }

    // ── Create a task ────────────────────────────────────────────────────
    public function create(array $params): array {
        $data = $this->load();
        $id   = 'task_' . bin2hex(random_bytes(6));
        $task = [
            'id'          => $id,
            'label'       => $params['label'] ?? $params['type'],
            'type'        => $params['type'],        // crawl_url | retrain | ping_webhook | send_report
            'model'       => $params['model']        ?? 'default',
            'url'         => $params['url']          ?? '',
            'max_pages'   => (int)($params['max_pages'] ?? 20),
            'steps'       => (int)($params['steps']     ?? 5000),
            'schedule'    => $params['schedule']     ?? 'daily',   // hourly|daily|weekly|monthly|custom
            'cron_expr'   => $params['cron_expr']    ?? '',        // custom: "0 3 * * 1"
            'active'      => true,
            'created'     => time(),
            'last_run'    => null,
            'next_run'    => $this->nextRun($params['schedule'] ?? 'daily', $params['cron_expr'] ?? ''),
            'last_status' => null,
            'run_count'   => 0,
        ];
        $data['tasks'][] = $task;
        $this->save($data);
        return $task;
    }

    // ── Delete a task ────────────────────────────────────────────────────
    public function delete(string $id): void {
        $data = $this->load();
        $data['tasks'] = array_values(array_filter($data['tasks'], fn($t) => $t['id'] !== $id));
        $this->save($data);
    }

    // ── Toggle active ────────────────────────────────────────────────────
    public function toggle(string $id, bool $active): void {
        $data = $this->load();
        foreach ($data['tasks'] as &$t) {
            if ($t['id'] === $id) { $t['active'] = $active; break; }
        }
        $this->save($data);
    }

    // ── List tasks ───────────────────────────────────────────────────────
    public function list(): array { return $this->load()['tasks'] ?? []; }

    // ── Recent run log ───────────────────────────────────────────────────
    public function recentLog(int $n = 30): array {
        $log = $this->load()['log'] ?? [];
        return array_slice(array_reverse($log), 0, $n);
    }

    // ── Get tasks due to run right now ───────────────────────────────────
    public function due(): array {
        $now  = time();
        $data = $this->load();
        return array_filter($data['tasks'] ?? [], fn($t) => $t['active'] && ($t['next_run'] ?? 0) <= $now);
    }

    // ── Mark task as run ─────────────────────────────────────────────────
    public function markRun(string $id, bool $ok, string $message = ''): void {
        $data = $this->load();
        foreach ($data['tasks'] as &$t) {
            if ($t['id'] === $id) {
                $t['last_run']    = time();
                $t['last_status'] = $ok ? 'ok' : 'error';
                $t['run_count']++;
                $t['next_run']    = $this->nextRun($t['schedule'], $t['cron_expr'] ?? '');
                break;
            }
        }
        $data['log'][] = [
            'task_id' => $id,
            'ok'      => $ok,
            'message' => $message,
            'ts'      => time(),
        ];
        if (count($data['log']) > 500) $data['log'] = array_slice($data['log'], -500);
        $this->save($data);
    }

    // ── Next run timestamp from schedule ────────────────────────────────
    public function nextRun(string $schedule, string $cron_expr = ''): int {
        $now = time();
        switch ($schedule) {
            case 'hourly':  return $now + 3600;
            case 'daily':   return strtotime('tomorrow midnight') ?: ($now + 86400);
            case 'weekly':  return strtotime('next monday midnight') ?: ($now + 604800);
            case 'monthly': return strtotime('first day of next month midnight') ?: ($now + 2592000);
            case 'custom':
                // Simple cron: "minute hour day month weekday"
                if ($cron_expr) return $this->parseCron($cron_expr, $now);
                return $now + 86400;
            default: return $now + 86400;
        }
    }

    // ── Basic cron expression parser → next timestamp ───────────────────
    private function parseCron(string $expr, int $from): int {
        $parts = preg_split('/\s+/', trim($expr));
        if (count($parts) < 5) return $from + 86400;
        [$min, $hour, $dom, $mon, $dow] = $parts;

        // Advance by 1 minute at a time (max 1 week look-ahead)
        $t = $from + 60;
        for ($i = 0; $i < 10080; $i++, $t += 60) {
            if ($this->cronMatch((int)date('i',$t), $min)
             && $this->cronMatch((int)date('H',$t), $hour)
             && $this->cronMatch((int)date('j',$t), $dom)
             && $this->cronMatch((int)date('n',$t), $mon)
             && $this->cronMatch((int)date('w',$t), $dow)
            ) return $t;
        }
        return $from + 86400;
    }

    private function cronMatch(int $val, string $field): bool {
        if ($field === '*') return true;
        if (is_numeric($field)) return $val === (int)$field;
        if (str_starts_with($field, '*/')) return $val % (int)substr($field,2) === 0;
        if (str_contains($field, ',')) return in_array($val, array_map('intval', explode(',', $field)));
        if (str_contains($field, '-')) {
            [$lo, $hi] = explode('-', $field);
            return $val >= (int)$lo && $val <= (int)$hi;
        }
        return false;
    }

    private function load(): array {
        return json_decode(file_get_contents($this->db_path), true) ?? ['tasks' => [], 'log' => []];
    }
    private function save(array $data): void {
        file_put_contents($this->db_path, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
    }
}
