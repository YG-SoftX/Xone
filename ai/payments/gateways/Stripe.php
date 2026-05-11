<?php
/**
 * Stripe Payment Gateway — Yuga
 * Uses Stripe Checkout (hosted page). No JS SDK needed server-side.
 * Docs: https://stripe.com/docs/api/checkout/sessions
 */
class Stripe {

    private string $secret_key;
    private bool   $test_mode;
    private string $base = 'https://api.stripe.com/v1';

    public function __construct(string $secret_key, bool $test_mode = true) {
        $this->secret_key = $secret_key;
        $this->test_mode  = $test_mode;
    }

    // ── Create a Stripe Checkout session ─────────────────────────────
    // Returns ['url' => 'https://checkout.stripe.com/...', 'session_id' => '...']
    public function createCheckoutSession(array $params): array {
        $usd_cents  = (int)round(($params['amount_usd'] ?? 9) * 100);
        $plan_name  = $params['plan_name']  ?? 'Yuga Plan';
        $success_url = $params['success_url'];
        $cancel_url  = $params['cancel_url'];
        $email       = $params['email'] ?? '';

        $payload = http_build_query(array_filter([
            'mode'                                     => 'payment',
            'success_url'                              => $success_url . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'                               => $cancel_url,
            'customer_email'                           => $email,
            'line_items[0][quantity]'                  => 1,
            'line_items[0][price_data][currency]'      => 'usd',
            'line_items[0][price_data][unit_amount]'   => $usd_cents,
            'line_items[0][price_data][product_data][name]'        => $plan_name,
            'line_items[0][price_data][product_data][description]' => 'Yuga AI Platform — ' . $plan_name,
            'metadata[txn_id]'  => $params['txn_id'] ?? '',
            'metadata[plan]'    => $params['plan']   ?? '',
            'metadata[sub_id]'  => $params['sub_id'] ?? '',
        ]));

        $result = $this->request('POST', '/checkout/sessions', $payload);

        if (isset($result['error'])) {
            return ['error' => $result['error']['message'] ?? 'Stripe error'];
        }

        return [
            'session_id' => $result['id'],
            'url'        => $result['url'],
        ];
    }

    // ── Retrieve session to verify payment ────────────────────────────
    public function retrieveSession(string $session_id): array {
        $result = $this->request('GET', '/checkout/sessions/' . urlencode($session_id));

        if (isset($result['error'])) {
            return ['status' => 'FAILED', 'message' => $result['error']['message'] ?? 'Stripe error'];
        }

        $paid = $result['payment_status'] === 'paid';
        return [
            'status'     => $paid ? 'COMPLETE' : 'PENDING',
            'session_id' => $result['id'],
            'payment_intent' => $result['payment_intent'] ?? '',
            'amount_total'   => ($result['amount_total'] ?? 0) / 100,
            'currency'       => $result['currency'] ?? 'usd',
            'txn_id'         => $result['metadata']['txn_id'] ?? '',
            'plan'           => $result['metadata']['plan'] ?? '',
            'sub_id'         => $result['metadata']['sub_id'] ?? '',
        ];
    }

    // ── Verify Stripe webhook signature ───────────────────────────────
    public function verifyWebhook(string $payload, string $sig_header, string $webhook_secret): array {
        // Parse timestamp and signatures from header
        $parts = [];
        foreach (explode(',', $sig_header) as $part) {
            [$k, $v] = array_pad(explode('=', $part, 2), 2, '');
            $parts[$k] = $v;
        }

        $timestamp = $parts['t'] ?? 0;
        $signatures = array_filter(array_map(fn($p) => explode('=', $p, 2)[1] ?? '', explode(',v1=', $sig_header)));

        $signed_payload = $timestamp . '.' . $payload;
        $expected       = hash_hmac('sha256', $signed_payload, $webhook_secret);

        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) {
                $event = json_decode($payload, true);
                return ['valid' => true, 'event' => $event];
            }
        }
        return ['valid' => false, 'error' => 'Invalid webhook signature'];
    }

    // ── HTTP helper ───────────────────────────────────────────────────
    private function request(string $method, string $path, string $body = ''): array {
        $ch = curl_init($this->base . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERPWD        => $this->secret_key . ':',
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
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
