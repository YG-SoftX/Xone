<?php
class APIKeyManager {

    private SQLite3 $db;

    public function __construct(string $data_dir) {
        $this->db = new SQLite3($data_dir . '/yuga_subscriptions.db');
        $this->db->enableExceptions(true);
        $this->migrate();
    }

    private function migrate(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS subscribers (
                id          TEXT PRIMARY KEY,
                name        TEXT NOT NULL,
                email       TEXT NOT NULL UNIQUE,
                plan        TEXT DEFAULT 'free',
                status      TEXT DEFAULT 'active',
                created_at  INTEGER,
                renewed_at  INTEGER,
                expires_at  INTEGER
            );

            CREATE TABLE IF NOT EXISTS api_keys (
                key_id      TEXT PRIMARY KEY,
                key_hash    TEXT NOT NULL UNIQUE,
                key_prefix  TEXT NOT NULL,
                sub_id      TEXT NOT NULL,
                label       TEXT DEFAULT '',
                status      TEXT DEFAULT 'active',
                created_at  INTEGER,
                last_used   INTEGER,
                FOREIGN KEY(sub_id) REFERENCES subscribers(id)
            );

            CREATE TABLE IF NOT EXISTS usage_log (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                key_id      TEXT NOT NULL,
                sub_id      TEXT NOT NULL,
                action      TEXT NOT NULL,
                model       TEXT DEFAULT '',
                ts          INTEGER NOT NULL,
                day         TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS webhooks (
                id          TEXT PRIMARY KEY,
                sub_id      TEXT NOT NULL,
                url         TEXT NOT NULL,
                events      TEXT DEFAULT 'all',
                secret      TEXT NOT NULL,
                status      TEXT DEFAULT 'active',
                created_at  INTEGER
            );

            CREATE INDEX IF NOT EXISTS idx_usage_day   ON usage_log(sub_id, day);
            CREATE INDEX IF NOT EXISTS idx_usage_key   ON usage_log(key_id, day);
            CREATE INDEX IF NOT EXISTS idx_keys_sub    ON api_keys(sub_id);
        ");
        // Add ai_config column if missing (safe on existing installs)
        try { $this->db->exec("ALTER TABLE subscribers ADD COLUMN ai_config TEXT DEFAULT ''"); } catch(\Exception $e) {}
    }

    // ── Customer AI backend config ────────────────────────────────────
    public function saveAIConfig(string $sub_id, array $cfg): void {
        $stmt = $this->db->prepare("UPDATE subscribers SET ai_config=:c WHERE id=:id");
        $stmt->bindValue(':c',  json_encode($cfg));
        $stmt->bindValue(':id', $sub_id);
        $stmt->execute();
    }

    public function getAIConfig(string $sub_id): array {
        $stmt = $this->db->prepare("SELECT ai_config FROM subscribers WHERE id=:id");
        $stmt->bindValue(':id', $sub_id);
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        if (!$row || empty($row['ai_config'])) return [];
        return json_decode($row['ai_config'], true) ?: [];
    }

    // ── Subscribers ───────────────────────────────────────────────────

    public function createSubscriber(string $name, string $email, string $plan = 'free'): array {
        $id = 'sub_' . bin2hex(random_bytes(8));
        $now = time();
        $stmt = $this->db->prepare(
            "INSERT INTO subscribers (id,name,email,plan,status,created_at,renewed_at,expires_at)
             VALUES (:id,:name,:email,:plan,'active',:now,:now,:exp)"
        );
        $stmt->bindValue(':id',    $id);
        $stmt->bindValue(':name',  $name);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':plan',  $plan);
        $stmt->bindValue(':now',   $now);
        $stmt->bindValue(':exp',   $now + 30 * 86400);
        $stmt->execute();
        return $this->getSubscriber($id);
    }

    public function getSubscriber(string $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM subscribers WHERE id=:id");
        $stmt->bindValue(':id', $id);
        $r = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        return $r ?: null;
    }

    public function getSubscriberByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM subscribers WHERE email=:e");
        $stmt->bindValue(':e', $email);
        $r = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        return $r ?: null;
    }

    public function listSubscribers(int $limit = 100): array {
        $limit = max(1, min((int)$limit, 1000));
        $stmt = $this->db->prepare("SELECT * FROM subscribers ORDER BY created_at DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }

    public function updatePlan(string $sub_id, string $plan): void {
        $now = time();
        $stmt = $this->db->prepare(
            "UPDATE subscribers SET plan=:p,renewed_at=:now,expires_at=:exp WHERE id=:id"
        );
        $stmt->bindValue(':p',   $plan);
        $stmt->bindValue(':now', $now);
        $stmt->bindValue(':exp', $now + 30 * 86400);
        $stmt->bindValue(':id',  $sub_id);
        $stmt->execute();
    }

    public function suspendSubscriber(string $sub_id): void {
        $stmt = $this->db->prepare("UPDATE subscribers SET status='suspended' WHERE id=:id");
        $stmt->bindValue(':id', $sub_id);
        $stmt->execute();
    }

    // ── API Keys ──────────────────────────────────────────────────────

    public function createKey(string $sub_id, string $label = ''): array {
        $raw    = 'yuga_live_' . bin2hex(random_bytes(20));
        $hash   = hash('sha256', $raw);
        $prefix = substr($raw, 0, 16) . '...';
        $key_id = 'key_' . bin2hex(random_bytes(6));

        $stmt = $this->db->prepare(
            "INSERT INTO api_keys (key_id,key_hash,key_prefix,sub_id,label,status,created_at)
             VALUES (:kid,:hash,:pfx,:sid,:lbl,'active',:now)"
        );
        $stmt->bindValue(':kid',  $key_id);
        $stmt->bindValue(':hash', $hash);
        $stmt->bindValue(':pfx',  $prefix);
        $stmt->bindValue(':sid',  $sub_id);
        $stmt->bindValue(':lbl',  $label);
        $stmt->bindValue(':now',  time());
        $stmt->execute();

        return ['key' => $raw, 'key_id' => $key_id, 'prefix' => $prefix];
    }

    public function validateKey(string $raw_key): ?array {
        $hash = hash('sha256', $raw_key);
        $stmt = $this->db->prepare(
            "SELECT k.*, s.plan, s.status as sub_status, s.name, s.email, s.expires_at
             FROM api_keys k
             JOIN subscribers s ON k.sub_id = s.id
             WHERE k.key_hash=:h AND k.status='active'"
        );
        $stmt->bindValue(':h', $hash);
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        if (!$row) return null;
        if ($row['sub_status'] !== 'active') return null;
        if ($row['expires_at'] < time()) return null;

        // Update last_used
        $u = $this->db->prepare("UPDATE api_keys SET last_used=:t WHERE key_id=:k");
        $u->bindValue(':t', time());
        $u->bindValue(':k', $row['key_id']);
        $u->execute();

        return $row;
    }

    public function listKeys(string $sub_id): array {
        $stmt = $this->db->prepare(
            "SELECT key_id,key_prefix,label,status,created_at,last_used FROM api_keys WHERE sub_id=:s ORDER BY created_at DESC"
        );
        $stmt->bindValue(':s', $sub_id);
        $res = $stmt->execute();
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }

    public function revokeKey(string $key_id, string $sub_id): void {
        $stmt = $this->db->prepare(
            "UPDATE api_keys SET status='revoked' WHERE key_id=:k AND sub_id=:s"
        );
        $stmt->bindValue(':k', $key_id);
        $stmt->bindValue(':s', $sub_id);
        $stmt->execute();
    }

    // ── Usage ─────────────────────────────────────────────────────────

    public function logUsage(string $key_id, string $sub_id, string $action, string $model = ''): void {
        $stmt = $this->db->prepare(
            "INSERT INTO usage_log (key_id,sub_id,action,model,ts,day) VALUES (:k,:s,:a,:m,:t,:d)"
        );
        $stmt->bindValue(':k', $key_id);
        $stmt->bindValue(':s', $sub_id);
        $stmt->bindValue(':a', $action);
        $stmt->bindValue(':m', $model);
        $stmt->bindValue(':t', time());
        $stmt->bindValue(':d', date('Y-m-d'));
        $stmt->execute();
    }

    public function getCallsToday(string $sub_id): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as c FROM usage_log WHERE sub_id=:s AND day=:d"
        );
        $stmt->bindValue(':s', $sub_id);
        $stmt->bindValue(':d', date('Y-m-d'));
        $r = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        return (int)($r['c'] ?? 0);
    }

    public function getUsageStats(string $sub_id, int $days = 30): array {
        $since = date('Y-m-d', strtotime("-{$days} days"));
        $stmt  = $this->db->prepare(
            "SELECT day, COUNT(*) as calls FROM usage_log WHERE sub_id=:s AND day>=:d GROUP BY day ORDER BY day"
        );
        $stmt->bindValue(':s', $sub_id);
        $stmt->bindValue(':d', $since);
        $res = $stmt->execute();
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }

    public function getGlobalStats(): array {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usage_log WHERE day=:d");
        $stmt->bindValue(':d', $today);
        $callsToday = (int)$stmt->execute()->fetchArray()[0];
        return [
            'total_subscribers' => (int)$this->db->querySingle("SELECT COUNT(*) FROM subscribers"),
            'active_keys'       => (int)$this->db->querySingle("SELECT COUNT(*) FROM api_keys WHERE status='active'"),
            'calls_today'       => $callsToday,
            'calls_total'       => (int)$this->db->querySingle("SELECT COUNT(*) FROM usage_log"),
            'plan_counts'       => $this->getPlanCounts(),
            'mrr'               => $this->getMRR(),
        ];
    }

    private function getPlanCounts(): array {
        $res = $this->db->query("SELECT plan, COUNT(*) as c FROM subscribers WHERE status='active' GROUP BY plan");
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[$row['plan']] = $row['c'];
        return $out;
    }

    private function getMRR(): float {
        $prices = ['free'=>0,'starter'=>9,'pro'=>29,'enterprise'=>99];
        $counts = $this->getPlanCounts();
        $mrr = 0.0;
        foreach ($counts as $plan => $count) $mrr += ($prices[$plan] ?? 0) * $count;
        return $mrr;
    }

    // ── Webhooks ──────────────────────────────────────────────────────

    public function addWebhook(string $sub_id, string $url, string $events = 'all'): string {
        $id     = 'wh_' . bin2hex(random_bytes(6));
        $secret = 'whsec_' . bin2hex(random_bytes(16));
        $stmt   = $this->db->prepare(
            "INSERT INTO webhooks (id,sub_id,url,events,secret,status,created_at) VALUES (:id,:s,:u,:e,:sec,'active',:t)"
        );
        $stmt->bindValue(':id',  $id);
        $stmt->bindValue(':s',   $sub_id);
        $stmt->bindValue(':u',   $url);
        $stmt->bindValue(':e',   $events);
        $stmt->bindValue(':sec', $secret);
        $stmt->bindValue(':t',   time());
        $stmt->execute();
        return $secret;
    }

    public function fireWebhook(string $sub_id, string $event, array $data): void {
        $stmt = $this->db->prepare(
            "SELECT * FROM webhooks WHERE sub_id=:s AND status='active' AND (events='all' OR events LIKE :e)"
        );
        $stmt->bindValue(':s', $sub_id);
        $stmt->bindValue(':e', '%' . str_replace(['%', '_'], ['\\%', '\\_'], $event) . '%');
        $res = $stmt->execute();
        while ($wh = $res->fetchArray(SQLITE3_ASSOC)) {
            $payload   = json_encode(['event'=>$event,'data'=>$data,'ts'=>time()]);
            $signature = hash_hmac('sha256', $payload, $wh['secret']);
            if (function_exists('curl_init')) {
                $ch = curl_init($wh['url']);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $payload, CURLOPT_TIMEOUT => 5,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        "X-Yuga-Signature: sha256=$signature",
                        "X-Yuga-Event: $event",
                    ],
                ]);
                curl_exec($ch); curl_close($ch);
            }
        }
    }
}
