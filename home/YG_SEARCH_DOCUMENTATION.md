# 🔍 YG Search - Google-like Search Engine Powered by YG AI

## 📋 Overview

**YG Search** is a production-ready, Google-style search engine integrated into **YG Home** (`ygxone.com`). It leverages your own **YG AI** platform to deliver intelligent, context-aware search results with AI-powered enhancements.

---

## ✨ Key Features

### 🎯 **Core Capabilities**
- ✅ **Web Search**: Comprehensive internet search powered by YG AI
- ✅ **AI Summaries**: Auto-generated answer boxes (like Google's featured snippets)
- ✅ **Smart Suggestions**: Real-time autocomplete as you type
- ✅ **Related Searches**: AI-curated "People also ask" suggestions
- ✅ **Result Ranking**: Intelligent relevance scoring and ranking
- ✅ **Knowledge Panels**: Rich information cards for entities
- ✅ **Multi-Source Integration**: Combines web + YG ecosystem content

### 🚀 **Advanced Features**
- ✅ **Caching**: 10-minute result cache for performance
- ✅ **Rate Limiting**: Prevents abuse (60 searches/hour per IP)
- ✅ **Analytics**: Tracks search queries for continuous improvement
- ✅ **Fallback Strategy**: Graceful degradation if AI unavailable
- ✅ **Ecosystem Search**: Optional integration with YG Mail, Drive, Docs, etc.

---

## 🏗️ Architecture

```
┌─────────────┐
│  User Types │
│  Query      │
└──────┬──────┘
       │
       ▼
┌──────────────────┐
│   YG Home        │
│   Search UI      │
└──────┬───────────┘
       │
       │ HTTP POST /api/search
       │
       ▼
┌──────────────────┐
│ YgSearchService  │
│ (Laravel)        │
└──────┬───────────┘
       │
       │ Calls YG AI API
       │
       ▼
┌──────────────────┐
│   YG AI          │
│ ai.ygxone.com    │
└──────┬───────────┘
       │
       │ Returns ranked results
       │ + AI insights
       │
       ▼
┌──────────────────┐
│ Enhanced Results │
│ + AI Summary     │
│ + Suggestions    │
└──────────────────┘
```

---

## ⚙️ Configuration

### **Step 1: Set Environment Variables**

Edit `.env` in `yg-home/`:

```env
# YG AI Service Configuration
YG_AI_URL=https://ai.ygxone.com
YG_AI_API_KEY=your_yg_ai_api_key_here
YG_AI_TIMEOUT=10

# Ecosystem Services (Optional)
YG_ACCOUNT_URL=https://account.ygxone.com
YG_ACCOUNT_API_KEY=your_account_api_key

YG_MAIL_URL=https://mail.ygxone.com
YG_MAIL_API_KEY=your_mail_api_key

YG_DRIVE_URL=https://drive.ygxone.com
YG_DRIVE_API_KEY=your_drive_api_key

YG_DOCX_URL=https://docs.ygxone.com
YG_DOCX_API_KEY=your_docx_api_key

YG_CALENDAR_URL=https://calendar.ygxone.com
YG_CALENDAR_API_KEY=your_calendar_api_key

YG_CONTACTS_URL=https://contacts.ygxone.com
YG_CONTACTS_API_KEY=your_contacts_api_key
```

### **Step 2: Generate API Key in YG AI**

In your YG AI admin panel:
1. Navigate to **API Keys** section
2. Create new key for **YG Home Search**
3. Copy the key to `.env`

### **Step 3: Clear Config Cache**

```bash
cd /path/to/yg-home
php artisan config:clear
php artisan cache:clear
```

---

## 🔌 YG AI API Endpoints Used

YG Search integrates with these YG AI endpoints:

### **1. Web Search**
```
POST https://ai.ygxone.com/api/
Content-Type: application/json
X-API-Key: {your_api_key}

{
  "action": "web_search",
  "query": "laravel tutorial",
  "tab": "all",
  "page": 1,
  "sources": 10
}
```

**Response:**
```json
{
  "ok": true,
  "organic": [
    {
      "title": "Laravel - The PHP Framework",
      "link": "https://laravel.com",
      "snippet": "Laravel is a web application framework...",
      "displayed_link": "laravel.com"
    }
  ],
  "knowledge_panel": {...},
  "related_questions": [...]
}
```

---

### **2. Result Ranking**
```
POST https://ai.ygxone.com/api/
{
  "action": "rank_results",
  "query": "laravel tutorial",
  "results": [
    {"title": "...", "url": "...", "snippet": "..."}
  ]
}
```

**Response:**
```json
{
  "ranked_results": ["url1", "url2", "url3"],
  "scores": {"url1": 95, "url2": 87},
  "top_sources": {"url1": true}
}
```

---

### **3. AI Summary Generation**
```
POST https://ai.ygxone.com/api/
{
  "action": "summarize_search",
  "query": "laravel tutorial",
  "results": "- Title 1: Snippet...\n- Title 2: Snippet..."
}
```

**Response:**
```json
{
  "summary": "Laravel is a popular PHP framework that provides..."
}
```

---

### **4. Related Searches**
```
POST https://ai.ygxone.com/api/
{
  "action": "related_searches",
  "query": "laravel tutorial"
}
```

**Response:**
```json
{
  "related_searches": [
    "laravel tutorial for beginners",
    "laravel vs symfony",
    "best laravel courses 2026"
  ]
}
```

---

### **5. Autocomplete Suggestions**
```
POST https://ai.ygxone.com/suggest.php
{
  "q": "laravel"
}
```

**Response:**
```json
{
  "suggestions": [
    "laravel tutorial",
    "laravel installation",
    "laravel authentication"
  ]
}
```

---

## 📁 File Structure

```
yg-home/
├── app/
│   ├── Services/
│   │   └── YgSearchService.php          # Core search logic
│   ├── Http/
│   │   └── Controllers/
│   │       └── SearchController.php     # Handles search requests
│   └── Models/
│       └── SearchAnalytics.php          # Search tracking
│
├── config/
│   └── services.php                     # Service configuration
│
├── resources/
│   └── views/
│       └── search/
│           ├── home.blade.php           # Search homepage
│           └── results.blade.php        # Results page
│
├── routes/
│   └── web.php                          # Search routes
│
└── .env                                 # Environment variables
```

---

## 🚀 Usage Examples

### **Basic Web Search**

```php
use App\Services\YgSearchService;

$searchService = app(YgSearchService::class);

$results = $searchService->searchWeb('laravel tutorial', [
    'page' => 1,
    'per_page' => 20,
]);

// Access results
echo $results['ai_summary'];           // AI-generated summary
print_r($results['results']);           // Array of search results
print_r($results['related_searches']);  // Related queries
print_r($results['suggestions']);       // Autocomplete suggestions
```

---

### **Search with Pagination**

```php
$page = request()->input('page', 1);
$perPage = 20;

$results = $searchService->searchWeb('php frameworks', [
    'page' => $page,
    'per_page' => $perPage,
]);

$totalResults = $results['total_results'];
$currentPage = $page;
$hasMorePages = count($results['results']) >= $perPage;
```

---

### **Autocomplete API**

```php
// In SearchController
public function suggest(Request $request)
{
    $query = $request->input('q');
    
    $suggestions = $this->ygSearchService->getAutocompleteSuggestions($query);
    
    return response()->json([
        'suggestions' => $suggestions
    ]);
}
```

**Frontend (JavaScript):**
```javascript
document.getElementById('search-input').addEventListener('input', async (e) => {
    const query = e.target.value;
    
    if (query.length < 2) return;
    
    const response = await fetch(`/api/suggest?q=${encodeURIComponent(query)}`);
    const data = await response.json();
    
    showSuggestions(data.suggestions);
});
```

---

### **Track Search Analytics**

```php
// After displaying results
$searchService->trackSearch(
    $query,
    $results['results'],
    auth()->id()  // Optional: track per-user
);

// View analytics
DB::table('search_analytics')
    ->select('query', DB::raw('count(*) as searches'))
    ->groupBy('query')
    ->orderByDesc('searches')
    ->limit(20)
    ->get();
```

---

## 🔧 Customization

### **Adjust Cache Duration**

Default: 10 minutes (600 seconds)

```php
// In YgSearchService::searchWeb()
return Cache::remember($cacheKey, 1800, function () use ($query, $options) {
    // ... search logic
});
```

---

### **Modify Result Scoring**

```php
private function applyBasicScoring(array $results): array
{
    foreach ($results as &$result) {
        // Customize scoring algorithm
        $result['ai_score'] = max(10, 100 - (array_search($result, $results) * 10));
        
        // Add custom factors
        if (str_contains($result['url'], '.edu')) {
            $result['ai_score'] += 20;  // Boost educational sites
        }
    }
    
    return $results;
}
```

---

### **Add Custom Filters**

```php
public function searchWeb(string $query, array $options = []): array
{
    // Add date filter
    if (isset($options['date_range'])) {
        // Filter by date
    }
    
    // Add language filter
    if (isset($options['language'])) {
        // Filter by language
    }
    
    // ... rest of search logic
}
```

---

## 🛡️ Security & Performance

### **Rate Limiting**

YG AI already implements rate limiting (60 searches/hour). Additional protection:

```php
// In SearchController
public function __construct()
{
    $this->middleware('throttle:30,1')->only(['index', 'suggest']);
}
```

---

### **Input Sanitization**

Already implemented in `YgSearchService`:
- Query length limited to 500 characters
- HTML tags stripped
- SQL injection prevention via parameterized queries

---

### **HTTPS Enforcement**

All YG AI API calls use HTTPS. Ensure your `.env` has:

```env
YG_AI_URL=https://ai.ygxone.com  # NOT http://
```

---

## 📊 Monitoring & Analytics

### **View Top Searches**

```bash
php artisan db:table search_analytics --limit=50
```

Or via code:

```php
$topSearches = DB::table('search_analytics')
    ->select('query', DB::raw('count(*) as count'))
    ->whereDate('created_at', '>=', now()->subDays(7))
    ->groupBy('query')
    ->orderByDesc('count')
    ->limit(20)
    ->get();
```

---

### **Monitor Zero-Result Queries**

```php
$zeroResults = DB::table('search_analytics')
    ->where('results_count', 0)
    ->whereDate('created_at', '>=', now()->subDays(7))
    ->select('query', DB::raw('count(*) as occurrences'))
    ->groupBy('query')
    ->orderByDesc('occurrences')
    ->get();

// Use this to identify content gaps
```

---

### **Track AI Enhancement Success Rate**

```php
// Add to YgSearchService
private function logAiEnhancement(bool $success, string $query): void
{
    DB::table('ai_enhancement_logs')->insert([
        'query' => $query,
        'success' => $success,
        'created_at' => now(),
    ]);
}
```

---

## 🐛 Troubleshooting

### **Issue: No Results Returned**

**Check:**
1. YG AI service is running: `curl https://ai.ygxone.com/api/health`
2. API key is correct in `.env`
3. Network connectivity: Check firewall rules
4. YG AI logs for errors

**Debug:**
```php
\Log::info('YG Search Query', ['query' => $query]);
\Log::info('YG AI Response', ['response' => $response->json()]);
```

---

### **Issue: Slow Search Performance**

**Solutions:**
1. Increase cache duration (default: 600s)
2. Enable Redis caching: `CACHE_STORE=redis`
3. Optimize YG AI response time
4. Reduce number of sources requested

**Profile:**
```php
$start = microtime(true);
$results = $searchService->searchWeb($query);
$time = microtime(true) - $start;

\Log::info('Search Time', ['seconds' => $time]);
```

---

### **Issue: AI Summary Not Showing**

**Check:**
1. YG AI supports `summarize_search` action
2. At least 3 results returned
3. No timeout errors in logs

**Fallback:**
```php
if (!$aiSummary && count($results) > 0) {
    // Generate simple summary from top result
    $aiSummary = substr($results[0]['snippet'], 0, 200) . '...';
}
```

---

## 🔄 Migration from Gemini

If you previously used Gemini AI, here's what changed:

| Feature | Before (Gemini) | Now (YG AI) |
|---------|----------------|-------------|
| **API URL** | `generativelanguage.googleapis.com` | `ai.ygxone.com` |
| **Auth** | API key in URL param | `X-API-Key` header |
| **Actions** | Custom prompts | Pre-built actions |
| **Cost** | Pay-per-token | Your infrastructure |
| **Control** | Limited | Full control |
| **Privacy** | Data to Google | Self-hosted |

---

## 🎓 Best Practices

### **1. Always Use Caching**
```php
// ✅ Good
Cache::remember($key, 600, fn() => $this->search());

// ❌ Bad - No caching
$this->search();
```

---

### **2. Handle Failures Gracefully**
```php
try {
    $results = $this->fetchFromYgAi($query);
} catch (\Exception $e) {
    \Log::error('Search failed: ' . $e->getMessage());
    return [];  // Return empty instead of crashing
}
```

---

### **3. Track Everything**
```php
// Log all searches for analytics
$this->trackSearch($query, $results, auth()->id());
```

---

### **4. Respect Rate Limits**
```php
// Don't hammer the API
if ($this->isRateLimited()) {
    return $this->getCachedResults($query);
}
```

---

### **5. Validate Input**
```php
$query = substr(strip_tags($request->input('q', '')), 0, 500);
```

---

## 📈 Future Enhancements

### **Planned Features**
- [ ] Voice search support
- [ ] Image search integration
- [ ] Video search results
- [ ] News feed aggregation
- [ ] Personalized results (based on user history)
- [ ] Advanced filters (date, location, language)
- [ ] Search history management
- [ ] Export results (PDF, CSV)

---

### **Integration Opportunities**
- [ ] YG Mail: Search emails alongside web
- [ ] YG Drive: Include documents in results
- [ ] YG DocX: Search document content
- [ ] YG Calendar: Show relevant events
- [ ] YG Contacts: Display contact matches

---

## 🤝 Support

For issues or questions:
1. Check YG AI logs: `/path/to/yg-ai/storage/logs/`
2. Review YG Home logs: `storage/logs/laravel.log`
3. Verify API connectivity: `curl -H "X-API-Key: YOUR_KEY" https://ai.ygxone.com/api/health`

---

## 📄 License

Part of the YG Ecosystem - Proprietary Software

---

## 🎉 Summary

**YG Search** provides a complete, production-ready search experience:

✅ **Powered by your own YG AI** - No third-party dependencies  
✅ **Google-like features** - AI summaries, suggestions, related searches  
✅ **Highly configurable** - Easy to customize and extend  
✅ **Performance optimized** - Caching, rate limiting, async processing  
✅ **Secure** - Input validation, HTTPS, API key authentication  
✅ **Analytics ready** - Track and improve search quality  

**Ready to deploy!** 🚀✨
