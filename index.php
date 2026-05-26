<?php
/**
 * YGXONE - Root Redirector
 * This file handles the initial request and routes it to the Home module.
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// If we're at the root, include the home module's index
if ($uri === '/' || $uri === '/index.php') {
    require_once __DIR__ . '/home/public/index.php';
    exit;
}

// Otherwise, if the file doesn't exist in root, try to serve it from home/public
if (!file_exists(__DIR__ . $uri)) {
    // Check if it's a request for another module
    $parts = explode('/', ltrim($uri, '/'));
    $first_part = $parts[0];
    $modules = ['account', 'ai', 'appstore', 'calendar', 'chat', 'collect', 'console', 'contacts', 'developer', 'docx', 'drive', 'mail', 'master', 'notes', 'support', 'xcel'];

    if (!in_array($first_part, $modules)) {
        require_once __DIR__ . '/home/public/index.php';
        exit;
    }
}

// Fallback to let Apache handle it or show 404
return false;
