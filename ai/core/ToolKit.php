<?php
/**
 * ToolKit — Plugin registry for Yuga
 *
 * Lets users connect ANY external app, database, or service as a tool.
 * The agent automatically discovers and calls the right tool based on
 * intent matching — no LLM needed to route tool calls.
 *
 * Tool types:
 *   'php'      — internal PHP callable (fast, no HTTP)
 *   'webhook'  — HTTP POST to any external URL (connect any app)
 *   'get'      — HTTP GET with query params (REST APIs)
 *   'sql'      — read-only SQLite/MySQL query (connect databases)
 *
 * How it works:
 *   1. User registers a tool with a name, description, param schema
 *   2. Agent's BM25 intent matcher picks the right tool for a query
 *   3. ToolKit executes it (PHP / HTTP / SQL) and returns the result
 *   4. Result feeds into Brain for answer synthesis
 *
 * Example — connect a CRM:
 *   $kit->register([
 *     'name'        => 'get_customer',
 *     'description' => 'Look up customer account order invoice status',
 *     'type'        => 'webhook',
 *     'endpoint'    => 'https://mycrm.com/api/lookup',
 *     'params'      => [['name'=>'email','type'=>'string','required'=>true]],
 *     'secret'      => 'my-webhook-secret',
 *   ]);
 *
 * Example — connect a database:
 *   $kit->register([
 *     'name'        => 'get_product_price',
 *     'description' => 'product price stock inventory',
 *     'type'        => 'sql',
 *     'db_path'     => '/data/products.db',
 *     'query'       => 'SELECT name, price, stock FROM products WHERE name LIKE :q LIMIT 5',
 *   ]);
 */
class ToolKit {

    private SQLite3 $db;
    private array   $phpTools = []; // name => callable (in-memory only)

    public function __construct(string $dataDir) {
        $this->db = new SQLite3($dataDir . '/yuga_tools.db');
        $this->db->enableExceptions(true);
        $this->migrate();
    }

    // =================================================================
    // TOOL REGISTRATION
    // =================================================================

    /**
     * Register a tool.
     *
     * $schema keys:
     *   name        string   required  unique tool name (snake_case)
     *   description string   required  keywords for BM25 intent matching
     *   type        string   required  'php' | 'webhook' | 'get' | 'sql'
     *   endpoint    string            URL for webhook/get types
     *   db_path     string            SQLite path for sql type
     *   query       string            SQL template (use :param placeholders)
     *   params      array             parameter schema (see above)
     *   secret      string            HMAC secret for webhook auth
     *   timeout     int               HTTP timeout in seconds (default 10)
     *   enabled     bool              default true
     */
    public function register(array $schema): array {
        $name = trim($schema['name'] ?? '');
        if (!preg_match('/^[a-z0-9_]{1,64}$/', $name)) {
            return ['error' => 'Tool name must be snake_case, max 64 chars'];
        }
        if (empty($schema['description'])) {
            return ['error' => 'description is required (used for intent matching)'];
        }
        $type = $schema['type'] ?? 'webhook';
        if (!in_array($type, ['php', 'webhook', 'get', 'sql'])) {
            return ['error' => "Invalid type. Use: php | webhook | get | sql"];
        }

        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO tools
            (name, description, type, endpoint, db_path, query, params, secret, timeout, enabled, created_at)
            VALUES (:n,:d,:t,:e,:dp,:q,:p,:s,:to,:en,:c)
        ");
        $stmt->bindValue(':n',  $name);
        $stmt->bindValue(':d',  $schema['description']);
        $stmt->bindValue(':t',  $type);
        $stmt->bindValue(':e',  $schema['endpoint'] ?? '');
        $stmt->bindValue(':dp', $schema['db_path']  ?? '');
        $stmt->bindValue(':q',  $schema['query']    ?? '');
        $stmt->bindValue(':p',  json_encode($schema['params'] ?? []));
        $stmt->bindValue(':s',  $schema['secret']   ?? '');
        $stmt->bindValue(':to', (int)($schema['timeout'] ?? 10));
        $stmt->bindValue(':en', 1);
        $stmt->bindValue(':c',  time());
        $stmt->execute();

        return ['registered' => true, 'name' => $name, 'type' => $type];
    }

    /** Register a PHP callable (in-memory, not persisted). */
    public function registerPhp(string $name, string $description, callable $fn): void {
        $this->phpTools[$name] = $fn;
        $this->register([
            'name'        => $name,
            'description' => $description,
            'type'        => 'php',
        ]);
    }

    public function unregister(string $name): void {
        $stmt = $this->db->prepare("DELETE FROM tools WHERE name=:n");
        $stmt->bindValue(':n', $name);
        $stmt->execute();
        unset($this->phpTools[$name]);
    }

    public function enable(string $name, bool $on = true): void {
        $stmt = $this->db->prepare("UPDATE tools SET enabled=:e WHERE name=:n");
        $stmt->bindValue(':e', (int)$on);
        $stmt->bindValue(':n', $name);
        $stmt->execute();
    }

    // =================================================================
    // TOOL SELECTION (BM25 intent matching — no LLM)
    // =================================================================

    /**
     * Given a user query, returns the best-matching tool name or null.
     * Uses the same BM25 keyword scoring as Agent.php.
     */
    public function selectTool(string $query): ?string {
        $tools = $this->listTools();
        if (empty($tools)) return null;

        $qWords = $this->tokenize($query);
        $scores = [];

        foreach ($tools as $tool) {
            $dWords       = $this->tokenize($tool['description']);
            $dFreq        = array_count_values($dWords);
            $score        = 0.0;
            foreach ($qWords as $w) {
                if (isset($dFreq[$w])) $score += 1.0 / $dFreq[$w];
            }
            $scores[$tool['name']] = $score;
        }

        arsort($scores);
        $top = array_key_first($scores);
        return ($scores[$top] ?? 0) > 0.1 ? $top : null;
    }

    /**
     * Returns all tools matching the query above a confidence threshold,
     * sorted by score. Used by Agent when multiple tools might help.
     */
    public function matchTools(string $query, float $threshold = 0.1): array {
        $tools = $this->listTools();
        $qWords = $this->tokenize($query);
        $results = [];

        foreach ($tools as $tool) {
            $dWords = $this->tokenize($tool['description']);
            $dFreq  = array_count_values($dWords);
            $score  = 0.0;
            foreach ($qWords as $w) {
                if (isset($dFreq[$w])) $score += 1.0 / $dFreq[$w];
            }
            if ($score >= $threshold) {
                $results[] = array_merge($tool, ['match_score' => round($score, 3)]);
            }
        }

        usort($results, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
        return $results;
    }

    // =================================================================
    // TOOL EXECUTION
    // =================================================================

    /**
     * Call a tool by name with parameters.
     * Returns ['output' => string, 'raw' => mixed, 'tool' => name, 'ok' => bool]
     */
    public function call(string $name, array $params = []): array {
        $tool = $this->getTool($name);
        if (!$tool) {
            return $this->err("Tool '$name' not found");
        }
        if (!$tool['enabled']) {
            return $this->err("Tool '$name' is disabled");
        }

        // Validate required params
        $schema = json_decode($tool['params'] ?? '[]', true) ?: [];
        foreach ($schema as $p) {
            if (!empty($p['required']) && !isset($params[$p['name']])) {
                return $this->err("Missing required param: {$p['name']}");
            }
        }

        try {
            return match ($tool['type']) {
                'php'     => $this->callPhp($name, $params),
                'webhook' => $this->callWebhook($tool, $params),
                'get'     => $this->callGet($tool, $params),
                'sql'     => $this->callSql($tool, $params),
                default   => $this->err("Unknown tool type: {$tool['type']}"),
            };
        } catch (\Throwable $e) {
            return $this->err("Tool '$name' failed: " . $e->getMessage());
        }
    }

    // ── PHP callable ───────────────────────────────────────────────────
    private function callPhp(string $name, array $params): array {
        if (!isset($this->phpTools[$name])) {
            return $this->err("PHP tool '$name' not registered in memory (restart needed)");
        }
        $result = ($this->phpTools[$name])($params);
        $output = is_string($result) ? $result : json_encode($result);
        return ['ok' => true, 'tool' => $name, 'output' => $output, 'raw' => $result];
    }

    // ── Webhook — POST to any external URL ─────────────────────────────
    private function callWebhook(array $tool, array $params): array {
        $url     = $tool['endpoint'] ?? '';
        if (!$url) return $this->err('Webhook URL not configured');

        $payload = json_encode(['params' => $params, 'tool' => $tool['name']]);
        $headers = ['Content-Type: application/json'];

        // HMAC auth if secret configured
        if (!empty($tool['secret'])) {
            $sig       = hash_hmac('sha256', $payload, $tool['secret']);
            $headers[] = 'X-Yuga-Signature: ' . $sig;
        }

        $response = $this->httpPost($url, $payload, $headers, (int)($tool['timeout'] ?? 10));
        $data     = json_decode($response, true);
        $output   = $data['output'] ?? $data['result'] ?? $data['text'] ?? $response;

        return [
            'ok'     => true,
            'tool'   => $tool['name'],
            'output' => is_string($output) ? $output : json_encode($output),
            'raw'    => $data,
        ];
    }

    // ── HTTP GET — REST API query ───────────────────────────────────────
    private function callGet(array $tool, array $params): array {
        $url = $tool['endpoint'] ?? '';
        if (!$url) return $this->err('GET URL not configured');

        // Append params as query string
        if ($params) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
        }

        $headers = [];
        if (!empty($tool['secret'])) {
            $headers[] = 'Authorization: Bearer ' . $tool['secret'];
        }

        $response = $this->httpGet($url, $headers, (int)($tool['timeout'] ?? 10));
        $data     = json_decode($response, true);
        $output   = $data['output'] ?? $data['result'] ?? $data['text'] ?? $response;

        // If result is array/object, convert to readable text
        if (is_array($output)) {
            $output = $this->arrayToText($output);
        }

        return [
            'ok'     => true,
            'tool'   => $tool['name'],
            'output' => (string)$output,
            'raw'    => $data,
        ];
    }

    // ── SQL — safe read-only database query ────────────────────────────
    private function callSql(array $tool, array $params): array {
        $dbPath = $tool['db_path'] ?? '';
        $query  = $tool['query']   ?? '';

        if (!$dbPath || !$query) {
            return $this->err('SQL tool requires db_path and query');
        }
        if (!file_exists($dbPath)) {
            return $this->err("Database not found: $dbPath");
        }

        // Safety: only allow SELECT statements
        $trimmed = ltrim(strtolower(trim($query)));
        if (!str_starts_with($trimmed, 'select')) {
            return $this->err('Only SELECT queries are allowed in SQL tools');
        }

        $db   = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
        $stmt = $db->prepare($query);

        // Bind :param placeholders
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }

        $result = $stmt->execute();
        $rows   = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        $db->close();

        $output = $this->arrayToText($rows);
        return [
            'ok'     => true,
            'tool'   => $tool['name'],
            'output' => $output,
            'raw'    => $rows,
            'rows'   => count($rows),
        ];
    }

    // =================================================================
    // CRUD + LISTING
    // =================================================================

    public function listTools(bool $enabledOnly = true): array {
        $sql  = $enabledOnly ? "SELECT * FROM tools WHERE enabled=1" : "SELECT * FROM tools";
        $res  = $this->db->query($sql);
        $out  = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $row['params'] = json_decode($row['params'] ?? '[]', true) ?: [];
            $out[]         = $row;
        }
        return $out;
    }

    public function getTool(string $name): ?array {
        $stmt = $this->db->prepare("SELECT * FROM tools WHERE name=:n");
        $stmt->bindValue(':n', $name);
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        if (!$row) return null;
        $row['params'] = json_decode($row['params'] ?? '[]', true) ?: [];
        return $row;
    }

    // =================================================================
    // HELPERS
    // =================================================================

    private function arrayToText(array $data): string {
        if (empty($data)) return 'No results found.';
        $lines = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $parts = [];
                foreach ($item as $k => $v) {
                    if (!is_array($v)) $parts[] = "$k: $v";
                }
                $lines[] = implode(', ', $parts);
            } else {
                $lines[] = (string)$item;
            }
        }
        return implode('. ', $lines) . '.';
    }

    private function tokenize(string $text): array {
        $text  = strtolower(preg_replace('/[^a-z0-9\s]/i', ' ', $text));
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $stop  = ['the','a','an','is','are','to','of','in','on','at','for','and','or','it','this','that'];
        return array_values(array_filter($words, fn($w) => !in_array($w, $stop) && strlen($w) > 1));
    }

    private function httpPost(string $url, string $body, array $headers, int $timeout): string {
        if (!function_exists('curl_init')) return '{}';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res ?: '{}';
    }

    private function httpGet(string $url, array $headers, int $timeout): string {
        if (!function_exists('curl_init')) return '{}';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res ?: '{}';
    }

    private function err(string $msg): array {
        return ['ok' => false, 'error' => $msg, 'output' => $msg];
    }

    private function migrate(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS tools (
                name        TEXT PRIMARY KEY,
                description TEXT NOT NULL,
                type        TEXT NOT NULL DEFAULT 'webhook',
                endpoint    TEXT DEFAULT '',
                db_path     TEXT DEFAULT '',
                query       TEXT DEFAULT '',
                params      TEXT DEFAULT '[]',
                secret      TEXT DEFAULT '',
                timeout     INTEGER DEFAULT 10,
                enabled     INTEGER DEFAULT 1,
                created_at  INTEGER
            );
            CREATE TABLE IF NOT EXISTS tool_calls (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                tool_name  TEXT NOT NULL,
                params     TEXT,
                output     TEXT,
                ok         INTEGER DEFAULT 1,
                duration_ms INTEGER,
                ts         INTEGER
            );
            CREATE INDEX IF NOT EXISTS idx_calls_tool ON tool_calls(tool_name, ts);
        ");
    }

    /** Log a tool call for analytics. */
    public function logCall(string $name, array $params, array $result, int $ms): void {
        $stmt = $this->db->prepare(
            "INSERT INTO tool_calls (tool_name,params,output,ok,duration_ms,ts)
             VALUES (:n,:p,:o,:ok,:ms,:t)"
        );
        $stmt->bindValue(':n',  $name);
        $stmt->bindValue(':p',  json_encode($params));
        $stmt->bindValue(':o',  $result['output'] ?? '');
        $stmt->bindValue(':ok', (int)($result['ok'] ?? false));
        $stmt->bindValue(':ms', $ms);
        $stmt->bindValue(':t',  time());
        $stmt->execute();
    }
}
