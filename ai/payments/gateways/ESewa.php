<?php
/**
 * eSewa Payment Gateway — v2 API
 *
 * Nepal's most popular digital wallet (since 2009, 10M+ users).
 * Uses HMAC-SHA256 signature + form redirect.
 *
 * Test credentials:
 *   merchant_code : EPAYTEST
 *   secret_key    : 8gBm/:&EnhH.1/q
 *   eSewa ID      : 9806800001/2/3/4/5
 *   password      : Nepal@123
 *   MPIN          : 1122
 *   OTP           : 123456
 *
 * Merchant signup: https://merchant.esewa.com.np
 */
class ESewa {

    // API endpoints
    const URL_TEST = 'https://rc-epay.esewa.com.np/api/epay/main/v2/form';
    const URL_LIVE = 'https://epay.esewa.com.np/api/epay/main/v2/form';
    const VFY_TEST = 'https://rc.esewa.com.np/api/epay/transaction/status/';
    const VFY_LIVE = 'https://epay.esewa.com.np/api/epay/transaction/status/';

    private string $merchant_code;
    private string $secret_key;
    private bool   $test_mode;

    public function __construct(string $merchant_code, string $secret_key, bool $test_mode = false) {
        $this->merchant_code = $merchant_code;
        $this->secret_key    = $secret_key;
        $this->test_mode     = $test_mode;
    }

    // ── Build payment form and auto-submit ────────────────────────────
    public function initiatePayment(array $params): string {
        // Required params:
        // amount, tax_amount, total_amount, transaction_uuid,
        // success_url, failure_url, signed_field_names

        $uuid      = $params['transaction_uuid'] ?? ('YUGA-' . uniqid());
        $amount    = number_format((float)$params['amount'], 2, '.', '');
        $tax       = number_format((float)($params['tax_amount'] ?? 0), 2, '.', '');
        $total     = number_format((float)($params['total_amount'] ?? $params['amount']), 2, '.', '');
        $success   = $params['success_url'];
        $failure   = $params['failure_url'];
        $fields    = 'total_amount,transaction_uuid,product_code';

        // Generate HMAC-SHA256 signature
        $message   = "{$total},{$uuid},{$this->merchant_code}";
        $signature = base64_encode(hash_hmac('sha256', $message, $this->secret_key, true));

        $url = $this->test_mode ? self::URL_TEST : self::URL_LIVE;

        $hidden = [
            'amount'              => $amount,
            'tax_amount'          => $tax,
            'total_amount'        => $total,
            'transaction_uuid'    => $uuid,
            'product_code'        => $this->merchant_code,
            'product_service_charge' => '0',
            'product_delivery_charge' => '0',
            'success_url'         => $success,
            'failure_url'         => $failure,
            'signed_field_names'  => $fields,
            'signature'           => $signature,
        ];

        // Return auto-submitting form
        $form  = '<form id="esewa-form" method="POST" action="' . htmlspecialchars($url) . '">';
        foreach ($hidden as $k => $v) {
            $form .= '<input type="hidden" name="' . $k . '" value="' . htmlspecialchars($v) . '">';
        }
        $form .= '</form><script>document.getElementById("esewa-form").submit();</script>';

        // Also save uuid to session for verification
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['esewa_uuid']  = $uuid;
        $_SESSION['esewa_total'] = $total;

        return $form;
    }

    // ── Verify payment from success callback ─────────────────────────
    public function verifyPayment(array $query_params): array {
        // eSewa sends base64-encoded data in ?data= param
        $encoded = $query_params['data'] ?? '';
        if (!$encoded) {
            return ['status' => 'FAILED', 'message' => 'No data parameter in callback'];
        }

        $decoded = json_decode(base64_decode($encoded), true);
        if (!$decoded) {
            return ['status' => 'FAILED', 'message' => 'Invalid callback data'];
        }

        // Verify signature from callback
        $status           = $decoded['status'] ?? '';
        $total_amount     = $decoded['total_amount'] ?? '';
        $transaction_uuid = $decoded['transaction_uuid'] ?? '';
        $product_code     = $decoded['product_code'] ?? '';
        $sig_from_esewa   = $decoded['signature'] ?? '';

        $message      = "{$total_amount},{$transaction_uuid},{$product_code}";
        $expected_sig = base64_encode(hash_hmac('sha256', $message, $this->secret_key, true));

        if ($sig_from_esewa !== $expected_sig) {
            return ['status' => 'FAILED', 'message' => 'Signature mismatch — possible tampering'];
        }

        // Double-check via status API
        $status_check = $this->checkStatus($product_code, $total_amount, $transaction_uuid);

        return [
            'status'           => $status_check['status'] ?? $status,
            'transaction_uuid' => $transaction_uuid,
            'ref_id'           => $decoded['ref_id'] ?? '',
            'total_amount'     => $total_amount,
            'product_code'     => $product_code,
            'raw'              => $decoded,
        ];
    }

    // ── Check payment status via API ──────────────────────────────────
    public function checkStatus(string $product_code, string $total_amount, string $transaction_uuid): array {
        $url   = ($this->test_mode ? self::VFY_TEST : self::VFY_LIVE);
        $url  .= '?' . http_build_query([
            'product_code'     => $product_code,
            'total_amount'     => $total_amount,
            'transaction_uuid' => $transaction_uuid,
        ]);

        $response = $this->httpGet($url);
        $data     = json_decode($response, true) ?? [];

        return [
            'status'    => $data['status'] ?? 'UNKNOWN',
            'ref_id'    => $data['ref_id'] ?? '',
            'amount'    => $data['total_amount'] ?? $total_amount,
            'raw'       => $data,
        ];
    }

    private function httpGet(string $url): string {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            ]);
            $r = curl_exec($ch); curl_close($ch); return $r ?: '{}';
        }
        return @file_get_contents($url) ?: '{}';
    }
}
