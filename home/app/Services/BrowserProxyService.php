<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * BrowserProxyService — PHP/cURL proxy engine that fetches web pages,
 * rewrites resource URLs to route through the proxy, and injects
 * the YG agent script for AI-powered browsing.
 *
 * Designed for cPanel shared hosting — uses only PHP/cURL, no Node.js/Puppeteer.
 */
class BrowserProxyService
{
    /**
     * Base URL for the proxy endpoint.
     */
    private string $proxyBase;

    /**
     * Timeout for cURL requests in seconds.
     */
    private int $timeout;

    /**
     * User-Agent string to use for proxied requests.
     */
    private string $userAgent;

    public function __construct()
    {
        $this->proxyBase = rtrim(config('app.url', 'https://ygxone.com'), '/') . '/browse';
        $this->timeout   = (int) config('browser.proxy_timeout', 15);
        $this->userAgent = config('browser.user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36');
    }

    /**
     * Fetch a URL through the proxy and return the modified content.
     *
     * @return array{content: string, contentType: string, statusCode: int, url: string, title: string|null}
     */
    public function fetch(string $targetUrl, array $options = []): array
    {
        $targetUrl = $this->normalizeUrl($targetUrl);

        try {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL            => $targetUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_USERAGENT      => $this->userAgent,
                CURLOPT_HTTPHEADER     => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.9',
                    'Accept-Encoding: identity', // Don't accept gzip so we can process raw HTML
                    'Cache-Control: no-cache',
                    'Pragma: no-cache',
                ],
                CURLOPT_SSL_VERIFYPEER => (bool) config('browser.verify_ssl', true),
                CURLOPT_SSL_VERIFYHOST => config('browser.verify_ssl', true) ? 2 : 0,
                CURLOPT_ENCODING       => '',     // Let cURL handle decoding
            ]);

            // Optional: forward cookies from the user's session
            if (!empty($options['cookies'])) {
                curl_setopt($ch, CURLOPT_COOKIE, $options['cookies']);
            }

            // Optional: POST data
            if (!empty($options['post'])) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($options['post'])
                    ? http_build_query($options['post'])
                    : $options['post']);
            }

            $content    = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'text/html';
            $finalUrl   = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $targetUrl;
            $error      = curl_error($ch);

            curl_close($ch);

            if ($content === false || !empty($error)) {
                Log::warning("BrowserProxy: cURL error for {$targetUrl}: {$error}");
                return $this->errorResponse("Failed to fetch page: {$error}");
            }

            if ($statusCode >= 400) {
                Log::warning("BrowserProxy: HTTP {$statusCode} for {$targetUrl}");
            }

            // Determine content type and process accordingly
            $contentTypeLower = strtolower($contentType);

            if (str_contains($contentTypeLower, 'text/html')) {
                // Rewrite HTML for proxy
                $content = $this->rewriteHtml($content, $finalUrl);
            } elseif (str_contains($contentTypeLower, 'text/css')) {
                // Rewrite CSS URLs
                $content = $this->rewriteCss($content, $finalUrl);
            }

            // Extract page title
            $title = null;
            if (str_contains($contentTypeLower, 'text/html')) {
                if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $m)) {
                    $title = trim($m[1]);
                }
            }

            return [
                'content'     => $content,
                'contentType' => $this->cleanContentType($contentType),
                'statusCode'  => $statusCode,
                'url'         => $finalUrl,
                'title'       => $title,
            ];

        } catch (\Exception $e) {
            Log::error("BrowserProxy: Exception for {$targetUrl}: " . $e->getMessage());
            return $this->errorResponse('An unexpected error occurred.');
        }
    }

    /**
     * Proxy a resource (image, CSS, JS, font) — fetches and serves it directly.
     * Returns raw content with appropriate headers for the controller to serve.
     */
    public function fetchResource(string $resourceUrl): array
    {
        $resourceUrl = html_entity_decode($resourceUrl);

        // Security: reject non-http(s) schemes (prevents file://, php://, etc.)
        if (!preg_match('#^https?://#i', $resourceUrl)) {
            Log::warning("BrowserProxy: Blocked non-http resource URL: {$resourceUrl}");
            return [
                'content'       => '',
                'contentType'   => 'text/plain',
                'contentLength' => 0,
                'statusCode'    => 403,
            ];
        }

        // Fix double-encoding
        if (!filter_var($resourceUrl, FILTER_VALIDATE_URL) && str_starts_with($resourceUrl, 'http')) {
            $resourceUrl = urldecode($resourceUrl);
        }

        try {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL            => $resourceUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_USERAGENT      => $this->userAgent,
                CURLOPT_SSL_VERIFYPEER => (bool) config('browser.verify_ssl', true),
                CURLOPT_SSL_VERIFYHOST => config('browser.verify_ssl', true) ? 2 : 0,
                CURLOPT_ENCODING       => '',
            ]);

            $content    = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'application/octet-stream';
            $contentLength = strlen($content);
            curl_close($ch);

            return [
                'content'       => $content,
                'contentType'   => $contentType,
                'contentLength' => $contentLength,
                'statusCode'    => $statusCode,
            ];

        } catch (\Exception $e) {
            Log::error("BrowserProxy: Resource fetch failed: " . $e->getMessage());
            return [
                'content'       => '',
                'contentType'   => 'text/plain',
                'contentLength' => 0,
                'statusCode'    => 500,
            ];
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Normalize a URL — add https:// if missing.
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        // If it's a search query (no dots, no protocol), redirect to search
        if (!str_contains($url, '.') && !str_contains($url, '://') && !str_starts_with($url, 'localhost')) {
            // This will be handled by the controller — just pass through
            return $url;
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    /**
     * Rewrite HTML content so all links and resources go through the proxy.
     */
    private function rewriteHtml(string $html, string $baseUrl): string
    {
        $parsed = parse_url($baseUrl);
        $baseDomain = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');

        // 1. Inject YG agent bar (at top of <body>)
        $injectedBar = $this->getAgentBarHtml();
        $html = preg_replace('/(<body[^>]*>)/is', '$1' . $injectedBar, $html, 1);

        // 2. Rewrite <a href="..."> links (handles href as first attribute or later)
        $html = preg_replace_callback(
            '/<a\b[^>]*?\bhref\s*=\s*["\']([^"\']+)["\'][^>]*>/is',
            function ($m) use ($baseDomain) {
                $url = $this->rewriteUrl($m[1], $baseDomain);
                return str_replace($m[1], $url, $m[0]);
            },
            $html
        );

        // 3. Rewrite <img src="...">
        $html = preg_replace_callback(
            '/<img\b[^>]*?\bsrc\s*=\s*["\']([^"\']+)["\'][^>]*>/is',
            function ($m) use ($baseDomain) {
                $url = $this->rewriteResourceUrl($m[1], $baseDomain);
                return str_replace($m[1], $url, $m[0]);
            },
            $html
        );

        // 4. Rewrite <link href="..."> (stylesheets, favicons)
        $html = preg_replace_callback(
            '/<link\b[^>]*?\bhref\s*=\s*["\']([^"\']+)["\'][^>]*>/is',
            function ($m) use ($baseDomain) {
                $url = $this->rewriteResourceUrl($m[1], $baseDomain);
                return str_replace($m[1], $url, $m[0]);
            },
            $html
        );

        // 5. Rewrite <script src="...">
        $html = preg_replace_callback(
            '/<script\b[^>]*?\bsrc\s*=\s*["\']([^"\']+)["\'][^>]*>/is',
            function ($m) use ($baseDomain) {
                $url = $this->rewriteResourceUrl($m[1], $baseDomain);
                return str_replace($m[1], $url, $m[0]);
            },
            $html
        );

        // 6. Rewrite url() in inline styles
        $html = preg_replace_callback(
            '/url\s*\(\s*["\']?([^)"\'\s]+)["\']?\s*\)/is',
            function ($m) use ($baseDomain) {
                $url = $this->rewriteResourceUrl($m[1], $baseDomain);
                return 'url(' . $url . ')';
            },
            $html
        );

        // 7. Add <base> tag to the <head>
        $baseTag = '<base href="' . htmlspecialchars($baseUrl) . '">';
        if (stripos($html, '<base ') === false) {
            $html = preg_replace('/(<head[^>]*>)/is', '$1' . $baseTag, $html, 1);
        }

        // 8. Remove X-Frame-Options and CSP frame-ancestors from meta tags
        $html = preg_replace(
            '/<meta\s[^>]*http-equiv\s*=\s*["\']X-Frame-Options["\'][^>]*>/is',
            '',
            $html
        );

        return $html;
    }

    /**
     * Rewrite a navigation URL to go through the proxy.
     */
    private function rewriteUrl(string $url, string $baseDomain): string
    {
        // Skip javascript:, mailto:, tel:, # anchors
        if (preg_match('#^(javascript:|mailto:|tel:|#)#i', $url)) {
            return $url;
        }

        // Resolve relative URLs
        if (!preg_match('#^https?://#i', $url)) {
            $url = $this->resolveRelativeUrl($url, $baseDomain);
        }

        return $this->proxyBase . '?url=' . urlencode($url);
    }

    /**
     * Rewrite a resource URL (image, CSS, JS, font) to go through the proxy.
     */
    private function rewriteResourceUrl(string $url, string $baseDomain): string
    {
        // Skip data: URIs
        if (str_starts_with($url, 'data:')) {
            return $url;
        }

        // Resolve relative URLs
        if (!preg_match('#^https?://#i', $url)) {
            $url = $this->resolveRelativeUrl($url, $baseDomain);
        }

        return $this->proxyBase . '/resource?url=' . urlencode($url);
    }

    /**
     * Resolve a relative URL to an absolute URL.
     */
    private function resolveRelativeUrl(string $relativeUrl, string $baseDomain): string
    {
        if (str_starts_with($relativeUrl, '//')) {
            // Protocol-relative
            $parsed = parse_url($baseDomain);
            return ($parsed['scheme'] ?? 'https') . ':' . $relativeUrl;
        }

        if (str_starts_with($relativeUrl, '/')) {
            return rtrim($baseDomain, '/') . $relativeUrl;
        }

        return rtrim($baseDomain, '/') . '/' . ltrim($relativeUrl, '/');
    }

    /**
     * Rewrite CSS content so url() references go through the proxy.
     */
    private function rewriteCss(string $css, string $baseUrl): string
    {
        $parsed = parse_url($baseUrl);
        $baseDomain = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');

        return preg_replace_callback(
            '/url\s*\(\s*["\']?([^)"\'\s]+)["\']?\s*\)/is',
            function ($m) use ($baseDomain) {
                $url = $this->rewriteResourceUrl($m[1], $baseDomain);
                return 'url(' . $url . ')';
            },
            $css
        );
    }

    /**
     * Get the YG Agent Bar HTML injected into proxied pages.
     */
    private function getAgentBarHtml(): string
    {
        return <<<'HTML'
<!-- YG Agentic Browser Bar -->
<div id="yg-browser-bar" style="
    position:fixed;top:0;left:0;right:0;z-index:999999;
    background:linear-gradient(135deg,#2563eb,#7c3aed);
    color:#fff;display:flex;align-items:center;gap:8px;
    padding:6px 12px;font-family:Inter,system-ui,sans-serif;
    font-size:13px;height:36px;box-shadow:0 2px 8px rgba(0,0,0,.15);
">
    <span style="font-weight:800;font-size:14px;margin-right:4px;">YG</span>
    <span style="opacity:.7;font-size:11px;">Browsing via YGXONE</span>
    <span style="flex:1;"></span>
    <span style="font-size:10px;opacity:.5;" id="yg-current-url"></span>
</div>
<script>
(function(){
    // Push page content down to accommodate the bar
    document.documentElement.style.marginTop = '36px';
    // Show current URL
    var bar = document.getElementById('yg-current-url');
    if (bar) {
        try { bar.textContent = (new URL(window.location.href)).searchParams.get('yg_url') || ''; }
        catch(e) {}
    }
})();
</script>
<!-- /YG Agentic Browser Bar -->
HTML;
    }

    /**
     * Clean content type string for response headers.
     */
    private function cleanContentType(string $contentType): string
    {
        // Strip charset for simplicity
        if (str_contains($contentType, ';')) {
            $parts = explode(';', $contentType);
            return trim($parts[0]);
        }
        return $contentType;
    }

    /**
     * Build an error response array.
     */
    private function errorResponse(string $message): array
    {
        return [
            'content'     => '<html><body><div style="text-align:center;padding:40px;font-family:sans-serif;"><h2>⚠️ YG Browser</h2><p>' . htmlspecialchars($message) . '</p></div></body></html>',
            'contentType' => 'text/html',
            'statusCode'  => 502,
            'url'         => '',
            'title'       => 'Error',
        ];
    }
}
