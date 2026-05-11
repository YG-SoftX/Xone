<?php

class Indexer {
    private $db;

    public function __construct() {
        $this->db = new PDO('sqlite:database/search.sqlite');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initSchema();
    }

    private function initSchema() {
        $this->db->exec("CREATE TABLE IF NOT EXISTS results (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT,
            url TEXT UNIQUE,
            snippet TEXT,
            category TEXT,
            node_type TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_title ON results(title)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_category ON results(category)");
    }

    public function index($title, $url, $snippet, $category = 'all', $node_type = 'general') {
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO results (title, url, snippet, category, node_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $url, $snippet, $category, $node_type]);
        echo "Indexed: $title\n";
    }

    public function seed() {
        $this->index('YGXONE: The Sovereign Identity Node', 'https://account.ygxone.com', 'Manage your sovereign identity node, security handshake, and neural payloads in one place.', 'all', 'iam');
        $this->index('YG Mail: Decentralized Communication Hub', 'https://mail.ygxone.com', 'Encrypted communication on top of the Ygxone neural network. No metadata tracking.', 'all', 'mail');
        $this->index('YG Drive: Sovereign Cloud Storage', 'https://drive.ygxone.com', 'Store your digital artifacts in a geofenced, encrypted storage node with absolute ownership.', 'all', 'drive');
        $this->index('YG Pay: Neural Settlement Protocol', 'https://pay.ygxone.com', 'Execute instant, double-entry global settlements with Fonepay and regional fiat nodes.', 'all', 'pay');
        $this->index('Bloodmate: National Emergency Presence', 'https://bloodmate.com.np', 'A resilient national emergency response ecosystem for Nepal, integrating NFC and IoT.', 'news', 'emergency');
        $this->index('Wikipedia: Sovereign Knowledge Node', 'https://ne.wikipedia.org', 'The free encyclopedia that anyone can edit, now federated into the YGX knowledge graph.', 'all', 'knowledge');
        $this->index('YG AI: Neural Logic Module', 'https://ai.ygxone.com', 'Advanced AI reasoning and ecosystem orchestration via decentralized LLM nodes.', 'all', 'ai');
        $this->index('Nepal Law Commission', 'https://www.lawcommission.gov.np', 'The official legal portal for the Sovereign Republic of Nepal, indexed for quick legal search.', 'all', 'gov');
    }
}

$indexer = new Indexer();
$indexer->seed();
echo "Search Index populated successfully.\n";
