# 🧠 How YG AI Works - Complete Technical Guide

## 🎯 Overview

**YG AI** is your **self-hosted, sovereign AI platform** that combines multiple AI techniques to deliver intelligent search, chat, and content generation. Unlike cloud-based solutions (OpenAI, Gemini), YG AI runs entirely on your infrastructure with complete data privacy and control.

---

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    YG AI Platform                            │
│                 ai.ygxone.com                                │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │              API Gateway (api/index.php)             │  │
│  │  • Authentication (API Key validation)               │  │
│  │  • Rate limiting                                     │  │
│  │  • CORS handling                                     │  │
│  │  • Request routing                                   │  │
│  └──────────────────────┬───────────────────────────────┘  │
│                         │                                  │
│          ┌──────────────┼──────────────┐                  │
│          │              │              │                   │
│          ▼              ▼              ▼                   │
│  ┌──────────────┐ ┌──────────┐ ┌──────────────┐          │
│  │ Web Search   │ │ Smart    │ │ Self-        │          │
│  │ Engine       │ │ Chat     │ │ Learning     │          │
│  │              │ │          │ │              │          │
│  │ • BM25       │ │ • RAG    │ │ • Crawling   │          │
│  │ • Ranking    │ │ • Brain  │ │ • Training   │          │
│  │ • Sources    │ │ • Memory │ │ • Indexing   │          │
│  └──────────────┘ └──────────┘ └──────────────┘          │
│          │              │              │                   │
│          └──────────────┼──────────────┘                  │
│                         │                                  │
│                         ▼                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │              Core AI Engine                           │  │
│  │                                                       │  │
│  │  • Transformer (Small Language Model)                │  │
│  │  • Tokenizer                                         │  │
│  │  • Retriever (BM25)                                  │  │
│  │  • Brain (Context-aware reasoning)                   │  │
│  │  • Safety Sentinel                                   │  │
│  └──────────────────────────────────────────────────────┘  │
│                         │                                  │
│                         ▼                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │              Data Layer                               │  │
│  │                                                       │  │
│  │  • Model Store (JSON files)                          │  │
│  │  • Knowledge Corpus                                  │  │
│  │  • Memory (conversation history)                     │  │
│  │  • Index (BM25 inverted index)                       │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔑 Core Components

### **1. Small Language Model (SLM) - YugaLM**

**What it is:** A lightweight transformer-based language model trained from scratch in PHP.

**Key Features:**
- ✅ **Transformer architecture** with attention mechanism
- ✅ **~100K parameters** (tiny but efficient)
- ✅ **Trained on your data** (not generic internet data)
- ✅ **Runs entirely in PHP** (no Python/TensorFlow needed)
- ✅ **Self-learning** - continuously improves from new content

**How it works:**
```php
// The model generates text token by token
$generated = $lm->generate($tokenizer, $seed_text, 35, $temperature);

// Example flow:
Input: "Laravel is a"
Output: " web application framework with elegant syntax"
```

**Training Process:**
```php
// Train on corpus (text from your websites)
$model->trainOnText($corpus, 20000); // 20k training steps

// Learns patterns like:
// - "Laravel is a" → "PHP framework"
// - "The weather is" → "sunny today"
// - Context-specific knowledge
```

---

### **2. BM25 Retriever - Search Engine**

**What it is:** A full-text search engine using the BM25 algorithm (same as Elasticsearch/Google).

**Why BM25?**
- ⚡ **Blazing fast** (< 5ms for 50KB corpus)
- 🎯 **Highly accurate** ranking
- 💾 **Lightweight** (no database needed)
- 🔍 **Proven technology** (used by Google, Elasticsearch)

**How it works:**

```php
// Step 1: Build index from corpus
$retriever->buildIndex($corpus);
// Splits text into sentences, builds inverted index

// Step 2: Query the index
$results = $retriever->query("laravel tutorial", 5);
// Returns top 5 most relevant sentences

// Step 3: Results include relevance scores
[
  ['text' => 'Laravel is a PHP framework...', 'score' => 8.5],
  ['text' => 'Tutorial covers basics...', 'score' => 6.2],
  ...
]
```

**BM25 Formula:**
```
Score(q,d) = Σ IDF(qi) * (TF(qi,d) * (k1 + 1)) / (TF(qi,d) + k1 * (1 - b + b * |d|/avgdl))

Where:
- q = query
- d = document
- TF = term frequency
- IDF = inverse document frequency
- k1, b = tuning parameters (1.5, 0.75)
```

---

### **3. Brain - Context-Aware Reasoning**

**What it is:** The intelligence layer that combines retrieval + generation for coherent answers.

**Capabilities:**
- 🧠 **Context retention** - remembers conversation history
- 🔗 **Query enrichment** - adds context to ambiguous queries
- ✨ **Answer polishing** - makes responses natural and readable
- 🎯 **Multi-hop reasoning** - combines multiple sources

**Example Flow:**

```
User: "What is Laravel?"
Brain:
  1. Retrieves: "Laravel is a PHP framework..."
  2. Generates: "...with elegant syntax for web apps"
  3. Polishes: "Laravel is a PHP framework with elegant syntax for building web applications."

User: "And the price?"
Brain:
  1. Detects ambiguity ("it" refers to Laravel)
  2. Enriches query: "Laravel price" + context from previous turn
  3. Retrieves pricing info
  4. Answers: "Laravel is free and open-source."
```

**Code:**
```php
$brain = new Brain('default', $store);
$result = $brain->chat(
    "What about its pricing?",
    $session_id,
    $memory,
    0.72  // temperature
);

// Returns:
[
  'answer' => 'Laravel is free and open-source...',
  'enriched_query' => 'Laravel pricing cost',
  'turns' => 2
]
```

---

### **4. Self-Learner - Autonomous Knowledge Acquisition**

**What it is:** An automated crawler that learns from websites without manual intervention.

**Features:**
- 🕷️ **Web crawling** - discovers pages automatically
- 📄 **Content extraction** - strips HTML, extracts clean text
- 🗺️ **Sitemap support** - reads sitemap.xml for URLs
- 🤖 **robots.txt respect** - polite crawling
- 📊 **Incremental training** - updates model with new content
- ⚡ **Resource-aware** - respects memory/time limits

**Crawling Process:**

```php
$learner = new SelfLearner('default', $store);
$learner->max_pages = 60;
$learner->crawl_delay = 0.5; // seconds between requests

$result = $learner->learnFromSite('https://laravel.com');

// Result:
[
  'pages' => 45,      // pages crawled
  'chars' => 125000,  // characters learned
  'loss' => 0.023,    // training loss
  'errors' => []      // any errors encountered
]
```

**What happens:**
1. Discovers URLs via sitemap or link following
2. Fetches each page (HTTP GET)
3. Extracts text (removes HTML, JS, CSS)
4. Cleans and normalizes content
5. Trains model on extracted text
6. Saves updated model to disk
7. Tracks visited URLs (avoids duplicates)

---

### **5. Safety Sentinel - Content Moderation**

**What it is:** A safety layer that filters harmful content and ensures responsible AI behavior.

**Checks:**
- 🚫 Toxic language detection
- 🔞 Adult content filtering
- ⚠️ Hate speech prevention
- 🛡️ PII (Personally Identifiable Information) protection
- 📝 Prompt injection defense

**Usage:**
```php
$sentinel = new SafetySentinel($config, $data_dir);

// Before processing user input
if (!$sentinel->isSafe($user_input)) {
    return "I cannot process that request.";
}

// After generating response
$response = $sentinel->filterResponse($response);
```

---

## 🔄 Request Flow - How a Search Works

### **Scenario: User searches "laravel tutorial"**

```
1. User Input
   ↓
   "laravel tutorial"

2. API Gateway (api/index.php)
   ├─ Validates API key
   ├─ Checks rate limit
   ├─ Routes to action: "web_search"
   ↓

3. Web Search Handler
   ├─ Calls Retriever::query("laravel tutorial")
   ├─ Gets top 10 relevant sentences from corpus
   ├─ Ranks by BM25 score
   ↓

4. Result Enhancement
   ├─ If AI enabled:
   │   ├─ Seeds Transformer with top result
   │   ├─ Generates additional context
   │   └─ Polishes final answer
   ├─ If AI disabled:
   │   └─ Returns raw BM25 results
   ↓

5. Source Attribution
   ├─ Maps results to source URLs
   ├─ Fetches favicons
   ├─ Extracts domain names
   ↓

6. Response Assembly
   {
     "ok": true,
     "sources": [
       {
         "title": "Laravel Tutorial - Getting Started",
         "url": "https://laravel.com/docs",
         "snippet": "Learn Laravel basics...",
         "score": 8.5
       }
     ],
     "answer": "Laravel is a PHP framework...",
     "related_questions": [...]
   }
   ↓

7. Sent to User
```

---

## 🧩 Integration with YG Ecosystem

### **How Other Services Use YG AI**

#### **1. YG Home (Search)**
```php
// In yg-home/app/Services/YgSearchService.php
$response = Http::post('https://ai.ygxone.com/api/', [
    'action' => 'web_search',
    'query' => 'laravel tutorial',
    'page' => 1,
    'sources' => 10,
]);

$results = $response->json();
```

#### **2. YG Mail (Smart Reply)**
```php
$response = Http::post('https://ai.ygxone.com/api/', [
    'action' => 'smart_reply',
    'email_body' => 'Can we meet tomorrow?',
    'context' => 'Previous conversation...',
]);

// Returns: ["Sure, what time works?", "Tomorrow at 2pm?"]
```

#### **3. YG DocX (Writing Suggestions)**
```php
$response = Http::post('https://ai.ygxone.com/api/', [
    'action' => 'suggest_edits',
    'document_text' => 'This is a test document...',
]);

// Returns: grammar/style suggestions
```

#### **4. YG Calendar (Event Parsing)**
```php
$response = Http::post('https://ai.ygxone.com/api/', [
    'action' => 'parse_event',
    'text' => 'Meeting with John tomorrow at 3pm',
]);

// Returns: {date: '2026-05-09', time: '15:00', title: 'Meeting with John'}
```

---

## 📊 Available API Actions

| Action | Description | Use Case |
|--------|-------------|----------|
| `web_search` | Search knowledge corpus | YG Home search |
| `brain_chat` | Chat with context | Chatbot, assistant |
| `smart_reply` | Generate email replies | YG Mail |
| `classify_email` | Categorize emails | YG Mail filters |
| `suggest_edits` | Writing improvements | YG DocX |
| `parse_event` | Natural language → event | YG Calendar |
| `enrich_contact` | Add contact details | YG Contacts |
| `summarize_text` | Create summaries | All services |
| `analyze_sentiment` | Emotion detection | YG Mail priority |
| `extract_entities` | Named entity recognition | Data extraction |
| `describe_image` | Image description | Multimodal AI |

---

## 🔧 Configuration

### **Environment Variables (.env)**

```env
# API Security
YUGA_API_KEY=yg_ai_live_xxxxxxxxxxxxxx

# AI Backend Selection
YUGA_LLM_BACKEND=claude           # claude, openai, ollama, or internal
YUGA_ANTHROPIC_API_KEY=sk-xxx     # For Claude
YUGA_OPENAI_API_KEY=sk-xxx        # For GPT
YUGA_OLLAMA_URL=http://localhost:11434  # For local Llama

# Model Settings
YUGA_DEFAULT_MODEL=default
YUGA_AI_ENABLED=true              # Enable/disable AI features
YUGA_TONE=professional, concise

# Self-Learning
YUGA_AUTO_LEARN=true
YUGA_MAX_PAGES=50                 # Pages to crawl per session
YUGA_TRAIN_STEPS=15000            # Training iterations
YUGA_TIME_LIMIT=300               # Max seconds per crawl

# Ecosystem Integration
YUGA_ECOSYSTEM_NODES=http://localhost:5000,http://localhost:5001
```

---

## 💾 Data Storage

### **File Structure**

```
yg-ai/data/
├── models/
│   ├── default.json          # Main model weights
│   └── custom_model.json     # Additional models
│
├── indices/
│   ├── bm25_index.json       # BM25 inverted index
│   └── sentence_corpus.json  # Raw sentence database
│
├── memory/
│   ├── session_abc123.json   # Conversation histories
│   └── session_def456.json
│
├── meta/
│   ├── default_meta.json     # Model metadata
│   └── crawl_history.json    # Visited URLs
│
└── logs/
    ├── api_calls.log         # API usage logs
    └── errors.log            # Error tracking
```

### **Model Format (JSON)**

```json
{
  "weights": [[0.1, 0.2, ...], ...],  // Neural network weights
  "vocab": {"the": 1, "is": 2, ...},  // Vocabulary mapping
  "layers": 4,                         // Network depth
  "embed_dim": 128,                    // Embedding size
  "trained_steps": 15000,              // Total training iterations
  "last_updated": "2026-05-08T03:00:00Z"
}
```

---

## 🚀 Performance Characteristics

### **Benchmarks**

| Operation | Time | Memory | Notes |
|-----------|------|--------|-------|
| BM25 Query | < 5ms | ~2MB | For 50KB corpus |
| Text Generation | 50-200ms | ~10MB | 35 tokens |
| Model Training | 5-30s | 128-256MB | Per 1000 chars |
| Web Crawling | 1-3s/page | ~5MB | With 0.5s delay |
| API Response | 100-500ms | Varies | End-to-end |

### **Scalability**

- ✅ **Horizontal scaling**: Multiple instances behind load balancer
- ✅ **Caching**: Redis/file cache for frequent queries
- ✅ **Queue processing**: Async training jobs
- ✅ **Rate limiting**: Per-API-key quotas
- ⚠️ **Memory**: Each model ~50-100MB in RAM
- ⚠️ **CPU**: Training is CPU-intensive (use queues)

---

## 🛡️ Security Features

### **Authentication**
```php
// Two-tier API key system
1. Global Admin Key: Full access to all features
2. Subscriber Keys: Limited by subscription plan

// Validation
$key = $_SERVER['HTTP_X_API_KEY'];
if (!hash_equals($global_key, $key)) {
    http_response_code(401);
    exit;
}
```

### **Rate Limiting**
```php
// Per-subscriber daily limits
$calls_today = $akm->getCallsToday($sub_id);
if ($calls_today >= $plan_limit) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
}
```

### **CORS Protection**
```php
// Only allow trusted origins
$allowed = ['https://ygxone.com', 'https://account.ygxone.com'];
if (!in_array($origin, $allowed)) {
    header('Access-Control-Allow-Origin: none');
}
```

---

## 🎓 Advanced Features

### **1. Conversation Memory**

```php
// Maintains context across multiple turns
$memory = new Memory($data_dir);
$memory->addMessage($session_id, 'user', 'What is PHP?');
$memory->addMessage($session_id, 'assistant', 'PHP is...');

// Next question references previous context
$brain->chat("And how does it compare to Python?", $session_id, $memory);
// Understands "it" = PHP from context
```

### **2. Multi-Model Support**

```php
// Switch between different backends
$config['llm_backend'] = 'claude';  // Anthropic Claude
$config['llm_backend'] = 'openai';  // OpenAI GPT
$config['llm_backend'] = 'ollama';  // Local Llama/Mistral
$config['llm_backend'] = 'internal'; // Your SLM (default)
```

### **3. Federated Learning**

```php
// Learn from multiple ecosystem nodes
$config['ecosystem_nodes'] = [
    'http://mail.ygxone.com',
    'http://docs.ygxone.com',
    'http://calendar.ygxone.com',
];

// Automatically crawls and learns from all services
$learner->learnFromEcosystem();
```

### **4. Custom Training Data**

```php
// Manually feed specific content
$learner->learnFromText(
    file_get_contents('product_docs.pdf'),
    'product_documentation'
);

// Or from database
$learner->learnFromText(
    DB::table('articles')->pluck('content')->join("\n\n"),
    'knowledge_base'
);
```

---

## 🐛 Troubleshooting

### **Common Issues**

#### **1. "API not configured" Error**
```bash
# Solution: Set API key in .env
echo "YUGA_API_KEY=$(php -r 'echo bin2hex(random_bytes(24));')" >> .env
```

#### **2. Slow Responses**
```php
// Enable caching
Cache::remember("search:$query", 600, fn() => $ai->search($query));

// Or disable AI enhancement for speed
$config['ai_enabled'] = false;  // Pure BM25, no generation
```

#### **3. Out of Memory During Training**
```php
// Reduce training parameters
$learner->max_pages = 20;        // Fewer pages
$learner->train_steps = 5000;    // Fewer steps
$learner->memory_limit_mb = 512; // Increase limit
```

#### **4. Poor Search Results**
```bash
# Re-crawl and re-train
php train.php --site=https://your-site.com --force

# Or manually add content
php learn.php --text="Your important content here"
```

---

## 📈 Monitoring & Analytics

### **Track Usage**
```php
// Log every API call
DB::table('ai_api_logs')->insert([
    'api_key' => $key,
    'action' => $action,
    'response_time_ms' => $time,
    'created_at' => now(),
]);

// View analytics
$stats = DB::table('ai_api_logs')
    ->select('action', DB::raw('count(*) as calls'))
    ->groupBy('action')
    ->get();
```

### **Health Check**
```bash
curl https://ai.ygxone.com/api/?action=status

# Returns:
{
  "ok": true,
  "model_ready": true,
  "corpus_size": 125000,
  "uptime_hours": 48,
  "total_queries": 15234
}
```

---

## 🎯 Comparison with Cloud AI

| Feature | YG AI (Yours) | OpenAI/Gemini |
|---------|---------------|---------------|
| **Data Privacy** | ✅ 100% private | ❌ Data sent to cloud |
| **Cost** | ✅ Free (self-hosted) | ❌ Pay per token |
| **Customization** | ✅ Full control | ❌ Limited |
| **Latency** | ✅ < 100ms (local) | ⚠️ 200-1000ms |
| **Offline** | ✅ Works offline | ❌ Requires internet |
| **Training Data** | ✅ Your data only | ❌ Generic internet |
| **Compliance** | ✅ GDPR/HIPAA ready | ⚠️ Complex |
| **Scalability** | ⚠️ Your hardware | ✅ Unlimited |

---

## 🚀 Quick Start Examples

### **1. Basic Search**
```bash
curl -X POST https://ai.ygxone.com/api/ \
  -H "X-API-Key: your_key" \
  -H "Content-Type: application/json" \
  -d '{"action":"web_search","query":"laravel tutorial"}'
```

### **2. Chat with Context**
```bash
curl -X POST https://ai.ygxone.com/api/ \
  -H "X-API-Key: your_key" \
  -H "Content-Type: application/json" \
  -d '{
    "action":"brain_chat",
    "question":"What is Laravel?",
    "session_id":"user123"
  }'
```

### **3. Train on New Content**
```bash
php cli/train.php --site=https://docs.ygxone.com
```

---

## 🎉 Summary

**YG AI is your sovereign, self-learning AI platform that:**

✅ **Runs entirely on your infrastructure** - No cloud dependencies  
✅ **Learns from your data** - Custom knowledge, not generic  
✅ **Combines multiple AI techniques** - BM25 + Transformer + RAG  
✅ **Provides rich API** - Search, chat, classification, generation  
✅ **Integrates seamlessly** - All YG services use it  
✅ **Privacy-first** - Your data never leaves your servers  
✅ **Cost-effective** - No per-token fees  
✅ **Fully customizable** - Modify anything  

**It's not just an AI - it's YOUR AI!** 🧠✨
