<?php
/**
 * YGXONE Global Intelligence Aggregator
 * Unified search across Search, Mail, Drive, and DocX
 */
header('Content-Type: application/json');
define('YUGA_ROOT', dirname(__DIR__));

$query = trim($_GET['q'] ?? '');
if (strlen($query) < 2) {
    echo json_encode(['ok' => false, 'error' => 'Query too short']);
    exit;
}

$results = [
    'web'   => [],
    'mail'  => [],
    'drive' => [],
    'docx'  => [],
];

$root = dirname(YUGA_ROOT);
$limit = 5;

// 1. 🌐 Web Search (Local Index)
try {
    require_once YUGA_ROOT . '/core/SearchIndex.php';
    $idx = new SearchIndex(YUGA_ROOT . '/data');
    $webRows = $idx->search($idx->tokenize($query), $limit);
    foreach($webRows as $r) {
        $results['web'][] = ['title' => $r['title'], 'url' => $r['url'], 'snippet' => mb_substr(strip_tags($r['content'] ?? ''), 0, 150)];
    }
} catch (Exception $e) {}

// 2. 📧 Mail Search
try {
    $mailDb = new PDO('sqlite:' . $root . '/YG Mail/database/database.sqlite');
    $stmt = $mailDb->prepare("SELECT id, subject, content, sender_name FROM mails WHERE subject LIKE ? OR content LIKE ? LIMIT ?");
    $stmt->execute(["%$query%", "%$query%", $limit]);
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $results['mail'][] = ['title' => $r['subject'], 'sender' => $r['sender_name'], 'snippet' => mb_substr(strip_tags($r['content']), 0, 150)];
    }
} catch (Exception $e) {}

// 3. 📂 Drive Search
try {
    $driveDb = new PDO('sqlite:' . $root . '/YG Drive/database/database.sqlite');
    $stmt = $driveDb->prepare("SELECT id, name, mime_type FROM drive_files WHERE name LIKE ? LIMIT ?");
    $stmt->execute(["%$query%", $limit]);
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $results['drive'][] = ['title' => $r['name'], 'type' => $r['mime_type']];
    }
} catch (Exception $e) {}

// 4. 📄 DocX Search
try {
    $docxDb = new PDO('sqlite:' . $root . '/YG DocX/database/database.sqlite');
    $stmt = $docxDb->prepare("SELECT id, title, content FROM documents WHERE title LIKE ? OR content LIKE ? LIMIT ?");
    $stmt->execute(["%$query%", "%$query%", $limit]);
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $results['docx'][] = ['title' => $r['title'], 'snippet' => mb_substr(strip_tags($r['content'] ?? ''), 0, 150)];
    }
} catch (Exception $e) {}

echo json_encode(['ok' => true, 'query' => $query, 'results' => $results]);
