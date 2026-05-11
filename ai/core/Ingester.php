<?php
/**
 * Ingester — Multi-format document ingestion for Yuga
 *
 * Extracts text from any document type and feeds it into Brain's
 * knowledge corpus. No external libraries. Pure PHP.
 *
 * Supported formats:
 *   .txt  .md   — plain text, strip markdown
 *   .csv        — tabular → readable sentences
 *   .json       — flatten nested structures to text
 *   .pdf        — stream-based text extraction (no pdftotext needed)
 *   .docx .odt  — ZIP + XML extraction (PHP ZipArchive)
 *   .html .htm  — strip tags, extract content
 *   URL         — fetch + extract (Brain::learnFromURL already exists)
 *
 * Usage:
 *   $ingester = new Ingester($brain);
 *   $result   = $ingester->ingestFile('/path/to/document.pdf', 'HR Policy');
 *   $result   = $ingester->ingestText($rawText, 'Manual entry');
 *   $result   = $ingester->ingestUrl('https://docs.example.com/api');
 */
class Ingester {

    private Brain  $brain;
    public  int    $maxChars   = 100000; // cap per document
    public  int    $trainSteps = 15000;

    // Supported MIME / extensions
    private static array $SUPPORTED = [
        'txt','md','csv','json','pdf','docx','odt','html','htm','xml','rtf',
    ];

    public function __construct(Brain $brain) {
        $this->brain = $brain;
    }

    // ── Ingest from file path ───────────────────────────────────────────
    public function ingestFile(string $path, string $source = ''): array {
        if (!file_exists($path)) {
            return ['error' => "File not found: $path"];
        }

        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $name = basename($path);

        if (!in_array($ext, self::$SUPPORTED)) {
            return ['error' => "Unsupported file type: .$ext"];
        }

        $text = $this->extractText($path, $ext);
        if (!$text) {
            return ['error' => "Could not extract text from $name"];
        }

        return $this->ingestText($text, $source ?: $name);
    }

    // ── Ingest from raw text ────────────────────────────────────────────
    public function ingestText(string $text, string $source = ''): array {
        $text = $this->clean($text);
        if (strlen($text) < 20) {
            return ['error' => 'Not enough text to ingest (min 20 chars)'];
        }

        // Cap at maxChars
        if (strlen($text) > $this->maxChars) {
            $text = substr($text, 0, $this->maxChars);
        }

        $result = $this->brain->learn($text, $this->trainSteps);

        return array_merge($result, [
            'source'  => $source,
            'chars'   => strlen($text),
            'ingested' => true,
        ]);
    }

    // ── Ingest from URL ─────────────────────────────────────────────────
    public function ingestUrl(string $url, bool $crawl = false): array {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['error' => 'Invalid URL'];
        }

        if ($crawl) {
            $result = $this->brain->learnFromSite($url, 30);
        } else {
            $result = $this->brain->learnFromURL($url);
        }

        return array_merge($result, ['source' => $url, 'ingested' => true]);
    }

    // ── Batch ingest: multiple files ────────────────────────────────────
    public function ingestBatch(array $paths): array {
        $results = [];
        foreach ($paths as $path) {
            $results[$path] = $this->ingestFile($path);
        }
        return ['batch' => $results, 'total' => count($paths)];
    }

    // ── Ingest from $_FILES upload ──────────────────────────────────────
    public function ingestUpload(array $file): array {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['error' => 'Invalid file upload'];
        }

        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::$SUPPORTED)) {
            return ['error' => "Unsupported file type: .$ext"];
        }

        $text = $this->extractText($file['tmp_name'], $ext);
        if (!$text) {
            return ['error' => 'Could not extract text from uploaded file'];
        }

        return $this->ingestText($text, $file['name']);
    }

    // =================================================================
    // TEXT EXTRACTION — one method per format
    // =================================================================

    private function extractText(string $path, string $ext): string {
        return match ($ext) {
            'txt', 'md', 'rtf' => $this->fromPlainText($path),
            'html', 'htm'      => $this->fromHtml($path),
            'xml'              => $this->fromXml($path),
            'csv'              => $this->fromCsv($path),
            'json'             => $this->fromJson($path),
            'pdf'              => $this->fromPdf($path),
            'docx', 'odt'      => $this->fromDocx($path),
            default            => $this->fromPlainText($path),
        };
    }

    // ── Plain text / Markdown ──────────────────────────────────────────
    private function fromPlainText(string $path): string {
        $text = file_get_contents($path) ?: '';
        // Strip markdown syntax
        $text = preg_replace('/#{1,6}\s+/', '', $text);       // headings
        $text = preg_replace('/\*{1,2}(.+?)\*{1,2}/', '$1', $text); // bold/italic
        $text = preg_replace('/`{1,3}[^`]*`{1,3}/', '', $text);     // code
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text); // links
        $text = preg_replace('/^[-*+]\s+/m', '', $text);             // list bullets
        return $text;
    }

    // ── HTML ───────────────────────────────────────────────────────────
    private function fromHtml(string $path): string {
        $html = file_get_contents($path) ?: '';
        $html = preg_replace('/<(script|style|nav|footer|header)[^>]*>.*?<\/\1>/is', '', $html);
        $html = preg_replace('/<(p|div|h[1-6]|li|tr|br)[^>]*>/i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return preg_replace('/\s+/', ' ', $text);
    }

    // ── XML ────────────────────────────────────────────────────────────
    private function fromXml(string $path): string {
        $xml  = file_get_contents($path) ?: '';
        $text = preg_replace('/<[^>]+>/', ' ', $xml);
        return html_entity_decode(preg_replace('/\s+/', ' ', $text));
    }

    // ── CSV → readable sentences ───────────────────────────────────────
    private function fromCsv(string $path): string {
        $handle = fopen($path, 'r');
        if (!$handle) return '';

        $headers = fgetcsv($handle);
        if (!$headers) { fclose($handle); return ''; }

        $lines = [];
        $row   = 0;
        while (($data = fgetcsv($handle)) !== false && $row < 500) {
            // Build a natural-language sentence per row
            $parts = [];
            foreach ($headers as $i => $header) {
                $val = trim($data[$i] ?? '');
                if ($val !== '') {
                    $parts[] = trim($header) . ' is ' . $val;
                }
            }
            if ($parts) {
                $lines[] = implode(', ', $parts) . '.';
            }
            $row++;
        }

        fclose($handle);
        return implode(' ', $lines);
    }

    // ── JSON → readable text ───────────────────────────────────────────
    private function fromJson(string $path): string {
        $raw  = file_get_contents($path) ?: '';
        $data = json_decode($raw, true);
        if (!$data) return strip_tags($raw);
        return $this->flattenJson($data);
    }

    private function flattenJson(mixed $data, string $prefix = '', int $depth = 0): string {
        if ($depth > 5) return '';
        $lines = [];

        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $label = $prefix ? $prefix . ' ' . $key : (string)$key;
                if (is_scalar($val)) {
                    $lines[] = $label . ' is ' . $val . '.';
                } elseif (is_array($val)) {
                    $lines[] = $this->flattenJson($val, $label, $depth + 1);
                }
            }
        } elseif (is_scalar($data)) {
            $lines[] = ($prefix ?: 'value') . ' is ' . $data . '.';
        }

        return implode(' ', array_filter($lines));
    }

    // ── PDF — stream-based text extraction (no external tools) ────────
    // Works on most text-based PDFs. Image-only PDFs return empty.
    private function fromPdf(string $path): string {
        $raw = file_get_contents($path) ?: '';
        if (!$raw) return '';

        $text = '';

        // Method 1: Extract text from stream objects
        preg_match_all('/stream(.*?)endstream/s', $raw, $streams);
        foreach ($streams[1] as $stream) {
            $decoded = @gzuncompress($stream);
            if ($decoded === false) $decoded = $stream;

            // Extract readable text from BT...ET blocks
            preg_match_all('/BT(.*?)ET/s', $decoded, $blocks);
            foreach ($blocks[1] as $block) {
                // Extract strings from Tj and TJ operators
                preg_match_all('/\(([^)]+)\)\s*Tj/', $block, $tj);
                foreach ($tj[1] as $s) $text .= $this->pdfDecode($s) . ' ';

                preg_match_all('/\[([^\]]+)\]\s*TJ/', $block, $TJ);
                foreach ($TJ[1] as $s) {
                    preg_match_all('/\(([^)]+)\)/', $s, $parts);
                    foreach ($parts[1] as $p) $text .= $this->pdfDecode($p) . ' ';
                }
            }
        }

        // Method 2: Fallback — extract any printable text runs
        if (strlen(trim($text)) < 50) {
            preg_match_all('/[\x20-\x7E]{4,}/', $raw, $printable);
            $text = implode(' ', $printable[0]);
        }

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function pdfDecode(string $s): string {
        // Decode common PDF escape sequences
        $s = str_replace(['\\n','\\r','\\t','\\\\','\\(','\\)'], [' ',' ',' ','\\','(',')'], $s);
        return preg_replace('/[^\x20-\x7E]/', ' ', $s);
    }

    // ── DOCX / ODT — ZIP + XML extraction ─────────────────────────────
    private function fromDocx(string $path): string {
        if (!class_exists('ZipArchive')) {
            // Fallback: try reading raw XML if ZipArchive not available
            $raw  = file_get_contents($path) ?: '';
            $text = preg_replace('/<[^>]+>/', ' ', $raw);
            return html_entity_decode(preg_replace('/\s+/', ' ', $text));
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) return '';

        // DOCX uses word/document.xml; ODT uses content.xml
        $xmlFile = 'word/document.xml';
        $content = $zip->getFromName($xmlFile);
        if (!$content) {
            $content = $zip->getFromName('content.xml'); // ODT
        }
        $zip->close();

        if (!$content) return '';

        // Strip XML tags, decode entities
        $text = preg_replace('/<w:p[ >]/', "\n", $content); // paragraph breaks
        $text = preg_replace('/<[^>]+>/', ' ', $text);
        return html_entity_decode(preg_replace('/\s+/', ' ', $text));
    }

    // ── Clean extracted text ───────────────────────────────────────────
    private function clean(string $text): string {
        // Remove non-printable except newlines
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim(strtolower($text));
    }
}
