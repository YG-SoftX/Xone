<?php
/**
 * SEO — generates all meta tags, Open Graph, Twitter Card, JSON-LD
 * Used by portal and any public-facing page.
 */
class SEO {

    private array $config;
    private string $site_url;
    private string $page;
    private array $pages;

    public function __construct(array $config, string $current_page = 'home') {
        $this->config   = $config;
        $this->page     = $current_page;
        $proto          = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $this->site_url = $config['site_url'] ?? ($proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

        $brand = $config['platform_name'] ?? 'Yuga AI';
        $desc  = $config['site_description'] ?? 'A self-learning AI assistant platform. Train it on your content, deploy privately on your server.';

        $this->pages = [
            'home'     => ['title' => "$brand — AI Search Engine & Self-Learning Platform", 'desc' => "The sovereign AI search engine for the new era. Synthesized answers grounded in real-time web data and your own platform knowledge."],
            'search'   => ['title' => "Search — $brand",                                  'desc' => "AI-powered semantic search engine. Get accurate, cited answers from the live web and private knowledge bases."],
            'pricing'  => ['title' => "Pricing — $brand",                                  'desc' => "Simple, transparent pricing for $brand. Free plan available. Upgrade for more API calls and features."],
            'signup'   => ['title' => "Get Your API Key — $brand",                         'desc' => "Sign up for $brand and get instant API access. Build AI-powered features on your own server."],
            'docs'     => ['title' => "API Documentation — $brand",                        'desc' => "Full REST API reference for $brand. Endpoints for chat, training, sessions, tools, pipelines and more."],
            'dashboard'=> ['title' => "Dashboard — $brand",                                'desc' => "Manage your $brand subscription, API keys, and train your private AI model."],
            'key'      => ['title' => "Your API Key — $brand",                             'desc' => "Your API key for $brand. Keep it safe — it grants access to your private AI."],
        ];
    }

    // ── Full <head> SEO block ─────────────────────────────────────────────
    public function head(): string {
        $meta   = $this->pages[$this->page] ?? $this->pages['home'];
        $title  = $meta['title'];
        $desc   = $meta['desc'];
        $url    = $this->canonicalUrl();
        $image  = $this->ogImage();
        $brand  = $this->config['platform_name'] ?? 'Yuga AI';

        $out  = "<!-- SEO -->\n";
        $out .= '<title>' . htmlspecialchars($title) . "</title>\n";
        $out .= '<meta name="description" content="' . htmlspecialchars($desc) . '">' . "\n";
        $out .= '<meta name="robots" content="index,follow">' . "\n";
        $out .= '<link rel="canonical" href="' . htmlspecialchars($url) . '">' . "\n";

        // Open Graph
        $out .= "\n<!-- Open Graph -->\n";
        $out .= '<meta property="og:type"        content="website">' . "\n";
        $out .= '<meta property="og:title"       content="' . htmlspecialchars($title) . '">' . "\n";
        $out .= '<meta property="og:description" content="' . htmlspecialchars($desc) . '">' . "\n";
        $out .= '<meta property="og:url"         content="' . htmlspecialchars($url) . '">' . "\n";
        $out .= '<meta property="og:site_name"   content="' . htmlspecialchars($brand) . '">' . "\n";
        if ($image) $out .= '<meta property="og:image" content="' . htmlspecialchars($image) . '">' . "\n";

        // Twitter Card
        $out .= "\n<!-- Twitter Card -->\n";
        $out .= '<meta name="twitter:card"        content="summary_large_image">' . "\n";
        $out .= '<meta name="twitter:title"       content="' . htmlspecialchars($title) . '">' . "\n";
        $out .= '<meta name="twitter:description" content="' . htmlspecialchars($desc) . '">' . "\n";
        if ($image) $out .= '<meta name="twitter:image" content="' . htmlspecialchars($image) . '">' . "\n";

        // AI / LLM crawlers
        $out .= "\n<!-- AI SEO -->\n";
        $out .= '<meta name="ai-content-declaration" content="human-assisted-ai">' . "\n";
        $out .= '<link rel="ai-index" href="' . htmlspecialchars($this->site_url) . '/llms.txt">' . "\n";

        // JSON-LD
        $out .= "\n" . $this->jsonLd() . "\n";

        return $out;
    }

    // ── JSON-LD structured data ───────────────────────────────────────────
    public function jsonLd(): string {
        $brand   = $this->config['platform_name'] ?? 'Yuga AI';
        $desc    = $this->config['site_description'] ?? '';
        $url     = $this->site_url;
        $portal  = $url . '/portal/';
        $plans   = [
            ['name'=>'Free',       'price'=>'0'],
            ['name'=>'Starter',    'price'=>'9'],
            ['name'=>'Pro',        'price'=>'29'],
            ['name'=>'Enterprise', 'price'=>'99'],
        ];

        $schemas = [];

        // Organization
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => $brand,
            'url'      => $portal,
            'description' => $desc,
            'sameAs'   => [],
        ];

        // SoftwareApplication
        $schemas[] = [
            '@context'           => 'https://schema.org',
            '@type'              => 'SoftwareApplication',
            'name'               => $brand,
            'applicationCategory'=> 'BusinessApplication',
            'operatingSystem'    => 'Web, Android',
            'description'        => $desc ?: "Self-learning AI platform. Runs on PHP shared hosting. Train on your content, deploy privately.",
            'url'                => $portal,
            'offers'             => array_map(fn($p) => [
                '@type'         => 'Offer',
                'name'          => $p['name'],
                'price'         => $p['price'],
                'priceCurrency' => 'USD',
                'priceSpecification' => ['@type'=>'UnitPriceSpecification','billingDuration'=>'P1M'],
            ], $plans),
            'featureList' => [
                'Self-learning from any website or document',
                'Private deployment — data never leaves your server',
                'REST API with 20+ endpoints',
                'Telegram, Slack, WhatsApp integrations',
                'BM25 retrieval-augmented generation',
                'No GPU or Python required',
                'Runs on $3/mo cPanel hosting',
            ],
        ];

        // BreadcrumbList for non-home pages
        if ($this->page !== 'home') {
            $schemas[] = [
                '@context'        => 'https://schema.org',
                '@type'           => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type'=>'ListItem','position'=>1,'name'=>$brand,'item'=>$portal],
                    ['@type'=>'ListItem','position'=>2,'name'=>ucfirst($this->page),'item'=>$this->canonicalUrl()],
                ],
            ];
        }

        // FAQPage on pricing page
        if ($this->page === 'pricing') {
            $schemas[] = [
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => [
                    ['@type'=>'Question','name'=>'Do I need a GPU or cloud server?','acceptedAnswer'=>['@type'=>'Answer','text'=>'No. '.$brand.' runs on standard PHP shared hosting (cPanel). No GPU, no Python, no VPS required.']],
                    ['@type'=>'Question','name'=>'Is my data private?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Yes. All data stays on your server. Nothing is sent to external AI services unless you configure an optional LLM backend.']],
                    ['@type'=>'Question','name'=>'Can I train it on my own content?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Yes. You can train on any website, PDF, DOCX, CSV, plain text, or HTML files.']],
                    ['@type'=>'Question','name'=>'Can I cancel anytime?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Yes. Cancel or downgrade your plan at any time with no penalties.']],
                ],
            ];
        }

        $out = '';
        foreach ($schemas as $s) {
            $out .= '<script type="application/ld+json">' . json_encode($s, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }
        return $out;
    }

    // ── Canonical URL for current page ───────────────────────────────────
    private function canonicalUrl(): string {
        $base = rtrim($this->site_url, '/') . '/portal/';
        if ($this->page === 'home') return $base;
        return $base . '?page=' . urlencode($this->page);
    }

    // ── OG image URL if one exists ────────────────────────────────────────
    private function ogImage(): string {
        $candidates = ['/og-image.png', '/og-image.jpg', '/portal/og-image.png'];
        foreach ($candidates as $c) {
            if (file_exists(__DIR__ . '/..' . $c)) {
                return rtrim($this->site_url, '/') . $c;
            }
        }
        return '';
    }
}
