# YGXONE Browser - Advanced Features Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                     YGXONE Agentic Browser v2.0                      │
│                   (Better Than Chrome + Comet)                      │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                         USER INTERFACE LAYER                         │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐           │
│  │ Research │  │  Cite    │  │  Graph   │  │   AI     │           │
│  │  Panel   │  │  Panel   │  │  Panel   │  │  Panel   │           │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘           │
│       │             │             │              │                  │
│       ▼             ▼             ▼              ▼                  │
│  Alpine.js Reactive State Management (browserState())              │
│                                                                      │
└───────────────────────────┬─────────────────────────────────────────┘
                            │ HTTP/AJAX
┌───────────────────────────▼─────────────────────────────────────────┐
│                          API ROUTE LAYER                             │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  POST /api/research/deep          → SearchController::deepResearch  │
│  GET  /api/citations/recent       → SearchController::getCitations  │
│  POST /api/citations/format       → SearchController::formatCit...  │
│  DEL  /api/citations/clear        → SearchController::clearCita...  │
│  GET  /api/knowledge-graph        → SearchController::getKnowle...  │
│  GET  /api/knowledge-graph/search → SearchController::searchEnti... │
│  GET  /api/knowledge-graph/con... → SearchController::getEntity...  │
│  DEL  /api/knowledge-graph/clear  → SearchController::clearKnowl... │
│                                                                      │
│  [Rate Limiting Middleware]                                          │
│  - Research: 10 req/min                                              │
│  - Others: No limit (lightweight)                                    │
│                                                                      │
└───────────────────────────┬─────────────────────────────────────────┘
                            │ Service Container
┌───────────────────────────▼─────────────────────────────────────────┐
│                        SERVICE LAYER                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │              DeepResearchService                             │  │
│  ├──────────────────────────────────────────────────────────────┤  │
│  │ • findSources() - YG Search + DuckDuckGo API                 │  │
│  │ • fetchPagesParallel() - curl_multi (3 concurrent)           │  │
│  │ • extractPageData() - DOMDocument + XPath                    │  │
│  │ • synthesizeFindings() - LLM (OpenAI/Claude/Ollama)          │  │
│  │ • generateReport() - Structure into sections                 │  │
│  └──────────────────────┬───────────────────────────────────────┘  │
│                         │                                          │
│  ┌──────────────────────▼───────────────────────────────────────┐  │
│  │              CitationTracker                                 │  │
│  ├──────────────────────────────────────────────────────────────┤  │
│  │ • addSource() - Track visited URLs                           │  │
│  │ • extractMetadata() - cURL fetch meta tags                   │  │
│  │ • getRecent() - Query by session_id                          │  │
│  │ • generateCitation() - Format APA/MLA/Chicago                │  │
│  │ • exportCitations() - Batch export                           │  │
│  └──────────────────────┬───────────────────────────────────────┘  │
│                         │                                          │
│  ┌──────────────────────▼───────────────────────────────────────┐  │
│  │            KnowledgeGraphService                             │  │
│  ├──────────────────────────────────────────────────────────────┤  │
│  │ • extractEntities() - Pattern matching (regex)               │  │
│  │ • storeEntity() - Insert/update with mentions count          │  │
│  │ • createRelationships() - Co-occurrence analysis             │  │
│  │ • getGraphData() - D3.js compatible format                   │  │
│  │ • findConnections() - Entity relationship query              │  │
│  └──────────────────────┬───────────────────────────────────────┘  │
│                         │                                          │
│  ┌──────────────────────▼───────────────────────────────────────┐  │
│  │         Existing Services (Reused)                           │  │
│  ├──────────────────────────────────────────────────────────────┤  │
│  │ • BrowserProxyService - Page fetching, shields               │  │
│  │ • AgentLLMService - LLM integration                          │  │
│  │ • UnifiedSearchService - YG ecosystem search                 │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                      │
└───────────────────────────┬─────────────────────────────────────────┘
                            │ Eloquent / Query Builder
┌───────────────────────────▼─────────────────────────────────────────┐
│                       DATABASE LAYER (MySQL)                         │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌────────────────────────────────────────────────────────────┐    │
│  │ browser_citations                                          │    │
│  ├────────────────────────────────────────────────────────────┤    │
│  │ id | session_id | user_id | url | title | author | ...    │    │
│  │ INDEX: session_id, created_at                              │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────┐    │
│  │ knowledge_entities                                         │    │
│  ├────────────────────────────────────────────────────────────┤    │
│  │ id | session_id | name | type | confidence | mentions |.. │    │
│  │ UNIQUE: (session_id, name)                                 │    │
│  │ INDEX: type, mentions                                      │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────┐    │
│  │ knowledge_relationships                                    │    │
│  ├────────────────────────────────────────────────────────────┤    │
│  │ id | source_entity_id | target_entity_id | type | streng..│    │
│  │ FK: source_entity_id → knowledge_entities.id               │    │
│  │ FK: target_entity_id → knowledge_entities.id               │    │
│  │ UNIQUE: (source_entity_id, target_entity_id)               │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────┐    │
│  │ agent_sessions                                             │    │
│  │ research_reports                                           │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                     EXTERNAL INTEGRATIONS                            │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  DuckDuckGo API  ←→  DeepResearchService::searchDuckDuckGo()       │
│  (Free, no auth)      Source discovery                               │
│                                                                      │
│  OpenAI API      ←→  AgentLLMService::callOpenAI()                 │
│  (Optional)         Research synthesis                                │
│                                                                      │
│  Claude API      ←→  AgentLLMService::callClaude()                 │
│  (Optional)         Research synthesis                                │
│                                                                      │
│  Ollama          ←→  AgentLLMService::callOllama()                 │
│  (Self-hosted)      Local LLM inference                              │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Data Flow Diagrams

### 1. Deep Research Flow

```
User enters query
    │
    ▼
┌─────────────────────┐
│  Research Panel UI   │
│  (Alpine.js state)   │
└─────────┬───────────┘
          │ POST /api/research/deep
          ▼
┌─────────────────────────┐
│  DeepResearchService     │
│  research($query)        │
└─────────┬───────────────┘
          │
    ┌─────┴──────┐
    │            │
    ▼            ▼
┌────────┐  ┌──────────────┐
│ YG     │  │ DuckDuckGo   │
│ Search │  │ API          │
└────┬───┘  └──────┬───────┘
     │             │
     └──────┬──────┘
            │ Merge sources
            ▼
┌─────────────────────────┐
│ fetchPagesParallel()    │
│ curl_multi (3 threads)  │
└─────────┬───────────────┘
          │
          ▼
┌─────────────────────────┐
│ extractPageData()       │
│ DOMDocument + XPath     │
└─────────┬───────────────┘
          │
          ▼
┌─────────────────────────┐
│ synthesizeFindings()    │
│ LLM call (GPT-4/Claude) │
└─────────┬───────────────┘
          │
          ▼
┌─────────────────────────┐
│ generateReport()        │
│ Structure sections      │
└─────────┬───────────────┘
          │
          ▼
┌─────────────────────┐
│  JSON Response       │
│  - executive_summary │
│  - sections[]        │
│  - key_takeaways[]   │
│  - sources[]         │
│  - citations[]       │
└─────────┬───────────┘
          │
          ▼
┌─────────────────────┐
│  Display in Panel   │
│  (Formatted HTML)   │
└─────────────────────┘
```

### 2. Citation Tracking Flow

```
User visits URL via proxy
    │
    ▼
┌─────────────────────┐
│ BrowserProxyService  │
│ fetch($url)          │
└─────────┬───────────┘
          │
          ▼
┌─────────────────────┐
│ CitationTracker      │
│ addSource($url)      │
└─────────┬───────────┘
          │
    ┌─────┴──────┐
    │ Check if   │
    │ exists?    │
    └─────┬──────┘
     Yes  │   No
    ┌─────┴──────┐
    │            │
    ▼            ▼
┌────────┐  ┌──────────────┐
│Update  │  │ Extract meta │
│visit_  │  │ (cURL fetch) │
│count   │  │              │
└────┬───┘  └──────┬───────┘
     │             │
     └──────┬──────┘
            │
            ▼
┌─────────────────────┐
│ INSERT/UPDATE        │
│ browser_citations    │
└─────────┬───────────┘
          │
          ▼
┌─────────────────────┐
│ Auto-update UI       │
│ Citation badge count │
└─────────────────────┘
```

### 3. Knowledge Graph Flow

```
Page content loaded
    │
    ▼
┌─────────────────────┐
│KnowledgeGraphService│
│extractEntities($html│
└─────────┬───────────┘
          │
          ▼
┌─────────────────────┐
│ parseEntities()      │
│ Regex pattern match  │
└─────────┬───────────┘
          │
          ▼
┌─────────────────────┐
│ Entities Found:      │
│ - Person names       │
│ - Organizations      │
│ - Locations          │
│ - Dates              │
└─────────┬───────────┘
          │
          ▼
┌─────────────────────┐
│ storeEntity()        │
│ For each entity:     │
└─────────┬───────────┘
          │
    ┌─────┴──────┐
    │ Exists?    │
    └─────┬──────┘
     Yes  │   No
    ┌─────┴──────┐
    │            │
    ▼            ▼
┌────────┐  ┌──────────────┐
│Increment│ │ INSERT new   │
│mentions │ │ entity row   │
└────┬────┘  └──────┬───────┘
     │              │
     └──────┬───────┘
            │
            ▼
┌─────────────────────┐
│createRelationships()│
│Co-occurrence on page│
└─────────┬───────────┘
          │
          ▼
┌─────────────────────┐
│ INSERT/UPDATE        │
│ knowledge_           │
│ relationships        │
└─────────┬───────────┘
          │
          ▼
┌─────────────────────┐
│ Update graph data    │
│ (nodes + links)      │
└─────────────────────┘
```

---

## Security Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    SECURITY LAYERS                           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Layer 1: Input Validation                                  │
│  ├─ strip_tags() on all user input                          │
│  ├─ substr() limits (500 chars max)                         │
│  ├─ filter_var() for URLs                                   │
│  └─ Type casting (int, bool)                                │
│                                                              │
│  Layer 2: Authentication & Authorization                    │
│  ├─ Session-based isolation (session_id)                    │
│  ├─ Optional user_id linking                                │
│  └─ No cross-user data access                               │
│                                                              │
│  Layer 3: Rate Limiting                                     │
│  ├─ Laravel throttle middleware                             │
│  ├─ Research: 10 req/min                                    │
│  ├─ Agent: 30 req/min                                       │
│  └─ Browse: 120 req/min                                     │
│                                                              │
│  Layer 4: SSRF Protection                                   │
│  ├─ Private IP blocking (10.x, 192.168.x, etc.)            │
│  ├─ DNS resolution checks                                   │
│  ├─ URL scheme validation (http/https only)                 │
│  └─ filter_var(FILTER_FLAG_NO_PRIV_RANGE)                   │
│                                                              │
│  Layer 5: Database Security                                 │
│  ├─ Parameterized queries (no SQL injection)                │
│  ├─ Foreign key constraints                                 │
│  ├─ Indexed lookups (performance + security)                │
│  └─ Session-scoped queries                                  │
│                                                              │
│  Layer 6: Output Sanitization                               │
│  ├─ Blade auto-escaping {{ }}                               │
│  ├─ Content-Type headers set explicitly                     │
│  ├─ X-Frame-Options: SAMEORIGIN                             │
│  └─ CSP headers (future enhancement)                        │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Performance Optimization Strategy

```
┌─────────────────────────────────────────────────────────────┐
│                 OPTIMIZATION TECHNIQUES                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  1. Parallel Fetching                                       │
│     ├─ curl_multi for concurrent requests                   │
│     ├─ Concurrency limit: 3 (cPanel safe)                   │
│     ├─ Timeout: 10s per request                             │
│     └─ Result: 3x faster than sequential                    │
│                                                              │
│  2. Database Indexing                                       │
│     ├─ session_id indexes (fast lookups)                    │
│     ├─ Composite indexes (session + timestamp)              │
│     ├─ UNIQUE constraints (prevent duplicates)              │
│     └─ FULLTEXT on research queries                         │
│                                                              │
│  3. Caching Strategy                                        │
│     ├─ Config cache (php artisan config:cache)              │
│     ├─ Route cache (php artisan route:cache)                │
│     ├─ View cache (php artisan view:cache)                  │
│     └─ Page cache in AgentToolService (5 pages, 30min)     │
│                                                              │
│  4. Lazy Loading                                            │
│     ├─ Panels load data on open (not init)                  │
│     ├─ Debounced search (300ms delay)                       │
│     └─ Progressive rendering (show partial results)         │
│                                                              │
│  5. Resource Limits                                         │
│     ├─ Max 15 pages per research query                      │
│     ├─ Max 100 entities per graph                           │
│     ├─ Max 50 citations displayed                           │
│     └─ Memory: <100MB per request                           │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## cPanel Compatibility Matrix

```
┌─────────────────────────────────────────────────────────────┐
│              CPANEL SHARED HOSTING COMPATIBILITY             │
├──────────────────────────┬──────────────────────────────────┤
│ Feature                  │ Status                           │
├──────────────────────────┼──────────────────────────────────┤
│ PHP 8.0+                 │ ✅ Supported                     │
│ MySQL 5.7+               │ ✅ Supported                     │
│ Composer                 │ ✅ CLI access                    │
│ cURL                     │ ✅ Enabled                       │
│ DOMDocument              │ ✅ Built-in                      │
│ cron Jobs                │ ✅ cPanel interface              │
│ File Permissions         │ ✅ File Manager                  │
│ Git Deployment           │ ✅ cPanel Git UI                 │
│ SSH Access               │ ⚠️ Optional (terminal works)    │
│ Node.js                  │ ❌ Not required (PHP-only)       │
│ WebSockets               │ ❌ Not used (HTTP polling)       │
│ Redis/Memcached          │ ❌ Using file/database cache     │
│ Queue Workers            │ ❌ Using sync driver             │
└──────────────────────────┴──────────────────────────────────┘

Key Design Decisions for cPanel:
- No Node.js dependencies
- No background workers (sync queues)
- File/database caching (no Redis)
- curl instead of Guzzle async
- DOMDocument instead of Puppeteer
- Session-based storage (no distributed cache)
```

---

## Scalability Roadmap

```
Current (Shared Hosting)
┌─────────────────────────────────────┐
│ • Single server                     │
│ • MySQL on same host                │
│ • File-based sessions               │
│ • Max ~100 concurrent users         │
└──────────────┬──────────────────────┘
               │ Scale vertically
               ▼
Phase 2 (VPS)
┌─────────────────────────────────────┐
│ • Dedicated CPU/RAM                 │
│ • Separate DB server                │
│ • Redis for cache/sessions          │
│ • Queue workers for research        │
│ • Max ~1000 concurrent users        │
└──────────────┬──────────────────────┘
               │ Scale horizontally
               ▼
Phase 3 (Cloud)
┌─────────────────────────────────────┐
│ • Load balancer                     │
│ • Multiple app servers              │
│ • Managed database (RDS)            │
│ • CDN for static assets             │
│ • Auto-scaling groups               │
│ • Max ~10,000+ concurrent users     │
└─────────────────────────────────────┘
```

---

**Architecture designed for:** 🎯 Simplicity, 🔒 Security, ⚡ Performance, 🚀 Scalability
