<?php
/**
 * Mailer — Transactional email for Yuga
 * Works with cPanel mail() or SMTP (recommended).
 *
 * Set SMTP config in config.php under 'mail' key.
 * Falls back to PHP mail() if no SMTP configured.
 */
class Mailer {

    private array  $config;
    private ?array $custom_tpl = null; // lazy-loaded custom templates

    public function __construct(array $config = []) {
        $this->config = $config['mail'] ?? [];
    }

    // ── Load custom templates from data/ (lazy, cached per instance) ──
    private function customTemplates(): array {
        if ($this->custom_tpl !== null) return $this->custom_tpl;
        $file = __DIR__ . '/../data/email_templates.json';
        if (file_exists($file)) {
            $raw = json_decode(file_get_contents($file), true);
            $this->custom_tpl = is_array($raw) ? $raw : [];
        } else {
            $this->custom_tpl = [];
        }
        return $this->custom_tpl;
    }

    // ── Helper: resolve subject (custom override or default) ─────────
    private function subject(string $name, string $default, array $vars = []): string {
        $tpls = $this->customTemplates();
        $subj = !empty($tpls[$name]['subject']) ? $tpls[$name]['subject'] : $default;
        foreach ($vars as $k => $v) {
            $subj = str_replace('{' . $k . '}', (string)$v, $subj);
        }
        return $subj;
    }

    // ── Send welcome email with API key ───────────────────────────────
    public function sendWelcome(string $to_email, string $to_name, string $api_key, string $plan): bool {
        $vars    = ['name' => $to_name, 'plan' => ucfirst($plan), 'api_key' => $api_key,
                    'portal' => $this->config['portal_url'] ?? '',
                    'docs'   => ($this->config['portal_url'] ?? '') . '?page=docs'];
        $subject = $this->subject('welcome', 'Welcome to Yuga — your API key is ready', $vars);
        $body    = $this->template('welcome', $vars);
        return $this->send($to_email, $to_name, $subject, $body);
    }

    // ── Usage limit warning ────────────────────────────────────────────
    public function sendUsageWarning(string $to_email, string $to_name, int $used, int $limit, string $plan): bool {
        $pct     = round($used / max($limit, 1) * 100);
        $subject = $this->subject('usage_warning', "Yuga: you've used {$pct}% of your daily API calls", ['pct' => $pct]);
        $body    = $this->template('usage_warning', [
            'name'    => $to_name,
            'used'    => number_format($used),
            'limit'   => number_format($limit),
            'pct'     => $pct,
            'plan'    => ucfirst($plan),
            'upgrade' => ($this->config['portal_url'] ?? '') . '?page=signup',
        ]);
        return $this->send($to_email, $to_name, $subject, $body);
    }

    // ── Training complete notification ─────────────────────────────────
    public function sendTrainingComplete(string $to_email, string $to_name, string $model, int $steps, float $loss): bool {
        $vars    = ['name' => $to_name, 'model' => $model, 'steps' => number_format($steps), 'loss' => round($loss, 4)];
        $subject = $this->subject('training_complete', "Yuga: model '{$model}' has finished training", $vars);
        $body    = $this->template('training_complete', $vars);
        return $this->send($to_email, $to_name, $subject, $body);
    }

    // ── Payment receipt ───────────────────────────────────────────────
    public function sendReceipt(string $to_email, string $to_name, array $txn): bool {
        $vars    = ['name' => $to_name, 'plan' => ucfirst($txn['plan'] ?? ''),
                    'amount' => 'Rs ' . number_format($txn['amount'] ?? 0, 2),
                    'gateway' => ucfirst($txn['gateway'] ?? ''),
                    'ref' => $txn['gateway_ref'] ?? $txn['id'] ?? '', 'date' => date('d M Y H:i')];
        $subject = $this->subject('receipt', 'Yuga payment receipt — ' . ($vars['plan']) . ' plan', $vars);
        $body    = $this->template('receipt', $vars);
        return $this->send($to_email, $to_name, $subject, $body);
    }

    // ── Key revoked / account suspended ──────────────────────────────
    public function sendSuspended(string $to_email, string $to_name, string $reason = ''): bool {
        $vars    = ['name' => $to_name, 'reason' => $reason ?: 'Please contact support for details.'];
        $subject = $this->subject('suspended', 'Yuga: your account has been suspended', $vars);
        $body    = $this->template('suspended', $vars);
        return $this->send($to_email, $to_name, $subject, $body);
    }

    // ── Core send method ──────────────────────────────────────────────
    /** Quick helper — no name needed, used for test emails */
    public function sendRaw(string $to, string $subject, string $html_body): bool {
        return $this->send($to, $to, $subject, $html_body);
    }

    public function send(string $to, string $to_name, string $subject, string $html_body): bool {
        $from_email = $this->config['from_email'] ?? 'noreply@yuga.ai';
        $from_name  = $this->config['from_name']  ?? 'Yuga';
        $smtp       = $this->config['smtp']        ?? null;

        if ($smtp) {
            return $this->sendSMTP($to, $to_name, $subject, $html_body, $from_email, $from_name, $smtp);
        }

        // Fall back to PHP mail()
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$from_name} <{$from_email}>\r\n";
        $headers .= "Reply-To: {$from_email}\r\n";
        $headers .= "X-Mailer: Yuga/1.0\r\n";

        return @mail($to, $subject, $html_body, $headers);
    }

    // ── SMTP via cURL (no PHPMailer needed) ───────────────────────────
    private function sendSMTP(
        string $to, string $to_name, string $subject, string $html,
        string $from_email, string $from_name, array $smtp
    ): bool {
        // Build MIME message manually
        $boundary = bin2hex(random_bytes(8));
        $message  = "From: {$from_name} <{$from_email}>\r\n"
                  . "To: {$to_name} <{$to}>\r\n"
                  . "Subject: {$subject}\r\n"
                  . "MIME-Version: 1.0\r\n"
                  . "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n"
                  . "--{$boundary}\r\n"
                  . "Content-Type: text/plain; charset=UTF-8\r\n\r\n"
                  . strip_tags($html) . "\r\n\r\n"
                  . "--{$boundary}\r\n"
                  . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
                  . $html . "\r\n\r\n"
                  . "--{$boundary}--";

        // Use cURL SMTP
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => "smtp://{$smtp['host']}:{$smtp['port']}",
            CURLOPT_USE_SSL        => CURLUSESSL_TRY,
            CURLOPT_USERNAME       => $smtp['username'],
            CURLOPT_PASSWORD       => $smtp['password'],
            CURLOPT_MAIL_FROM      => "<{$from_email}>",
            CURLOPT_MAIL_RCPT      => ["<{$to}>"],
            CURLOPT_READDATA       => fopen('php://memory', 'r+'),
            CURLOPT_UPLOAD         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        // Write message to stream
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $message);
        rewind($stream);
        curl_setopt($ch, CURLOPT_READDATA, $stream);
        curl_setopt($ch, CURLOPT_INFILESIZE, strlen($message));
        curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($stream);
        return empty($error);
    }

    // ── Email templates ────────────────────────────────────────────────
    private function template(string $name, array $vars): string {
        // Check for custom override from admin template editor
        $tpls = $this->customTemplates();
        if (!empty($tpls[$name]['body'])) {
            $content = $tpls[$name]['body'];
            $html    = $this->wrapLayout($content);
            foreach ($vars as $k => $v) {
                $html = str_replace('{' . $k . '}', htmlspecialchars((string)$v), $html);
            }
            return $html;
        }

        $content = match ($name) {
            'welcome' => "
                <h2>Welcome to Yuga, {name}!</h2>
                <p>Your <strong>{plan}</strong> plan is active. Here is your API key:</p>
                <div style='background:#0a0f1e;color:#14b8a6;padding:14px;border-radius:8px;font-family:monospace;word-break:break-all;margin:16px 0'>{api_key}</div>
                <p style='color:#ef4444;font-size:13px'>Save this key — it will not be shown again.</p>
                <p><a href='{portal}' style='background:#6366f1;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none'>Open developer portal</a></p>
                <p style='margin-top:16px'><a href='{docs}'>Read the API docs →</a></p>
            ",
            'usage_warning' => "
                <h2>API usage alert, {name}</h2>
                <p>You have used <strong>{used} of {limit}</strong> API calls today ({pct}%).</p>
                <p>You are on the <strong>{plan}</strong> plan. Upgrade now to avoid service interruption.</p>
                <p><a href='{upgrade}' style='background:#6366f1;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none'>Upgrade plan</a></p>
            ",
            'training_complete' => "
                <h2>Training complete, {name}!</h2>
                <p>Your model <strong>{model}</strong> has finished training.</p>
                <ul><li>Steps: {steps}</li><li>Final loss: {loss}</li></ul>
                <p>Your model is now ready to generate text. Visit the admin dashboard to test it.</p>
            ",
            'receipt' => "
                <h2>Payment receipt</h2>
                <p>Thank you, {name}. Your payment was successful.</p>
                <table style='width:100%;border-collapse:collapse'>
                    <tr><td style='padding:8px;border-bottom:1px solid #eee'>Plan</td><td style='padding:8px;border-bottom:1px solid #eee'><strong>{plan}</strong></td></tr>
                    <tr><td style='padding:8px;border-bottom:1px solid #eee'>Amount</td><td style='padding:8px;border-bottom:1px solid #eee'><strong>{amount}</strong></td></tr>
                    <tr><td style='padding:8px;border-bottom:1px solid #eee'>Gateway</td><td style='padding:8px;border-bottom:1px solid #eee'>{gateway}</td></tr>
                    <tr><td style='padding:8px;border-bottom:1px solid #eee'>Reference</td><td style='padding:8px;border-bottom:1px solid #eee;font-family:monospace;font-size:12px'>{ref}</td></tr>
                    <tr><td style='padding:8px'>Date</td><td style='padding:8px'>{date}</td></tr>
                </table>
            ",
            'suspended' => "
                <h2>Account suspended, {name}</h2>
                <p>{reason}</p>
                <p>Please contact support if you believe this is an error.</p>
            ",
            default => "<p>{content}</p>"
        };

        // Wrap in layout and replace variables
        $html = $this->wrapLayout($content);
        foreach ($vars as $k => $v) {
            $html = str_replace('{' . $k . '}', htmlspecialchars((string)$v), $html);
        }
        return $html;
    }

    private function wrapLayout(string $content): string {
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
            <div class="header"><div class="logo">Y</div><div class="logo-text">Yuga</div></div>
            <div class="body">' . $content . '</div>
            <div class="footer">Yuga v1.0 · Self-learning language model · <a href="#">Unsubscribe</a></div>
        </div></body></html>';
    }
}
