<?php
/**
 * Commander — Maps natural language voice commands to device actions.
 *
 * Two-stage pipeline:
 *   1. Pattern match against 25+ command intents (open, search, call, timer, etc.)
 *   2. If no device intent found → hand off to Brain::chat() for knowledge Q&A
 *
 * The frontend JS executes the returned action (window.open, Notification, etc.)
 * Commander only detects intent and builds the action payload.
 *
 * Usage:
 *   $cmd = new Commander($brain, $memory);
 *   $res = $cmd->handle("open YouTube", $sessionId);
 *   // → ['reply'=>'Opening YouTube.', 'action'=>['type'=>'open','url'=>'https://youtube.com']]
 */
class Commander {

    private Brain  $brain;
    private Memory $memory;

    // App shortcuts: spoken name → URL
    private static array $SHORTCUTS = [
        'youtube'       => 'https://youtube.com',
        'facebook'      => 'https://facebook.com',
        'instagram'     => 'https://instagram.com',
        'twitter'       => 'https://twitter.com',
        'x'             => 'https://twitter.com',
        'whatsapp'      => 'https://web.whatsapp.com',
        'gmail'         => 'https://mail.google.com',
        'google'        => 'https://google.com',
        'google maps'   => 'https://maps.google.com',
        'maps'          => 'https://maps.google.com',
        'google docs'   => 'https://docs.google.com',
        'google sheets' => 'https://sheets.google.com',
        'google drive'  => 'https://drive.google.com',
        'google meet'   => 'https://meet.google.com',
        'calendar'      => 'https://calendar.google.com',
        'github'        => 'https://github.com',
        'linkedin'      => 'https://linkedin.com',
        'netflix'       => 'https://netflix.com',
        'spotify'       => 'https://open.spotify.com',
        'amazon'        => 'https://amazon.com',
        'zoom'          => 'https://zoom.us',
        'slack'         => 'https://slack.com',
        'notion'        => 'https://notion.so',
        'reddit'        => 'https://reddit.com',
        'wikipedia'     => 'https://wikipedia.org',
        'news'          => 'https://news.google.com',
        'weather'       => 'https://weather.com',
        'translate'     => 'https://translate.google.com',
        'calculator'    => 'https://calculator.net',
        'gmail'         => 'https://mail.google.com',
    ];

    public function __construct(Brain $brain, Memory $memory) {
        $this->brain  = $brain;
        $this->memory = $memory;
    }

    // ── Main entry point ────────────────────────────────────────────────
    public function handle(string $input, string $sessionId): array {
        $input = trim($input);
        if (!$input) return ['reply' => 'I did not catch that. Please try again.', 'source' => 'commander'];

        // Try device action first
        $action = $this->detectAction($input);
        if ($action) {
            // Save to memory so conversation stays coherent
            $this->memory->addMessage($sessionId, 'user',      $input);
            $this->memory->addMessage($sessionId, 'assistant', $action['reply']);
            return [
                'reply'   => $action['reply'],
                'action'  => $action,
                'source'  => 'commander',
                'session' => $sessionId,
            ];
        }

        // No device action — use Brain for knowledge Q&A
        $result = $this->brain->chat($input, $sessionId, $this->memory);
        return [
            'reply'   => $result['answer'],
            'source'  => 'brain',
            'session' => $sessionId,
        ];
    }

    // ── Intent detection — ordered by specificity ───────────────────────
    private function detectAction(string $q): ?array {
        $q = trim($q);
        $l = strtolower($q);

        // ── Time / Date (no URL needed, JS handles) ──────────────────────
        if (preg_match('/\b(what(?:\'s| is)?\s+(?:the\s+)?(?:current\s+)?time|tell me the time)\b/i', $q))
            return ['type' => 'time', 'reply' => '__time__'];

        if (preg_match('/\b(what(?:\'s| is)?\s+(?:the\s+)?(?:today\'?s?\s+)?date|what day is (?:it|today))\b/i', $q))
            return ['type' => 'date', 'reply' => '__date__'];

        // ── YouTube / Play ───────────────────────────────────────────────
        if (preg_match('/\b(?:play|watch|search youtube for?)\s+(.+)/i', $q, $m)) {
            $query = trim($m[1]);
            return ['type'=>'youtube', 'query'=>$query, 'url'=>'https://youtube.com/results?search_query='.urlencode($query), 'reply'=>"Playing $query on YouTube."];
        }

        // ── Weather ──────────────────────────────────────────────────────
        if (preg_match('/\bweather\b(?:\s+(?:in|for|at)\s+(.+))?/i', $q, $m)) {
            $loc   = trim($m[1] ?? '');
            $param = $loc ? '?q='.urlencode($loc) : '';
            return ['type'=>'weather', 'location'=>$loc ?: 'current location', 'url'=>'https://weather.com/weather/today'.$param, 'reply'=>"Checking weather" . ($loc ? " for $loc" : '') . "."];
        }

        // ── Maps / Directions ────────────────────────────────────────────
        if (preg_match('/\b(?:directions?\s+to|navigate\s+to|show\s+(?:on\s+)?map(?:s)?\s+for?|where\s+is)\s+(.+)/i', $q, $m)) {
            $loc = trim($m[1]);
            return ['type'=>'maps', 'location'=>$loc, 'url'=>'https://maps.google.com/?q='.urlencode($loc), 'reply'=>"Opening maps for $loc."];
        }

        // ── Search ───────────────────────────────────────────────────────
        if (preg_match('/\b(?:search|google|look up|find)\s+(?:for\s+)?(.+)/i', $q, $m)) {
            $query = trim($m[1]);
            return ['type'=>'search', 'query'=>$query, 'url'=>'https://google.com/search?q='.urlencode($query), 'reply'=>"Searching for $query."];
        }

        // ── Open app / website ───────────────────────────────────────────
        if (preg_match('/\b(?:open|go\s+to|launch|take\s+me\s+to|show\s+me)\s+(.+)/i', $q, $m)) {
            $target = trim(preg_replace('/\bplease\b|\bnow\b/i', '', $m[1]));
            $url    = $this->resolveUrl($target);
            $label  = $target;
            return ['type'=>'open', 'url'=>$url, 'target'=>$target, 'reply'=>"Opening $label."];
        }

        // ── Email ────────────────────────────────────────────────────────
        if (preg_match('/\b(?:send|write|compose|draft)\s+(?:an?\s+)?email\s+(?:to\s+)?(.+)/i', $q, $m)) {
            $to = trim($m[1]);
            return ['type'=>'email', 'to'=>$to, 'url'=>'mailto:'.urlencode($to), 'reply'=>"Opening email to $to."];
        }

        // ── Call ─────────────────────────────────────────────────────────
        if (preg_match('/\b(?:call|dial|phone|ring)\s+(.+)/i', $q, $m)) {
            $who = trim($m[1]);
            return ['type'=>'call', 'contact'=>$who, 'url'=>'tel:'.preg_replace('/[^0-9+]/', '', $who), 'reply'=>"Calling $who."];
        }

        // ── Timer ────────────────────────────────────────────────────────
        if (preg_match('/\b(?:set\s+(?:a\s+)?)?timer\s+(?:for\s+)?(.+)/i', $q, $m)) {
            $dur = trim($m[1]);
            $ms  = $this->parseDuration($dur);
            return ['type'=>'timer', 'ms'=>$ms, 'label'=>"Timer: $dur", 'reply'=>"Timer set for $dur."];
        }

        // ── Reminder / Alarm ─────────────────────────────────────────────
        if (preg_match('/\b(?:remind\s+me|set\s+(?:a\s+)?(?:reminder|alarm))\s+(?:to\s+|about\s+)?(.+)/i', $q, $m)) {
            $what = trim($m[1]);
            // Check for time spec
            $ms   = $this->parseDuration($what) ?: 0;
            return ['type'=>'reminder', 'text'=>$what, 'ms'=>$ms, 'reply'=>"Reminder set: $what."];
        }

        // ── Take a note ──────────────────────────────────────────────────
        if (preg_match('/\b(?:take|make|create|write|save)\s+(?:a\s+)?note[:\s]+(.+)/i', $q, $m)) {
            $note = trim($m[1]);
            return ['type'=>'note', 'text'=>$note, 'reply'=>"Note saved: $note."];
        }

        // ── Copy to clipboard ────────────────────────────────────────────
        if (preg_match('/\b(?:copy|clipboard)\s+(?:this|that|the\s+)?(.+)/i', $q, $m)) {
            $text = trim($m[1]);
            return ['type'=>'clipboard', 'text'=>$text, 'reply'=>"Copied to clipboard."];
        }

        // ── Translate ────────────────────────────────────────────────────
        if (preg_match('/\btranslate\s+(.+?)\s+(?:to|into)\s+(.+)/i', $q, $m)) {
            $text = trim($m[1]); $lang = trim($m[2]);
            $url  = 'https://translate.google.com/?sl=auto&tl='.urlencode($lang).'&text='.urlencode($text);
            return ['type'=>'translate', 'text'=>$text, 'lang'=>$lang, 'url'=>$url, 'reply'=>"Translating to $lang."];
        }

        // ── Take a screenshot / capture ──────────────────────────────────
        if (preg_match('/\b(?:take\s+a?\s*screenshot|screenshot|capture\s+screen)\b/i', $q))
            return ['type'=>'screenshot', 'reply'=>"Taking a screenshot. Note: browser-based screenshot requires page permission."];

        // ── Stop / pause / cancel ────────────────────────────────────────
        if (preg_match('/\b(?:stop|pause|cancel|quit|exit|never\s+mind|forget\s+it)\b/i', $q))
            return ['type'=>'stop', 'reply'=>"Okay, stopping."];

        // ── Volume (informational — browser can't control OS volume) ─────
        if (preg_match('/\b(?:volume\s+(?:up|down|mute)|mute|unmute)\b/i', $q))
            return ['type'=>'volume_hint', 'reply'=>"I can't control system volume from the browser, but you can use your device's volume buttons or keyboard."];

        return null;
    }

    // ── Resolve app/site name → URL ─────────────────────────────────────
    private function resolveUrl(string $target): string {
        $key = strtolower(trim($target));

        // Exact shortcut match
        if (isset(self::$SHORTCUTS[$key])) return self::$SHORTCUTS[$key];

        // Partial match (e.g. "my gmail" → gmail)
        foreach (self::$SHORTCUTS as $name => $url) {
            if (str_contains($key, $name)) return $url;
        }

        // Looks like a domain
        if (preg_match('/\.(com|org|net|io|co|app|dev)\b/i', $target))
            return 'https://' . ltrim($target, '/');

        // Fallback: Google search
        return 'https://google.com/search?q=' . urlencode($target);
    }

    // ── Parse duration string → milliseconds ────────────────────────────
    private function parseDuration(string $text): int {
        $ms = 0;
        if (preg_match('/(\d+)\s*hour/i',   $text, $m)) $ms += (int)$m[1] * 3600000;
        if (preg_match('/(\d+)\s*min/i',    $text, $m)) $ms += (int)$m[1] * 60000;
        if (preg_match('/(\d+)\s*sec/i',    $text, $m)) $ms += (int)$m[1] * 1000;
        if (preg_match('/half\s+(?:an?\s+)?hour/i', $text)) $ms += 1800000;
        return $ms;
    }
}
