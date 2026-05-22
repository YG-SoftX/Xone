<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * BrowserProxyService — PHP/cURL proxy engine that fetches web pages,
 * rewrites resource URLs to route through the proxy, and injects
 * the YG agent script for AI-powered browsing.
 *
 * Designed for cPanel shared hosting — uses only PHP/cURL, no Node.js/Puppeteer.
 *
 * Brave-style Shields:
 *   - Ad/tracker domain blocklists (EasyList-based)
 *   - HTTPS Everywhere (auto-upgrade http → https)
 *   - Fingerprinting protection (header spoofing, tracking param removal)
 *   - Script blocking (strip all <script> except YG agent)
 *   - Speed Reader mode (strip CSS/JS/images, keep readable text only)
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

    /** Shields config loaded from master panel (cached per request) */
    private ?array $shieldsConfig = null;

    /** Shields statistics for the current request */
    private array $shieldsStats = [
        'ads_blocked'      => 0,
        'trackers_blocked' => 0,
        'scripts_blocked'  => 0,
        'https_upgraded'   => false,
        'fingerprinting_blocked' => 0,
    ];

    // ── Ad / Tracker Domain Blocklists ───────────────────────────────────────
    // Based on EasyList + common ad/tracker networks.
    // Extendable via storage/app/shields-blocklist.json

    private array $adDomains = [
        'doubleclick.net', 'googleadservices.com', 'googlesyndication.com',
        'google-analytics.com', 'googletagmanager.com', 'googletagservices.com',
        'adservice.google.com', 'pagead2.googlesyndication.com',
        'amazon-adsystem.com', 'criteo.com', 'criteo.net', 'outbrain.com',
        'taboola.com', 'revcontent.com', 'mgid.com', 'adnxs.com',
        'rubiconproject.com', 'pubmatic.com', 'openx.net', 'casalemedia.com',
        'adsrvr.org', 'adzerk.net', 'adform.net', 'advertising.com',
        'bluekai.com', 'exelator.com', 'demdex.net', 'krxd.net',
        'scorecardresearch.com', 'quantserve.com', 'moatads.com',
        'adsafeprotected.com', '2mdn.net', 'adition.com', 'adroll.com',
        'adsymptotic.com', 'bidswitch.net', 'sharethrough.com',
        'smartadserver.com', 'sovrn.com', 'spotxchange.com', 'tremorhub.com',
        'triplelift.com', 'yieldmo.com', 'zedo.com', 'lijit.com',
        'popads.net', 'popcash.net', 'propellerads.com', 'adcash.com',
        'exoclick.com', 'trafficjunky.net', 'juicyads.com', 'ero-advertising.com',
    ];

    private array $trackerDomains = [
        'facebook.com/tr', 'connect.facebook.net', 'analytics.twitter.com',
        't.co', 'linkedin.com/px', 'snap.licdn.com',
        'bat.bing.com', 'clarity.ms', 'hotjar.com', 'mouseflow.com',
        'fullstory.com', 'crazyegg.com', 'optimizely.com', 'vwo.com',
        'mixpanel.com', 'amplitude.com', 'segment.com', 'segment.io',
        'heap.io', 'pendo.io', 'intercom.io', 'intercomcdn.com',
        'zendesk.com/embeddable', 'tawk.to', 'livechatinc.com',
        'newrelic.com', 'datadoghq.com', 'sentry.io', 'raygun.io',
        'bugsnag.com', 'logrocket.com', 'rollbar.com',
        'pixel.facebook.com', 'analytics.tiktok.com', 'ads.tiktok.com',
        'snapchat.com/collect', 'tr.snapchat.com', 'pinterest.com/ct',
        'redditstatic.com/ads', 'quantserve.com', 'chartbeat.com',
        'parsely.com', 'outbrain.com', 'taboola.com',
    ];

    public function __construct()
    {
        $this->proxyBase = rtrim(config('app.url', 'https://ygxone.com'), '/') . '/browse';
        $this->timeout   = (int) config('browser.proxy_timeout', 15);
        $this->userAgent = config('browser.user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36');
    }

    /**
     * Fetch a URL through the proxy and return the modified content.
     *
     * Shields options (Brave-style privacy protections):
     *   shields_enabled     => bool   Master toggle for all shields
     *   block_ads           => bool   Block ad networks & elements
     *   block_trackers      => bool   Block tracking scripts & pixels
     *   https_upgrade       => bool   Auto-upgrade http:// to https://
     *   block_fingerprinting=> bool   Spoof headers, remove tracking params
     *   block_scripts       => bool   Strip all <script> tags (except YG agent)
     *   speed_reader        => bool   Strip CSS/JS/images, show readable text only
     *
     * @return array{content: string, contentType: string, statusCode: int, url: string, title: string|null, shields: array}
     */
    public function fetch(string $targetUrl, array $options = []): array
    {
        // Reset shields stats for this request
        $this->shieldsStats = [
            'ads_blocked'      => 0,
            'trackers_blocked' => 0,
            'scripts_blocked'  => 0,
            'https_upgraded'   => false,
            'fingerprinting_blocked' => 0,
        ];

        // Apply HTTPS upgrade before fetching
        $shieldsConfig = $this->getShieldsConfig();
        $useShields = $options['shields_enabled'] ?? $shieldsConfig['enabled'] ?? true;
        $upgradeHttps = $options['https_upgrade'] ?? $shieldsConfig['https_upgrade'] ?? true;
        $blockFingerprinting = $options['block_fingerprinting'] ?? $shieldsConfig['block_fingerprinting'] ?? true;

        if ($useShields && $upgradeHttps) {
            $upgraded = $this->upgradeToHttps($targetUrl);
            if ($upgraded !== $targetUrl) {
                $this->shieldsStats['https_upgraded'] = true;
                $targetUrl = $upgraded;
            }
        }

        $targetUrl = $this->normalizeUrl($targetUrl);

        try {
            $ch = curl_init();

            // Build headers with fingerprinting protection
            $headers = $this->buildShieldedHeaders($useShields && $blockFingerprinting);

            curl_setopt_array($ch, [
                CURLOPT_URL            => $targetUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_USERAGENT      => $useShields && $blockFingerprinting
                    ? $this->getShieldedUserAgent()
                    : $this->userAgent,
                CURLOPT_HTTPHEADER     => $headers,
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
                // Apply shields to HTML content
                if ($useShields) {
                    $content = $this->applyShields($content, $shieldsConfig, $options);
                }
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
                'shields'     => $this->shieldsStats,
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

        // SSRF protection: resolve host and block private IP ranges
        $parsed = parse_url($resourceUrl);
        $host = $parsed['host'] ?? '';
        if ($host) {
            $ip = gethostbyname($host);
            if ($ip !== $host) { // DNS resolution succeeded
                $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
                if (!filter_var($ip, FILTER_VALIDATE_IP, $flags)) {
                    Log::warning("BrowserProxy: Blocked private/reserved IP resource: {$resourceUrl} → {$ip}");
                    return [
                        'content'       => '',
                        'contentType'   => 'text/plain',
                        'contentLength' => 0,
                        'statusCode'    => 403,
                    ];
                }
            }
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

    // ── Shields Processing ─────────────────────────────────────────────────────

    /**
     * Apply Brave-style shields to HTML content.
     * Handles: ad blocking, tracker blocking, script blocking, speed reader, fingerprinting protection.
     */
    private function applyShields(string $html, array $options, array $shieldsConfig): string
    {
        $enabled    = $options['shields_enabled'] ?? $shieldsConfig['enabled'] ?? true;
        $blockAds   = $options['block_ads'] ?? $shieldsConfig['block_ads'] ?? true;
        $blockTrack = $options['block_trackers'] ?? $shieldsConfig['block_trackers'] ?? true;
        $blockScripts = $options['block_scripts'] ?? $shieldsConfig['block_scripts'] ?? false;
        $speedReader = $options['speed_reader'] ?? $shieldsConfig['speed_reader'] ?? false;
        $blockFp    = $options['block_fingerprinting'] ?? $shieldsConfig['block_fingerprinting'] ?? true;

        // Speed Reader: strip all CSS/JS/images, keep only text structure
        if ($enabled && $speedReader) {
            return $this->applySpeedReader($html);
        }

        // Remove tracking URL parameters
        if ($enabled && $blockFp) {
            $html = $this->stripTrackingParams($html);
        }

        // Block ad elements (EasyList-based element hiding)
        if ($enabled && $blockAds) {
            $html = $this->blockAdElements($html);
        }

        // Strip tracking pixels/beacons
        if ($enabled && $blockTrack) {
            $html = $this->blockTrackers($html);
        }

        // Strip scripts (keep YG agent script)
        if ($enabled && $blockScripts) {
            $html = $this->stripScriptsWithShields($html);
        }

        // Remove fingerprinting vectors
        if ($enabled && $blockFp) {
            $html = $this->removeFingerprintingVectors($html);
        }

        return $html;
    }

    /**
     * Block ad-related elements: hidden divs, ad containers, overlay wrappers.
     */
    private function blockAdElements(string $html): string
    {
        // Remove common ad container classes/ids
        $adSelectors = [
            'class="[^"]*ad[^"]*"', 'id="[^"]*ad[^"]*"',
            'class="[^"]*banner[^"]*"', 'id="[^"]*banner[^"]*"',
            'class="[^"]*sponsor[^"]*"', 'id="[^"]*sponsor[^"]*"',
            'class="[^"]*promotion[^"]*"',
            'data-ad', 'data-advertisement',
        ];

        foreach ($adSelectors as $sel) {
            $html = preg_replace_callback(
                '/<(?:div|section|article|aside|span|p|table|tbody)[^>]*(?:' . $sel . ')[^>]*>.*?<\/(?:div|section|article|aside|p|table|tbody)>/is',
                function ($m) {
                    // Count blocked ads
                    $this->shieldsStats['ads_blocked']++;
                    return '<!-- ad blocked -->';
                },
                $html
            );
        }

        // Remove iframe ad slots
        $html = preg_replace_callback(
            '/<iframe[^>]*>(?:.*?)<\/iframe>/is',
            function ($m) {
                $src = $m[0];
                foreach ($this->adDomains as $domain) {
                    if (stripos($src, $domain) !== false) {
                        $this->shieldsStats['ads_blocked']++;
                        return '<!-- ad iframe blocked -->';
                    }
                }
                return $m[0];
            },
            $html
        );

        return $html;
    }

    /**
     * Block tracking pixels, beacons, and analytics scripts.
     */
    private function blockTrackers(string $html): string
    {
        // Remove tracking pixels (1x1 transparent images)
        $html = preg_replace(
            '/<img\b[^>]*\b(src|width|height)\s*=\s*["\']?(?:https?)?[^"\'>]*(?:pixel|track|beacon|analytics)[^"\'>]*["\']?[^>]*>/is',
            '<!-- tracker pixel blocked -->',
            $html
        );

        // Remove known tracker domains in img src
        foreach ($this->trackerDomains as $tracker) {
            $escaped = preg_quote($tracker, '/');
            $html = preg_replace_callback(
                '/<img\b[^>]*\bsrc\s*=\s*["\']https?://[^"\']*' . $escaped . '[^"\']*["\'][^>]*>/is',
                function ($m) {
                    $this->shieldsStats['trackers_blocked']++;
                    return '<!-- tracker blocked -->';
                },
                $html
            );
        }

        // RemoveNoscript tracker content (analytics in noscript)
        $html = preg_replace_callback(
            '/<noscript[^>]*>.*?(?:' . implode('|', array_map(fn($d) => preg_quote($d, '/'), $this->trackerDomains)) . ').*?<\/noscript>/is',
            function ($m) {
                $this->shieldsStats['trackers_blocked']++;
                return '<!-- tracker noscript blocked -->';
            },
            $html
        );

        return $html;
    }

    /**
     * Strip all <script> tags except YG agent and allowed scripts.
     */
    private function stripScriptsWithShields(string $html): string
    {
        return preg_replace_callback(
            '/<script\b([^>]*)>(.*?)<\/script>/is',
            function ($m) {
                $attrs = $m[1];
                $content = $m[2];

                // Keep YG agent — check both content AND attributes (src=, data-src=, etc.)
                if (stripos($content, 'yg') !== false || stripos($attrs, 'yg') !== false || stripos($attrs, '/browse') !== false) {
                    return $m[0];
                }

                // Keep inline scripts with no src (likely functional)
                if (empty(trim($attrs)) && strlen($content) < 200) {
                    return $m[0];
                }

                $this->shieldsStats['scripts_blocked']++;
                return '<!-- script blocked -->';
            },
            $html
        );
    }

    /**
     * Strip tracking parameters from all URLs in HTML (UTM, fbclid, gclid, etc.).
     */
    private function stripTrackingParams(string $html): string
    {
        $trackingParams = [
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
            'fbclid', 'gclid', 'gclsrc', 'dclid',
            'msclkid', 'twclid', 'igshid',
            '_ga', 'mc_eid', 'mc_cid',
            'ref', 'ref_src', 'ref_url',
            'source', 'campaign', 'affiliate',
        ];

        // Strip from href attributes
        $html = preg_replace_callback(
            '/href\s*=\s*["\']([^"\']*)["\']/i',
            function ($m) use ($trackingParams) {
                $url = $m[1];
                $parsed = parse_url($url);
                if (empty($parsed['query'])) return $m[0];

                parse_str($parsed['query'], $qs);
                $before = count($qs);
                $qs = array_diff_key($qs, array_flip($trackingParams));

                if (count($qs) < $before) {
                    $this->shieldsStats['fingerprinting_blocked'] += ($before - count($qs));
                    $newQuery = http_build_query($qs);
                    $newUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . ($parsed['path'] ?? '') . ($newQuery ? '?' . $newQuery : '');
                    return 'href="' . $newUrl . '"';
                }
                return $m[0];
            },
            $html
        );

        // Strip from src attributes
        $html = preg_replace_callback(
            '/src\s*=\s*["\']([^"\']*)["\']/i',
            function ($m) use ($trackingParams) {
                $url = $m[1];
                if (!filter_var($url, FILTER_VALIDATE_URL)) return $m[0];

                $parsed = parse_url($url);
                if (empty($parsed['query'])) return $m[0];

                parse_str($parsed['query'], $qs);
                $before = count($qs);
                $qs = array_diff_key($qs, array_flip($trackingParams));

                if (count($qs) < $before) {
                    $this->shieldsStats['fingerprinting_blocked'] += ($before - count($qs));
                    $newQuery = http_build_query($qs);
                    $newUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . ($parsed['path'] ?? '') . ($newQuery ? '?' . $newQuery : '');
                    return 'src="' . $newUrl . '"';
                }
                return $m[0];
            },
            $html
        );

        return $html;
    }

    /**
     * Remove fingerprinting vectors: canvas audit, webdriver detection, etc.
     */
    private function removeFingerprintingVectors(string $html): string
    {
        // Remove canvas fingerprinting scripts
        $html = preg_replace(
            '/<script\b[^>]*>(?:.*?(?:toDataURL|getImageData|getPixelData|canvas|pixelDiff).*?)<\/script>/is',
            '<!-- fp script blocked -->',
            $html
        );

        // Remove webdriver detection
        $html = preg_replace(
            '/<script\b[^>]*>(?:.*?(?:webdriver|navigator\.webdriver|chrome\.runtime).*?)<\/script>/is',
            '<!-- webdriver detection blocked -->',
            $html
        );

        // Remove automation detection
        $html = preg_replace(
            '/<script\b[^>]*>(?:.*?(?:\.automation|\.webdriver|selenium|__webdriver)\.*?)<\/script>/is',
            '<!-- automation detection blocked -->',
            $html
        );

        return $html;
    }

    /**
     * Speed Reader: strip CSS/JS/images, show plain readable text.
     */
    private function applySpeedReader(string $html): string
    {
        // Remove all styles
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);

        // Remove scripts
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);

        // Remove images
        $html = preg_replace('/<img\b[^>]*>/is', '', $html);

        // Remove svgs
        $html = preg_replace('/<svg\b[^>]*>.*?<\/svg>/is', '', $html);

        // Remove tracking pixels
        $html = preg_replace('/<img\b[^>]*>/is', '', $html);

        // Remove nav/header/footer (keep main content)
        $html = preg_replace('/<(?:nav|header|footer|aside)\b[^>]*>.*?<\/(?:nav|header|footer|aside)>/is', '', $html);

        // Remove ads
        $html = $this->blockAdElements($html);

        // Add speed-reader styling
        $readerCss = <<<'CSS'
<style>
.speed-reader {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 18px;
    line-height: 1.8;
    max-width: 700px;
    margin: 0 auto;
    padding: 40px 20px;
    color: #1a1a2e;
}
.speed-reader h1, .speed-reader h2, .speed-reader h3 {
    font-family: 'Inter', system-ui, sans-serif;
    margin: 1.5em 0 0.5em;
    line-height: 1.3;
}
.speed-reader p { margin: 1em 0; }
.speed-reader a { color: #2563eb; }
.speed-reader img { display: none; }
.speed-reader blockquote {
    border-left: 4px solid #2563eb;
    padding-left: 20px;
    margin: 1.5em 0;
    color: #4a5568;
}
.speed-reader pre, .speed-reader code {
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.9em;
}
.speed-reader pre { padding: 16px; overflow-x: auto; }
</style>
CSS;

        // Wrap content in speed reader div
        $html = preg_replace('/(<body[^>]*>)/is', '$1<div class="speed-reader">', $html);
        $html = preg_replace('/(<\/body>)/is', '</div>$1', $html);
        $html = $readerCss . $html;

        // Add speed reader badge
        $badge = '<div style="position:fixed;top:8px;right:8px;z-index:99999;background:#2563eb;color:white;padding:4px 12px;border-radius:20px;font-size:12px;font-family:sans-serif;">📖 Speed Reader</div>';
        $html = preg_replace('/(<body[^>]*>)/is', '$1' . $badge, $html);

        return $html;
    }

    /**
     * Load shields configuration from BrowserConfigService.
     */
    private function getShieldsConfig(): array
    {
        if ($this->shieldsConfig !== null) {
            return $this->shieldsConfig;
        }

        try {
            $config = app(BrowserConfigService::class);
            $this->shieldsConfig = [
                'enabled'            => $config->bool('shields_enabled', true),
                'block_ads'          => $config->bool('block_ads', true),
                'block_trackers'     => $config->bool('block_trackers', true),
                'https_upgrade'      => $config->bool('https_upgrade', true),
                'block_fingerprinting'=> $config->bool('block_fingerprinting', true),
                'block_scripts'      => $config->bool('block_scripts', false),
                'speed_reader'       => $config->bool('speed_reader', false),
            ];
        } catch (\Exception $e) {
            $this->shieldsConfig = [
                'enabled'            => true,
                'block_ads'          => true,
                'block_trackers'     => true,
                'https_upgrade'      => true,
                'block_fingerprinting'=> true,
                'block_scripts'      => false,
                'speed_reader'       => false,
            ];
        }

        return $this->shieldsConfig;
    }

    /**
     * Upgrade http:// to https:// if available.
     */
    private function upgradeToHttps(string $url): string
    {
        if (!str_starts_with($url, 'http://')) {
            return $url;
        }
        $https = substr_replace($url, 'https', 0, 4);
        // Verify https is available
        $ch = curl_init($https);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($code > 0) ? $https : $url;
    }

    /**
     * Build HTTP headers with fingerprinting protection values.
     */
    private function buildShieldedHeaders(bool $shielded): array
    {
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Accept-Encoding: gzip, deflate, br',
            'DNT: 1',
        ];

        if ($shielded) {
            // Spoof common browser headers to reduce fingerprint
            $headers[] = 'Sec-CH-UA: "Chromium";v="148", "Google Chrome";v="148"';
            $headers[] = 'Sec-CH-UA-Mobile: ?0';
            $headers[] = 'Sec-CH-UA-Platform: "Windows"';
            $headers[] = 'Sec-Fetch-Dest: document';
            $headers[] = 'Sec-Fetch-Mode: navigate';
            $headers[] = 'Sec-Fetch-Site: none';
            $headers[] = 'Sec-Fetch-User: ?1';
            $headers[] = 'Upgrade-Insecure-Requests: 1';
        }

        return $headers;
    }

    /**
     * Get a spoofed user agent for fingerprinting protection.
     */
    private function getShieldedUserAgent(): string
    {
        $agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36',
        ];
        return $agents[array_rand($agents)];
    }

    // ── POST Form Handling ──────────────────────────────────────────────────

    /**
     * Extract all <form> elements from raw HTML.
     * Used by the worker to identify actionable forms and by the
     * outer app to render a structured forms panel.
     *
     * @return array{action:string, method:string, enctype:string, fields:array}
     */
    public function extractForms(string $html, string $baseUrl): array
    {
        $forms = [];
        $baseDomain = (parse_url($baseUrl, PHP_URL_SCHEME) ?? 'https') . '://' . (parse_url($baseUrl, PHP_URL_HOST) ?? '');

        preg_match_all('/<form\b[^>]*>/i', $html, $openMatches, PREG_OFFSET_CAPTURE);

        foreach ($openMatches[0] as $match) {
            $tag     = $match[0];
            $offset  = $match[1];
            $tagEnd  = $offset + strlen($tag);

            // Find closing </form>
            $closePos = stripos($html, '</form>', $tagEnd);
            if ($closePos === false) $closePos = strlen($html);

            $formBlock = substr($html, $offset, $closePos - $offset + 7);

            // Parse attributes
            $action = $this->parseFormAttr($formBlock, 'action') ?: '';
            if ($action && !preg_match('#^https?://#i', $action)) {
                $action = rtrim($baseDomain, '/') . '/' . ltrim($action, '/');
            }
            $method   = strtoupper($this->parseFormAttr($formBlock, 'method') ?: 'GET');
            $enctype  = $this->parseFormAttr($formBlock, 'enctype') ?: 'application/x-www-form-urlencoded';
            $formId    = $this->parseFormAttr($formBlock, 'id') ?: '';
            $formClass = $this->parseFormAttr($formBlock, 'class') ?: '';

            // Extract fields
            $fields = [];
            preg_match_all('/<(input|select|textarea)\b[^>]*>/i', $formBlock, $fieldMatches, PREG_OFFSET_CAPTURE);
            foreach ($fieldMatches[0] as $fm) {
                $ftag  = $fm[0];
                $name  = strtolower($this->parseFormAttr($ftag, 'name') ?: '');
                $type  = strtolower($this->parseFormAttr($ftag, 'type') ?: '');
                $fid   = strtolower($this->parseFormAttr($ftag, 'id') ?: '');
                $label = '';
                // Try to find associated <label>
                if ($name) {
                    $pat = '/<label\b[^>]*\bfor\s*=\s*["\']' . preg_quote($fid ?: $name, '/') . '["\'][^>]*>([^<]*)<\/label>/i';
                    if (preg_match($pat, $html, $lm, 0, $offset)) {
                        $label = trim(strip_tags($lm[1]));
                    }
                }
                if ($name) {
                    $fields[] = compact('name', 'type', 'fid', 'label');
                }
            }

            $forms[] = compact('action', 'method', 'enctype', 'formId', 'formClass', 'fields');
        }

        return $forms;
    }

    /**
     * Parse a single HTML attribute value from a tag string.
     */
    private function parseFormAttr(string $tag, string $attr): ?string
    {
        if (preg_match('/\b' . preg_quote($attr, '/') . '\s*=\s*["\']([^"\']*)["\']/i', $tag, $m)) {
            return html_entity_decode($m[1]);
        }
        return null;
    }

    /**
     * Submit a form to the target URL and return the proxied response.
     *
     * Called by SearchController::browserSubmit() when the worker forwards
     * a form submission via postMessage.
     *
     * @return array{content: string, contentType: string, statusCode: int, url: string, title: string|null}
     */
    public function submitForm(string $pageUrl, array $formData): array
    {
        $targetUrl = $formData['action'] ?? $pageUrl;
        $method    = strtoupper($formData['method'] ?? 'GET');
        $enctype   = strtoupper($formData['enctype'] ?? '');
        $inputs    = $formData['data'] ?? [];

        $targetUrl = $this->normalizeUrl($targetUrl);

        $this->shieldsStats = [
            'ads_blocked' => 0, 'trackers_blocked' => 0, 'scripts_blocked' => 0,
            'https_upgraded' => false, 'fingerprinting_blocked' => 0,
        ];

        $shieldsConfig  = $this->getShieldsConfig();
        $useShields     = $shieldsConfig['enabled'] ?? true;
        $blockFp        = $shieldsConfig['block_fingerprinting'] ?? true;
        $upgradeHttps   = $shieldsConfig['https_upgrade'] ?? true;

        if ($useShields && $upgradeHttps) {
            $upgraded = $this->upgradeToHttps($targetUrl);
            if ($upgraded !== $targetUrl) {
                $this->shieldsStats['https_upgraded'] = true;
                $targetUrl = $upgraded;
            }
        }

        try {
            $ch = curl_init();
            $headers = $this->buildShieldedHeaders($useShields && $blockFp);

            curl_setopt_array($ch, [
                CURLOPT_URL            => $targetUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_USERAGENT      => $useShields && $blockFp
                    ? $this->getShieldedUserAgent()
                    : $this->userAgent,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => (bool) config('browser.verify_ssl', true),
                CURLOPT_SSL_VERIFYHOST => config('browser.verify_ssl', true) ? 2 : 0,
                CURLOPT_ENCODING       => '',
            ]);

            // Attach POST fields
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if (stripos($enctype, 'multipart') !== false) {
                    // multipart/form-data
                    $postfields = [];
                    foreach ($inputs as $k => $v) {
                        if (is_array($v)) {
                            foreach ($v as $sub) $postfields[] = [$k => $sub];
                        } else {
                            $postfields[$k] = $v;
                        }
                    }
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($inputs));
                    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }

            $content     = curl_exec($ch);
            $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'text/html';
            $finalUrl    = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $targetUrl;
            $error       = curl_error($ch);
            curl_close($ch);

            if ($content === false || !empty($error)) {
                Log::warning("BrowserProxy: Form submit error for {$targetUrl}: {$error}");
                return $this->errorResponse("Form submission failed: {$error}");
            }

            $contentTypeLower = strtolower($contentType);
            if (str_contains($contentTypeLower, 'text/html')) {
                if ($useShields) {
                    $shieldsConfig['submit_mode'] = true;
                    $content = $this->applyShields($content, $shieldsConfig, []);
                }
                $content = $this->rewriteHtml($content, $finalUrl);
            } elseif (str_contains($contentTypeLower, 'text/css')) {
                $content = $this->rewriteCss($content, $finalUrl);
            }

            $title = null;
            if (str_contains($contentTypeLower, 'text/html') && preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $m)) {
                $title = trim($m[1]);
            }

            return [
                'content'     => $content,
                'contentType' => $this->cleanContentType($contentType),
                'statusCode'  => $statusCode,
                'url'         => $finalUrl,
                'title'       => $title,
            ];

        } catch (\Exception $e) {
            Log::error("BrowserProxy: Form submit exception for {$targetUrl}: " . $e->getMessage());
            return $this->errorResponse('Form submission error.');
        }
    }

    /**
     * Fetch a blocked-popup URL and return rewritten content.
     * Used when the worker intercepts window.open() and sends the URL
     * back to the outer app via postMessage so it can open the page
     * in a fresh iframe or a new tab.
     *
     * @return array{content: string, contentType: string, statusCode: int, url: string, title: string|null}
     */
    public function fetchPopupPage(string $targetUrl): array
    {
        $targetUrl = $this->normalizeUrl($targetUrl);
        if (!preg_match('#^https?://#i', $targetUrl)) {
            return ['content' => '', 'contentType' => 'text/html', 'statusCode' => 400, 'url' => $targetUrl, 'title' => null];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $targetUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => $this->userAgent,
            CURLOPT_SSL_VERIFYPEER => (bool) config('browser.verify_ssl', true),
            CURLOPT_SSL_VERIFYHOST => config('browser.verify_ssl', true) ? 2 : 0,
            CURLOPT_ENCODING       => '',
        ]);
        $content    = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType= curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'text/html';
        $finalUrl   = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $targetUrl;
        curl_close($ch);

        if ($content === false) {
            return $this->errorResponse("Failed to fetch popup page: " . curl_error($ch));
        }

        return [
            'content'     => $content,
            'contentType' => $this->cleanContentType($contentType),
            'statusCode'  => $statusCode,
            'url'         => $finalUrl,
            'title'       => preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $m) ? trim($m[1]) : null,
        ];
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

        // 0. Inject anti-frame-busting script BEFORE anything else
        // This prevents sites from breaking out of the iframe using window.top.location
        $antiFrameBustScript = <<<'JS'
<script>
(function() {
    // Prevent frame busting / break-out attempts
    if (window !== window.top) {
        // Override location setter to prevent top navigation
        Object.defineProperty(window, 'location', {
            configurable: true,
            enumerable: true,
            get: function() { return document.location; },
            set: function(value) { 
                console.log('[YG Browser] Blocked frame-busting attempt:', value);
                document.location.href = value; 
            }
        });
        
        // Prevent top.location assignment
        try {
            Object.defineProperty(window.top, 'location', {
                configurable: false,
                writable: false,
                value: window.top.location
            });
        } catch(e) {}
        
        // Block common frame-bust patterns
        var originalOpen = window.open;
        window.open = function() {
            console.log('[YG Browser] Blocked window.open in iframe');
            return null;
        };
    }
})();
</script>
JS;
        
        // Inject right after <head> opening tag
        $html = preg_replace('/(<head[^>]*>)/is', '$1' . $antiFrameBustScript, $html, 1);

        // 0b. Inject bidirectional navigation worker
        // This bridges the iframe content with the outer YG Browser app shell.
        // The worker runs inside the proxied iframe (same-origin via proxy) and
        // posts structured messages back to the outer Alpine.js-driven app via postMessage.
        // The outer app handles navigation, popup opening, form POST submission,
        // and push notification display — the iframe is never trusted to navigate itself.
        $browseNavWorker = <<<'JS'
<script>
(function() {
    'use strict';

    const base = new URL(window.location.href).origin + '/browse';
    window.__yg_browse = { base };

    // ── helpers ──────────────────────────────────────────────────────────
    function postMsg(type, payload) {
        try {
            window.top.postMessage({ __yg_browse: true, type, payload }, '*');
        } catch(e) { console.warn('[YG] postMessage failed:', e); }
    }

    function resolveUrl(href) {
        try { return new URL(href, window.location.href).href; } catch(e) { return href; }
    }

    // ── worker hooks ─────────────────────────────────────────────────────

    // 1. Popup blocker — intercept window.open and notify outer app
    var _origOpen = window.open;
    window.open = function(url, name, features) {
        var u = resolveUrl(url || '');
        postMsg('popup', { url: u, name: name || '', features: features || '' });
        // Return a dummy window so the calling script doesn't crash
        return { closed: false, close: function(){}, focus: function(){} };
    };

    // 2. POST form interceptor — intercept form submissions
    // Normalises action + enctype + method + inputs → JSON for outer app
    function patchForms() {
        var forms = document.querySelectorAll('form[action]');
        forms.forEach(function(f) {
            if (f.dataset.ygPatched) return;
            f.dataset.ygPatched = '1';
            f.addEventListener('submit', function(e) {
                e.preventDefault();
                var fd = {};
                try {
                    var inputs = f.querySelectorAll('input[name], select[name], textarea[name]');
                    inputs.forEach(function(inp) {
                        if (inp.type === 'submit' && !inp.name) return;
                        if (inp.type === 'checkbox') fd[inp.name] = inp.checked;
                        else if (inp.type === 'radio' && !inp.checked) return;
                        else fd[inp.name] = inp.value;
                    });
                } catch(_) {}
                postMsg('form-submit', {
                    action: resolveUrl(f.action || window.location.href),
                    method: (f.method || 'GET').toUpperCase(),
                    enctype: f.enctype || 'application/x-www-form-urlencoded',
                    data: fd
                });
            }, true);
        });
    }

    // 3. Reloaded-page hook — re-patch forms on SPA/mutation updates
    if (typeof MutationObserver !== 'undefined') {
        var _mo = new MutationObserver(patchForms);
        _mo.observe(document.documentElement, { childList: true, subtree: true });
    }
    // Also patch on DOMContentLoaded / load
    patchForms();
    document.addEventListener('DOMContentLoaded', patchForms);
    window.addEventListener('load', patchForms);

    // 4. Session isolation fix — proxy-scoped localStorage shim
    // In a sandboxed same-origin iframe localStorage is scoped to the proxy
    // origin (e.g. ygxone.com). Sites that check for login tokens in
    // localStorage for their own domain will find an empty store. This shim
    // stores values under a per-origin namespace so third-party scripts don't
    // appear to be fighting over the same key-space.
    (function() {
        var proxyOrigin = window.location.origin;
        Object.defineProperty(window, '__yg_proxy_origin', { value: proxyOrigin });

        var _origLSGet  = Object.getOwnPropertyDescriptor(Storage.prototype, 'getItem');
        var _origLSSet  = Object.getOwnPropertyDescriptor(Storage.prototype, 'setItem');
        var _origLSRem  = Object.getOwnPropertyDescriptor(Storage.prototype, 'removeItem');
        var _origLSClear= Object.getOwnPropertyDescriptor(Storage.prototype, 'clear');

        function _key(k) { return '__yg_ls_' + proxyOrigin + '__' + k; }

        Object.defineProperty(Storage.prototype, 'getItem', {
            value: function(key) {
                try { return _origLSGet.value.call(this, _key(key)); } catch(e) { return null; }
            }
        });
        Object.defineProperty(Storage.prototype, 'setItem', {
            value: function(key, val) {
                try { _origLSSet.value.call(this, _key(key), String(val)); } catch(e) {}
            }
        });
        Object.defineProperty(Storage.prototype, 'removeItem', {
            value: function(key) {
                try { _origLSRem.value.call(this, _key(key)); } catch(e) {}
            }
        });
        Object.defineProperty(Storage.prototype, 'clear', {
            value: function() {
                // Only clear keys belonging to this proxy origin
                try {
                    for (var i = 0; i < this.length; i++) {
                        var k = this.key(i);
                        if (k && k.indexOf('__yg_ls_' + proxyOrigin + '__') === 0) {
                            this.removeItem(k.replace('__yg_ls_' + proxyOrigin + '__', ''));
                        }
                    }
                } catch(e) {}
            }
        });
    })();

    // 5. Scroll position cache — survive within-session history restores
    // When the user navigates back/forward, the outer app re-sets iframe src.
    // The site reloads from top. We cache the per-URL scroll position in
    // sessionStorage (namespaced to the proxy origin) and restore it on load.
    (function() {
        var CACHE_KEY = '__yg_scroll_cache__';
        var cached = {};
        try {
            var raw = sessionStorage.getItem(CACHE_KEY);
            if (raw) cached = JSON.parse(raw);
        } catch(e) {}

        window.addEventListener('beforeunload', function() {
            try {
                cached[window.location.href] = { x: window.scrollX, y: window.scrollY };
                sessionStorage.setItem(CACHE_KEY, JSON.stringify(cached));
            } catch(e) {}
        });

        window.addEventListener('load', function() {
            try {
                var entry = cached[window.location.href];
                if (entry) window.scrollTo(entry.x || 0, entry.y || 0);
            } catch(e) {}
        }, true);
    })();

    // 6. Pull-to-refresh gesture (mobile) — triggers postMessage so outer app
    // can reload the iframe cleanly.
    (function() {
        var startY = 0, pulling = false, threshold = 120;
        document.addEventListener('touchstart', function(e) {
            if (window.scrollY === 0) { startY = e.touches[0].clientY; pulling = true; }
        }, { passive: true });
        document.addEventListener('touchmove', function(e) {
            if (!pulling) return;
            var dy = e.touches[0].clientY - startY;
            if (dy > threshold && window.scrollY === 0) {
                pulling = false;
                postMsg('pull-refresh', { url: window.location.href });
            }
        }, { passive: true });
    })();

    // 7. Notify outer app that page has loaded (for loading-bar hide)
    window.addEventListener('load', function() {
        postMsg('page-loaded', { url: window.location.href });
    }, true);

    console.log('[YG Browser] Worker initialised — postMessage bridge active');
})();
</script>
JS;

        // Inject browseNavWorker right after the anti-bust closing tag
        // Detects either <!-- /YG Anti-Frame-Bust --> or <!-- /YG Agentic Browser Bar -->
        $html = preg_replace(
            '/(<!-- \/YG (?:Anti-Frame-Bust|Agentic Browser Bar) -->)/',
            '$1' . "\n" . $browseNavWorker,
            $html,
            1
        );

        // Fallback: if neither marker found yet, inject after the first </script>
        if (strpos($html, '__yg_browse') === false) {
            $html = preg_replace('/(<\/script>\s*\n)\s*/is', '$1' . $browseNavWorker . "\n", $html, 1);
        }

        return $html;
    }

    // ── Structured-JSON page extraction (REST data protocol, Phase 2) ──────────

    /**
     * Build a structured DOM tree from the <body> of an HTML fragment.
     * Recursive — only structural elements; style/text children are leaf nodes.
     */
    private function buildDomTree(string $html, string $baseUrl): array
    {
        $parsed = parse_url($baseUrl);
        $baseDomain = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');

        if (!preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $m)) {
            return [];
        }
        return $this->domFromHtml($m[1], $baseDomain);
    }

    private function domFromHtml(string $fragment, string $baseDomain): array
    {
        $tree  = [];
        $chunks = preg_split('/(?=<[a-z\/!])/i', $fragment);

        foreach ($chunks as $raw) {
            $raw = trim($raw);
            if ($raw === '') continue;

            // Text node (no leading <)
            if (!preg_match('/^</', $raw)) {
                $text = preg_replace('/\s+/', ' ', strip_tags($raw));
                if (trim($text)) $tree[] = ['__text' => $text];
                continue;
            }

            // Opening tag
            if (preg_match('/^<([a-z][a-z0-9]*)\b/i', $raw, $tagM)) {
                $tag   = strtolower($tagM[1]);
                $skips = ['script','style','svg','noscript','head','html','meta','link','title','iframe','canvas','video','audio','embed','object','source','track'];
                if (in_array($tag, $skips, true)) continue;

                $attrs   = $this->domParseAttrs(substr($raw, strpos($raw, ' ')));
                $isVoid  = in_array($tag, ['img','br','hr','input','meta','link','area','base','col','param','wbr'], true);
                $isClose = preg_match('/<\//', $raw);
                $inner   = '';
                if (!$isVoid && !$isClose && preg_match('/>(.*)/is', $raw, $im)) {
                    $inner = $im[1];
                }

                $children = $inner ? $this->domFromHtml($inner, $baseDomain) : [];
                $tree[]   = ['tag' => $tag, 'attrs' => $attrs, 'children' => $children];
            }
        }

        return $tree;
    }

    private function domParseAttrs(string $segment): array
    {
        $attrs = [];
        preg_match_all('/([a-z_:][-a-z0-9_:.]*)\s*=\s*"([^"]*)"|([a-z_:][-a-z0-9_:.]*)\s*=\s*\'([^\']*)\'/i', $segment, $m, PREG_SET_ORDER);
        foreach ($m as $pair) {
            $name = strtolower($pair[1] ?: $pair[3]);
            $val  = html_entity_decode($pair[2] ?: $pair[4] ?? '');
            $attrs[$name] = $val;
        }
        return $attrs;
    }

    private function extractPlainText(string $html): string
    {
        $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html);
        $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is',  ' ', $text);
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim(substr($text, 0, 8000));
    }

    private function extractStyles(string $html, string $baseDomain): array
    {
        $styles = [];
        preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $html, $m);
        foreach ($m[1] as $css) $styles[] = ['type' => 'inline', 'body' => trim($css)];
        preg_match_all('/<link\b[^>]*\brel\s*=\s*["\'][^"\']*stylesheet[^"\']*["\'][^>]*>/i', $html, $m2);
        foreach ($m2[0] as $link) {
            if (preg_match('/href\s*=\s*["\']([^"\']+)["\']/i', $link, $hr)) {
                $href = html_entity_decode($hr[1]);
                $abs  = !preg_match('#^https?://#i', $href)
                    ? rtrim($baseDomain, '/') . '/' . ltrim($href, '/')
                    : $href;
                $styles[] = ['type' => 'external', 'href' => $href, 'abs' => $abs];
            }
        }
        return $styles;
    }

    private function extractHead(string $html, string $baseDomain): array
    {
        $heads = [];
        preg_match('/<head[^>]*>(.*?)<\/head>/is', $html, $hm);
        foreach (preg_split('/(?=<[a-z\/!])/i', $hm[1] ?? '') as $raw) {
            if (!preg_match('/^<(meta|title|base|link)\b/i', trim($raw))) continue;
            $raw = preg_replace('/<\/?[a-z][^>]*>/i', '', $raw); // strip tags — head-entries are self-closing meta/link
            $heads[] = $this->domParseAttrs($raw);
        }
        return $heads;
    }

    private function extractLinks(string $html, string $baseDomain): array
    {
        $links = [];
        preg_match_all('/<a\b[^>]*\bhref\s*=\s*["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $lm);
        foreach ($lm[1] as $i => $rawHref) {
            if (preg_match('#^(javascript:|mailto:|tel:|#)#i', $rawHref)) continue;
            $abs = !preg_match('#^https?://#i', $rawHref)
                ? rtrim($baseDomain, '/') . '/' . ltrim($rawHref, '/')
                : $rawHref;
            $links[] = ['text' => trim(strip_tags($lm[2][$i])), 'href' => $abs];
        }
        return array_slice($links, 0, 200);
    }

    private function extractScripts(string $html, string $baseDomain): array
    {
        $scripts = [];
        preg_match_all('/<script\b[^>]*\bsrc\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $html, $sm);
        foreach ($sm[1] as $src) {
            $abs = !preg_match('#^https?://#i', $src)
                ? rtrim($baseDomain, '/') . '/' . ltrim($src, '/')
                : $src;
            $scripts[] = ['src' => $abs];
        }
        return $scripts;
    }

    private function extractImages(string $html, string $baseDomain): array
    {
        $imgs = [];
        preg_match_all('/<img\b[^>]*\bsrc\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $html, $im);
        foreach ($im[1] as $src) {
            if (str_starts_with($src, 'data:')) continue;
            $abs = !preg_match('#^https?://#i', $src)
                ? rtrim($baseDomain, '/') . '/' . ltrim($src, '/')
                : $src;
            $imgs[] = ['src' => $abs];
        }
        return array_slice($imgs, 0, 100);
    }

    private function extractMetaTags(string $html): array
    {
        $meta = [];
        preg_match_all('/<meta\b[^>]*>/i', $html, $mm);
        foreach ($mm[0] as $tag) {
            $attrs = $this->domParseAttrs(str_replace('<meta', '', $tag));
            $meta[] = $attrs;
        }
        return $meta;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**

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
        
        // 9. Remove Content-Security-Policy meta tags that might block framing
        $html = preg_replace(
            '/<meta\s[^>]*http-equiv\s*=\s*["\']Content-Security-Policy["\'][^>]*>/is',
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
