<?php
/**
 * YG Search Autocomplete Suggestions
 * Returns JSON array of query suggestions.
 */
define('YUGA_ROOT', __DIR__);

header('Content-Type: application/json');
header('Cache-Control: public, max-age=60');
header('Access-Control-Allow-Origin: *');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$q = strtolower(substr($q, 0, 100));

// ── Pull suggestions from the search index ────────────────────────────────
$dbPath = YUGA_ROOT . '/data/yuga.db';
$suggestions = [];

if (file_exists($dbPath)) {
    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

        // Search page titles for matching suggestions
        $stmt = $pdo->prepare(
            'SELECT DISTINCT title FROM pages
             WHERE lower(title) LIKE ? AND status = 200
             ORDER BY inbound DESC
             LIMIT 8'
        );
        $stmt->execute([$q . '%']);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['title']) $suggestions[] = $row['title'];
        }

        // Also search descriptions
        if (count($suggestions) < 6) {
            $remaining = 8 - count($suggestions);
            $stmt2 = $pdo->prepare(
                'SELECT DISTINCT title FROM pages
                 WHERE lower(description) LIKE ? AND status = 200
                 ORDER BY inbound DESC
                 LIMIT ' . $remaining
            );
            $stmt2->execute(['%' . $q . '%']);
            $existing = array_flip($suggestions);
            while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                if ($row['title'] && !isset($existing[$row['title']])) {
                    $suggestions[] = $row['title'];
                }
            }
        }
    } catch (Exception $e) {
        // Silently fail
    }
}

// Append static suggestions for common queries when index is sparse
$staticSuggestions = [
    'what' => ['What is artificial intelligence?', 'What is YGXONE?', 'What is machine learning?'],
    'how'  => ['How does search work?', 'How to learn programming?', 'How to start a business?'],
    'best' => ['Best programming languages 2025', 'Best AI tools', 'Best places in Nepal'],
    'why'  => ['Why is the sky blue?', 'Why does search matter?'],
    'nepal' => ['Nepal tourism', 'Nepal economy', 'Nepal government'],
    'ai'   => ['AI news today', 'AI search engine', 'AI tools for business'],
];

foreach ($staticSuggestions as $key => $statics) {
    if (str_starts_with($q, $key)) {
        foreach ($statics as $s) {
            if (!in_array($s, $suggestions) && count($suggestions) < 8) {
                $suggestions[] = $s;
            }
        }
    }
}

echo json_encode(array_values(array_slice($suggestions, 0, 8)));
