<?php
require 'vendor/autoload.php';

$query = 'jeff bezos';
$client = new \GuzzleHttp\Client(['timeout' => 5]);
$response = $client->get('https://lite.duckduckgo.com/lite/?' . http_build_query(['q' => $query]), [
    'headers' => ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36']
]);
$html = (string) $response->getBody();

echo "HTML Length: " . strlen($html) . "\n";
if (str_contains($html, 'result-link')) {
    echo "Found result-link\n";
} else {
    echo "NO result-link found\n";
}

preg_match_all(
    '/<a[^>]+href=["\']([^"\']+)["\'][^>]*class=["\']result-link["\'][^>]*>([^<]+)<\/a>.*?<td[^>]*class=["\']result-snippet["\'][^>]*>\s*(.*?)\s*<\/td>/si',
    $html,
    $matches,
    PREG_SET_ORDER
);

echo "Matches count: " . count($matches) . "\n";
foreach ($matches as $m) {
    echo "Title: " . $m[2] . "\n";
}
