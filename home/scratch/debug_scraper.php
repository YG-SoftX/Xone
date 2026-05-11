<?php
require __DIR__ . '/vendor/autoload.php';
$query = 'jeff bezos';
$url = 'https://en.wikipedia.org/w/api.php?' . http_build_query([
    'action' => 'query', 'list' => 'search', 'srsearch' => $query,
    'srlimit' => 3, 'format' => 'json', 'origin' => '*'
]);
$client = new \GuzzleHttp\Client(['timeout' => 10]);
try {
    $response = $client->get($url);
    $data = (string) $response->getBody();
    echo "Wikipedia Data: " . substr($data, 0, 500) . "\n";
} catch (\Exception $e) {
    echo "Wikipedia Error: " . $e->getMessage() . "\n";
}
