<?php
/**
 * Slack slash command endpoint
 * Handles: /yuga <question>
 * URL: https://yoursite.com/yuga/integrations/slack_hook.php
 */
define('YUGA_ROOT', dirname(__DIR__));
require_once YUGA_ROOT . '/integrations/Slack.php';
require_once YUGA_ROOT . '/core/YugaLM.php';
require_once YUGA_ROOT . '/core/ModelStore.php';
require_once YUGA_ROOT . '/core/Tokenizer.php';
require_once YUGA_ROOT . '/core/Transformer.php';
require_once YUGA_ROOT . '/core/Retriever.php';
require_once YUGA_ROOT . '/core/Brain.php';
require_once YUGA_ROOT . '/core/Memory.php';

header('Content-Type: application/json');

$config  = file_exists(YUGA_ROOT . '/config.php') ? require YUGA_ROOT . '/config.php' : [];
$sl_cfg  = $config['slack'] ?? [];

if (empty($sl_cfg['signing_secret'])) { http_response_code(403); exit; }

// Verify Slack signature
$body = file_get_contents('php://input');
$ts   = $_SERVER['HTTP_X_SLACK_REQUEST_TIMESTAMP'] ?? '';
$sig  = $_SERVER['HTTP_X_SLACK_SIGNATURE'] ?? '';

if (!Slack::verifySignature($sl_cfg['signing_secret'], $body, $ts, $sig)) {
    http_response_code(403);
    echo json_encode(['text' => 'Invalid signature.']);
    exit;
}

parse_str($body, $params);

$text      = trim($params['text'] ?? '');
$user_id   = $params['user_id']   ?? 'slack_user';
$user_name = $params['user_name'] ?? 'User';

if (!$text || $text === 'help') {
    echo json_encode([
        'response_type' => 'ephemeral',
        'text' => "*Yuga AI* — usage:\n`/yuga <your question>`\n`/yuga clear` — reset conversation",
    ]);
    exit;
}

if ($text === 'clear') {
    $mem = new Memory(YUGA_ROOT . '/data');
    $mem->deleteSession('slack_' . $user_id);
    echo json_encode(['response_type' => 'ephemeral', 'text' => 'Conversation cleared.']);
    exit;
}

// Answer
$model  = $sl_cfg['model'] ?? ($config['default_model'] ?? 'default');
$store  = new ModelStore(YUGA_ROOT . '/data');
$brain  = new Brain($model, $store);
$memory = new Memory(YUGA_ROOT . '/data');

try {
    $result = $brain->chat($text, 'slack_' . $user_id, $memory);
    $reply  = $result['answer'] ?? 'No answer found.';
} catch (Exception $e) {
    $reply = 'Error: ' . $e->getMessage();
}

echo json_encode([
    'response_type' => 'in_channel',
    'blocks' => [
        ['type' => 'section', 'text' => ['type' => 'mrkdwn',
            'text' => "*{$user_name} asked:* $text"]],
        ['type' => 'section', 'text' => ['type' => 'mrkdwn',
            'text' => ":brain: $reply"]],
    ],
]);
