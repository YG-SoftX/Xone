<?php
/**
 * Payment Callback Handler
 * yoursite.com/yuga/payments/callback.php?gateway=esewa&txn=txn_xxx
 *
 * All three gateways redirect here after payment.
 * Also handles IME Pay webhook (IPN / delivery service).
 */
define('YUGA_ROOT', dirname(__DIR__));
require_once YUGA_ROOT . '/subscriptions/Plans.php';
require_once YUGA_ROOT . '/subscriptions/APIKeyManager.php';
require_once YUGA_ROOT . '/payments/PaymentManager.php';
require_once YUGA_ROOT . '/payments/gateways/ESewa.php';
require_once YUGA_ROOT . '/payments/gateways/FonePay.php';
require_once YUGA_ROOT . '/payments/gateways/IMEPay.php';
require_once YUGA_ROOT . '/payments/gateways/Stripe.php';
require_once YUGA_ROOT . '/payments/gateways/PayPal.php';
require_once YUGA_ROOT . '/core/Mailer.php';

session_start();
$config = file_exists(YUGA_ROOT.'/config.php') ? require YUGA_ROOT.'/config.php' : [];
$store  = new APIKeyManager(YUGA_ROOT . '/data');
$pm     = new PaymentManager(YUGA_ROOT . '/data', $store, $config);

$gateway = $_GET['gateway'] ?? $_POST['gateway'] ?? '';
$txn_id  = $_GET['txn']     ?? $_SESSION['yuga_txn_id'] ?? '';
$txn     = $txn_id ? $pm->getTransaction($txn_id) : null;

$success = false;
$message = '';
$result  = [];

// ── IME Pay webhook (IPN) ─────────────────────────────────────────────
// IME Pay POSTs to this URL directly — respond with JSON immediately
if ($gateway === 'imepay' && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['txn'])) {
    $gw      = $pm->imepay();
    $result  = $gw->handleWebhook($_POST);
    // Find txn by ref_id
    if ($result['ref_id'] && !$txn) {
        $txn_id = $result['ref_id'];
        $txn    = $pm->getTransaction($txn_id);
    }
    if ($result['success'] && $txn) {
        $pm->completeTransaction($txn_id, $result['transaction_id'], $result['transaction_id']);
    } elseif ($txn) {
        $pm->failTransaction($txn_id, $result['message']);
    }
    header('Content-Type: application/json');
    echo $result['webhook_response'];
    exit;
}

// ── eSewa callback ────────────────────────────────────────────────────
if ($gateway === 'esewa') {
    $gw     = $pm->esewa();
    $result = $gw->verifyPayment($_GET);
    if (($result['status'] ?? '') === 'COMPLETE') {
        $pm->completeTransaction($txn_id, $result['ref_id'] ?? '', $result['transaction_uuid'] ?? '');
        $success = true;
        $message = 'Payment verified successfully via eSewa.';
    } else {
        $pm->failTransaction($txn_id, $result['message'] ?? 'eSewa verification failed');
        $message = 'Payment failed or could not be verified. Please try again.';
    }
}

// ── FonePay callback ──────────────────────────────────────────────────
elseif ($gateway === 'fonepay') {
    $gw     = $pm->fonepay();
    $result = $gw->verifyPayment($_GET);
    if ($result['status'] === 'COMPLETE') {
        $pm->completeTransaction($txn_id, $result['uid'] ?? '', $result['bid'] ?? '');
        $success = true;
        $message = 'Payment verified successfully via FonePay.';
    } else {
        $pm->failTransaction($txn_id, $result['message'] ?? 'FonePay verification failed');
        $message = $result['message'] ?? 'Payment failed. Please try again.';
    }
}

// ── IME Pay return URL ────────────────────────────────────────────────
elseif ($gateway === 'imepay') {
    $gw     = $pm->imepay();
    $result = $gw->verifyFromReturn($_GET);
    if ($result['status'] === 'COMPLETE') {
        $pm->completeTransaction($txn_id, $result['txn_id'] ?? '', $result['ref_id'] ?? '');
        $success = true;
        $message = 'Payment verified successfully via IME Pay.';
    } else {
        $pm->failTransaction($txn_id, $result['message'] ?? 'IME Pay verification failed');
        $message = $result['message'] ?? 'Payment failed. Please try again.';
    }
}

// ── Stripe callback ───────────────────────────────────────────────────
elseif ($gateway === 'stripe') {
    $session_id = $_GET['session_id'] ?? '';
    if ($session_id) {
        $gw     = $pm->stripe();
        $result = $gw->retrieveSession($session_id);
        if ($result['status'] === 'COMPLETE') {
            $pm->completeTransaction($txn_id, $session_id, $result['payment_intent'] ?? '');
            $success = true;
            $message = 'Payment verified successfully via Stripe.';
        } else {
            $pm->failTransaction($txn_id, 'Stripe session not paid');
            $message = 'Payment not completed. Please try again.';
        }
    } else {
        $pm->failTransaction($txn_id, 'No Stripe session_id returned');
        $message = 'Stripe payment cancelled.';
    }
}

// ── PayPal callback ───────────────────────────────────────────────────
elseif ($gateway === 'paypal') {
    $order_id = $_GET['token'] ?? ''; // PayPal returns token=ORDER_ID
    if ($order_id) {
        $gw     = $pm->paypal();
        $result = $gw->captureOrder($order_id);
        if ($result['status'] === 'COMPLETE') {
            $pm->completeTransaction($txn_id, $order_id, $result['capture_id'] ?? '');
            $success = true;
            $message = 'Payment verified successfully via PayPal.';
        } else {
            $pm->failTransaction($txn_id, $result['message'] ?? 'PayPal capture failed');
            $message = $result['message'] ?? 'PayPal payment failed. Please try again.';
        }
    } else {
        $pm->failTransaction($txn_id, 'PayPal payment cancelled');
        $message = 'PayPal payment was cancelled.';
    }
}

// Get subscriber details for the receipt
$sub = null;
if ($txn && $txn['sub_id']) {
    $sub = $store->getSubscriber($txn['sub_id']);
}

// ── Send receipt email ────────────────────────────────────────────────
if ($success && $sub && $txn) {
    try {
        $mailer = new Mailer($config);
        $mailer->sendReceipt($sub['email'], $sub['name'], array_merge($txn, ['gateway' => $gateway]));
    } catch (Exception $e) { /* non-fatal */ }
}

// Get API keys if newly created
$keys = ($sub && $success) ? $store->listKeys($sub['id']) : [];
$latest_key = null;
foreach ($keys as $k) {
    if ($k['status'] === 'active') { $latest_key = $k; break; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Payment <?= $success ? 'Successful' : 'Failed' ?> — Yuga</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0a0f1e;color:#e2e8f0;min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:40px 16px}
.container{width:100%;max-width:480px}
.result-card{background:#0d1526;border:1px solid;border-radius:16px;padding:32px;text-align:center}
.result-card.ok{border-color:rgba(16,185,129,.4)}
.result-card.fail{border-color:rgba(239,68,68,.3)}
.icon{font-size:52px;margin-bottom:16px}
h1{font-size:24px;font-weight:700;margin-bottom:8px}
.ok h1{color:#6ee7b7}.fail h1{color:#fca5a5}
.sub-msg{font-size:14px;color:#64748b;margin-bottom:24px;line-height:1.6}
.receipt{background:#050a14;border-radius:10px;padding:16px;text-align:left;margin-bottom:20px}
.receipt-row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid #1e293b;font-size:13px}
.receipt-row:last-child{border-bottom:none}
.receipt-label{color:#64748b}.receipt-value{color:#e2e8f0;font-weight:500}
.key-box{background:#050a14;border:1px solid rgba(20,184,166,.4);border-radius:10px;padding:14px;margin-bottom:20px}
.key-label{font-size:11px;color:#64748b;margin-bottom:6px;text-transform:uppercase;letter-spacing:.06em}
.key-value{font-family:monospace;font-size:13px;color:#14b8a6;word-break:break-all;margin-bottom:10px}
.key-warn{font-size:11px;color:#f59e0b}
.btn{display:inline-block;padding:12px 28px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;transition:.15s;border:none}
.btn-primary{background:#6366f1;color:#fff}.btn-primary:hover{background:#4f46e5}
.btn-ghost{background:transparent;border:1px solid #334155;color:#94a3b8;margin-left:8px}
.btn-ghost:hover{border-color:#6366f1;color:#a5b4fc}
.btn-copy{background:#1e293b;border:1px solid #334155;color:#14b8a6;padding:6px 14px;font-size:12px;border-radius:6px;cursor:pointer}
</style>
</head>
<body>
<div class="container">

<?php if ($success): ?>
  <div class="result-card ok">
    <div class="icon">✅</div>
    <h1>Payment successful!</h1>
    <div class="sub-msg">
      Your <?= htmlspecialchars($txn['plan'] ?? '') ?> plan is now active.
      <?= $sub ? 'Welcome, ' . htmlspecialchars($sub['name']) . '!' : '' ?>
    </div>

    <?php if ($txn): ?>
    <div class="receipt">
      <div class="receipt-row"><span class="receipt-label">Plan</span><span class="receipt-value"><?= ucfirst($txn['plan']) ?></span></div>
      <div class="receipt-row"><span class="receipt-label">Amount paid</span><span class="receipt-value">Rs <?= number_format($txn['amount'], 2) ?></span></div>
      <div class="receipt-row"><span class="receipt-label">Payment method</span><span class="receipt-value"><?= ucfirst($gateway) ?></span></div>
      <div class="receipt-row"><span class="receipt-label">Reference</span><span class="receipt-value" style="font-family:monospace;font-size:11px"><?= htmlspecialchars($txn['gateway_ref'] ?: $txn_id) ?></span></div>
      <div class="receipt-row"><span class="receipt-label">Date</span><span class="receipt-value"><?= date('d M Y H:i') ?></span></div>
    </div>
    <?php endif; ?>

    <?php if ($latest_key): ?>
    <div class="key-box">
      <div class="key-label">Your API key</div>
      <div class="key-value" id="api-key"><?= htmlspecialchars($latest_key['key_prefix']) ?></div>
      <div class="key-warn">If you need your full key, check your email or contact support.</div>
    </div>
    <?php endif; ?>

    <a href="../portal/" class="btn btn-primary">Go to developer portal</a>
    <a href="../admin/" class="btn btn-ghost">Admin dashboard</a>
  </div>

<?php else: ?>
  <div class="result-card fail">
    <div class="icon">❌</div>
    <h1>Payment failed</h1>
    <div class="sub-msg"><?= htmlspecialchars($message) ?></div>

    <?php if ($txn): ?>
    <div class="receipt">
      <div class="receipt-row"><span class="receipt-label">Transaction ID</span><span class="receipt-value" style="font-family:monospace;font-size:11px"><?= htmlspecialchars($txn_id) ?></span></div>
      <div class="receipt-row"><span class="receipt-label">Gateway</span><span class="receipt-value"><?= ucfirst($gateway) ?></span></div>
      <div class="receipt-row"><span class="receipt-label">Amount</span><span class="receipt-value">Rs <?= number_format($txn['amount'], 2) ?></span></div>
    </div>
    <?php endif; ?>

    <a href="checkout.php?plan=<?= urlencode($txn['plan'] ?? 'starter') ?>&sub_id=<?= urlencode($txn['sub_id'] ?? '') ?>"
       class="btn btn-primary">Try again</a>
    <a href="../portal/" class="btn btn-ghost">Back to portal</a>
  </div>
<?php endif; ?>

</div>
</body>
</html>
