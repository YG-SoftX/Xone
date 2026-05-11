# 🚀 YG Search Quick Setup Guide

## ⚡ 5-Minute Setup

### **Step 1: Configure Environment** (1 minute)

Edit `yg-home/.env`:

```env
YG_AI_URL=https://ai.ygxone.com
YG_AI_API_KEY=your_actual_api_key_from_yg_ai
```

---

### **Step 2: Get API Key from YG AI** (2 minutes)

1. Open your YG AI admin panel: `https://ai.ygxone.com/admin`
2. Navigate to **API Keys** or **Developer Portal**
3. Click **Create New API Key**
4. Name it: "YG Home Search"
5. Copy the generated key
6. Paste into `.env` as `YG_AI_API_KEY`

---

### **Step 3: Clear Cache** (30 seconds)

```bash
cd /path/to/yg-home
php artisan config:clear
php artisan cache:clear
```

---

### **Step 4: Test Search** (1 minute)

Visit: `http://localhost:8001/search?q=test`

Or via API:

```bash
curl -X POST http://localhost:8001/api/search \
  -H "Content-Type: application/json" \
  -d '{"query": "laravel tutorial"}'
```

---

### **Step 5: Verify Results** (1.5 minutes)

Check that you see:
- ✅ Search results list
- ✅ AI-generated summary box
- ✅ Related searches suggestions
- ✅ Autocomplete working (type in search box)

---

## 🔍 Testing Checklist

Run through these tests:

| Test | Command/Action | Expected Result |
|------|---------------|----------------|
| Basic search | Visit `/search?q=php` | Shows results |
| AI summary | Check top of results | Summary box appears |
| Suggestions | Type in search box | Dropdown with suggestions |
| Related searches | Scroll to bottom | "People also ask" section |
| Pagination | Click page 2 | Next results load |
| Empty query | Visit `/search` | Shows trending/recent |
| Rate limit | 60+ searches/hour | Gets 429 error |

---

## 🐛 Common Issues & Fixes

### **Issue: "Search temporarily unavailable"**

**Cause:** YG AI not reachable  
**Fix:**
```bash
# Test connectivity
curl https://ai.ygxone.com/api/health

# Check API key
echo $YG_AI_API_KEY

# Verify URL in .env matches actual YG AI URL
grep YG_AI_URL .env
```

---

### **Issue: No AI Summary**

**Cause:** YG AI doesn't support summarization yet  
**Fix:** Add `summarize_search` action to YG AI, or use fallback:

```php
// In YgSearchService::generateAiSummary()
if (!$response->successful()) {
    // Fallback to first result snippet
    return substr($results[0]['snippet'] ?? '', 0, 200) . '...';
}
```

---

### **Issue: Slow Performance**

**Cause:** No caching enabled  
**Fix:**
```bash
# Enable Redis cache (recommended)
sed -i 's/CACHE_STORE=.*/CACHE_STORE=redis/' .env

# Or increase cache duration in code
# Change 600 to 1800 (30 minutes)
Cache::remember($cacheKey, 1800, ...)
```

---

## 📊 Monitoring Setup

### **View Real-time Searches**

```bash
# Watch live search queries
tail -f storage/logs/laravel.log | grep "YG Search"
```

---

### **Database Analytics**

Create migration for analytics table:

```bash
php artisan make:migration create_search_analytics_table
```

```php
// In migration file
public function up()
{
    Schema::create('search_analytics', function (Blueprint $table) {
        $table->id();
        $table->string('query');
        $table->unsignedBigInteger('user_id')->nullable();
        $table->integer('results_count');
        $table->integer('search_time_ms');
        $table->timestamps();
        
        $table->index('query');
        $table->index('created_at');
    });
}
```

```bash
php artisan migrate
```

---

## 🎨 UI Integration

The search is already integrated in YG Home views. To customize:

### **Modify Search Homepage**

Edit: `resources/views/search/home.blade.php`

```blade
<!-- Add custom branding -->
<h1 class="logo">
    <span style="color: #9B1B30;">YG</span> Search
</h1>

<!-- Customize search box -->
<input type="text" 
       placeholder="Search the web with YG AI..." 
       class="search-input">
```

---

### **Customize Results Page**

Edit: `resources/views/search/results.blade.php`

```blade
<!-- AI Summary Box -->
@if($results['ai_summary'])
<div class="ai-summary-box">
    <h3>🤖 AI Summary</h3>
    <p>{{ $results['ai_summary'] }}</p>
</div>
@endif

<!-- Related Searches -->
<div class="related-searches">
    <h4>People also ask:</h4>
    @foreach($results['related_searches'] as $related)
        <a href="/search?q={{ urlencode($related) }}">{{ $related }}</a>
    @endforeach
</div>
```

---

## 🔧 Advanced Configuration

### **Enable Ecosystem Search**

To search YG Mail, Drive, Docs alongside web:

1. Set API keys in `.env`:
```env
YG_MAIL_API_KEY=your_mail_key
YG_DRIVE_API_KEY=your_drive_key
YG_DOCX_API_KEY=your_docx_key
```

2. Update `YgSearchService::searchEcosystem()`:
```php
public function searchEcosystem(string $query, string $userId): array
{
    return [
        'mail' => $this->searchMail($query, $userId),
        'drive' => $this->searchDrive($query, $userId),
        'docs' => $this->searchDocx($query, $userId),
    ];
}

private function searchMail(string $query, string $userId): array
{
    $response = Http::withHeaders([
        'X-API-Key' => config('services.yg_mail.api_key'),
    ])->get(config('services.yg_mail.url') . '/api/search', [
        'q' => $query,
        'user_id' => $userId,
    ]);
    
    return $response->json()['results'] ?? [];
}
```

---

### **Add Custom Filters**

```php
// In SearchController
$results = $this->ygSearchService->searchWeb($query, [
    'date_range' => 'last_year',  // last_day, last_week, last_month, last_year
    'language' => 'en',           // en, es, fr, etc.
    'region' => 'us',             // us, uk, in, etc.
    'safe_search' => true,        // Filter adult content
]);
```

---

## 🚀 Deployment to Production

### **cPanel Deployment**

1. Upload `yg-home` folder to cPanel
2. Set document root to `public/`
3. Configure `.htaccess` with environment variables:

```apache
SetEnv YG_AI_URL https://ai.ygxone.com
SetEnv YG_AI_API_KEY your_production_api_key
```

4. Run migrations:
```bash
php artisan migrate --force
```

5. Optimize:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

### **Docker Deployment**

```dockerfile
FROM php:8.2-fpm

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader

ENV YG_AI_URL=https://ai.ygxone.com
ENV YG_AI_API_KEY=${YG_AI_API_KEY}

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

```bash
docker build -t yg-home .
docker run -p 8000:8000 -e YG_AI_API_KEY=your_key yg-home
```

---

## 📈 Performance Optimization

### **Enable Redis Caching**

```bash
# Install Redis
sudo apt install redis-server

# Update .env
CACHE_STORE=redis
SESSION_DRIVER=redis

# Restart Laravel
php artisan serve
```

---

### **Optimize Database Queries**

Add indexes to `search_analytics` table:

```sql
ALTER TABLE search_analytics ADD INDEX idx_query (query);
ALTER TABLE search_analytics ADD INDEX idx_created (created_at);
```

---

### **Use CDN for Static Assets**

In `vite.config.js`:

```javascript
export default defineConfig({
    plugins: [laravel()],
    base: 'https://cdn.ygxone.com/',  // Use CDN
});
```

---

## 🎉 You're Ready!

YG Search is now fully operational with your YG AI platform!

**Next Steps:**
1. Monitor search analytics daily
2. Review zero-result queries weekly
3. Optimize based on user behavior
4. Add new features as needed

**Questions?** Check `YG_SEARCH_DOCUMENTATION.md` for detailed docs.

Happy Searching! 🔍✨
