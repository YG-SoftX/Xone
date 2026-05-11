<?php
/**
 * Slack Integration
 *
 * Two modes:
 *  A) Incoming webhook  — Yuga posts notifications to a Slack channel
 *  B) Slash command     — Users type /yuga <question> in Slack → Yuga answers
 *
 * Setup:
 *  1. Go to api.slack.com/apps → Create app → From scratch
 *  2. Incoming Webhooks → Activate → Add to workspace → copy webhook URL
 *  3. Slash Commands → /yuga → Request URL: https://yoursite.com/yuga/integrations/slack_hook.php
 *  4. Add to config.php:
 *     'slack' => ['webhook_url' => '...', 'signing_secret' => '...', 'model' => 'default']
 */
class Slack {

    private string $webhook_url;

    public function __construct(string $webhook_url) {
        $this->webhook_url = $webhook_url;
    }

    // ── Post a plain message to channel ──────────────────────────────────
    public function post(string $text): bool {
        return $this->send(['text' => $text]);
    }

    // ── Post a rich Block Kit message ─────────────────────────────────────
    public function postBlocks(string $text, array $blocks = []): bool {
        $payload = ['text' => $text];
        if ($blocks) $payload['blocks'] = $blocks;
        return $this->send($payload);
    }

    // ── Notify: training complete ─────────────────────────────────────────
    public function notifyTraining(string $model, int $steps, float $loss): bool {
        return $this->postBlocks("Training complete on *$model*", [
            ['type' => 'section', 'text' => ['type' => 'mrkdwn',
                'text' => ":brain: *Training complete*\nModel: `$model` | Steps: $steps | Loss: " . round($loss, 4)]],
        ]);
    }

    // ── Notify: new subscriber ────────────────────────────────────────────
    public function notifySubscriber(string $name, string $email, string $plan): bool {
        return $this->postBlocks("New subscriber: $name", [
            ['type' => 'section', 'text' => ['type' => 'mrkdwn',
                'text' => ":tada: *New subscriber*\n$name ($email) — plan: *$plan*"]],
        ]);
    }

    // ── Notify: payment received ──────────────────────────────────────────
    public function notifyPayment(string $name, float $amount, string $currency = 'USD'): bool {
        return $this->post(":money_with_wings: Payment received from *$name* — $currency " . number_format($amount, 2));
    }

    // ── Verify Slack request signature ───────────────────────────────────
    public static function verifySignature(string $signing_secret, string $body, string $ts, string $sig): bool {
        if (abs(time() - (int)$ts) > 300) return false;
        $expected = 'v0=' . hash_hmac('sha256', "v0:{$ts}:{$body}", $signing_secret);
        return hash_equals($expected, $sig);
    }

    private function send(array $payload): bool {
        $ch = curl_init($this->webhook_url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return trim($resp) === 'ok';
    }
}
