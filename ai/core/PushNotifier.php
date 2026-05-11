<?php
/**
 * PushNotifier — Web push notifications via OneSignal for Yuga
 *
 * OneSignal is free (unlimited web push, up to 10k subscribers).
 * No server-side crypto needed — just REST API calls.
 *
 * Setup (5 minutes):
 *   1. Sign up at onesignal.com → Create app → Web push
 *   2. Set your site URL, upload icon, paste the SDK snippet into your portal
 *   3. Copy App ID + REST API Key → Yuga admin → Push notifications
 *
 * Config in config.php:
 *   'push' => [
 *       'app_id'   => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
 *       'api_key'  => 'your-rest-api-key',
 *       'events'   => [
 *           'new_subscriber'     => true,
 *           'payment'            => true,
 *           'training_complete'  => true,
 *           'api_limit_warning'  => false,
 *           'api_limit_exceeded' => false,
 *       ],
 *   ]
 */
class PushNotifier {

    private string $app_id;
    private string $api_key;
    private array  $events;
    private string $endpoint = 'https://onesignal.com/api/v1/notifications';
    private int    $timeout  = 10;

    // ── Event registry: key → human label ────────────────────────────
    public const EVENTS = [
        'new_subscriber'     => 'New subscriber sign-up',
        'payment'            => 'Payment received',
        'training_complete'  => 'Model training complete',
        'api_limit_warning'  => 'Subscriber hit 80% API limit',
        'api_limit_exceeded' => 'Subscriber hit 100% API limit',
    ];

    public function __construct(array $config = []) {
        $this->app_id  = trim($config['app_id']  ?? '');
        $this->api_key = trim($config['api_key'] ?? '');
        $this->events  = $config['events']        ?? [];
    }

    public function ready(): bool {
        return $this->app_id !== '' && $this->api_key !== '';
    }

    // ── Broadcast to all OneSignal subscribers ────────────────────────
    public function sendToAll(string $title, string $body, string $url = '', string $icon = ''): array {
        $payload = [
            'included_segments' => ['Total Subscribed'],
            'headings'          => ['en' => $title],
            'contents'          => ['en' => $body],
        ];
        if ($url)  $payload['url']        = $url;
        if ($icon) $payload['chrome_web_icon'] = $icon;
        return $this->deliver($payload);
    }

    // ── Send to a specific subscriber by external_id ──────────────────
    public function sendToSubscriber(string $external_id, string $title, string $body, string $url = ''): array {
        $payload = [
            'include_aliases' => ['external_id' => [$external_id]],
            'target_channel'  => 'push',
            'headings'        => ['en' => $title],
            'contents'        => ['en' => $body],
        ];
        if ($url) $payload['url'] = $url;
        return $this->deliver($payload);
    }

    // ── Fire a named event (only if enabled in settings) ─────────────
    public function fireEvent(string $event, string $title, string $body, string $url = ''): ?array {
        if (!$this->ready())             return null;
        if (empty($this->events[$event])) return null;
        return $this->sendToAll($title, $body, $url);
    }

    // ── Convenience event helpers ─────────────────────────────────────

    public function notifyNewSubscriber(string $name, string $plan): ?array {
        return $this->fireEvent(
            'new_subscriber',
            'New subscriber joined!',
            "{$name} signed up for the {$plan} plan."
        );
    }

    public function notifyPayment(string $name, string $plan, string $amount): ?array {
        return $this->fireEvent(
            'payment',
            'Payment received',
            "{$name} paid {$amount} for the {$plan} plan."
        );
    }

    public function notifyTrainingComplete(string $model, float $loss): ?array {
        return $this->fireEvent(
            'training_complete',
            'Model training complete',
            "Model '{$model}' finished. Final loss: " . round($loss, 4)
        );
    }

    public function notifyApiLimitWarning(string $name, int $pct): ?array {
        return $this->fireEvent(
            'api_limit_warning',
            'API limit warning',
            "{$name} has used {$pct}% of their daily API quota."
        );
    }

    public function notifyApiLimitExceeded(string $name): ?array {
        return $this->fireEvent(
            'api_limit_exceeded',
            'API limit reached',
            "{$name} has hit their daily API limit."
        );
    }

    // ── Get subscriber count from OneSignal ───────────────────────────
    public function getStats(): array {
        if (!$this->ready()) return ['error' => 'Not configured'];
        $ch = curl_init("https://onesignal.com/api/v1/apps/{$this->app_id}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . $this->api_key],
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($err) return ['error' => $err];
        $data = json_decode($body, true);
        return is_array($data) ? $data : ['error' => 'Invalid response'];
    }

    // ── Core HTTP delivery ────────────────────────────────────────────
    private function deliver(array $payload): array {
        if (!$this->ready()) {
            return ['error' => 'OneSignal not configured. Add App ID and REST API Key in Admin → Push notifications.'];
        }

        $payload['app_id'] = $this->app_id;
        $json = json_encode($payload);

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . $this->api_key,
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($json),
            ],
            CURLOPT_POSTFIELDS => $json,
        ]);

        $body  = curl_exec($ch);
        $error = curl_error($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) return ['error' => $error];
        $data = json_decode($body, true);
        if (!is_array($data)) return ['error' => "HTTP {$code}: Invalid response from OneSignal"];
        if (!empty($data['errors'])) return ['error' => implode(', ', (array)$data['errors'])];

        return [
            'ok'         => true,
            'recipients' => $data['recipients'] ?? 0,
            'id'         => $data['id'] ?? '',
        ];
    }
}
