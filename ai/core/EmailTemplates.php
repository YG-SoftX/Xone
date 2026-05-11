<?php
/**
 * EmailTemplates — Editable transactional email templates for Yuga
 * Custom templates are stored in data/email_templates.json
 * Falls back to built-in Mailer defaults when no override exists.
 */
class EmailTemplates {

    private string $file;
    private array  $data;

    // ── Human labels ──────────────────────────────────────────────────
    public const LABELS = [
        'welcome'           => 'Welcome / API key delivery',
        'usage_warning'     => 'API usage warning (80%)',
        'training_complete' => 'Model training complete',
        'receipt'           => 'Payment receipt',
        'suspended'         => 'Account suspended',
    ];

    // ── Default subjects ──────────────────────────────────────────────
    public const SUBJECTS = [
        'welcome'           => 'Welcome to {platform} — your API key is ready',
        'usage_warning'     => '{platform}: you\'ve used {pct}% of your daily API calls',
        'training_complete' => '{platform}: model \'{model}\' has finished training',
        'receipt'           => '{platform} payment receipt — {plan} plan',
        'suspended'         => '{platform}: your account has been suspended',
    ];

    // ── Default body HTML (inner content, not the wrapper) ────────────
    public const BODIES = [
        'welcome' => "<h2>Welcome to {platform}, {name}!</h2>
<p>Your <strong>{plan}</strong> plan is active. Here is your API key:</p>
<div style='background:#0a0f1e;color:#14b8a6;padding:14px;border-radius:8px;font-family:monospace;word-break:break-all;margin:16px 0'>{api_key}</div>
<p style='color:#ef4444;font-size:13px'>Save this key — it will not be shown again.</p>
<p><a href='{portal}' style='background:#6366f1;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none'>Open developer portal</a></p>
<p style='margin-top:16px'><a href='{docs}'>Read the API docs →</a></p>",

        'usage_warning' => "<h2>API usage alert, {name}</h2>
<p>You have used <strong>{used} of {limit}</strong> API calls today ({pct}%).</p>
<p>You are on the <strong>{plan}</strong> plan. Upgrade now to avoid service interruption.</p>
<p><a href='{upgrade}' style='background:#6366f1;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none'>Upgrade plan</a></p>",

        'training_complete' => "<h2>Training complete, {name}!</h2>
<p>Your model <strong>{model}</strong> has finished training.</p>
<ul><li>Steps: {steps}</li><li>Final loss: {loss}</li></ul>
<p>Your model is now ready to generate text. Visit the admin dashboard to test it.</p>",

        'receipt' => "<h2>Payment receipt</h2>
<p>Thank you, {name}. Your payment was successful.</p>
<table style='width:100%;border-collapse:collapse'>
  <tr><td style='padding:8px;border-bottom:1px solid #eee'>Plan</td><td style='padding:8px;border-bottom:1px solid #eee'><strong>{plan}</strong></td></tr>
  <tr><td style='padding:8px;border-bottom:1px solid #eee'>Amount</td><td style='padding:8px;border-bottom:1px solid #eee'><strong>{amount}</strong></td></tr>
  <tr><td style='padding:8px;border-bottom:1px solid #eee'>Gateway</td><td style='padding:8px;border-bottom:1px solid #eee'>{gateway}</td></tr>
  <tr><td style='padding:8px;border-bottom:1px solid #eee'>Reference</td><td style='padding:8px;border-bottom:1px solid #eee;font-family:monospace;font-size:12px'>{ref}</td></tr>
  <tr><td style='padding:8px'>Date</td><td style='padding:8px'>{date}</td></tr>
</table>",

        'suspended' => "<h2>Account suspended, {name}</h2>
<p>{reason}</p>
<p>Please contact support if you believe this is an error.</p>",
    ];

    // ── Available variables per template ──────────────────────────────
    public const VARS = [
        'welcome'           => ['{name}', '{platform}', '{plan}', '{api_key}', '{portal}', '{docs}'],
        'usage_warning'     => ['{name}', '{platform}', '{used}', '{limit}', '{pct}', '{plan}', '{upgrade}'],
        'training_complete' => ['{name}', '{platform}', '{model}', '{steps}', '{loss}'],
        'receipt'           => ['{name}', '{platform}', '{plan}', '{amount}', '{gateway}', '{ref}', '{date}'],
        'suspended'         => ['{name}', '{platform}', '{reason}'],
    ];

    public function __construct(string $data_dir) {
        $this->file = rtrim($data_dir, '/') . '/email_templates.json';
        $raw        = file_exists($this->file) ? json_decode(file_get_contents($this->file), true) : null;
        $this->data = is_array($raw) ? $raw : [];
    }

    public function getSubject(string $name): string {
        return $this->data[$name]['subject'] ?? self::SUBJECTS[$name] ?? '';
    }

    public function getBody(string $name): string {
        return $this->data[$name]['body'] ?? self::BODIES[$name] ?? '';
    }

    public function isCustom(string $name): bool {
        return isset($this->data[$name]);
    }

    public function getUpdatedAt(string $name): ?int {
        return $this->data[$name]['updated_at'] ?? null;
    }

    public function save(string $name, string $subject, string $body): bool {
        if (!array_key_exists($name, self::LABELS)) return false;
        $this->data[$name] = [
            'subject'    => $subject,
            'body'       => $body,
            'updated_at' => time(),
        ];
        return (bool) file_put_contents(
            $this->file,
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    public function reset(string $name): void {
        unset($this->data[$name]);
        file_put_contents(
            $this->file,
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /** Return all template names */
    public function names(): array {
        return array_keys(self::LABELS);
    }

    /** Build a full preview HTML using sample data */
    public function preview(string $name, string $platform = 'Yuga'): string {
        $samples = [
            'welcome'           => ['name'=>'Jane Smith','platform'=>$platform,'plan'=>'Pro','api_key'=>'yk_live_xxxxxxxxxxxxxxxxxxx','portal'=>'#','docs'=>'#'],
            'usage_warning'     => ['name'=>'Jane Smith','platform'=>$platform,'used'=>'8,200','limit'=>'10,000','pct'=>'82','plan'=>'Starter','upgrade'=>'#'],
            'training_complete' => ['name'=>'Jane Smith','platform'=>$platform,'model'=>'yug10','steps'=>'50,000','loss'=>'1.4823'],
            'receipt'           => ['name'=>'Jane Smith','platform'=>$platform,'plan'=>'Pro','amount'=>'Rs 999.00','gateway'=>'eSewa','ref'=>'ESW-20260325-0042','date'=>date('d M Y H:i')],
            'suspended'         => ['name'=>'Jane Smith','platform'=>$platform,'reason'=>'Payment overdue for 30+ days. Please update your billing information to restore access.'],
        ];
        $vars  = $samples[$name] ?? [];
        $body  = $this->getBody($name);
        foreach ($vars as $k => $v) {
            $body = str_replace('{' . $k . '}', htmlspecialchars($v), $body);
        }
        return $this->wrapLayout($body, $platform);
    }

    private function wrapLayout(string $content, string $platform): string {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
body{font-family:system-ui,sans-serif;background:#f8fafc;margin:0;padding:20px}
.wrap{max-width:520px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1)}
.header{background:#0a0f1e;padding:20px 28px;display:flex;align-items:center;gap:10px}
.logo{width:32px;height:32px;background:#6366f1;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px}
.logo-text{color:#fff;font-size:16px;font-weight:700}
.body{padding:28px;color:#1e293b;line-height:1.6;font-size:14px}
.footer{padding:16px 28px;background:#f8fafc;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0}
h2{margin:0 0 14px;color:#0f172a}a{color:#6366f1}
</style></head><body>
<div class="wrap">
<div class="header"><div class="logo">Y</div><div class="logo-text">' . htmlspecialchars($platform) . '</div></div>
<div class="body">' . $content . '</div>
<div class="footer">' . htmlspecialchars($platform) . ' · Powered by Yuga v1.0 · <a href="#">Unsubscribe</a></div>
</div></body></html>';
    }
}
