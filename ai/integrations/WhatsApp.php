<?php
/**
 * WhatsApp Integration via Twilio
 *
 * Setup:
 *  1. twilio.com → sign up → get Account SID + Auth Token
 *  2. Activate WhatsApp Sandbox (or apply for a business number)
 *  3. Set webhook URL in Twilio console:
 *     https://yoursite.com/yuga/integrations/whatsapp_hook.php
 *  4. Add to config.php:
 *     'whatsapp' => [
 *         'account_sid' => 'ACxxx',
 *         'auth_token'  => '...',
 *         'from_number' => 'whatsapp:+14155238886',  // Twilio sandbox number
 *         'model'       => 'default',
 *     ]
 */
class WhatsApp {

    private string $account_sid;
    private string $auth_token;
    private string $from_number;
    private string $api;

    public function __construct(string $account_sid, string $auth_token, string $from_number) {
        $this->account_sid = $account_sid;
        $this->auth_token  = $auth_token;
        $this->from_number = $from_number;
        $this->api         = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";
    }

    // ── Send a WhatsApp message ───────────────────────────────────────────
    public function send(string $to, string $message): bool {
        // Ensure whatsapp: prefix
        if (!str_starts_with($to, 'whatsapp:')) $to = 'whatsapp:' . $to;

        $ch = curl_init($this->api);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_USERPWD        => "{$this->account_sid}:{$this->auth_token}",
            CURLOPT_POSTFIELDS     => http_build_query([
                'From' => $this->from_number,
                'To'   => $to,
                'Body' => $message,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 300;
    }

    // ── Parse incoming Twilio webhook ────────────────────────────────────
    public static function parseIncoming(): ?array {
        if (empty($_POST['Body'])) return null;
        return [
            'from'    => $_POST['From']       ?? '',
            'to'      => $_POST['To']         ?? '',
            'body'    => trim($_POST['Body']  ?? ''),
            'msg_sid' => $_POST['MessageSid'] ?? '',
        ];
    }

    // ── Verify Twilio request signature ──────────────────────────────────
    public static function verifySignature(string $auth_token, string $url, array $params, string $signature): bool {
        ksort($params);
        $str = $url . implode('', array_map(fn($k, $v) => $k . $v, array_keys($params), $params));
        $expected = base64_encode(hash_hmac('sha1', $str, $auth_token, true));
        return hash_equals($expected, $signature);
    }
}
