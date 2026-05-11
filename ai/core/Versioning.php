<?php
/**
 * Versioning — Model checkpoint management
 *
 * Keeps named versions of trained models.
 * Roll back to v1 if v2 gets worse.
 * A/B test two model versions on live traffic.
 * Never overwrites — always keeps history.
 */
class Versioning {

    private string  $data_dir;
    private SQLite3 $db;

    public function __construct(string $data_dir) {
        $this->data_dir = $data_dir;
        $this->db       = new SQLite3($data_dir . '/yuga_versions.db');
        $this->db->enableExceptions(true);
        $this->migrate();
    }

    private function migrate(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS versions (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                model_name  TEXT NOT NULL,
                version     TEXT NOT NULL,
                steps       INTEGER DEFAULT 0,
                loss        REAL DEFAULT 0,
                vocab_size  INTEGER DEFAULT 0,
                size_kb     INTEGER DEFAULT 0,
                notes       TEXT DEFAULT '',
                created_at  INTEGER,
                is_active   INTEGER DEFAULT 0,
                is_ab       INTEGER DEFAULT 0,
                ab_traffic  INTEGER DEFAULT 50,
                UNIQUE(model_name, version)
            );
            CREATE TABLE IF NOT EXISTS ab_results (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                model_name  TEXT NOT NULL,
                version_a   TEXT NOT NULL,
                version_b   TEXT NOT NULL,
                calls_a     INTEGER DEFAULT 0,
                calls_b     INTEGER DEFAULT 0,
                thumbs_a    INTEGER DEFAULT 0,
                thumbs_b    INTEGER DEFAULT 0,
                created_at  INTEGER,
                ended_at    INTEGER,
                winner      TEXT DEFAULT ''
            );
        ");
    }

    // ── Save current model as a named version ─────────────────────────
    public function saveVersion(string $model_name, string $version, array $stats = [], string $notes = ''): bool {
        // Copy the checkpoint file to a versioned file
        $src  = $this->data_dir . "/ckpt_{$model_name}.json.gz";
        $dest = $this->data_dir . "/ckpt_{$model_name}_v{$version}.json.gz";

        if (!file_exists($src)) {
            // Try Brain-style model
            $brain_data = (new ModelStore($this->data_dir))->loadModel('brain_' . $model_name);
            if ($brain_data) {
                file_put_contents($dest, gzencode(json_encode($brain_data), 6));
            } else {
                return false;
            }
        } else {
            copy($src, $dest);
        }

        $stmt = $this->db->prepare(
            "INSERT OR REPLACE INTO versions
             (model_name,version,steps,loss,vocab_size,size_kb,notes,created_at)
             VALUES (:m,:v,:s,:l,:vs,:sz,:n,:t)"
        );
        $stmt->bindValue(':m',  $model_name);
        $stmt->bindValue(':v',  $version);
        $stmt->bindValue(':s',  $stats['steps']      ?? 0);
        $stmt->bindValue(':l',  $stats['loss']        ?? 0);
        $stmt->bindValue(':vs', $stats['vocab_size']  ?? 0);
        $stmt->bindValue(':sz', file_exists($dest) ? round(filesize($dest)/1024) : 0);
        $stmt->bindValue(':n',  $notes);
        $stmt->bindValue(':t',  time());
        $stmt->execute();
        return true;
    }

    // ── Set active version (what the API serves) ──────────────────────
    public function setActive(string $model_name, string $version): bool {
        $src  = $this->data_dir . "/ckpt_{$model_name}_v{$version}.json.gz";
        $dest = $this->data_dir . "/ckpt_{$model_name}.json.gz";

        if (!file_exists($src)) return false;

        copy($src, $dest);

        $this->db->exec("UPDATE versions SET is_active=0 WHERE model_name='" . SQLite3::escapeString($model_name) . "'");
        $stmt = $this->db->prepare("UPDATE versions SET is_active=1 WHERE model_name=:m AND version=:v");
        $stmt->bindValue(':m', $model_name);
        $stmt->bindValue(':v', $version);
        $stmt->execute();
        return true;
    }

    // ── List versions ─────────────────────────────────────────────────
    public function listVersions(string $model_name): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM versions WHERE model_name=:m ORDER BY created_at DESC"
        );
        $stmt->bindValue(':m', $model_name);
        $res = $stmt->execute();
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }

    // ── Delete a version ──────────────────────────────────────────────
    public function deleteVersion(string $model_name, string $version): void {
        $file = $this->data_dir . "/ckpt_{$model_name}_v{$version}.json.gz";
        if (file_exists($file)) unlink($file);
        $stmt = $this->db->prepare("DELETE FROM versions WHERE model_name=:m AND version=:v");
        $stmt->bindValue(':m', $model_name);
        $stmt->bindValue(':v', $version);
        $stmt->execute();
    }

    // ── Start A/B test ────────────────────────────────────────────────
    public function startAB(string $model_name, string $v_a, string $v_b, int $traffic_b = 50): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO ab_results (model_name,version_a,version_b,created_at) VALUES (:m,:a,:b,:t)"
        );
        $stmt->bindValue(':m', $model_name);
        $stmt->bindValue(':a', $v_a);
        $stmt->bindValue(':b', $v_b);
        $stmt->bindValue(':t', time());
        $stmt->execute();

        // Mark both versions as in A/B
        foreach ([$v_a, $v_b] as $v) {
            $u = $this->db->prepare("UPDATE versions SET is_ab=1,ab_traffic=:t WHERE model_name=:m AND version=:v");
            $u->bindValue(':t', $v === $v_b ? $traffic_b : (100 - $traffic_b));
            $u->bindValue(':m', $model_name);
            $u->bindValue(':v', $v);
            $u->execute();
        }
        return true;
    }

    // ── Get model version for a request (A/B routing) ─────────────────
    public function routeRequest(string $model_name): string {
        // Check if A/B test is active
        $stmt = $this->db->prepare(
            "SELECT * FROM ab_results WHERE model_name=:m AND winner='' ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->bindValue(':m', $model_name);
        $ab = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if (!$ab) return $model_name; // no A/B test

        $traffic_b = (int)$this->db->querySingle(
            "SELECT ab_traffic FROM versions WHERE model_name='" . SQLite3::escapeString($model_name) . "' AND version='" . SQLite3::escapeString($ab['version_b']) . "'"
        );

        $use_b = (mt_rand(1, 100) <= $traffic_b);
        return $use_b ? $model_name . '_v' . $ab['version_b'] : $model_name . '_v' . $ab['version_a'];
    }

    // ── Declare A/B winner ────────────────────────────────────────────
    public function endAB(string $model_name, string $winner_version): void {
        $stmt = $this->db->prepare(
            "UPDATE ab_results SET winner=:w,ended_at=:t WHERE model_name=:m AND winner=''"
        );
        $stmt->bindValue(':w', $winner_version);
        $stmt->bindValue(':t', time());
        $stmt->bindValue(':m', $model_name);
        $stmt->execute();

        // Set winner as active
        $this->setActive($model_name, $winner_version);

        // Clear A/B flags
        $this->db->exec("UPDATE versions SET is_ab=0 WHERE model_name='" . SQLite3::escapeString($model_name) . "'");
    }
}
