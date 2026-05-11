<?php
/**
 * Telegram Bot Integration
 *
 * Setup:
 *  1. Message @BotFather → /newbot → get token
 *  2. Set webhook: https://api.telegram.org/bot{TOKEN}/setWebhook?url=https://yoursite.com/yuga/integrations/telegram_hook.php
 *  3. Add token + model to config.php:
 *     'telegram' => ['token' => '...', 'model' => 'default']
 */
class Telegram {

    private string $token;
    private string $api;

    public function __construct(string $token) {
        $this->token = $token;
        $this->api   = "https://api.telegram.org/bot{$token}";
    }

    // ── Send a text message ───────────────────────────────────────────────
    public function send(int|string $chat_id, string $text): bool {
        return $this->post('sendMessage', [
            'chat_id'    => $chat_id,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ]);
    }

    // ── Send typing indicator ─────────────────────────────────────────────
    public function typing(int|string $chat_id): void {
        $this->post('sendChatAction', ['chat_id' => $chat_id, 'action' => 'typing']);
    }

    // ── Parse incoming webhook update ─────────────────────────────────────
    public static function parseUpdate(string $raw): ?array {
        $data = json_decode($raw, true);
        if (!$data || !isset($data['message'])) return null;
        return [
            'chat_id'  => $data['message']['chat']['id'],
            'username' => $data['message']['from']['username'] ?? '',
            'name'     => trim(($data['message']['from']['first_name'] ?? '') . ' ' . ($data['message']['from']['last_name'] ?? '')),
            'text'     => $data['message']['text'] ?? '',
            'msg_id'   => $data['message']['message_id'],
        ];
    }

    // ── Set webhook URL ───────────────────────────────────────────────────
    public function setWebhook(string $url): bool {
        return $this->post('setWebhook', ['url' => $url]);
    }

    // ── Get bot info ──────────────────────────────────────────────────────
    public function getMe(): array {
        $r = $this->get('getMe');
        return $r['result'] ?? [];
    }

    private function post(string $method, array $params): bool {
        $ch = curl_init("{$this->api}/{$method}");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($params),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        $d = json_decode($resp, true);
        return (bool)($d['ok'] ?? false);
    }

    private function get(string $method): array {
        $ch = curl_init("{$this->api}/{$method}");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return json_decode($resp, true) ?? [];
    }
}
