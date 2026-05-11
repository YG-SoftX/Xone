<?php
/**
 * SearchIndex — SQLite-backed Inverted Index for YG Search Engine
 *
 * Schema:
 *   pages    → every crawled page with metadata + signals
 *   postings → term → page_id mapping with TF scores
 *   links    → outbound link graph (for PageRank)
 *
 * Runs on any PHP host with SQLite3 (standard on cPanel).
 * No external dependencies. No cloud APIs.
 */
class SearchIndex
{
    private SQLite3 $db;
    private string  $db_path;

    // BM25 constants
    public float $bm25_k1 = 1.5;
    public float $bm25_b  = 0.75;

    public function __construct(string $data_dir)
    {
        $this->db_path = rtrim($data_dir, '/') . '/yg_search.db';
        $this->db = new SQLite3($this->db_path);
        $this->db->busyTimeout(5000);
        $this->db->exec('PRAGMA journal_mode=WAL');
        $this->db->exec('PRAGMA synchronous=NORMAL');
        $this->db->exec('PRAGMA cache_size=10000');
        $this->createSchema();
    }

    // ── Schema ───────────────────────────────────────────────────────────
    private function createSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pages (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                url         TEXT UNIQUE NOT NULL,
                domain      TEXT NOT NULL DEFAULT '',
                title       TEXT NOT NULL DEFAULT '',
                description TEXT NOT NULL DEFAULT '',
                content     TEXT NOT NULL DEFAULT '',
                word_count  INTEGER NOT NULL DEFAULT 0,
                inbound     INTEGER NOT NULL DEFAULT 0,
                page_rank   REAL    NOT NULL DEFAULT 1.0,
                crawled_at  INTEGER NOT NULL DEFAULT 0,
                lang        TEXT NOT NULL DEFAULT 'en',
                status      INTEGER NOT NULL DEFAULT 200
            );

            CREATE TABLE IF NOT EXISTS postings (
                term        TEXT    NOT NULL,
                page_id     INTEGER NOT NULL,
                tf          REAL    NOT NULL DEFAULT 0.0,
                in_title    INTEGER NOT NULL DEFAULT 0,
                in_desc     INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY (term, page_id)
            );

            CREATE TABLE IF NOT EXISTS links (
                from_id     INTEGER NOT NULL,
                to_url      TEXT    NOT NULL,
                PRIMARY KEY (from_id, to_url)
            );

            CREATE INDEX IF NOT EXISTS idx_postings_term   ON postings(term);
            CREATE INDEX IF NOT EXISTS idx_postings_page   ON postings(page_id);
            CREATE INDEX IF NOT EXISTS idx_pages_domain    ON pages(domain);
            CREATE INDEX IF NOT EXISTS idx_pages_crawled   ON pages(crawled_at);
            CREATE INDEX IF NOT EXISTS idx_links_from      ON links(from_id);
        ");
    }

    // ── Index a page ─────────────────────────────────────────────────────
    /**
     * @param array $page {url, title, description, content, domain, status, lang}
     * @return int  page_id
     */
    public function indexPage(array $page): int
    {
        $url   = $page['url']         ?? '';
        $title = $page['title']       ?? '';
        $desc  = $page['description'] ?? '';
        $body  = $page['content']     ?? '';
        $dom   = $page['domain']      ?? parse_url($url, PHP_URL_HOST) ?? '';
        $lang  = $page['lang']        ?? 'en';
        $status = (int) ($page['status'] ?? 200);

        $allText   = $title . ' ' . $desc . ' ' . $body;
        $wordCount = str_word_count(strip_tags($allText));
        $now       = time();

        // Upsert page
        $stmt = $this->db->prepare("
            INSERT INTO pages (url, domain, title, description, content, word_count, crawled_at, lang, status)
            VALUES (:url, :dom, :title, :desc, :content, :wc, :now, :lang, :status)
            ON CONFLICT(url) DO UPDATE SET
                title       = excluded.title,
                description = excluded.description,
                content     = excluded.content,
                word_count  = excluded.word_count,
                crawled_at  = excluded.crawled_at,
                lang        = excluded.lang,
                status      = excluded.status
        ");
        $stmt->bindValue(':url',     $url);
        $stmt->bindValue(':dom',     $dom);
        $stmt->bindValue(':title',   $title);
        $stmt->bindValue(':desc',    $desc);
        $stmt->bindValue(':content', mb_substr($body, 0, 50000)); // cap at 50KB
        $stmt->bindValue(':wc',      $wordCount, SQLITE3_INTEGER);
        $stmt->bindValue(':now',     $now,       SQLITE3_INTEGER);
        $stmt->bindValue(':lang',    $lang);
        $stmt->bindValue(':status',  $status,    SQLITE3_INTEGER);
        $stmt->execute();

        $idStmt = $this->db->prepare("SELECT id FROM pages WHERE url = :url");
        $idStmt->bindValue(':url', $url);
        $idRes  = $idStmt->execute();
        $idRow  = $idRes ? $idRes->fetchArray(SQLITE3_NUM) : null;
        $pageId = $idRow ? (int) $idRow[0] : (int) $this->db->lastInsertRowID();

        // Remove old postings for this page (use prepared statement)
        $delStmt = $this->db->prepare("DELETE FROM postings WHERE page_id = :pid");
        $delStmt->bindValue(':pid', $pageId, SQLITE3_INTEGER);
        $delStmt->execute();

        // Tokenize and index
        $titleWords = $this->tokenize($title);
        $descWords  = $this->tokenize($desc);
        $bodyWords  = $this->tokenize($body);
        $allWords   = array_merge($titleWords, $descWords, $bodyWords);

        if (empty($allWords)) return $pageId;

        $freq = array_count_values($allWords);
        $total = count($allWords);
        $titleSet = array_flip($titleWords);
        $descSet  = array_flip($descWords);

        $this->db->exec('BEGIN TRANSACTION');
        $postStmt = $this->db->prepare("
            INSERT OR REPLACE INTO postings (term, page_id, tf, in_title, in_desc)
            VALUES (:term, :pid, :tf, :it, :id)
        ");
        foreach ($freq as $term => $count) {
            if (strlen($term) < 2 || strlen($term) > 40) continue;
            $tf = $count / max($total, 1);
            $postStmt->bindValue(':term',  $term);
            $postStmt->bindValue(':pid',   $pageId, SQLITE3_INTEGER);
            $postStmt->bindValue(':tf',    $tf,     SQLITE3_FLOAT);
            $postStmt->bindValue(':it',    isset($titleSet[$term]) ? 1 : 0, SQLITE3_INTEGER);
            $postStmt->bindValue(':id',    isset($descSet[$term])  ? 1 : 0, SQLITE3_INTEGER);
            $postStmt->execute();
        }
        $this->db->exec('COMMIT');

        return $pageId;
    }

    // ── Store outbound links ──────────────────────────────────────────────
    public function storeLinks(int $fromId, array $toUrls): void
    {
        if (empty($toUrls)) return;
        $this->db->exec('BEGIN TRANSACTION');
        $stmt = $this->db->prepare(
            "INSERT OR IGNORE INTO links (from_id, to_url) VALUES (:fid, :url)"
        );
        foreach (array_slice($toUrls, 0, 200) as $url) {
            $stmt->bindValue(':fid', $fromId, SQLITE3_INTEGER);
            $stmt->bindValue(':url', $url);
            $stmt->execute();
        }
        $this->db->exec('COMMIT');

        // Update inbound link counts for targets
        $this->updateInboundCounts($toUrls);
    }

    private function updateInboundCounts(array $urls): void
    {
        $stmt = $this->db->prepare(
            "UPDATE pages SET inbound = inbound + 1 WHERE url = :url"
        );
        foreach ($urls as $url) {
            $stmt->bindValue(':url', $url);
            $stmt->execute();
            $stmt->reset();
        }
    }

    // ── BM25 Search ──────────────────────────────────────────────────────
    /**
     * Search the index and return scored results.
     *
     * @param  array  $terms      Tokenized query terms
     * @param  int    $limit      Max results
     * @param  array  $filters    Optional: ['domain' => '...', 'lang' => '...']
     * @return array  [{page_id, url, title, description, domain, score, inbound, crawled_at}]
     */
    public function search(array $terms, int $limit = 20, array $filters = []): array
    {
        if (empty($terms)) return [];

        $N      = (int) $this->db->querySingle("SELECT COUNT(*) FROM pages WHERE status = 200");
        $avgdl  = (float) ($this->db->querySingle("SELECT AVG(word_count) FROM pages WHERE status = 200") ?? 1);
        if ($N === 0) return [];

        // Collect candidate page IDs from postings
        $candidates = [];

        // Prepare reusable statements outside the loop for performance
        $searchStmt = $this->db->prepare(
            "SELECT p.page_id, p.tf, p.in_title, p.in_desc,
                    pg.word_count, pg.inbound, pg.page_rank,
                    pg.url, pg.title, pg.description, pg.content,
                    pg.domain, pg.crawled_at
             FROM postings p
             JOIN pages pg ON pg.id = p.page_id
             WHERE p.term = :term AND pg.status = 200"
        );
        $dfStmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT page_id) FROM postings WHERE term = :term"
        );

        foreach ($terms as $term) {
            $searchStmt->bindValue(':term', $term);
            $res = $searchStmt->execute();
            if (!$res) { $searchStmt->reset(); continue; }

            // Document frequency for IDF
            $dfStmt->bindValue(':term', $term);
            $dfRes = $dfStmt->execute();
            $df    = $dfRes ? (int) $dfRes->fetchArray(SQLITE3_NUM)[0] : 0;
            $dfStmt->reset();
            $idf = log(($N - $df + 0.5) / ($df + 0.5) + 1);

            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                $pid  = (int) $row['page_id'];
                $dl   = max((int) $row['word_count'], 1);
                $tf   = (float) $row['tf'];

                // BM25 score
                $bm25 = $idf * ($tf * ($this->bm25_k1 + 1))
                      / ($tf + $this->bm25_k1 * (1 - $this->bm25_b + $this->bm25_b * $dl / $avgdl));

                // Boost for title / description match
                if ($row['in_title']) $bm25 *= 3.0;
                elseif ($row['in_desc']) $bm25 *= 1.8;

                if (!isset($candidates[$pid])) {
                    $candidates[$pid] = [
                        'page_id'     => $pid,
                        'url'         => $row['url'],
                        'title'       => $row['title'],
                        'description' => $row['description'],
                        'content'     => $row['content'] ?? '',
                        'domain'      => $row['domain'],
                        'score'       => 0.0,
                        'inbound'     => (int) $row['inbound'],
                        'crawled_at'  => (int) $row['crawled_at'],
                        'page_rank'   => (float) $row['page_rank'],
                    ];
                }
                $candidates[$pid]['score'] += $bm25;
            }
            $searchStmt->reset();
        }

        if (empty($candidates)) return [];

        // Apply domain filter
        if (!empty($filters['domain'])) {
            $fd = strtolower($filters['domain']);
            $candidates = array_filter($candidates, fn($c) => str_contains(strtolower($c['domain']), $fd));
        }

        // Combine BM25 with PageRank signal
        foreach ($candidates as &$c) {
            $pr_boost   = log1p($c['inbound']) * 0.15;     // inbound links signal
            $pr_boost  += $c['page_rank'] * 0.1;           // stored PageRank
            $freshness  = $this->freshnessScore($c['crawled_at']); // recency bonus
            $c['score'] = $c['score'] * (1 + $pr_boost) + $freshness;
        }
        unset($c);

        // Sort by score descending
        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_values(array_slice($candidates, 0, $limit));
    }

    private function freshnessScore(int $crawled_at): float
    {
        $age_days = (time() - $crawled_at) / 86400;
        return max(0.0, 0.2 - $age_days * 0.002); // decays over 100 days
    }

    // ── PageRank (simplified iterative) ─────────────────────────────────
    /**
     * Compute a simplified PageRank over all indexed pages.
     * Run this from the admin/scheduler — not per-request.
     */
    public function computePageRank(int $iterations = 20, float $damping = 0.85): void
    {
        $pages = [];
        $res = $this->db->query("SELECT id FROM pages WHERE status = 200");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $pages[] = (int) $row['id'];
        }
        $N = count($pages);
        if ($N === 0) return;

        // Init PR
        $pr = array_fill_keys($pages, 1.0 / $N);

        for ($i = 0; $i < $iterations; $i++) {
            $newPr = array_fill_keys($pages, (1 - $damping) / $N);

            foreach ($pages as $pid) {
                // Get outbound link count
                $outStmt = $this->db->prepare("SELECT COUNT(*) FROM links WHERE from_id = :pid");
                $outStmt->bindValue(':pid', $pid, SQLITE3_INTEGER);
                $outRes = $outStmt->execute();
                $out_count = $outRes ? (int)$outRes->fetchArray(SQLITE3_NUM)[0] : 0;
                if ($out_count === 0) continue;

                // Distribute PR to linked pages that exist in the index
                $linkedStmt = $this->db->prepare(
                    "SELECT p.id FROM links l
                     JOIN pages p ON p.url = l.to_url
                     WHERE l.from_id = :pid AND p.status = 200"
                );
                $linkedStmt->bindValue(':pid', $pid, SQLITE3_INTEGER);
                $linked = $linkedStmt->execute();
                while ($row = $linked->fetchArray(SQLITE3_ASSOC)) {
                    $tid = (int) $row['id'];
                    if (isset($newPr[$tid])) {
                        $newPr[$tid] += $damping * ($pr[$pid] / $out_count);
                    }
                }
            }
            $pr = $newPr;
        }

        // Store back using prepared statement (no string interpolation)
        $this->db->exec('BEGIN TRANSACTION');
        $prStmt = $this->db->prepare('UPDATE pages SET page_rank = :pr WHERE id = :id');
        foreach ($pr as $pid => $score) {
            $prStmt->bindValue(':pr', round($score, 6), SQLITE3_FLOAT);
            $prStmt->bindValue(':id', (int) $pid,       SQLITE3_INTEGER);
            $prStmt->execute();
            $prStmt->reset();
        }
        $this->db->exec('COMMIT');
    }

    // ── Utilities ────────────────────────────────────────────────────────
    public function getPage(string $url): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pages WHERE url = :url');
        $stmt->bindValue(':url', $url);
        $res = $stmt->execute();
        return $res ? ($res->fetchArray(SQLITE3_ASSOC) ?: null) : null;
    }

    public function getPageById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pages WHERE id = :id');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $res = $stmt->execute();
        return $res ? ($res->fetchArray(SQLITE3_ASSOC) ?: null) : null;
    }

    public function isIndexed(string $url): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM pages WHERE url = :url');
        $stmt->bindValue(':url', $url);
        $res = $stmt->execute();
        return $res && $res->fetchArray(SQLITE3_NUM) !== false;
    }

    public function deleteUrl(string $url): void
    {
        $idStmt = $this->db->prepare('SELECT id FROM pages WHERE url = :url');
        $idStmt->bindValue(':url', $url);
        $idRes  = $idStmt->execute();
        $idRow  = $idRes ? $idRes->fetchArray(SQLITE3_NUM) : null;
        $id     = $idRow ? (int) $idRow[0] : 0;

        if ($id > 0) {
            $del1 = $this->db->prepare('DELETE FROM postings WHERE page_id = :id');
            $del1->bindValue(':id', $id, SQLITE3_INTEGER);
            $del1->execute();

            $del2 = $this->db->prepare('DELETE FROM links WHERE from_id = :id');
            $del2->bindValue(':id', $id, SQLITE3_INTEGER);
            $del2->execute();
        }

        $delPage = $this->db->prepare('DELETE FROM pages WHERE url = :url');
        $delPage->bindValue(':url', $url);
        $delPage->execute();
    }

    public function stats(): array
    {
        return [
            'total_pages'    => (int)   $this->db->querySingle("SELECT COUNT(*) FROM pages"),
            'indexed_pages'  => (int)   $this->db->querySingle("SELECT COUNT(*) FROM pages WHERE status = 200"),
            'total_terms'    => (int)   $this->db->querySingle("SELECT COUNT(DISTINCT term) FROM postings"),
            'total_postings' => (int)   $this->db->querySingle("SELECT COUNT(*) FROM postings"),
            'total_links'    => (int)   $this->db->querySingle("SELECT COUNT(*) FROM links"),
            'domains'        => (int)   $this->db->querySingle("SELECT COUNT(DISTINCT domain) FROM pages"),
            'avg_word_count' => (float) ($this->db->querySingle("SELECT AVG(word_count) FROM pages") ?? 0),
            'db_size_kb'     => file_exists($this->db_path) ? round(filesize($this->db_path) / 1024) : 0,
            'last_crawled'   => (int)   $this->db->querySingle("SELECT MAX(crawled_at) FROM pages"),
        ];
    }

    public function recentPages(int $limit = 20): array
    {
        $limit = max(1, min((int)$limit, 500));
        $stmt = $this->db->prepare(
            "SELECT url, title, domain, crawled_at, inbound FROM pages
             WHERE status = 200 ORDER BY crawled_at DESC LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, SQLITE3_INTEGER);
        $res  = $stmt->execute();
        $rows = [];
        while ($r = $res->fetchArray(SQLITE3_ASSOC)) $rows[] = $r;
        return $rows;
    }

    public function topDomains(int $limit = 10): array
    {
        $limit = max(1, min((int)$limit, 100));
        $stmt = $this->db->prepare(
            "SELECT domain, COUNT(*) as page_count, SUM(inbound) as total_inbound
             FROM pages WHERE status = 200
             GROUP BY domain ORDER BY page_count DESC LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, SQLITE3_INTEGER);
        $res  = $stmt->execute();
        $rows = [];
        while ($r = $res->fetchArray(SQLITE3_ASSOC)) $rows[] = $r;
        return $rows;
    }

    // ── Tokenizer ────────────────────────────────────────────────────────
    public static array $stopWords = [
        'the','a','an','and','or','but','in','on','at','to','for','of','with',
        'as','by','from','is','are','was','were','be','been','have','has','had',
        'do','does','did','will','would','could','should','may','might','can',
        'it','its','this','that','these','those','i','you','he','she','we','they',
        'not','no','so','if','then','than','more','also','just','up','about',
        'into','over','after','all','what','how','when','where','who','which',
    ];

    public function tokenize(string $text): array
    {
        $text  = strtolower(strip_tags($text));
        $text  = preg_replace('/[^a-z0-9\s\-]/', ' ', $text) ?? $text;
        $words = preg_split('/\s+/', $text) ?: [];
        $out   = [];
        foreach ($words as $w) {
            $w = trim($w, '-');
            if (strlen($w) < 2 || strlen($w) > 40) continue;
            if (in_array($w, self::$stopWords, true)) continue;
            $out[] = $this->stem($w);
        }
        return $out;
    }

    // Simple suffix-based stemmer (Porter-lite — good enough for BM25)
    private function stem(string $word): string
    {
        if (strlen($word) <= 4) return $word;
        $suffixes = ['ational'=>'ate','tional'=>'tion','enci'=>'ence','anci'=>'ance',
                     'iser'=>'ise','izer'=>'ize','alism'=>'al','aliti'=>'al',
                     'fulness'=>'ful','ousness'=>'ous','iveness'=>'ive',
                     'ication'=>'ic','ations'=>'ate','ments'=>'ment',
                     'ment'=>'','ness'=>'','less'=>'','able'=>'','ible'=>'',
                     'ing'=>'','tion'=>'te','ers'=>'er','ies'=>'y',
                     'ed'=>'','es'=>'','s'=>''];
        foreach ($suffixes as $sfx => $rep) {
            if (str_ends_with($word, $sfx) && strlen($word) - strlen($sfx) >= 3) {
                return substr($word, 0, strlen($word) - strlen($sfx)) . $rep;
            }
        }
        return $word;
    }
}
