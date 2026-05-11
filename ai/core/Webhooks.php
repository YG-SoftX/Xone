<?php
/**
 * Webhooks — fire events to registered URLs
 *
 * Events:
 *   chat.message        — every chat API call
 *   training.complete   — after a model finishes training
 *   subscriber.created  — new subscriber signed up
 *   subscriber.upgraded — plan changed
 *   subscriber.suspended
 *   payment.success     — payment confirmed
 *   api.limit_warning   — subscriber hit 80% of daily limit
 *   api.limit_exceeded  — subscriber hit 100%
 */
class Webhooks {

    private string $db_path;
    private array  $EVENTS = [
        'chat.message', 'training.complete',
        'subscriber.created', 'subscriber.upgraded', 'subscriber.suspended',
        'payment.success', 'api.limit_warning', 'api.limit_exceeded',
    ];

    public function __construct(string $data_dir) {
        $this->db_path = $data_dir . '/webhooks.json';
        if (!file_exists($this->db_path)) {
            file_put_contents($this->db_path, json_encode(['hooks' => [], 'log' => []]));
        }
    }

    // ── Register a new webhook ────────────────────────────────────────────
    public function register(string $url, array $events, string $label = '', string $secret = ''): array {
        if (!filter_var($url, FILTER_VALIDATE_URL)) throw new Exception('Invalid URL');
        $data = $this->load();
        $id   = 'wh_' . bin2hex(random_bytes(8));
        $data['hooks'][] = [
            'id'      => $id,
            'url'     => $url,
            'events'  => array_values(array_intersect($events, $this->EVENTS)),
            'label'   => $label ?: $url,
            'secret'  => $secret ?: bin2hex(random_bytes(16)),
            'active'  => true,
            'created' => time(),
            'last_fired'   => null,
            'fail_count'   => 0,
            'success_count'=> 0,
        ];
        $this->save($data);
        return end($data['hooks']);
    }

    // ── Delete a webhook ─────────────────────────────────────────────────
    public function delete(string $id): void {
        $data = $this->load();
        $data['hooks'] = array_values(array_filter($data['hooks'], fn($h) => $h['id'] !== $id));
        $this->save($data);
    }

    // ── Toggle active state ───────────────────────────────────────────────
    public function toggle(string $id, bool $active): void {
        $data = $this->load();
        foreach ($data['hooks'] as &$h) {
            if ($h['id'] === $id) { $h['active'] = $active; break; }
        }
        $this->save($data);
    }

    // ── List all hooks ───────────────────────────────────────────────────
    public function list(): array {
        return $this->load()['hooks'] ?? [];
    }

    // ── Recent delivery log ──────────────────────────────────────────────
    public function recentLog(int $n = 30): array {
        $log = $this->load()['log'] ?? [];
        return array_slice(array_reverse($log), 0, $n);
    }

    // ── Fire an event ─────────────────────────────────────────────────────
    public function fire(string $event, array $payload): void {
        $data  = $this->load();
        $hooks = array_filter($data['hooks'] ?? [], fn($h) => $h['active'] && in_array($event, $h['events']));

        if (!$hooks) return;

        $body = json_encode([
            'event'     => $event,
            'timestamp' => time(),
            'payload'   => $payload,
        ]);

        foreach ($hooks as &$hook) {
            $sig = 'sha256=' . hash_hmac('sha256', $body, $hook['secret']);
            [$ok, $status] = $this->deliver($hook['url'], $body, $sig);

            $hook['last_fired'] = time();
            if ($ok) {
                $hook['success_count']++;
                $hook['fail_count'] = 0;
            } else {
                $hook['fail_count']++;
                // Auto-disable after 10 consecutive failures
                if ($hook['fail_count'] >= 10) $hook['active'] = false;
            }

            // Append to log (keep last 200)
            $data['log'][] = [
                'hook_id'   => $hook['id'],
                'label'     => $hook['label'],
                'event'     => $event,
                'status'    => $status,
                'ok'        => $ok,
                'ts'        => time(),
            ];
        }

        $data['hooks'] = array_values($hooks) + array_values(
            array_filter($data['hooks'], fn($h) => !in_array($h, $hooks))
        );
        // Rebuild hooks properly
        $updatedIds = array_column(array_values($hooks), 'id');
        foreach ($data['hooks'] as &$orig) {
            foreach ($hooks as $updated) {
                if ($orig['id'] === $updated['id']) { $orig = $updated; break; }
            }
        }

        if (count($data['log']) > 200) {
            $data['log'] = array_slice($data['log'], -200);
        }

        $this->save($data);
    }

    // ── Test a webhook (sends a ping event) ──────────────────────────────
    public function test(string $id): array {
        $data = $this->load();
        $hook = null;
        foreach ($data['hooks'] as $h) { if ($h['id'] === $id) { $hook = $h; break; } }
        if (!$hook) return ['ok' => false, 'error' => 'Hook not found'];

        $body = json_encode(['event' => 'ping', 'timestamp' => time(), 'payload' => ['message' => 'Yuga webhook test']]);
        $sig  = 'sha256=' . hash_hmac('sha256', $body, $hook['secret']);
        [$ok, $status] = $this->deliver($hook['url'], $body, $sig);
        return ['ok' => $ok, 'http_status' => $status];
    }

    // ── HTTP delivery ────────────────────────────────────────────────────
    private function deliver(string $url, string $body, string $sig): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Yuga-Event: webhook',
                'X-Yuga-Signature: ' . $sig,
                'User-Agent: Yuga-Webhook/1.0',
            ],
        ]);
        curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$status >= 200 && $status < 300, $status];
    }

    private function load(): array {
        return json_decode(file_get_contents($this->db_path), true) ?? ['hooks' => [], 'log' => []];
    }
    private function save(array $data): void {
        file_put_contents($this->db_path, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
    }

    public function allEvents(): array { return $this->EVENTS; }
}
