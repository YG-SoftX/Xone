<?php
/**
 * IME Pay Payment Gateway
 *
 * Licensed by Nepal Rastra Bank, powered by IME Remit.
 * 25,000+ agent network across Nepal.
 * Uses HTTP Basic Auth → token → redirect payment page.
 *
 * Flow:
 *   1. GET /GetToken  (Basic Auth) → receive MerchantToken
 *   2. Redirect user to payment page with token
 *   3. IME Pay calls your delivery service webhook on completion
 *   4. User redirected back to your success/failure URL
 *
 * Test credentials: obtain from developer.imepay.com.np
 *   Test host: https://stg.imepay.com.np:7979
 *   Live host: https://payment.imepay.com.np:7979
 *
 * Merchant registration: https://imepay.com.np
 */
class IMEPay {

    const HOST_TEST = 'https://stg.imepay.com.np:7979';
    const HOST_LIVE = 'https://payment.imepay.com.np:7979';

    const PATH_TOKEN   = '/api/Web/GetToken';
    const PATH_PROCESS = '/api/Web/Process';
    const PATH_CONFIRM = '/api/Web/Confirm';  // For confirmation after delivery

    private string $merchant_code;
    private string $merchant_name;
    private string $module;           // Module code from IME Pay
    private string $username;         // Merchant username
    private string $password;         // Merchant password
    private bool   $test_mode;

    public function __construct(
        string $merchant_code,
        string $merchant_name,
        string $module,
        string $username,
        string $password,
        bool   $test_mode = false
    ) {
        $this->merchant_code = $merchant_code;
        $this->merchant_name = $merchant_name;
        $this->module        = $module;
        $this->username      = $username;
        $this->password      = $password;
        $this->test_mode     = $test_mode;
    }

    private function baseUrl(): string {
        return $this->test_mode ? self::HOST_TEST : self::HOST_LIVE;
    }

    // ── Step 1: Get merchant token ────────────────────────────────────
    public function getToken(string $ref_id, float $amount): array {
        $url     = $this->baseUrl() . self::PATH_TOKEN;
        $payload = [
            'MerchantCode' => $this->merchant_code,
            'Amount'       => (string)$amount,
            'RefId'        => $ref_id,
        ];

        $response = $this->httpPost($url, json_encode($payload), true);
        $data     = json_decode($response, true) ?? [];

        return [
            'success' => ($data['ResponseCode'] ?? '') === '0',
            'token'   => $data['TokenId'] ?? '',
            'message' => $data['ResponseDescription'] ?? 'Unknown error',
            'raw'     => $data,
        ];
    }

    // ── Step 2: Build payment redirect URL ───────────────────────────
    public function buildPaymentUrl(string $token, string $ref_id, float $amount, string $return_url): string {
        $params = http_build_query([
            'merchantCode' => $this->merchant_code,
            'merchantName' => $this->merchant_name,
            'amount'       => number_format($amount, 2, '.', ''),
            'refId'        => $ref_id,
            'tokenId'      => $token,
            'module'       => $this->module,
            'returnUrl'    => $return_url,
        ]);

        return $this->baseUrl() . self::PATH_PROCESS . '?' . $params;
    }

    // ── Initiate: get token + redirect ───────────────────────────────
    public function initiatePayment(array $params): string {
        $ref_id     = $params['ref_id']     ?? ('IMEPY-' . uniqid());
        $amount     = (float)$params['amount'];
        $return_url = $params['return_url'];

        // Save ref for verification
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['imepay_ref']    = $ref_id;
        $_SESSION['imepay_amount'] = $amount;

        $token_result = $this->getToken($ref_id, $amount);
        if (!$token_result['success']) {
            return '<p style="color:red">IME Pay token error: '
                . htmlspecialchars($token_result['message']) . '</p>';
        }

        $pay_url = $this->buildPaymentUrl($token_result['token'], $ref_id, $amount, $return_url);

        return '<script>window.location.href=' . json_encode($pay_url) . ';</script>'
             . '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($pay_url) . '">'
             . '<p>Redirecting to IME Pay...</p>';
    }

    // ── Delivery service webhook handler (IME Pay calls this) ─────────
    // IME Pay POSTs these params to your ipn/delivery URL:
    //   RefId, TokenId, TransactionId, Msisdn, Amount, ResponseCode, ResponseDescription
    public function handleWebhook(array $post_data): array {
        $ref_id    = $post_data['RefId']              ?? '';
        $token_id  = $post_data['TokenId']            ?? '';
        $txn_id    = $post_data['TransactionId']      ?? '';
        $msisdn    = $post_data['Msisdn']             ?? '';
        $amount    = $post_data['Amount']             ?? '';
        $rc        = $post_data['ResponseCode']       ?? '';
        $desc      = $post_data['ResponseDescription'] ?? '';

        $success = ($rc === '0');

        // IME Pay expects a JSON response from your webhook
        // Return this from your webhook handler:
        return [
            'success'        => $success,
            'ref_id'         => $ref_id,
            'transaction_id' => $txn_id,
            'msisdn'         => $msisdn,
            'amount'         => $amount,
            'message'        => $desc,
            // Send this back to IME Pay:
            'webhook_response' => json_encode([
                'ResponseCode'        => '0',
                'ResponseDescription' => 'Success',
            ]),
        ];
    }

    // ── Verify payment from return URL ────────────────────────────────
    public function verifyFromReturn(array $params): array {
        $success = ($params['status'] ?? '') === 'success'
                || ($params['ResponseCode'] ?? '') === '0'
                || ($params['rc'] ?? '') === '0';

        return [
            'status'  => $success ? 'COMPLETE' : 'FAILED',
            'ref_id'  => $params['refId'] ?? $params['RefId'] ?? '',
            'txn_id'  => $params['transactionId'] ?? $params['TransactionId'] ?? '',
            'amount'  => $params['amount'] ?? $params['Amount'] ?? '',
            'message' => $success ? 'Payment successful' : 'Payment failed or cancelled',
            'raw'     => $params,
        ];
    }

    // ── HTTP helper with Basic Auth ────────────────────────────────────
    private function httpPost(string $url, string $body, bool $use_auth = false): string {
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($use_auth) {
            $headers[] = 'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password);
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_SSL_VERIFYPEER => !$this->test_mode,
            ]);
            $r = curl_exec($ch); curl_close($ch); return $r ?: '{}';
        }

        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", $headers),
            'content' => $body,
            'timeout' => 20,
        ]]);
        return @file_get_contents($url, false, $ctx) ?: '{}';
    }
}
