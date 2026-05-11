<?php
/**
 * AutoRetrain — Detect content changes and retrain automatically
 *
 * Run via cron every hour:
 *   0 * * * * php /path/to/yuga/training/auto_retrain.php
 *
 * Detects:
 *   - New/changed pages on the registered site
 *   - New feedback examples to incorporate
 *   - Significant corpus changes (>5% new content)
 * Then triggers incremental retraining.
 */
class AutoRetrain {

    private SQLite3 $db;
    private string  $data_dir;

    public function __construct(string $data_dir) {
        $this->data_dir = $data_dir;
        $this->db       = new SQLite3($data_dir . '/yuga_autotrain.db');
        $this->db->enableExceptions(true);
        $this->migrate();
    }

    private function migrate(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS site_pages (
                url         TEXT NOT NULL,
                model       TEXT NOT NULL,
                content_hash TEXT NOT NULL,
                last_check  INTEGER,
                last_change INTEGER,
                PRIMARY KEY(url, model)
            );
            CREATE TABLE IF NOT EXISTS retrain_log (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                model       TEXT NOT NULL,
                trigger     TEXT NOT NULL,
                new_chars   INTEGER DEFAULT 0,
                steps       INTEGER DEFAULT 0,
                loss_before REAL DEFAULT 0,
                loss_after  REAL DEFAULT 0,
                ts          INTEGER
            );
            CREATE TABLE IF NOT EXISTS retrain_config (
                model           TEXT PRIMARY KEY,
                site_url        TEXT DEFAULT '',
                check_interval  INTEGER DEFAULT 3600,
                last_check      INTEGER DEFAULT 0,
                enabled         INTEGER DEFAULT 1,
                min_change_pct  INTEGER DEFAULT 5,
                steps_per_run   INTEGER DEFAULT 3000
            );
        ");
    }

    // ── Register a model for auto-retraining ──────────────────────────
    public function register(string $model, string $site_url, array $options = []): void {
        $stmt = $this->db->prepare(
            "INSERT OR REPLACE INTO retrain_config
             (model,site_url,check_interval,last_check,enabled,min_change_pct,steps_per_run)
             VALUES (:m,:u,:ci,:lc,:en,:mc,:sp)"
        );
        $stmt->bindValue(':m',  $model);
        $stmt->bindValue(':u',  $site_url);
        $stmt->bindValue(':ci', $options['check_interval'] ?? 3600);
        $stmt->bindValue(':lc', 0);
        $stmt->bindValue(':en', 1);
        $stmt->bindValue(':mc', $options['min_change_pct'] ?? 5);
        $stmt->bindValue(':sp', $options['steps_per_run']  ?? 3000);
        $stmt->execute();
    }

    // ── Check if any models need retraining ───────────────────────────
    public function checkAll(): array {
        $now  = time();
        $res  = $this->db->query("SELECT * FROM retrain_config WHERE enabled=1");
        $triggered = [];

        while ($cfg = $res->fetchArray(SQLITE3_ASSOC)) {
            $due = ($now - (int)$cfg['last_check']) >= (int)$cfg['check_interval'];
            if (!$due) continue;

            $changes = $this->checkSite($cfg['model'], $cfg['site_url']);
            $this->db->exec("UPDATE retrain_config SET last_check=$now WHERE model='" . SQLite3::escapeString($cfg['model']) . "'");

            if ($changes['new_chars'] > 0) {
                $change_pct = $changes['changed_pages'] / max($changes['total_pages'], 1) * 100;
                if ($change_pct >= $cfg['min_change_pct'] || $changes['new_chars'] > 5000) {
                    $triggered[] = [
                        'model'      => $cfg['model'],
                        'trigger'    => 'site_change',
                        'new_chars'  => $changes['new_chars'],
                        'steps'      => $cfg['steps_per_run'],
                        'new_text'   => $changes['new_text'],
                    ];
                }
            }
        }
        return $triggered;
    }

    // ── Check a site for changed content ──────────────────────────────
    public function checkSite(string $model, string $base_url): array {
        if (!$base_url) return ['new_chars' => 0, 'changed_pages' => 0, 'total_pages' => 0, 'new_text' => ''];

        $urls   = $this->discoverURLs($base_url);
        $total  = count($urls);
        $changed = 0;
        $new_text = '';

        foreach (array_slice($urls, 0, 30) as $url) {
            $html = $this->fetch($url);
            if (!$html) continue;

            $text  = $this->htmlToText($html);
            $hash  = md5($text);

            // Check stored hash
            $stmt  = $this->db->prepare("SELECT content_hash FROM site_pages WHERE url=:u AND model=:m");
            $stmt->bindValue(':u', $url);
            $stmt->bindValue(':m', $model);
            $stored = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

            if (!$stored || $stored['content_hash'] !== $hash) {
                $changed++;
                $new_text .= "\n\n" . $text;

                // Update stored hash
                $u = $this->db->prepare(
                    "INSERT OR REPLACE INTO site_pages (url,model,content_hash,last_check,last_change) VALUES (:u,:m,:h,:t,:t)"
                );
                $u->bindValue(':u', $url); $u->bindValue(':m', $model);
                $u->bindValue(':h', $hash); $u->bindValue(':t', time());
                $u->execute();
            } else {
                $u = $this->db->prepare("UPDATE site_pages SET last_check=:t WHERE url=:u AND model=:m");
                $u->bindValue(':t', time()); $u->bindValue(':u', $url); $u->bindValue(':m', $model);
                $u->execute();
            }
            usleep(200000);
        }

        return [
            'new_chars'     => strlen($new_text),
            'changed_pages' => $changed,
            'total_pages'   => $total,
            'new_text'      => $new_text,
        ];
    }

    // ── Log a retraining run ──────────────────────────────────────────
    public function logRetrain(string $model, string $trigger, int $new_chars, int $steps, float $loss_before = 0, float $loss_after = 0): void {
        $stmt = $this->db->prepare(
            "INSERT INTO retrain_log (model,trigger,new_chars,steps,loss_before,loss_after,ts)
             VALUES (:m,:tr,:nc,:s,:lb,:la,:t)"
        );
        $stmt->bindValue(':m',  $model);
        $stmt->bindValue(':tr', $trigger);
        $stmt->bindValue(':nc', $new_chars);
        $stmt->bindValue(':s',  $steps);
        $stmt->bindValue(':lb', $loss_before);
        $stmt->bindValue(':la', $loss_after);
        $stmt->bindValue(':t',  time());
        $stmt->execute();
    }

    // ── Get retrain history ────────────────────────────────────────────
    public function getLog(string $model = '', int $limit = 20): array {
        $where = $model ? "WHERE model='" . SQLite3::escapeString($model) . "'" : '';
        $res   = $this->db->query("SELECT * FROM retrain_log $where ORDER BY ts DESC LIMIT $limit");
        $out   = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }

    // ── Helpers ───────────────────────────────────────────────────────
    private function discoverURLs(string $base): array {
        $urls = [$base];
        $sm   = $this->fetch($base . '/sitemap.xml');
        if ($sm && str_contains($sm, '<loc>')) {
            preg_match_all('/<loc>(.*?)<\/loc>/s', $sm, $m);
            foreach ($m[1] as $u) {
                if (parse_url(trim($u), PHP_URL_HOST) === parse_url($base, PHP_URL_HOST)) {
                    $urls[] = trim($u);
                }
            }
        }
        return array_unique($urls);
    }

    private function fetch(string $url): ?string {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>8,CURLOPT_USERAGENT=>'YugaAutoRetrain/1.0',CURLOPT_SSL_VERIFYPEER=>true]);
            $r = curl_exec($ch); curl_close($ch); return $r ?: null;
        }
        return @file_get_contents($url) ?: null;
    }

    private function htmlToText(string $h): string {
        $h = preg_replace('/<(script|style|nav|footer)[^>]*>.*?<\/\1>/is', '', $h);
        $h = preg_replace('/<(p|div|h[1-6]|li|br)[^>]*>/', "\n", $h);
        $t = html_entity_decode(strip_tags($h), ENT_QUOTES, 'UTF-8');
        return mb_substr(trim(preg_replace('/\s+/', ' ', $t)), 0, 5000);
    }
}
