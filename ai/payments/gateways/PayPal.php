<?php
/**
 * PayPal Payment Gateway — Yuga
 * Uses PayPal Orders API v2 (REST).
 * Docs: https://developer.paypal.com/docs/api/orders/v2/
 */
class PayPal {

    private string $client_id;
    private string $client_secret;
    private bool   $test_mode;
    private string $base;

    public function __construct(string $client_id, string $client_secret, bool $test_mode = true) {
        $this->client_id     = $client_id;
        $this->client_secret = $client_secret;
        $this->test_mode     = $test_mode;
        $this->base = $test_mode
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    // ── Get OAuth2 access token ────────────────────────────────────────
    private function getAccessToken(): string {
        $ch = curl_init($this->base . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
            CURLOPT_USERPWD        => $this->client_id . ':' . $this->client_secret,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response ?: '{}', true);
        return $data['access_token'] ?? '';
    }

    // ── Create a PayPal order ──────────────────────────────────────────
    // Returns ['approval_url' => '...', 'order_id' => '...']
    public function createOrder(array $params): array {
        $token  = $this->getAccessToken();
        if (!$token) return ['error' => 'Could not authenticate with PayPal'];

        $amount    = number_format($params['amount_usd'] ?? 9, 2, '.', '');
        $plan_name = $params['plan_name']  ?? 'Yuga Plan';
        $return_url = $params['return_url'];
        $cancel_url = $params['cancel_url'];

        $body = json_encode([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => ['currency_code' => 'USD', 'value' => $amount],
                'description' => $plan_name,
                'custom_id' => $params['txn_id'] ?? '',
                'reference_id' => $params['txn_id'] ?? '',
            ]],
            'application_context' => [
                'return_url'          => $return_url,
                'cancel_url'          => $cancel_url,
                'brand_name'          => 'Yuga',
                'user_action'         => 'PAY_NOW',
                'shipping_preference' => 'NO_SHIPPING',
            ],
        ]);

        $result = $this->request('POST', '/v2/checkout/orders', $body, $token);

        if (isset($result['error'])) {
            return ['error' => $result['error_description'] ?? 'PayPal error'];
        }

        // Find approval URL
        $approval_url = '';
        foreach ($result['links'] ?? [] as $link) {
            if ($link['rel'] === 'approve') {
                $approval_url = $link['href'];
                break;
            }
        }

        return [
            'order_id'     => $result['id'] ?? '',
            'approval_url' => $approval_url,
            'status'       => $result['status'] ?? '',
        ];
    }

    // ── Capture order after buyer approval ────────────────────────────
    public function captureOrder(string $order_id): array {
        // Validate order_id format before using in URL
        if (!preg_match('/^[A-Z0-9]{1,64}$/', $order_id)) {
            return ['status' => 'FAILED', 'message' => 'Invalid order ID format'];
        }
        $token = $this->getAccessToken();
        if (!$token) return ['status' => 'FAILED', 'message' => 'PayPal auth failed'];

        $result = $this->request('POST', '/v2/checkout/orders/' . $order_id . '/capture', '{}', $token);

        if (($result['status'] ?? '') === 'COMPLETED') {
            $capture = $result['purchase_units'][0]['payments']['captures'][0] ?? [];
            return [
                'status'     => 'COMPLETE',
                'order_id'   => $result['id'],
                'capture_id' => $capture['id'] ?? '',
                'amount'     => $capture['amount']['value'] ?? 0,
                'txn_id'     => $result['purchase_units'][0]['reference_id'] ?? '',
            ];
        }

        return [
            'status'  => 'FAILED',
            'message' => $result['message'] ?? ('PayPal status: ' . ($result['status'] ?? 'unknown')),
        ];
    }

    // ── HTTP helper ───────────────────────────────────────────────────
    private function request(string $method, string $path, string $body, string $token): array {
        $ch = curl_init($this->base . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response ?: '{}', true) ?? [];
    }
}
