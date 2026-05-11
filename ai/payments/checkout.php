<?php
/**
 * Yuga Checkout — Nepali Payment Gateway Selection
 * yoursite.com/yuga/payments/checkout.php?plan=starter&sub_id=sub_xxx
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

session_start();
$config = file_exists(YUGA_ROOT.'/config.php') ? require YUGA_ROOT.'/config.php' : [];
$store  = new APIKeyManager(YUGA_ROOT . '/data');
$pm     = new PaymentManager(YUGA_ROOT . '/data', $store, $config);

// CSRF protection
if (empty($_SESSION['checkout_csrf'])) {
    $_SESSION['checkout_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['checkout_csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['_csrf'] ?? '') !== $csrf) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

$plan_id   = $_GET['plan']   ?? $_POST['plan']   ?? 'starter';
$sub_id    = $_GET['sub_id'] ?? $_POST['sub_id'] ?? '';
$email     = $_GET['email']  ?? $_POST['email']  ?? '';
$gateway   = $_POST['gateway'] ?? '';
$plans     = Plans::all();
$plan      = $plans[$plan_id] ?? $plans['starter'];

$base_url   = (isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'];
$self_url   = $base_url . $_SERVER['SCRIPT_NAME'];
$return_url = $base_url . str_replace('checkout.php','callback.php',$_SERVER['SCRIPT_NAME']);

// Price in NPR (approximate: 1 USD ≈ 133 NPR as of 2024/2025)
$usd_price  = $plan['price_month'];
$npr_rate   = (float)($config['usd_to_npr_rate'] ?? 133.5);
$npr_price  = round($usd_price * $npr_rate, 2);
// Minimum NPR for gateways is typically Rs 1
if ($npr_price < 1) $npr_price = 1.0;

$error = '';

// ── Handle gateway selection + initiate payment ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $gateway) {
    // Resolve subscriber
    $sub = null;
    if ($sub_id) {
        $sub = $store->getSubscriber($sub_id);
    } elseif ($email) {
        $sub = $store->getSubscriberByEmail($email);
        if (!$sub) {
            // Auto-create subscriber
            $name = $_POST['name'] ?? explode('@', $email)[0];
            try {
                $sub = $store->createSubscriber($name, $email, $plan_id);
                $store->createKey($sub['id'], 'default');
            } catch (Exception $e) {
                $error = 'Could not create account: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Please enter your email address.';
    }

    if (!$error && $sub) {
        // Create pending transaction
        $txn_id = $pm->createTransaction($sub['id'], $gateway, $npr_price, $plan_id);
        $_SESSION['yuga_txn_id']  = $txn_id;
        $_SESSION['yuga_sub_id']  = $sub['id'];
        $_SESSION['yuga_plan']    = $plan_id;

        $callback = $return_url . '?gateway=' . $gateway . '&txn=' . $txn_id;

        // Initiate gateway payment
        if ($gateway === 'esewa') {
            $gw   = $pm->esewa();
            $form = $gw->initiatePayment([
                'amount'           => $npr_price,
                'total_amount'     => $npr_price,
                'tax_amount'       => 0,
                'transaction_uuid' => $txn_id,
                'success_url'      => $callback . '&status=success',
                'failure_url'      => $callback . '&status=failed',
            ]);
            echo $form; exit;

        } elseif ($gateway === 'fonepay') {
            $gw   = $pm->fonepay();
            $html = $gw->initiatePayment([
                'prn'        => $txn_id,
                'amount'     => $npr_price,
                'return_url' => $callback,
                'remarks'    => 'Yuga ' . ucfirst($plan_id) . ' plan',
                'remarks2'   => $sub['email'],
            ]);
            echo $html; exit;

        } elseif ($gateway === 'imepay') {
            $gw   = $pm->imepay();
            $html = $gw->initiatePayment([
                'ref_id'     => $txn_id,
                'amount'     => $npr_price,
                'return_url' => $callback,
            ]);
            echo $html; exit;

        } elseif ($gateway === 'stripe') {
            $gw     = $pm->stripe();
            $result = $gw->createCheckoutSession([
                'amount_usd'  => $usd_price,
                'plan_name'   => 'Yuga ' . $plan['name'] . ' Plan',
                'plan'        => $plan_id,
                'txn_id'      => $txn_id,
                'sub_id'      => $sub['id'],
                'email'       => $sub['email'],
                'success_url' => $return_url . '?gateway=stripe&txn=' . $txn_id,
                'cancel_url'  => $self_url . '?plan=' . $plan_id . '&sub_id=' . $sub['id'],
            ]);
            if (isset($result['error'])) { $error = $result['error']; }
            else { header('Location: ' . $result['url']); exit; }

        } elseif ($gateway === 'paypal') {
            $gw     = $pm->paypal();
            $result = $gw->createOrder([
                'amount_usd'  => $usd_price,
                'plan_name'   => 'Yuga ' . $plan['name'] . ' Plan',
                'txn_id'      => $txn_id,
                'return_url'  => $return_url . '?gateway=paypal&txn=' . $txn_id,
                'cancel_url'  => $self_url . '?plan=' . $plan_id . '&sub_id=' . $sub['id'],
            ]);
            if (isset($result['error'])) { $error = $result['error']; }
            else { header('Location: ' . $result['approval_url']); exit; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Checkout — Yuga <?= htmlspecialchars($plan['name']) ?> Plan</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,sans-serif;background:#0a0f1e;color:#e2e8f0;min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:40px 16px}
.container{width:100%;max-width:480px}
.logo{text-align:center;margin-bottom:32px}
.logo-mark{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;background:#6366f1;border-radius:12px;font-size:20px;font-weight:800;color:#fff;margin-bottom:10px}
.logo-name{font-size:20px;font-weight:700;color:#e2e8f0}
.plan-card{background:#0d1526;border:1px solid #1e293b;border-radius:16px;padding:22px;margin-bottom:20px}
.plan-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
.plan-name{font-size:18px;font-weight:700}
.plan-price-npm{text-align:right}
.plan-price-usd{font-size:12px;color:#64748b}
.plan-price-npr{font-size:24px;font-weight:700;color:#a5b4fc}
.plan-features{display:flex;flex-direction:column;gap:6px}
.plan-feature{font-size:13px;color:#94a3b8;display:flex;align-items:center;gap:7px}
.plan-feature::before{content:'';width:5px;height:5px;border-radius:50%;background:#10b981;flex-shrink:0}
.section{margin-bottom:20px}
label{display:block;font-size:12px;color:#64748b;font-weight:500;margin-bottom:6px}
input[type=text],input[type=email]{width:100%;background:#111827;border:1px solid #334155;border-radius:8px;padding:11px 14px;color:#e2e8f0;font-size:14px;outline:none;transition:.15s}
input:focus{border-color:#6366f1}
.gateway-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.gw-option{border:1.5px solid #1e293b;border-radius:12px;padding:16px 10px;cursor:pointer;text-align:center;transition:.15s;position:relative}
.gw-option:hover{border-color:#334155;background:rgba(255,255,255,.03)}
.gw-option.selected{border-color:#6366f1;background:rgba(99,102,241,.08)}
.gw-option input{position:absolute;opacity:0;pointer-events:none}
.gw-logo{font-size:28px;margin-bottom:8px}
.gw-esewa .gw-logo{color:#60B527}
.gw-fonepay .gw-logo{color:#D62128}
.gw-imepay .gw-logo{color:#F26522}
.gw-name{font-size:12px;font-weight:600;color:#e2e8f0}
.gw-desc{font-size:10px;color:#475569;margin-top:2px}
.btn-pay{width:100%;background:#6366f1;color:#fff;border:none;border-radius:10px;padding:14px;font-size:15px;font-weight:600;cursor:pointer;transition:.15s;margin-top:16px}
.btn-pay:hover{background:#4f46e5}
.btn-pay:disabled{background:#475569;cursor:not-allowed}
.error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:12px 16px;font-size:13px;color:#fca5a5;margin-bottom:16px}
.security{text-align:center;font-size:11px;color:#334155;margin-top:16px}
.divider{text-align:center;color:#334155;font-size:12px;margin:16px 0;position:relative}
.divider::before,.divider::after{content:'';position:absolute;top:50%;width:42%;height:1px;background:#1e293b}
.divider::before{left:0}.divider::after{right:0}
.rate-note{font-size:11px;color:#475569;text-align:center;margin-top:8px}
</style>
</head>
<body>
<div class="container">

  <div class="logo">
    <div class="logo-mark">Y</div>
    <div class="logo-name">Yuga</div>
  </div>

  <!-- Plan summary -->
  <div class="plan-card">
    <div class="plan-header">
      <div class="plan-name"><?= htmlspecialchars($plan['name']) ?> Plan</div>
      <div class="plan-price-npm">
        <div class="plan-price-npr">Rs <?= number_format($npr_price, 2) ?></div>
        <div class="plan-price-usd">$<?= $usd_price ?>/mo</div>
      </div>
    </div>
    <div class="plan-features">
      <?php foreach ($plan['features'] as $f): ?>
      <div class="plan-feature"><?= htmlspecialchars($f) ?></div>
      <?php endforeach; ?>
    </div>
    <div class="rate-note">Exchange rate: 1 USD = Rs <?= $npr_rate ?></div>
  </div>

  <?php if ($error): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" id="checkout-form">
    <input type="hidden" name="plan" value="<?= htmlspecialchars($plan_id) ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
    <?php if ($sub_id): ?>
    <input type="hidden" name="sub_id" value="<?= htmlspecialchars($sub_id) ?>">
    <?php else: ?>
    <div class="section">
      <label>Your email address</label>
      <input type="email" name="email" placeholder="you@example.com"
             value="<?= htmlspecialchars($email) ?>" required>
    </div>
    <div class="section">
      <label>Your name</label>
      <input type="text" name="name" placeholder="Full name" required>
    </div>
    <?php endif; ?>

    <div class="section">
      <label>Choose payment method</label>
      <div class="gateway-grid">

        <label class="gw-option gw-esewa" id="opt-esewa" onclick="selectGW('esewa')">
          <input type="radio" name="gateway" value="esewa" required>
          <div class="gw-logo">&#128995;</div>
          <div class="gw-name">eSewa</div>
          <div class="gw-desc">Digital wallet</div>
        </label>

        <label class="gw-option gw-fonepay" id="opt-fonepay" onclick="selectGW('fonepay')">
          <input type="radio" name="gateway" value="fonepay">
          <div class="gw-logo">&#128997;</div>
          <div class="gw-name">FonePay</div>
          <div class="gw-desc">Bank QR payment</div>
        </label>

        <label class="gw-option gw-imepay" id="opt-imepay" onclick="selectGW('imepay')">
          <input type="radio" name="gateway" value="imepay">
          <div class="gw-logo">&#128992;</div>
          <div class="gw-name">IME Pay</div>
          <div class="gw-desc">Mobile wallet</div>
        </label>

      </div>

      <?php if (!empty($config['stripe']['secret_key']) || !empty($config['paypal']['client_id'])): ?>
      <div style="text-align:center;color:#334155;font-size:12px;margin:14px 0 10px;position:relative">
        <span style="background:#0a0f1e;padding:0 10px;position:relative;z-index:1">International payments</span>
        <div style="position:absolute;top:50%;left:0;right:0;height:1px;background:#1e293b"></div>
      </div>
      <div class="gateway-grid">
        <?php if (!empty($config['stripe']['secret_key'])): ?>
        <label class="gw-option" id="opt-stripe" onclick="selectGW('stripe')">
          <input type="radio" name="gateway" value="stripe">
          <div class="gw-logo" style="color:#635bff">&#9889;</div>
          <div class="gw-name">Stripe</div>
          <div class="gw-desc">Card / Apple Pay</div>
        </label>
        <?php endif; ?>
        <?php if (!empty($config['paypal']['client_id'])): ?>
        <label class="gw-option" id="opt-paypal" onclick="selectGW('paypal')">
          <input type="radio" name="gateway" value="paypal">
          <div class="gw-logo" style="color:#003087">&#128181;</div>
          <div class="gw-name">PayPal</div>
          <div class="gw-desc">PayPal / Card</div>
        </label>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </div>

    <button type="submit" class="btn-pay" id="pay-btn" disabled>
      Select a payment method
    </button>
  </form>

  <div class="security">
    &#128274; Secure payment &bull; Your data is protected &bull; Powered by Yuga
  </div>

</div>
<script>
function selectGW(gw) {
  document.querySelectorAll('.gw-option').forEach(o => o.classList.remove('selected'));
  document.getElementById('opt-'+gw).classList.add('selected');
  document.querySelector('input[value="'+gw+'"]').checked = true;
  const btn = document.getElementById('pay-btn');
  const names = {esewa:'eSewa',fonepay:'FonePay',imepay:'IME Pay'};
  btn.textContent = 'Pay Rs <?= number_format($npr_price,2) ?> with ' + names[gw];
  btn.disabled = false;
}
</script>
</body>
</html>
