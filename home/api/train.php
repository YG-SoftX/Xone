<?php
/**
 * YG AI Training Bridge
 * Only accepts requests from same server (127.0.0.1 / ::1).
 */

// Only allow same-server calls
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteIp, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    echo json_encode(['status' => 'forbidden']);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'method_not_allowed']);
    exit;
}

$config = require __DIR__ . '/../config.php';

try {
    $db = new PDO(
        'mysql:host=' . $config['DB_HOST'] . ';dbname=' . $config['DB_DATABASE'] . ';charset=utf8mb4',
        $config['DB_USERNAME'],
        $config['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $db->exec("CREATE TABLE IF NOT EXISTS ai_training_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        query TEXT NOT NULL,
        context VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $query   = isset($data['query'])   ? substr(strip_tags((string) $data['query']),   0, 500) : '';
    $context = isset($data['context']) ? substr(strip_tags((string) $data['context']), 0, 100) : 'search_engine';

    if ($query !== '') {
        $stmt = $db->prepare('INSERT INTO ai_training_queue (query, context) VALUES (?, ?)');
        $stmt->execute([$query, $context]);
    }

    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    error_log('AI Training Bridge Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error']);
}
