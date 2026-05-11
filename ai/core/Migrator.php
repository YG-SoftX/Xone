<?php
/**
 * Migrator — tracks and runs database schema migrations.
 *
 * Migrations live in update/migrations/ as numbered PHP files:
 *   001_initial_schema.php
 *   002_add_subscribers_plan.php
 *   etc.
 *
 * Each file must define a function migrate_NNN(SQLite3 $db): void
 * Migration state is stored in yuga.db (or a separate sqlite file
 * if ModelStore uses JSON mode).
 */
class Migrator {

    private string $migrationsDir;
    private string $dbPath;
    private ?SQLite3 $db = null;

    public function __construct(string $dataDir, string $migrationsDir) {
        $this->dbPath        = rtrim($dataDir, '/') . '/yuga.db';
        $this->migrationsDir = rtrim($migrationsDir, '/');
        $this->init();
    }

    /** Run all pending migrations. Returns list of applied migration names. */
    public function run(callable $log = null): array {
        $pending = $this->pending();
        $applied = [];

        foreach ($pending as $file) {
            $name = basename($file, '.php');
            if ($log) $log("Running migration: $name");

            require_once $file;

            $fn = 'migrate_' . preg_replace('/[^a-z0-9_]/i', '_', $name);
            if (!function_exists($fn)) {
                throw new RuntimeException("Migration $file must define function $fn(SQLite3 \$db)");
            }

            $fn($this->db);
            $this->markApplied($name);
            $applied[] = $name;

            if ($log) $log("  ✓ $name");
        }

        return $applied;
    }

    /** Return migration files not yet applied, sorted ascending. */
    public function pending(): array {
        $files   = glob($this->migrationsDir . '/*.php') ?: [];
        sort($files);
        $applied = $this->appliedSet();

        return array_filter($files, function ($f) use ($applied) {
            return !isset($applied[basename($f, '.php')]);
        });
    }

    /** List all applied migrations with timestamps. */
    public function history(): array {
        $res  = $this->db->query("SELECT name, applied_at FROM migrations ORDER BY applied_at ASC");
        $rows = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function hasPending(): bool {
        return count($this->pending()) > 0;
    }

    public function getDb(): SQLite3 {
        return $this->db;
    }

    // ── Private ──────────────────────────────────────────────────────────

    private function init(): void {
        $this->db = new SQLite3($this->dbPath);
        $this->db->exec("CREATE TABLE IF NOT EXISTS migrations (
            name       TEXT PRIMARY KEY,
            applied_at INTEGER NOT NULL
        )");
    }

    private function appliedSet(): array {
        $res = $this->db->query("SELECT name FROM migrations");
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $out[$row['name']] = true;
        }
        return $out;
    }

    private function markApplied(string $name): void {
        $stmt = $this->db->prepare("INSERT OR IGNORE INTO migrations (name, applied_at) VALUES (:n, :t)");
        $stmt->bindValue(':n', $name);
        $stmt->bindValue(':t', time());
        $stmt->execute();
    }
}
