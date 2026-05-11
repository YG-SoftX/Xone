<?php
/**
 * PaymentManager — Unified Nepali payment orchestrator
 *
 * Handles all three gateways (eSewa, FonePay, IME Pay),
 * stores transactions in SQLite, manages plan upgrades,
 * sends confirmation to subscriber after payment.
 */
class PaymentManager {

    private SQLite3       $db;
    private APIKeyManager $akm;
    private array         $config;

    public function __construct(string $data_dir, APIKeyManager $akm, array $config) {
        $this->db     = new SQLite3($data_dir . '/yuga_payments.db');
        $this->db->enableExceptions(true);
        $this->akm    = $akm;
        $this->config = $config;
        $this->migrate();
    }

    private function migrate(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS transactions (
                id            TEXT PRIMARY KEY,
                sub_id        TEXT,
                gateway       TEXT NOT NULL,
                amount        REAL NOT NULL,
                currency      TEXT DEFAULT 'NPR',
                plan          TEXT NOT NULL,
                status        TEXT DEFAULT 'PENDING',
                gateway_ref   TEXT DEFAULT '',
                gateway_txn   TEXT DEFAULT '',
                created_at    INTEGER,
                completed_at  INTEGER,
                notes         TEXT DEFAULT ''
            );
            CREATE INDEX IF NOT EXISTS idx_txn_sub ON transactions(sub_id);
            CREATE INDEX IF NOT EXISTS idx_txn_status ON transactions(status);
        ");
    }

    // ── Create a pending transaction ──────────────────────────────────
    public function createTransaction(string $sub_id, string $gateway, float $amount, string $plan): string {
        $id = 'txn_' . bin2hex(random_bytes(8));
        $stmt = $this->db->prepare(
            "INSERT INTO transactions (id,sub_id,gateway,amount,plan,status,created_at)
             VALUES (:id,:s,:g,:a,:p,'PENDING',:t)"
        );
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':s',  $sub_id);
        $stmt->bindValue(':g',  $gateway);
        $stmt->bindValue(':a',  $amount);
        $stmt->bindValue(':p',  $plan);
        $stmt->bindValue(':t',  time());
        $stmt->execute();
        return $id;
    }

    // ── Get transaction ────────────────────────────────────────────────
    public function getTransaction(string $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE id=:id");
        $stmt->bindValue(':id', $id);
        $r = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        return $r ?: null;
    }

    // ── Mark transaction complete + upgrade subscriber plan ───────────
    public function completeTransaction(string $txn_id, string $gateway_ref, string $gateway_txn = ''): bool {
        $txn = $this->getTransaction($txn_id);
        if (!$txn) return false;

        // Update transaction
        $stmt = $this->db->prepare(
            "UPDATE transactions SET status='COMPLETE',gateway_ref=:ref,gateway_txn=:txn,completed_at=:t WHERE id=:id"
        );
        $stmt->bindValue(':ref', $gateway_ref);
        $stmt->bindValue(':txn', $gateway_txn);
        $stmt->bindValue(':t',   time());
        $stmt->bindValue(':id',  $txn_id);
        $stmt->execute();

        // Upgrade subscriber plan
        if ($txn['sub_id']) {
            $this->akm->updatePlan($txn['sub_id'], $txn['plan']);
        }

        return true;
    }

    public function failTransaction(string $txn_id, string $reason = ''): void {
        $stmt = $this->db->prepare(
            "UPDATE transactions SET status='FAILED',notes=:n,completed_at=:t WHERE id=:id"
        );
        $stmt->bindValue(':n',  $reason);
        $stmt->bindValue(':t',  time());
        $stmt->bindValue(':id', $txn_id);
        $stmt->execute();
    }

    // ── List transactions ──────────────────────────────────────────────
    public function listTransactions(int $limit = 100): array {
        $res = $this->db->query(
            "SELECT t.*, s.name as sub_name, s.email as sub_email
             FROM transactions t
             LEFT JOIN subscribers s ON t.sub_id = s.id
             ORDER BY t.created_at DESC LIMIT $limit"
        );
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }

    public function getStats(): array {
        $today = date('Y-m-d');
        return [
            'total_revenue' => (float)($this->db->querySingle(
                "SELECT SUM(amount) FROM transactions WHERE status='COMPLETE'"
            ) ?? 0),
            'today_revenue' => (float)($this->db->querySingle(
                "SELECT SUM(amount) FROM transactions WHERE status='COMPLETE' AND date(completed_at,'unixepoch')='$today'"
            ) ?? 0),
            'total_txns'    => (int)$this->db->querySingle("SELECT COUNT(*) FROM transactions"),
            'complete_txns' => (int)$this->db->querySingle("SELECT COUNT(*) FROM transactions WHERE status='COMPLETE'"),
            'pending_txns'  => (int)$this->db->querySingle("SELECT COUNT(*) FROM transactions WHERE status='PENDING'"),
        ];
    }

    // ── Build gateway instances ────────────────────────────────────────
    public function esewa(): ESewa {
        $c = $this->config['esewa'] ?? [];
        return new ESewa(
            $c['merchant_code'] ?? 'EPAYTEST',
            $c['secret_key']    ?? '8gBm/:&EnhH.1/q',
            $c['test_mode']     ?? true
        );
    }

    public function fonepay(): FonePay {
        $c = $this->config['fonepay'] ?? [];
        return new FonePay(
            $c['merchant_id'] ?? 'NBQM',
            $c['secret_key']  ?? '',
            $c['test_mode']   ?? true
        );
    }

    public function stripe(): Stripe {
        $c = $this->config['stripe'] ?? [];
        return new Stripe($c['secret_key'] ?? '', $c['test_mode'] ?? true);
    }

    public function paypal(): PayPal {
        $c = $this->config['paypal'] ?? [];
        return new PayPal($c['client_id'] ?? '', $c['client_secret'] ?? '', $c['test_mode'] ?? true);
    }

    public function imepay(): IMEPay {
        $c = $this->config['imepay'] ?? [];
        return new IMEPay(
            $c['merchant_code'] ?? '',
            $c['merchant_name'] ?? 'Yuga',
            $c['module']        ?? '',
            $c['username']      ?? '',
            $c['password']      ?? '',
            $c['test_mode']     ?? true
        );
    }
}
