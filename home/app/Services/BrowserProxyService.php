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
