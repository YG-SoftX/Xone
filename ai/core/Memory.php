<?php
/**
 * Memory — Persistent multi-turn conversation memory
 *
 * Stores conversation history per (subscriber, session) pair in SQLite.
 * The Brain.answer() call becomes context-aware:
 *   User: "what is your pricing?"
 *   Yuga: "Our pricing starts at $29/month..."
 *   User: "what about the pro plan?"  ← now knows context is pricing
 */
class Memory
{

    private SQLite3 $db;

    public function __construct(string $data_dir)
    {
        $this->db = new SQLite3($data_dir . '/yuga_memory.db');
        $this->db->enableExceptions(true);
        $this->migrate();
    }

    private function migrate(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS sessions (
                session_id TEXT PRIMARY KEY,
                sub_id     TEXT NOT NULL,
                model      TEXT NOT NULL,
                created_at INTEGER,
                updated_at INTEGER,
                metadata   TEXT DEFAULT '{}'
            );
            CREATE TABLE IF NOT EXISTS messages (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id TEXT NOT NULL,
                role       TEXT NOT NULL,
                content    TEXT NOT NULL,
                ts         INTEGER NOT NULL,
                score      INTEGER DEFAULT 0
            );
            CREATE INDEX IF NOT EXISTS idx_msg_sess ON messages(session_id, ts);
            CREATE INDEX IF NOT EXISTS idx_sess_sub ON sessions(sub_id);
        ");
    }

    // ── Create a new session ──────────────────────────────────────────
    public function createSession(string $sub_id, string $model = 'default'): string
    {
        $sid = 'sess_' . bin2hex(random_bytes(8));
        $now = time();
        $stmt = $this->db->prepare(
            "INSERT INTO sessions (session_id,sub_id,model,created_at,updated_at) VALUES (:s,:u,:m,:t,:t)"
        );
        $stmt->bindValue(':s', $sid);
        $stmt->bindValue(':u', $sub_id);
        $stmt->bindValue(':m', $model);
        $stmt->bindValue(':t', $now);
        $stmt->execute();
        return $sid;
    }

    // ── Add a message to a session ────────────────────────────────────
    public function addMessage(string $session_id, string $role, string $content): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO messages (session_id,role,content,ts) VALUES (:s,:r,:c,:t)"
        );
        $stmt->bindValue(':s', $session_id);
        $stmt->bindValue(':r', $role);  // 'user' | 'assistant'
        $stmt->bindValue(':c', $content);
        $stmt->bindValue(':t', time());
        $stmt->execute();

        // Update session timestamp
        $u = $this->db->prepare("UPDATE sessions SET updated_at=:t WHERE session_id=:s");
        $u->bindValue(':t', time());
        $u->bindValue(':s', $session_id);
        $u->execute();
    }

    // ── Get conversation history ──────────────────────────────────────
    public function getHistory(string $session_id, int $last_n = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT role, content, ts FROM messages
             WHERE session_id=:s ORDER BY ts DESC LIMIT :n"
        );
        $stmt->bindValue(':s', $session_id);
        $stmt->bindValue(':n', $last_n);
        $res = $stmt->execute();
        $msgs = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC))
            $msgs[] = $row;
        return array_reverse($msgs);
    }

    // ── Build context string for the Brain ────────────────────────────
    // Takes last N turns and builds a compact context summary
    public function buildContext(string $session_id, int $turns = 5): string
    {
        $history = $this->getHistory($session_id, $turns * 2);
        if (empty($history))
            return '';

        $lines = [];
        foreach ($history as $m) {
            $prefix = $m['role'] === 'user' ? 'User' : 'Assistant';
            $lines[] = $prefix . ': ' . substr($m['content'], 0, 200);
        }
        return "Previous conversation:\n" . implode("\n", $lines) . "\n\nCurrent question: ";
    }

    // ── List sessions for a subscriber ────────────────────────────────
    public function getSessions(string $sub_id, int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, COUNT(m.id) as msg_count
             FROM sessions s
             LEFT JOIN messages m ON s.session_id = m.session_id
             WHERE s.sub_id=:u
             GROUP BY s.session_id
             ORDER BY s.updated_at DESC LIMIT :l"
        );
        $stmt->bindValue(':u', $sub_id);
        $stmt->bindValue(':l', $limit);
        $res = $stmt->execute();
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC))
            $out[] = $row;
        return $out;
    }

    // ── Delete a session ──────────────────────────────────────────────
    public function deleteSession(string $session_id): void
    {
        $stmt = $this->db->prepare("DELETE FROM messages WHERE session_id=:s");
        $stmt->bindValue(':s', $session_id);
        $stmt->execute();

        $stmt2 = $this->db->prepare("DELETE FROM sessions WHERE session_id=:s");
        $stmt2->bindValue(':s', $session_id);
        $stmt2->execute();
    }

    // ── Rate a message (for feedback loop) ───────────────────────────
    public function rateMessage(int $message_id, int $score): void
    {
        $stmt = $this->db->prepare("UPDATE messages SET score=:s WHERE id=:id");
        $stmt->bindValue(':s', $score);
        $stmt->bindValue(':id', $message_id);
        $stmt->execute();
    }

    // ── Get session info ──────────────────────────────────────────────
    public function getSession(string $session_id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM sessions WHERE session_id=:s");
        $stmt->bindValue(':s', $session_id);
        $r = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        return $r ?: null;
    }

    // ── Prune old sessions (run in cron) ──────────────────────────────
    public function pruneOld(int $days = 30): int
    {
        $cutoff = time() - ($days * 86400);
        $stmt = $this->db->prepare("SELECT session_id FROM sessions WHERE updated_at < :cutoff");
        $stmt->bindValue(':cutoff', $cutoff);
        $old = $stmt->execute();
        $count = 0;
        while ($row = $old->fetchArray(SQLITE3_ASSOC)) {
            $this->deleteSession($row['session_id']);
            $count++;
        }
        return $count;
    }

    // ── Stats ─────────────────────────────────────────────────────────
    public function getStats(): array
    {
        $cutoff = time() - 86400;
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM sessions WHERE updated_at > :cutoff");
        $stmt->bindValue(':cutoff', $cutoff, SQLITE3_INTEGER);
        $activeToday = (int)$stmt->execute()->fetchArray(SQLITE3_NUM)[0];
        return [
            'total_sessions' => (int) $this->db->querySingle("SELECT COUNT(*) FROM sessions"),
            'total_messages' => (int) $this->db->querySingle("SELECT COUNT(*) FROM messages"),
            'active_today'   => $activeToday,
        ];
    }
}
