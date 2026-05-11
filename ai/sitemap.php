<?php
/**
 * Dynamic XML Sitemap
 * URL: https://yourdomain.com/yuga/sitemap.php
 * Referenced in robots.txt automatically after install.
 */
define('YUGA_ROOT', __DIR__);
$config = file_exists(YUGA_ROOT . '/config.php') ? require YUGA_ROOT . '/config.php' : [];

$proto    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$site_url = $config['site_url'] ?? ($proto . '://' . $_SERVER['HTTP_HOST']);
$base     = rtrim($site_url, '/');
$portal   = $base . '/portal/';
$now      = date('Y-m-d');

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex'); // sitemap itself should not be indexed as a page

$urls = [
    // Portal public pages
    ['loc' => $portal,                       'priority' => '1.0', 'changefreq' => 'weekly'],
    ['loc' => $portal . '?page=pricing',     'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => $portal . '?page=docs',        'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => $portal . '?page=signup',      'priority' => '0.7', 'changefreq' => 'monthly'],
    // API endpoint (for AI crawlers that index APIs)
    ['loc' => $base . '/api/',               'priority' => '0.5', 'changefreq' => 'monthly'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
echo '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n";
echo '          http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

foreach ($urls as $u) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($u['loc']) . "</loc>\n";
    echo "    <lastmod>{$now}</lastmod>\n";
    echo "    <changefreq>{$u['changefreq']}</changefreq>\n";
    echo "    <priority>{$u['priority']}</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
