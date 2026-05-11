<?php
/**
 * FonePay Payment Gateway
 *
 * Nepal's inter-bank QR payment network.
 * Redirect merchant with signed GET parameters.
 * Signature: HMAC-SHA512 of pipe-delimited fields.
 *
 * Test credentials:
 *   PID (Merchant Code) : NBQM
 *   Secret Key          : (provided by FonePay on merchant registration)
 *   Test URL            : https://dev-clientapi.fonepay.com/api/merchantRequest
 *   Test bank login     : any 10-digit number starting with 98
 *   Test OTP            : 1234
 *
 * Merchant signup: https://login.fonepay.com
 * Dev portal: https://developer.fonepay.com
 */
class FonePay {

    const URL_TEST = 'https://dev-clientapi.fonepay.com/api/merchantRequest';
    const URL_LIVE = 'https://clientapi.fonepay.com/api/merchantRequest';

    // Verification endpoint (POST)
    const VFY_TEST = 'https://dev-clientapi.fonepay.com/api/merchantRequest';
    const VFY_LIVE = 'https://clientapi.fonepay.com/api/merchantRequest';

    private string $merchant_id;   // PID
    private string $secret_key;    // Shared secret from FonePay
    private bool   $test_mode;

    public function __construct(string $merchant_id, string $secret_key, bool $test_mode = false) {
        $this->merchant_id = $merchant_id;
        $this->secret_key  = $secret_key;
        $this->test_mode   = $test_mode;
    }

    // ── Initiate payment — redirect to FonePay ────────────────────────
    public function initiatePayment(array $params): string {
        $prn    = $params['prn']        ?? ('FP-' . uniqid()); // Unique purchase reference
        $amount = number_format((float)$params['amount'], 2, '.', '');
        $return = $params['return_url'];
        $r1     = substr($params['remarks'] ?? 'Yuga subscription', 0, 50);
        $r2     = substr($params['remarks2'] ?? 'N/A', 0, 50);
        $date   = date('m/d/Y');

        // DV = HMAC-SHA512 of comma-separated values
        $message = implode(',', [
            $this->merchant_id,  // PID
            'P',                 // MD (Payment mode)
            $prn,                // PRN
            $amount,             // AMT
            'NPR',               // CRN
            $date,               // DT
            $r1,                 // R1
            $r2,                 // R2
            $return,             // RU
        ]);
        $dv = strtoupper(hash_hmac('sha512', $message, $this->secret_key));

        $query = http_build_query([
            'PID' => $this->merchant_id,
            'MD'  => 'P',
            'PRN' => $prn,
            'AMT' => $amount,
            'CRN' => 'NPR',
            'DT'  => $date,
            'R1'  => $r1,
            'R2'  => $r2,
            'RU'  => $return,
            'DV'  => $dv,
        ]);

        $url = ($this->test_mode ? self::URL_TEST : self::URL_LIVE) . '?' . $query;

        // Save PRN for verification
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['fonepay_prn']    = $prn;
        $_SESSION['fonepay_amount'] = $amount;

        // Return auto-redirect
        return '<script>window.location.href=' . json_encode($url) . ';</script>'
             . '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url) . '">'
             . '<p>Redirecting to FonePay...</p>';
    }

    // ── Verify callback from FonePay ──────────────────────────────────
    // FonePay sends: PRN, UID, BID, AMT, CRN, DT, R1, R2, RU, S (status), RC, DV
    public function verifyPayment(array $params): array {
        $prn = $params['PRN'] ?? '';
        $uid = $params['UID'] ?? '';
        $bid = $params['BID'] ?? '';
        $amt = $params['AMT'] ?? '';
        $crn = $params['CRN'] ?? 'NPR';
        $dt  = $params['DT']  ?? '';
        $r1  = $params['R1']  ?? '';
        $r2  = $params['R2']  ?? '';
        $ru  = $params['RU']  ?? '';
        $s   = $params['S']   ?? '';   // Y = success, N = failure
        $rc  = $params['RC']  ?? '';   // Response code
        $dv  = $params['DV']  ?? '';   // Their hash — we verify it

        // Reconstruct expected DV
        $message = implode(',', [
            $this->merchant_id, 'P', $prn, $amt, $crn, $dt, $r1, $r2, $ru, $uid, $bid, $rc, $s,
        ]);
        $expected = strtoupper(hash_hmac('sha512', $message, $this->secret_key));

        $valid = hash_equals($expected, strtoupper($dv));

        return [
            'status'  => ($s === 'Y' && $valid) ? 'COMPLETE' : 'FAILED',
            'valid'   => $valid,
            'prn'     => $prn,
            'uid'     => $uid,
            'bid'     => $bid,
            'amount'  => $amt,
            'message' => $s === 'Y' ? 'Payment successful' : ('Payment failed. Code: ' . $rc),
            'raw'     => $params,
        ];
    }
}
