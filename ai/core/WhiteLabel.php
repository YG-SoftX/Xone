<?php
/**
 * WhiteLabel — Multi-tenant configuration
 *
 * Each subscriber can have their own:
 *   - Widget name, colour, greeting
 *   - Custom "Powered by" removal (Pro+)
 *   - Isolated model (their data never mixes)
 *   - Custom domain for the API
 *   - Avatar and persona
 */
class WhiteLabel {

    private SQLite3 $db;

    public function __construct(string $data_dir) {
        $this->db = new SQLite3($data_dir . '/yuga_tenants.db');
        $this->db->enableExceptions(true);
        $this->migrate();
    }

    private function migrate(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS tenants (
                sub_id          TEXT PRIMARY KEY,
                widget_name     TEXT DEFAULT 'AI Assistant',
                widget_greeting TEXT DEFAULT 'Hi! How can I help you today?',
                widget_theme    TEXT DEFAULT 'dark',
                primary_color   TEXT DEFAULT '#6366f1',
                logo_url        TEXT DEFAULT '',
                avatar_initials TEXT DEFAULT 'AI',
                persona         TEXT DEFAULT 'helpful and friendly',
                hide_branding   INTEGER DEFAULT 0,
                custom_domain   TEXT DEFAULT '',
                model_prefix    TEXT DEFAULT '',
                allowed_origins TEXT DEFAULT '*',
                updated_at      INTEGER
            );
        ");
    }

    // ── Get or create tenant config ───────────────────────────────────
    public function getTenant(string $sub_id): array {
        $stmt = $this->db->prepare("SELECT * FROM tenants WHERE sub_id=:s");
        $stmt->bindValue(':s', $sub_id);
        $r = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        if ($r) return $r;
        // Create default
        $this->saveTenant($sub_id, []);
        return $this->getTenant($sub_id);
    }

    // ── Save tenant config ────────────────────────────────────────────
    public function saveTenant(string $sub_id, array $config): void {
        $fields = [
            'widget_name', 'widget_greeting', 'widget_theme', 'primary_color',
            'logo_url', 'avatar_initials', 'persona', 'hide_branding',
            'custom_domain', 'allowed_origins',
        ];
        $existing = $this->getTenantRaw($sub_id) ?: [];
        $merged   = array_merge($existing, $config);

        $stmt = $this->db->prepare("INSERT OR REPLACE INTO tenants
            (sub_id,widget_name,widget_greeting,widget_theme,primary_color,
             logo_url,avatar_initials,persona,hide_branding,custom_domain,allowed_origins,updated_at)
            VALUES (:id,:wn,:wg,:wt,:pc,:lu,:ai,:pe,:hb,:cd,:ao,:t)");
        $stmt->bindValue(':id', $sub_id);
        $stmt->bindValue(':wn', $merged['widget_name']     ?? 'AI Assistant');
        $stmt->bindValue(':wg', $merged['widget_greeting'] ?? 'Hi! How can I help?');
        $stmt->bindValue(':wt', $merged['widget_theme']    ?? 'dark');
        $stmt->bindValue(':pc', $merged['primary_color']   ?? '#6366f1');
        $stmt->bindValue(':lu', $merged['logo_url']        ?? '');
        $stmt->bindValue(':ai', $merged['avatar_initials'] ?? 'AI');
        $stmt->bindValue(':pe', $merged['persona']         ?? 'helpful and friendly');
        $stmt->bindValue(':hb', (int)($merged['hide_branding'] ?? 0));
        $stmt->bindValue(':cd', $merged['custom_domain']   ?? '');
        $stmt->bindValue(':ao', $merged['allowed_origins'] ?? '*');
        $stmt->bindValue(':t',  time());
        $stmt->execute();
    }

    private function getTenantRaw(string $sub_id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM tenants WHERE sub_id=:s");
        $stmt->bindValue(':s', $sub_id);
        $r = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        return $r ?: null;
    }

    // ── Generate white-label embed snippet ────────────────────────────
    public function generateEmbed(string $sub_id, string $api_base, string $model, string $plan): string {
        $t    = $this->getTenant($sub_id);
        $hide = ($t['hide_branding'] && in_array($plan, ['pro', 'enterprise']));

        $attrs = [
            'data-api'         => $api_base,
            'data-model'       => $model,
            'data-theme'       => $t['widget_theme'],
            'data-title'       => $t['widget_name'],
            'data-placeholder' => $t['widget_greeting'],
            'data-color'       => $t['primary_color'],
            'data-avatar'      => $t['avatar_initials'],
            'data-branding'    => $hide ? 'false' : 'true',
            'data-auto-learn'  => 'true',
        ];

        $widget_url = rtrim($api_base, '/api/') . '/widget/yuga-widget.js';
        $attr_str   = implode("\n  ", array_map(
            fn($k, $v) => $k . '="' . htmlspecialchars($v) . '"',
            array_keys($attrs), $attrs
        ));

        return "<script\n  src=\"{$widget_url}\"\n  {$attr_str}\n></script>";
    }

    // ── Validate request origin ────────────────────────────────────────
    public function isOriginAllowed(string $sub_id, string $origin): bool {
        $t       = $this->getTenant($sub_id);
        $allowed = $t['allowed_origins'] ?? '*';
        if ($allowed === '*') return true;
        $domains = array_map('trim', explode(',', $allowed));
        foreach ($domains as $d) {
            if ($d && str_contains($origin, $d)) return true;
        }
        return false;
    }

    // ── List all tenants ──────────────────────────────────────────────
    public function listTenants(int $limit = 100): array {
        $res = $this->db->query("SELECT * FROM tenants ORDER BY updated_at DESC LIMIT $limit");
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }
}
