<?php
/**
 * WhatsApp webhook endpoint (Twilio)
 * URL: https://yoursite.com/yuga/integrations/whatsapp_hook.php
 */
define('YUGA_ROOT', dirname(__DIR__));
require_once YUGA_ROOT . '/integrations/WhatsApp.php';
require_once YUGA_ROOT . '/core/YugaLM.php';
require_once YUGA_ROOT . '/core/ModelStore.php';
require_once YUGA_ROOT . '/core/Tokenizer.php';
require_once YUGA_ROOT . '/core/Transformer.php';
require_once YUGA_ROOT . '/core/Retriever.php';
require_once YUGA_ROOT . '/core/Brain.php';
require_once YUGA_ROOT . '/core/Memory.php';

header('Content-Type: text/xml');

$config = file_exists(YUGA_ROOT . '/config.php') ? require YUGA_ROOT . '/config.php' : [];
$wa_cfg = $config['whatsapp'] ?? [];

if (empty($wa_cfg['account_sid'])) {
    echo '<Response></Response>'; exit;
}

// Optional: verify Twilio signature
$sig = $_SERVER['HTTP_X_TWILIO_SIGNATURE'] ?? '';
$url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
if ($sig && !WhatsApp::verifySignature($wa_cfg['auth_token'], $url, $_POST, $sig)) {
    echo '<Response></Response>'; exit;
}

$msg = WhatsApp::parseIncoming();
if (!$msg) { echo '<Response></Response>'; exit; }

$text    = $msg['body'];
$from    = $msg['from'];
$session = 'wa_' . preg_replace('/[^0-9]/', '', $from);

// Commands
if (strtolower($text) === 'clear') {
    $mem = new Memory(YUGA_ROOT . '/data');
    $mem->deleteSession($session);
    $reply = 'Conversation cleared. Start fresh!';
} elseif (strtolower($text) === 'help') {
    $name  = $config['assistant_name'] ?? 'Yuga';
    $reply = "$name — WhatsApp AI\n\nJust type your question.\nSend *clear* to reset conversation.";
} else {
    $model  = $wa_cfg['model'] ?? ($config['default_model'] ?? 'default');
    $store  = new ModelStore(YUGA_ROOT . '/data');
    $brain  = new Brain($model, $store);
    $memory = new Memory(YUGA_ROOT . '/data');

    try {
        $result = $brain->chat($text, $session, $memory);
        $reply  = $result['answer'] ?? 'Sorry, I could not find an answer.';
    } catch (Exception $e) {
        $reply = 'Sorry, something went wrong. Try again.';
    }
}

// TwiML response
$safe = htmlspecialchars($reply, ENT_XML1);
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response><Message>{$safe}</Message></Response>";
