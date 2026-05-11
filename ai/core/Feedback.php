<?php
/**
 * Feedback — RLHF-lite answer quality loop
 *
 * Collects thumbs up/down on every widget response.
 * Stores good examples as additional training data.
 * Tracks unanswered / poorly-answered questions.
 * Admin can retrain on collected feedback.
 */
class Feedback {

    private SQLite3 $db;

    public function __construct(string $data_dir) {
        $this->db = new SQLite3($data_dir . '/yuga_feedback.db');
        $this->db->enableExceptions(true);
        $this->migrate();
    }

    private function migrate(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS feedback (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id  TEXT,
                sub_id      TEXT,
                model       TEXT NOT NULL,
                question    TEXT NOT NULL,
                answer      TEXT NOT NULL,
                score       INTEGER NOT NULL,  -- 1=thumbs up, -1=thumbs down
                correction  TEXT DEFAULT '',   -- human-provided correction
                ts          INTEGER NOT NULL,
                day         TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS unanswered (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                model       TEXT NOT NULL,
                question    TEXT NOT NULL,
                count       INTEGER DEFAULT 1,
                last_seen   INTEGER,
                resolved    INTEGER DEFAULT 0
            );
            CREATE INDEX IF NOT EXISTS idx_fb_model ON feedback(model, score);
            CREATE INDEX IF NOT EXISTS idx_fb_day   ON feedback(day);
        ");
    }

    // ── Record feedback on an answer ──────────────────────────────────
    public function record(
        string $model,
        string $question,
        string $answer,
        int    $score,      // 1 or -1
        string $session_id = '',
        string $sub_id     = '',
        string $correction = ''
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO feedback (session_id,sub_id,model,question,answer,score,correction,ts,day)
             VALUES (:sess,:sub,:m,:q,:a,:s,:c,:t,:d)"
        );
        $stmt->bindValue(':sess', $session_id);
        $stmt->bindValue(':sub',  $sub_id);
        $stmt->bindValue(':m',    $model);
        $stmt->bindValue(':q',    $question);
        $stmt->bindValue(':a',    $answer);
        $stmt->bindValue(':s',    $score);
        $stmt->bindValue(':c',    $correction);
        $stmt->bindValue(':t',    time());
        $stmt->bindValue(':d',    date('Y-m-d'));
        $stmt->execute();
        return (int)$this->db->lastInsertRowID();
    }

    // ── Track unanswered / "I don't know" responses ───────────────────
    public function trackUnanswered(string $model, string $question): void {
        // Check if we've seen this before (fuzzy: first 80 chars)
        $key  = substr($question, 0, 80);
        $stmt = $this->db->prepare(
            "SELECT id, count FROM unanswered WHERE model=:m AND question LIKE :q AND resolved=0"
        );
        $stmt->bindValue(':m', $model);
        $stmt->bindValue(':q', $key . '%');
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if ($row) {
            $u = $this->db->prepare("UPDATE unanswered SET count=count+1,last_seen=:t WHERE id=:id");
            $u->bindValue(':t', time()); $u->bindValue(':id', $row['id']); $u->execute();
        } else {
            $i = $this->db->prepare("INSERT INTO unanswered (model,question,last_seen) VALUES (:m,:q,:t)");
            $i->bindValue(':m', $model); $i->bindValue(':q', $question); $i->bindValue(':t', time()); $i->execute();
        }
    }

    // ── Add a correction ──────────────────────────────────────────────
    public function addCorrection(int $feedback_id, string $correct_answer): void {
        $stmt = $this->db->prepare("UPDATE feedback SET correction=:c WHERE id=:id");
        $stmt->bindValue(':c',   $correct_answer);
        $stmt->bindValue(':id',  $feedback_id);
        $stmt->execute();
    }

    // ── Export good examples as training text ─────────────────────────
    // Returns a corpus of question-answer pairs from thumbs-up responses
    public function exportGoodExamples(string $model, int $min_score = 1): string {
        $stmt = $this->db->prepare(
            "SELECT question, correction, answer FROM feedback
             WHERE model=:m AND score >= :s ORDER BY ts DESC LIMIT 500"
        );
        $stmt->bindValue(':m', $model);
        $stmt->bindValue(':s', $min_score);
        $res  = $stmt->execute();
        $text = '';

        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            // Use correction if provided, else the thumbs-up answer
            $answer = trim($row['correction']) ?: trim($row['answer']);
            $q = trim($row['question']);
            if ($q && $answer) {
                $text .= "Q: {$q}\nA: {$answer}\n\n";
            }
        }
        return $text;
    }

    // ── Stats ─────────────────────────────────────────────────────────
    public function getStats(string $model = ''): array {
        $where = $model ? "WHERE model='" . SQLite3::escapeString($model) . "'" : '';
        $today = date('Y-m-d');
        return [
            'total'      => (int)$this->db->querySingle("SELECT COUNT(*) FROM feedback $where"),
            'thumbs_up'  => (int)$this->db->querySingle("SELECT COUNT(*) FROM feedback $where " . ($where?'AND':'WHERE') . " score=1"),
            'thumbs_down'=> (int)$this->db->querySingle("SELECT COUNT(*) FROM feedback $where " . ($where?'AND':'WHERE') . " score=-1"),
            'today'      => (int)$this->db->querySingle("SELECT COUNT(*) FROM feedback WHERE day='$today'" . ($where?" AND model='" . SQLite3::escapeString($model) . "'":'')),
            'unanswered' => (int)$this->db->querySingle("SELECT COUNT(*) FROM unanswered WHERE resolved=0" . ($model?" AND model='" . SQLite3::escapeString($model) . "'":'')),
            'corrections'=> (int)$this->db->querySingle("SELECT COUNT(*) FROM feedback $where " . ($where?'AND':'WHERE') . " correction!=''"),
        ];
    }

    // ── List feedback ─────────────────────────────────────────────────
    public function list(string $model = '', int $score = 0, int $limit = 50): array {
        $where = [];
        if ($model) $where[] = "model='" . SQLite3::escapeString($model) . "'";
        if ($score) $where[] = "score=$score";
        $w  = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $res = $this->db->query("SELECT * FROM feedback $w ORDER BY ts DESC LIMIT $limit");
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }

    // ── List unanswered questions ─────────────────────────────────────
    public function listUnanswered(string $model = '', int $limit = 50): array {
        $where = $model ? "WHERE model='" . SQLite3::escapeString($model) . "' AND resolved=0" : 'WHERE resolved=0';
        $res   = $this->db->query("SELECT * FROM unanswered $where ORDER BY count DESC LIMIT $limit");
        $out   = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }

    public function resolveUnanswered(int $id): void {
        $this->db->exec("UPDATE unanswered SET resolved=1 WHERE id=$id");
    }
}
