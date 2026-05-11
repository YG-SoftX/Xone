<?php
/**
 * Telegram webhook endpoint
 * URL: https://yoursite.com/yuga/integrations/telegram_hook.php
 */
define('YUGA_ROOT', dirname(__DIR__));
require_once YUGA_ROOT . '/integrations/Telegram.php';
require_once YUGA_ROOT . '/core/YugaLM.php';
require_once YUGA_ROOT . '/core/ModelStore.php';
require_once YUGA_ROOT . '/core/Tokenizer.php';
require_once YUGA_ROOT . '/core/Transformer.php';
require_once YUGA_ROOT . '/core/Retriever.php';
require_once YUGA_ROOT . '/core/Brain.php';
require_once YUGA_ROOT . '/core/Memory.php';

$config = file_exists(YUGA_ROOT . '/config.php') ? require YUGA_ROOT . '/config.php' : [];
$tg_cfg = $config['telegram'] ?? [];

if (empty($tg_cfg['token'])) { http_response_code(403); exit; }

$raw    = file_get_contents('php://input');
$update = Telegram::parseUpdate($raw);

if (!$update || !$update['text']) { http_response_code(200); exit; }

$bot     = new Telegram($tg_cfg['token']);
$model   = $tg_cfg['model'] ?? ($config['default_model'] ?? 'default');
$store   = new ModelStore(YUGA_ROOT . '/data');
$brain   = new Brain($model, $store);
$memory  = new Memory(YUGA_ROOT . '/data');

$chat_id   = $update['chat_id'];
$text      = trim($update['text']);
$session   = 'tg_' . $chat_id;

// /start command
if ($text === '/start') {
    $name = $tg_cfg['assistant_name'] ?? ($config['assistant_name'] ?? 'Yuga');
    $bot->send($chat_id, "Hello {$update['name']}! I'm <b>{$name}</b>. Ask me anything.");
    http_response_code(200);
    exit;
}

// /clear command
if ($text === '/clear') {
    $memory->deleteSession($session);
    $bot->send($chat_id, 'Conversation cleared.');
    http_response_code(200);
    exit;
}

// Answer via Brain
$bot->typing($chat_id);

try {
    $result = $brain->chat($text, $session, $memory);
    $reply  = $result['answer'] ?? 'Sorry, I could not find an answer.';
} catch (Exception $e) {
    $reply = 'Sorry, something went wrong.';
}

$bot->send($chat_id, htmlspecialchars($reply, ENT_QUOTES));
http_response_code(200);
