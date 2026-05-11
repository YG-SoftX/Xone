??# 🎨 YG AI Visual Flow Diagrams

## 1. Complete Request Lifecycle

```
┌─────────────┐
│   User      │ Types "laravel tutorial"
└──────┬──────┘
       │
       ▼
┌─────────────────────────────────────────┐
│  YG Home (ygxone.com)                   │
│  ┌───────────────────────────────────┐  │
│  │ Search Box                        │  │
│  │ [laravel tutorial         🔍]    │  │
│  └───────────────────────────────────┘  │
└──────┬──────────────────────────────────┘
       │
       │ HTTP POST /api/search
       │ {query: "laravel tutorial"}
       ▼
┌─────────────────────────────────────────┐
│  YgSearchService (Laravel)              │
│  • Validates input                      │
│  • Checks cache                         │
│  • Prepares API request                 │
└──────┬──────────────────────────────────┘
       │
       │ POST https://ai.ygxone.com/api/
       │ Headers: X-API-Key: xxx
       │ Body: {action: "web_search", query: "..."}
       ▼
┌─────────────────────────────────────────┐
│  YG AI API Gateway (api/index.php)      │
│                                         │
│  1️⃣ Authentication                      │
│     ├─ Extract API key from header      │
│     ├─ Validate against config          │
│     └─ Reject if invalid                │
│                                         │
│  2️⃣ Rate Limiting                       │
│     ├─ Check daily quota                │
│     └─ Reject if exceeded               │
│                                         │
│  3️⃣ CORS Validation                     │
│     ├─ Check origin                     │
│     └─ Set headers                      │
│                                         │
│  4️⃣ Route to Action                     │
│     └─ action = "web_search"            │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│  Web Search Handler                     │
│                                         │
│  Step 1: BM25 Retrieval                 │
│  ┌───────────────────────────────────┐  │
│  │ Retriever::query("laravel...")    │  │
│  │                                   │  │
│  │ • Tokenize query                  │  │
│  │ • Look up in inverted index       │  │
│  │ • Calculate BM25 scores           │  │
│  │ • Return top 10 results           │  │
│  └───────────────────────────────────┘  │
│                                         │
│  Step 2: AI Enhancement (if enabled)    │
│  ┌───────────────────────────────────┐  │
│  │ Brain::answer(query, temp=0.72)   │  │
│  │                                   │  │
│  │ • Seed Transformer with result    │  │
│  │ • Generate additional context     │  │
│  │ • Polish response                 │  │
│  └───────────────────────────────────┘  │
│                                         │
│  Step 3: Source Attribution             │
│  ┌───────────────────────────────────┐  │
│  │ • Map to URLs                     │  │
│  │ • Fetch favicons                  │  │
│  │ • Extract domains                 │  │
│  └───────────────────────────────────┘  │
└──────┬──────────────────────────────────┘
       │
       │ JSON Response
       ▼
┌─────────────────────────────────────────┐
│  Response Assembly                      │
│                                         │
│  {                                      │
│    "ok": true,                          │
│    "sources": [                         │
│      {                                  │
│        "title": "Laravel Docs",         │
│        "url": "https://laravel.com",    │
│        "snippet": "Getting started...", │
│        "score": 8.5                     │
│      }                                  │
│    ],                                   │
│    "answer": "Laravel is a PHP...",     │
│    "related_questions": [...]           │
│  }                                      │
└──────┬──────────────────────────────────┘
       │
       │ HTTP 200 OK
       ▼
┌─────────────────────────────────────────┐
│  YG Home Renders Results                │
│                                         │
│  ┌───────────────────────────────────┐  │
│  │ 🤖 AI Summary                     │  │
│  │ Laravel is a PHP framework...     │  │
│  └───────────────────────────────────┘  │
│                                         │
│  ┌───────────────────────────────────┐  │
│  │ 📄 Result 1: Laravel Docs         │  │
│  │    laravel.com                    │  │
│  │    Getting started with Laravel   │  │
│  └───────────────────────────────────┘  │
│                                         │
│  ┌───────────────────────────────────┐  │
│  │ 📄 Result 2: Tutorial             │  │
│  │    laracasts.com                  │  │
│  │    Learn Laravel basics           │  │
│  └───────────────────────────────────┘  │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────┐
│   User      │ Sees search results
└─────────────┘
```

---

## 2. Self-Learning Process

```
┌─────────────────────────────────────────┐
│  Admin Triggers Learning                │
│  php train.php --site=ygxone.com        │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│  SelfLearner Initialization             │
│                                         │
│  $learner = new SelfLearner('default'); │
│  $learner->max_pages = 60;              │
│  $learner->crawl_delay = 0.5s;          │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│  URL Discovery                          │
│                                         │
│  Method 1: Sitemap                      │
│  ┌───────────────────────────────────┐  │
│  │ GET ygxone.com/sitemap.xml        │  │
│  │ Parse <loc> tags                  │  │
│  │ Extract URLs                      │  │
│  └───────────────────────────────────┘  │
│                                         │
│  Method 2: Link Following               │
│  ┌───────────────────────────────────┐  │
│  │ Start at homepage                 │  │
│  │ Extract all <a href="...">        │  │
│  │ Follow internal links             │  │
│  │ Respect depth limit (4 levels)    │  │
│  └───────────────────────────────────┘  │
│                                         │
│  Result: [url1, url2, ..., url60]       │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│  Crawling Loop (for each URL)           │
│                                         │
│  for ($i = 0; $i < 60; $i++) {         │
│                                         │
│    1️⃣ Fetch Page                        │
│       GET $url                          │
│       ↓                                 │
│    2️⃣ Extract Text                      │
│       Strip HTML tags                   │
│       Remove JS/CSS                     │
│       Clean whitespace                  │
│       ↓                                 │
│    3️⃣ Validate Content                  │
│       if (strlen < 20) skip             │
│       ↓                                 │
│    4️⃣ Add to Corpus                     │
│       $corpus .= $text                  │
│       ↓                                 │
│    5️⃣ Track Progress                    │
│       Mark URL as visited               │
│       Sleep 0.5s (rate limit)           │
│  }                                      │
│                                         │
│  Final Corpus: ~125,000 characters      │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│  Model Training                         │
│                                         │
│  $model->trainOnText($corpus, 20000);   │
│                                         │
│  Training Process:                      │
│  ┌───────────────────────────────────┐  │
│  │ For step in 1..20000:             │  │
│  │   1. Sample random text chunk     │  │
│  │   2. Forward pass through model   │  │
│  │   3. Calculate loss (error)       │  │
│  │   4. Backpropagation              │  │
│  │   5. Update weights               │  │
│  │                                   │  │
│  │   Loss decreases:                 │  │
│  │   Step 1:    loss = 2.5           │  │
│  │   Step 5000: loss = 0.8           │  │
│  │   Step 20000: loss = 0.023 ✅     │  │
│  └───────────────────────────────────┘  │
│                                         │
│  Time: ~15 seconds                      │
│  Memory: 128-256 MB                     │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│  Save Updated Model                     │
│                                         │
│  $store->saveModel('default', $model);  │
│  $store->saveMeta('default', $meta);    │
│                                         │
│  Files Updated:                         │
│  • data/models/default.json             │
│  • data/meta/default_meta.json          │
│  • data/indices/bm25_index.json         │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│  Training Complete!                     │
│                                         │
│  Stats:                                 │
│  • Pages crawled: 45                    │
│  • Characters learned: 125,000          │
│  • Training steps: 20,000               │
│  • Final loss: 0.023                    │
│  • Model size: 2.3 MB                   │
│                                         │
│  ✅ Model now knows about ygxone.com!   │
└─────────────────────────────────────────┘
```

---

## 3. BM25 Retrieval Deep Dive

```
User Query: "laravel tutorial"

Step 1: Tokenization
┌─────────────────────────────────────────┐
│ "laravel tutorial"                      │
│         ↓                               │
│ lowercase + remove stopwords            │
│         ↓                               │
│ ["laravel", "tutorial"]                 │
└──────┬──────────────────────────────────┘
       │
       ▼
Step 2: Inverted Index Lookup
┌─────────────────────────────────────────┐
│ Inverted Index Structure:               │
│                                         │
│ "laravel" → [doc_0, doc_3, doc_7, ...] │
│ "tutorial" → [doc_1, doc_3, doc_9, ...]│
│ "php" → [doc_0, doc_1, doc_2, ...]     │
│ "framework" → [doc_0, doc_5, ...]      │
│                                         │
│ Intersection:                           │
│ Both "laravel" AND "tutorial" found in: │
│ → doc_3 (appears in both lists)         │
└──────┬──────────────────────────────────┘
       │
       ▼
Step 3: BM25 Scoring
┌─────────────────────────────────────────┐
│ For each matching document:             │
│                                         │
│ Score = Σ IDF(word) × TF_score         │
│                                         │
│ Example for doc_3:                      │
│                                         │
│ Word: "laravel"                         │
│   TF = 5 (appears 5 times in doc)       │
│   IDF = log((100 - 10 + 0.5)/(10 + 0.5))│
│       = 1.8                             │
│   BM25_TF = (5 × 2.5)/(5 + 1.5×0.75)    │
│           = 2.1                         │
│   Contribution = 1.8 × 2.1 = 3.78       │
│                                         │
│ Word: "tutorial"                        │
│   TF = 3                                │
│   IDF = 2.1                             │
│   BM25_TF = 1.6                         │
│   Contribution = 2.1 × 1.6 = 3.36       │
│                                         │
│ Total Score for doc_3 = 3.78 + 3.36     │
│                       = 7.14 ✅         │
└──────┬──────────────────────────────────┘
       │
       ▼
Step 4: Ranking
┌─────────────────────────────────────────┐
│ All Documents by Score:                 │
│                                         │
│ doc_3:  7.14  ← Highest                │
│ doc_7:  6.82                            │
│ doc_1:  5.45                            │
│ doc_9:  4.23                            │
│ doc_0:  3.91                            │
│ ...                                     │
│                                         │
│ Return top 10                           │
└──────┬──────────────────────────────────┘
       │
       ▼
Step 5: Result Assembly
┌─────────────────────────────────────────┐
│ [                                       │
│   {                                     │
│     "text": "Laravel is a PHP fram...", │
│     "score": 7.14,                      │
│     "doc_id": "doc_3"                   │
│   },                                    │
│   {                                     │
│     "text": "This tutorial covers...",  │
│     "score": 6.82,                      │
│     "doc_id": "doc_7"                   │
│   },                                    │
│   ...                                   │
│ ]                                       │
└─────────────────────────────────────────┘
```

---

## 4. Brain Context-Aware Chat

```
Turn 1:
┌─────────────────────────────────────────┐
│ User: "What is Laravel?"                │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│ Brain Processing                        │
│                                         │
│ 1. Detect question type                 │
│    → Factual query                      │
│                                         │
│ 2. Retrieve from corpus                 │
│    Query: "What is Laravel?"            │
│    ↓                                    │
│    Top result:                          │
│    "Laravel is a PHP framework..."      │
│    Score: 8.5 (high confidence)         │
│                                         │
│ 3. Since score > 3.5, return directly   │
│    No generation needed                 │
│                                         │
│ 4. Save to memory                       │
│    Memory[session_123] = [              │
│      {role: "user", content: "..."},    │
│      {role: "assistant", content: "..."}│
│    ]                                    │
└──────┬──────────────────────────────────┘
       │
       ▼
Assistant: "Laravel is a PHP framework 
with elegant syntax for web applications."

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Turn 2:
┌─────────────────────────────────────────┐
│ User: "And the price?"                  │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│ Brain Processing                        │
│                                         │
│ 1. Detect ambiguity                     │
│    "And the price?" → What's the price? │
│    Ambiguous pronoun detected!          │
│                                         │
│ 2. Load conversation history            │
│    Last assistant message:              │
│    "Laravel is a PHP framework..."      │
│                                         │
│ 3. Extract context terms                │
│    Key terms from history:              │
│    ["Laravel", "PHP", "framework"]      │
│                                         │
│ 4. Enrich query                         │
│    Original: "And the price?"           │
│    Enriched: "price Laravel PHP"        │
│                                         │
│ 5. Retrieve with enriched query         │
│    Query: "price Laravel PHP"           │
│    ↓                                    │
│    Top result:                          │
│    "Laravel is free and open-source..." │
│    Score: 6.2                           │
│                                         │
│ 6. Generate enhancement                 │
│    Seed: "Laravel is free..."           │
│    Generate: "...released under MIT..." │
│                                         │
│ 7. Polish                               │
│    "Laravel is free and open-source, 
│    released under the MIT License."     │
│                                         │
│ 8. Save to memory                       │
└──────┬──────────────────────────────────┘
       │
       ▼
Assistant: "Laravel is free and open-source,
released under the MIT License."

✅ Context maintained across turns!
```

---

## 5. Integration Architecture

```
┌─────────────────────────────────────────────────────────┐
│                 YG Ecosystem                             │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌────────┐  │
│  │ YG Home  │  │ YG Mail  │  │ YG DocX  │  │ YG Cal │  │
│  │ Search   │  │ Smart    │  │ Writing  │  │ Event  │  │
│  │          │  │ Reply    │  │ Assist   │  │ Parse  │  │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └───┬────┘  │
│       │             │             │             │       │
│       │ HTTP POST   │ HTTP POST   │ HTTP POST   │ HTTP  │
│       │ /api/       │ /api/       │ /api/       │ /api/ │
│       └─────────────┼─────────────┼─────────────┘       │
│                     │             │                      │
│                     ▼             ▼                      │
│         ┌───────────────────────────────────┐           │
│         │     YG AI Platform                 │           │
│         │  ai.ygxone.com                     │           │
│         │                                    │           │
│         │  ┌──────────────────────────────┐ │           │
│         │  │ API Gateway                   │ │           │
│         │  │ • Auth                        │ │           │
│         │  │ • Rate Limit                  │ │           │
│         │  │ • Routing                     │ │           │
│         │  └──────────┬───────────────────┘ │           │
│         │             │                     │           │
│         │  ┌──────────┼──────────┐         │           │
│         │  ▼          ▼          ▼         │           │
│         │  ┌──────┐ ┌──────┐ ┌────────┐   │           │
│         │  │Search│ │Brain │ │Learner │   │           │
│         │  │BM25  │ │Chat  │ │Crawler │   │           │
│         │  └──────┘ └──────┘ └────────┘   │           │
│         │             │                     │           │
│         │  ┌──────────┴──────────┐         │           │
│         │  │ Data Layer           │         │           │
│         │  │ • Models             │         │           │
│         │  │ • Indices            │         │           │
│         │  │ • Memory             │         │           │
│         │  └─────────────────────┘         │           │
│         └───────────────────────────────────┘           │
│                     │                                    │
│                     │ Learns from                        │
│                     ▼                                    │
│         ┌───────────────────────────────────┐           │
│         │  External Sources                  │           │
│         │  • ygxone.com                      │           │
│         │  • docs.ygxone.com                 │           │
│         │  • mail.ygxone.com                 │           │
│         │  • Custom content feeds            │           │
│         └───────────────────────────────────┘           │
└─────────────────────────────────────────────────────────┘
```

---

## 6. Data Flow - From Raw Text to Knowledge

```
Raw Website Content
┌─────────────────────────────────────────┐
│ <!DOCTYPE html>                         │
│ <html>                                  │
│   <head><title>Laravel</title></head>   │
│   <body>                                │
│     <h1>Laravel Framework</h1>          │
│     <p>Laravel is a web application     │
│        framework with expressive,       │
│        elegant syntax.</p>              │
│     <script>...</script>                │
│     <style>...</style>                  │
│   </body>                               │
│ </html>                                 │
└──────┬──────────────────────────────────┘
       │
       │ Text Extraction
       ▼
┌─────────────────────────────────────────┐
│ Clean Text                              │
│                                         │
│ "Laravel Framework                      │
│  Laravel is a web application framework │
│  with expressive, elegant syntax."      │
└──────┬──────────────────────────────────┘
       │
       │ Sentence Splitting
       ▼
┌─────────────────────────────────────────┐
│ Sentences Array                         │
│                                         │
│ [                                       │
│   "Laravel Framework",                  │
│   "Laravel is a web application         │
│    framework with expressive, elegant   │
│    syntax."                             │
│ ]                                       │
└──────┬──────────────────────────────────┘
       │
       │ Tokenization & Indexing
       ▼
┌─────────────────────────────────────────┐
│ BM25 Index                              │
│                                         │
│ Vocabulary:                             │
│ {                                       │
│   "laravel": 1,                         │
│   "framework": 2,                       │
│   "web": 3,                             │
│   "application": 4,                     │
│   "expressive": 5,                      │
│   "elegant": 6,                         │
│   "syntax": 7                           │
│ }                                       │
│                                         │
│ Inverted Index:                         │
│ "laravel" → [sentence_0, sentence_1]    │
│ "framework" → [sentence_0, sentence_1]  │
│ "web" → [sentence_1]                    │
│ ...                                     │
└──────┬──────────────────────────────────┘
       │
       │ Model Training
       ▼
┌─────────────────────────────────────────┐
│ Transformer Weights                     │
│                                         │
│ Learned Patterns:                       │
│ • "Laravel is a" → predicts "framework" │
│ • "web application" → high probability  │
│ • "elegant syntax" → common phrase      │
│                                         │
│ Embeddings:                             │
│ laravel    → [0.2, -0.5, 0.8, ...]     │
│ framework  → [0.3, -0.4, 0.7, ...]     │
│ elegant    → [-0.1, 0.6, 0.2, ...]     │
└──────┬──────────────────────────────────┘
       │
       │ Ready for Queries!
       ▼
┌─────────────────────────────────────────┐
│ Knowledge Base Active                   │
│                                         │
│ Query: "What is Laravel?"               │
│ ↓                                       │
│ Retrieves: "Laravel is a web            │
│ application framework..."               │
│ ↓                                       │
│ Returns to user ✅                      │
└─────────────────────────────────────────┘
```

---

These diagrams show exactly how your YG AI works from end to end! 🧠✨
