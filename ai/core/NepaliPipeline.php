<?php
/**
 * NepaliPipeline — Curated Nepali language training pipeline for Yuga
 *
 * Provides:
 *   1. Pre-defined crawlable Nepali content sources (news, gov, edu, wiki)
 *   2. Nepali text normalization and cleaning
 *   3. Bilingual (Nepali + English) corpus builder
 *   4. Progress-aware streaming training
 *
 * Usage:
 *   $pipeline = new NepaliPipeline($brain, $progress_cb);
 *   $pipeline->runSource('constitution');
 *   $pipeline->runAll(['news', 'government']);
 */
class NepaliPipeline {

    private object  $brain;
    private $cb;
    private int     $default_steps = 30000;

    // ── Curated Nepali content sources ────────────────────────────────
    public const SOURCES = [

        // ── Government & Legal ────────────────────────────────────────
        'constitution' => [
            'label'    => 'Constitution of Nepal (Nepali)',
            'type'     => 'crawl',
            'url'      => 'https://www.lawcommission.gov.np/en/constitution-of-nepal/',
            'pages'    => 30,
            'lang'     => 'np',
            'priority' => 1,
            'tags'     => ['government', 'legal', 'nepali'],
        ],
        'lawcommission' => [
            'label'    => 'Nepal Law Commission',
            'type'     => 'crawl',
            'url'      => 'https://www.lawcommission.gov.np/',
            'pages'    => 40,
            'lang'     => 'np_en',
            'priority' => 1,
            'tags'     => ['government', 'legal'],
        ],
        'mofaga' => [
            'label'    => 'Ministry of Federal Affairs',
            'type'     => 'crawl',
            'url'      => 'https://www.mofaga.gov.np/',
            'pages'    => 20,
            'lang'     => 'np_en',
            'priority' => 2,
            'tags'     => ['government'],
        ],
        'mofe' => [
            'label'    => 'Ministry of Finance Nepal',
            'type'     => 'crawl',
            'url'      => 'https://www.mof.gov.np/',
            'pages'    => 20,
            'lang'     => 'np_en',
            'priority' => 2,
            'tags'     => ['government', 'finance'],
        ],
        'opmcm' => [
            'label'    => 'Office of PM and Council of Ministers',
            'type'     => 'crawl',
            'url'      => 'https://opmcm.gov.np/',
            'pages'    => 20,
            'lang'     => 'np_en',
            'priority' => 2,
            'tags'     => ['government'],
        ],

        // ── News ──────────────────────────────────────────────────────
        'onlinekhabar' => [
            'label'    => 'OnlineKhabar News (Nepali)',
            'type'     => 'crawl',
            'url'      => 'https://www.onlinekhabar.com/',
            'pages'    => 50,
            'lang'     => 'np',
            'priority' => 1,
            'tags'     => ['news', 'nepali'],
        ],
        'ratopati' => [
            'label'    => 'Ratopati News (Nepali)',
            'type'     => 'crawl',
            'url'      => 'https://ratopati.com/',
            'pages'    => 40,
            'lang'     => 'np',
            'priority' => 1,
            'tags'     => ['news', 'nepali'],
        ],
        'ekantipur' => [
            'label'    => 'Ekantipur (Kantipur)',
            'type'     => 'crawl',
            'url'      => 'https://ekantipur.com/',
            'pages'    => 50,
            'lang'     => 'np',
            'priority' => 1,
            'tags'     => ['news', 'nepali'],
        ],
        'therisingnepal' => [
            'label'    => 'The Rising Nepal (English)',
            'type'     => 'crawl',
            'url'      => 'https://risingnepaldaily.com/',
            'pages'    => 40,
            'lang'     => 'en',
            'priority' => 2,
            'tags'     => ['news', 'english'],
        ],
        'myrepublica' => [
            'label'    => 'My Republica (English)',
            'type'     => 'crawl',
            'url'      => 'https://myrepublica.nagariknetwork.com/',
            'pages'    => 40,
            'lang'     => 'en',
            'priority' => 2,
            'tags'     => ['news', 'english'],
        ],
        'nepalnews' => [
            'label'    => 'Nepal News (Nepali)',
            'type'     => 'crawl',
            'url'      => 'https://nepalnews.com/',
            'pages'    => 30,
            'lang'     => 'np_en',
            'priority' => 2,
            'tags'     => ['news'],
        ],

        // ── Education ─────────────────────────────────────────────────
        'tribhuvan' => [
            'label'    => 'Tribhuvan University',
            'type'     => 'crawl',
            'url'      => 'https://www.tribhuvan-university.edu.np/',
            'pages'    => 20,
            'lang'     => 'np_en',
            'priority' => 2,
            'tags'     => ['education'],
        ],
        'ku' => [
            'label'    => 'Kathmandu University',
            'type'     => 'crawl',
            'url'      => 'https://ku.edu.np/',
            'pages'    => 20,
            'lang'     => 'en',
            'priority' => 3,
            'tags'     => ['education'],
        ],
        'wikipedia_np' => [
            'label'    => 'Wikipedia Nepali (ne.wikipedia.org)',
            'type'     => 'crawl',
            'url'      => 'https://ne.wikipedia.org/wiki/%E0%A4%A8%E0%A5%87%E0%A4%AA%E0%A4%BE%E0%A4%B2',
            'pages'    => 60,
            'lang'     => 'np',
            'priority' => 1,
            'tags'     => ['knowledge', 'nepali', 'wikipedia'],
        ],

        // ── Culture & Society ─────────────────────────────────────────
        'nepalitimes' => [
            'label'    => 'Nepali Times (English)',
            'type'     => 'crawl',
            'url'      => 'https://nepalitimes.com/',
            'pages'    => 30,
            'lang'     => 'en',
            'priority' => 2,
            'tags'     => ['news', 'culture'],
        ],
        'setopati' => [
            'label'    => 'Setopati (Nepali)',
            'type'     => 'crawl',
            'url'      => 'https://www.setopati.com/',
            'pages'    => 40,
            'lang'     => 'np',
            'priority' => 2,
            'tags'     => ['news', 'nepali'],
        ],

        // ── Business & Finance ────────────────────────────────────────
        'sharesansar' => [
            'label'    => 'ShareSansar (Stock market Nepali)',
            'type'     => 'crawl',
            'url'      => 'https://www.sharesansar.com/',
            'pages'    => 20,
            'lang'     => 'np_en',
            'priority' => 3,
            'tags'     => ['finance', 'business'],
        ],
        'merolagani' => [
            'label'    => 'Merolagani (NEPSE)',
            'type'     => 'crawl',
            'url'      => 'https://merolagani.com/',
            'pages'    => 20,
            'lang'     => 'np_en',
            'priority' => 3,
            'tags'     => ['finance'],
        ],
    ];

    // ── Source groups for quick selection ─────────────────────────────
    public const GROUPS = [
        'core_nepali'  => ['constitution', 'onlinekhabar', 'ekantipur', 'wikipedia_np', 'ratopati'],
        'government'   => ['constitution', 'lawcommission', 'mofaga', 'mofe', 'opmcm'],
        'news_nepali'  => ['onlinekhabar', 'ekantipur', 'ratopati', 'setopati', 'nepalnews'],
        'news_english' => ['therisingnepal', 'myrepublica', 'nepalitimes'],
        'education'    => ['tribhuvan', 'ku', 'wikipedia_np'],
        'finance'      => ['sharesansar', 'merolagani'],
        'full_bilingual' => ['constitution','wikipedia_np','onlinekhabar','ekantipur',
                             'therisingnepal','myrepublica','lawcommission','tribhuvan'],
    ];

    public function __construct(object $brain, ?callable $cb = null) {
        $this->brain = $brain;
        $this->cb    = $cb;
    }

    // ── Run a single named source ─────────────────────────────────────
    public function runSource(string $key, int $steps = 0): array {
        $src = self::SOURCES[$key] ?? null;
        if (!$src) return ['error' => "Unknown source: $key"];

        $this->emit('info', "Starting: {$src['label']}");
        $this->emit('info', "URL: {$src['url']} | Max pages: {$src['pages']}");

        $r = $this->brain->learnFromSite(
            $src['url'],
            $src['pages'],
            function($p) use ($src) {
                $this->emit('page', "[{$src['label']}] Page {$p['page']}: {$p['url']}");
            }
        );

        if (isset($r['error'])) {
            $this->emit('error', "Failed: {$r['error']}");
            return $r;
        }

        $this->emit('done', "Finished {$src['label']}: {$r['pages']} pages · Loss: {$r['loss']} · Vocab: {$r['vocab']}");
        return array_merge($r, ['source' => $key, 'label' => $src['label']]);
    }

    // ── Run a group of sources ────────────────────────────────────────
    public function runGroup(string $group): array {
        $keys = self::GROUPS[$group] ?? null;
        if (!$keys) return ['error' => "Unknown group: $group"];

        $results = [];
        $total_pages = 0;
        $this->emit('info', "Starting group: $group (" . count($keys) . " sources)");

        foreach ($keys as $key) {
            $this->emit('info', "--- Source $key ---");
            $r = $this->runSource($key);
            $results[$key] = $r;
            if (!isset($r['error'])) $total_pages += (int)($r['pages'] ?? 0);
            // Brief pause between sources to be polite to servers
            sleep(2);
        }

        $this->emit('done', "Group '$group' complete. Total pages crawled: $total_pages");
        return ['group' => $group, 'results' => $results, 'total_pages' => $total_pages];
    }

    // ── Normalize Nepali text ─────────────────────────────────────────
    public static function normalize(string $text): string {
        // Remove zero-width chars
        $text = str_replace(["\u{200B}", "\u{200C}", "\u{200D}", "\u{FEFF}"], '', $text);

        // Normalize common Devanagari variant forms
        // ि vs ि (combining vs precomposed)
        $text = \Normalizer::normalize($text, \Normalizer::FORM_C);

        // Fix common OCR/encoding mistakes in Nepali text
        $replacements = [
            'ि' => 'ि',   // alternate combining i-matra
            'ृ' => 'ृ',   // alternate r-vowel sign
        ];
        $text = str_replace(array_keys($replacements), array_values($replacements), $text);

        // Normalize double danda
        $text = preg_replace('/।{2,}/u', '॥', $text);

        // Collapse multiple spaces
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    // ── Check if text is primarily Devanagari ─────────────────────────
    public static function isDevanagari(string $text): bool {
        $total = mb_strlen($text, 'UTF-8');
        if ($total === 0) return false;
        preg_match_all('/[\x{0900}-\x{097F}]/u', $text, $m);
        return (count($m[0]) / $total) > 0.2;
    }

    // ── Detect language composition ───────────────────────────────────
    public static function detectLanguage(string $text): string {
        $np_chars = preg_match_all('/[\x{0900}-\x{097F}]/u', $text);
        $en_chars = preg_match_all('/[a-zA-Z]/', $text);
        if ($np_chars > $en_chars * 2) return 'np';
        if ($en_chars > $np_chars * 2) return 'en';
        return 'np_en';
    }

    // ── List all sources with metadata ────────────────────────────────
    public static function listSources(): array {
        return self::SOURCES;
    }

    public static function listGroups(): array {
        $result = [];
        foreach (self::GROUPS as $g => $keys) {
            $result[$g] = [
                'keys'   => $keys,
                'count'  => count($keys),
                'labels' => array_map(fn($k) => self::SOURCES[$k]['label'] ?? $k, $keys),
            ];
        }
        return $result;
    }

    // ── Internal progress emitter ─────────────────────────────────────
    private function emit(string $type, string $msg): void {
        if ($this->cb) {
            ($this->cb)(['type' => $type, 'msg' => $msg]);
        }
    }
}
