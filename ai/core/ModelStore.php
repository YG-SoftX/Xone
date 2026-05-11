<?php
/**
 * ModelStore - Persists Yuga weights to disk (SQLite or JSON file)
 * Works on all cPanel plans - no MySQL required
 */
class ModelStore {

    private string $path;    // path to storage file
    private string $mode;    // 'sqlite' | 'json'

    private SQLite3 $db;

    public function __construct(string $data_dir = __DIR__ . '/../data') {
        if (!is_dir($data_dir)) {
            mkdir($data_dir, 0755, true);
        }
        // Prefer SQLite, fall back to JSON
        if (extension_loaded('pdo_sqlite') || extension_loaded('sqlite3')) {
            $this->path = $data_dir . '/yuga.db';
            $this->mode = 'sqlite';
            $this->db   = new SQLite3($this->path);
            $this->db->busyTimeout(5000);
            $this->initSqlite();
        } else {
            $this->path = $data_dir . '/yuga_models.json';
            $this->mode = 'json';
        }
    }

    // ---------------------------------------------------------------
    // Public API
    // ---------------------------------------------------------------
    public function saveModel(string $name, array $model_data): bool {
        $json = json_encode($model_data, JSON_UNESCAPED_UNICODE);
        if ($this->mode === 'sqlite') {
            return $this->sqliteSet('models', $name, $json);
        }
        return $this->jsonSet($name, $model_data);
    }

    public function loadModel(string $name): ?array {
        if ($this->mode === 'sqlite') {
            $json = $this->sqliteGet('models', $name);
            return $json ? json_decode($json, true) : null;
        }
        return $this->jsonGet($name);
    }

    public function listModels(): array {
        if ($this->mode === 'sqlite') {
            return $this->sqliteList('models');
        }
        $data = $this->jsonLoad();
        return array_keys($data);
    }

    public function deleteModel(string $name): bool {
        if ($this->mode === 'sqlite') {
            return $this->sqliteDel('models', $name);
        }
        return $this->jsonDel($name);
    }

    // Save a KV record (corpus chunks, metadata, etc.)
    public function saveMeta(string $name, array $meta): bool {
        $json = json_encode($meta);
        if ($this->mode === 'sqlite') {
            return $this->sqliteSet('meta', $name, $json);
        }
        // In JSON mode, store in separate file
        $p = str_replace('yuga_models.json', "meta_{$name}.json", $this->path);
        return file_put_contents($p, $json) !== false;
    }

    public function loadMeta(string $name): array {
        if ($this->mode === 'sqlite') {
            $json = $this->sqliteGet('meta', $name);
            return $json ? json_decode($json, true) : [];
        }
        $p = str_replace('yuga_models.json', "meta_{$name}.json", $this->path);
        return file_exists($p) ? json_decode(file_get_contents($p), true) ?? [] : [];
    }

    // ---------------------------------------------------------------
    // SQLite backend
    // ---------------------------------------------------------------
    private function initSqlite(): void {
        $this->db->exec("CREATE TABLE IF NOT EXISTS models (
            name TEXT PRIMARY KEY,
            data TEXT,
            updated_at INTEGER
        )");
        $this->db->exec("CREATE TABLE IF NOT EXISTS meta (
            name TEXT PRIMARY KEY,
            data TEXT,
            updated_at INTEGER
        )");
    }

    private function getDb(): SQLite3 {
        return $this->db;
    }

    private function sqliteSet(string $table, string $key, string $val): bool {
        $table = $table === 'meta' ? 'meta' : 'models'; // whitelist table name
        $db   = $this->getDb();
        $stmt = $db->prepare("INSERT OR REPLACE INTO $table (name, data, updated_at) VALUES (:n, :d, :t)");
        $stmt->bindValue(':n', $key);
        $stmt->bindValue(':d', $val);
        $stmt->bindValue(':t', time());
        $r = $stmt->execute();
        return $r !== false;
    }

    private function sqliteGet(string $table, string $key): ?string {
        $table = $table === 'meta' ? 'meta' : 'models';
        $db   = $this->getDb();
        $stmt = $db->prepare("SELECT data FROM $table WHERE name = :n");
        $stmt->bindValue(':n', $key);
        $r    = $stmt->execute();
        $row  = $r ? $r->fetchArray(SQLITE3_ASSOC) : null;
        return $row ? $row['data'] : null;
    }

    private function sqliteList(string $table): array {
        $table = $table === 'meta' ? 'meta' : 'models';
        $db  = $this->getDb();
        $res = $db->query("SELECT name FROM $table");
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $out[] = $row['name'];
        }
        return $out;
    }

    private function sqliteDel(string $table, string $key): bool {
        $table = $table === 'meta' ? 'meta' : 'models';
        $db   = $this->getDb();
        $stmt = $db->prepare("DELETE FROM $table WHERE name = :n");
        $stmt->bindValue(':n', $key);
        $r = $stmt->execute();
        return $r !== false;
    }

    // ---------------------------------------------------------------
    // JSON file backend (fallback)
    // ---------------------------------------------------------------
    private function jsonLoad(): array {
        if (!file_exists($this->path)) return [];
        $c = file_get_contents($this->path);
        return json_decode($c, true) ?? [];
    }

    private function jsonSave(array $data): bool {
        return file_put_contents($this->path, json_encode($data)) !== false;
    }

    private function jsonGet(string $key): ?array {
        $data = $this->jsonLoad();
        return $data[$key] ?? null;
    }

    private function jsonSet(string $key, $val): bool {
        $data = $this->jsonLoad();
        $data[$key] = $val;
        return $this->jsonSave($data);
    }

    private function jsonDel(string $key): bool {
        $data = $this->jsonLoad();
        unset($data[$key]);
        return $this->jsonSave($data);
    }
}
