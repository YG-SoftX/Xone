# YG Home - Sovereign Search Engine & Ecosystem Hub

## Overview

YG Home is the central search and discovery hub for the YGXONE ecosystem, providing Google-style unified search across all YG services (Mail, Drive, DocX, Contacts, Calendar). It combines full-text search with AI-powered enhancements to deliver intelligent, context-aware results.

**Version:** 1.0.0  
**Framework:** Laravel 11.x  
**PHP:** 8.2+  
**Database:** SQLite (Development) / MySQL (Production)

---

## Features

### Core Capabilities
- ✅ **Unified Cross-Module Search**: Index and search content from all YG services
- ✅ **AI-Powered Answers**: Generate intelligent summaries using YG AI integration
- ✅ **Smart Autocomplete**: Real-time suggestions with throttling protection
- ✅ **Click Tracking**: Learn from user behavior to improve result ranking
- ✅ **Trending Searches**: Track popular queries across the ecosystem
- ✅ **"I'm Feeling Lucky"**: Direct redirect to top result
- ✅ **Voice Search Support**: Web Speech API integration (browser-dependent)

### Search Types
- **Web Search**: External web pages with PageRank scoring
- **Ecosystem Search**: Internal YG services (Mail, Drive, Docs, Contacts)
- **AI Enhanced**: Generated answers with source citations
- **Personalized**: User-specific results based on search history

---

## Architecture

### Technology Stack
- **Backend**: Laravel 11, PHP 8.2+
- **Search Engine**: Laravel Scout + TNTSearch Driver
- **Database**: SQLite (dev), MySQL 8.0+ (production)
- **Frontend**: Blade Templates, Tailwind CSS, Alpine.js
- **Queue**: Database driver (sync mode for cPanel)
- **Cache**: Database cache

### Key Components

```
yg-home/
├── app/
│   ├── Http/Controllers/
│   │   └── SearchController.php      # Main search logic
│   ├── Services/
│   │   ├── UnifiedSearchService.php  # Cross-module search orchestration
│   │   └── SearchIndexerService.php  # Module synchronization
│   ├── Models/
│   │   └── IndexedItem.php           # Search index model
│   └── Console/Commands/
│       └── SyncSearchIndex.php       # CLI sync command
├── database/migrations/
│   ├── create_search_tables.php      # Training data, rankings
│   ├── create_indexed_items_table.php # Unified index
│   └── add_search_analytics_tables.php
├── resources/views/
│   ├── layouts/app.blade.php         # Base layout
│   └── search/
│       ├── home.blade.php            # Landing page
│       └── results.blade.php         # Results display
├── routes/
│   ├── web.php                       # HTTP routes
│   └── console.php                   # Artisan commands
├── config/scout.php                  # Search configuration
└── .env                             # Environment variables
```

### Data Flow

```
User Query → SearchController → UnifiedSearchService
                                    ↓
                    ┌───────────────┴───────────────┐
                    ↓                               ↓
            Web Pages (DB)                  Indexed Items (Scout)
                    ↓                               ↓
            Parameterized LIKE              TNTSearch Full-Text
                    ↓                               ↓
            ┌───────────────┴───────────────┐
                    ↓
            AI Enhancement (Optional)
                    ↓
            Results + Suggestions + Training
```

---

## Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL 8.0+ (for production)
- Node.js 18+ (optional, for asset compilation)

### Quick Start (Development)

```bash
# 1. Clone and navigate
cd yg-home

# 2. Install dependencies
composer install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Setup database
touch database/database.sqlite

# 5. Run migrations
php artisan migrate --force

# 6. Build search indexes
php artisan scout:import "App\Models\IndexedItem"

# 7. Sync ecosystem modules
php artisan search:sync

# 8. Start development server
php artisan serve --host=0.0.0.0 --port=8001
```

### Production Deployment (cPanel)

See `scripts/deploy-cpanel.sh` for automated deployment.

**Key Configuration Changes:**
```env
APP_ENV=production
APP_DEBUG=false
QUEUE_CONNECTION=sync
CACHE_STORE=database
SESSION_DRIVER=database
LOG_LEVEL=error
```

**Required Cron Job:**
```bash
* * * * * cd /path/to/yg-home && php artisan schedule:run >> /dev/null 2>&1
```

---

## Configuration

### Environment Variables (.env)

```env
# Application
APP_NAME="YGXONE Search"
APP_URL=https://ygxone.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yg_home
DB_USERNAME=root
DB_PASSWORD=your_password

# Search Engine
SCOUT_DRIVER=tntsearch
TNTSEARCH_FUZZINESS=true
TNTSEARCH_BOOLEAN=true
TNTSEARCH_MAX_DOCS=500

# YG AI Integration
YG_AI_API_URL=http://localhost/yg-ai/api
YG_AI_API_KEY=your_api_key_here

# Cross-Module Database Paths
YG_MAIL_DB_PATH="../YG Mail/database/database.sqlite"
YG_DRIVE_DB_PATH="../YG Drive/database/database.sqlite"
YG_DOCX_DB_PATH="../YG DocX/database/database.sqlite"
YG_CONTACTS_DB_PATH="../YG Contacts/database/database.sqlite"
YG_CALENDAR_DB_PATH="../YG Calendar/database/database.sqlite"
```

### Search Configuration (config/scout.php)

```php
return [
    'driver' => env('SCOUT_DRIVER', 'tntsearch'),
    
    'tntsearch' => [
        'storage' => storage_path('indexes'),
        'fuzziness' => env('TNTSEARCH_FUZZINESS', true),
        'fuzzy' => [
            'prefix_length' => 2,
            'max_expansions' => 50,
            'distance' => 2,
        ],
        'asYouType' => false,
        'searchBoolean' => env('TNTSEARCH_BOOLEAN', true),
        'maxDocs' => env('TNTSEARCH_MAX_DOCS', 500),
    ],
];
```

---

## Usage

### Basic Search

```
GET /search?q=project+deadline&type=all
```

**Parameters:**
- `q` (required): Search query (max 500 chars)
- `type` (optional): Filter by service (`all`, `web`, `mail`, `drive`, `docs`, `contacts`)

### API Endpoints

#### Autocomplete Suggestions
```
GET /api/search/suggestions?q=proj
Rate Limit: 60 requests/minute
Response: { "suggestions": { "history": [...], "trending": [...], "ai": [...] } }
```

#### Track Click
```
POST /search/track-click
Body: { "query": "...", "result_url": "...", "result_type": "web", "position": 1 }
Rate Limit: 120 requests/minute
```

#### Voice Search
```
POST /search/voice
Body: FormData with audio file (webm/mp4/wav)
Rate Limit: 10 requests/minute
```

#### I'm Feeling Lucky
```
GET /search/lucky?q=query
Rate Limit: 30 requests/minute
Redirects to first result URL
```

### Command Line

#### Sync All Modules
```bash
php artisan search:sync
```

Output:
```
Starting ecosystem search sync...
- mail: indexed 1247 items
- drive: indexed 523 items
- docs: indexed 89 items
- contacts: indexed 156 items
Sync complete!
```

#### Rebuild Search Index
```bash
php artisan scout:import "App\Models\IndexedItem"
```

---

## Database Schema

### Core Tables

#### `indexed_items`
Unified search index for all YG services.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| source_id | VARCHAR | Original item ID in source module |
| service | VARCHAR | Service name (mail, drive, docs, contacts) |
| title | VARCHAR | Item title/subject |
| content | TEXT | Full text content |
| snippet | TEXT | Preview text (first 200 chars) |
| url | VARCHAR | Deep link to item |
| user_id | BIGINT | Owner (nullable for public items) |
| metadata | JSON | Service-specific data |
| relevance_boost | FLOAT | Ranking multiplier (default 1.0) |

#### `search_training_data`
AI learning feedback loop.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| query | VARCHAR | Search query |
| result_count | INT | Number of results returned |
| had_clicks | BOOLEAN | User clicked any result |
| session_id | VARCHAR | Session identifier |
| user_id | BIGINT | Authenticated user (nullable) |

#### `user_search_patterns`
Personalization data.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| user_id | BIGINT | User reference |
| query_pattern | VARCHAR | Normalized pattern (e.g., "how to *") |
| frequency | INT | Search count |
| last_searched | TIMESTAMP | Most recent search |

#### `search_result_rankings`
Click-through optimization.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| url | VARCHAR | Result URL |
| result_type | VARCHAR | Type (web, mail, drive, etc.) |
| ranking_score | DECIMAL | Calculated score (default 1.0) |
| click_count | INT | Total clicks |
| impression_count | INT | Times shown |

#### `trending_searches`
Popular queries tracking.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| query | VARCHAR | Search query |
| count | INT | Daily occurrence |
| trend_date | DATE | Date of trend |

#### `web_pages`
External web content index.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| url | VARCHAR | Page URL (unique) |
| title | VARCHAR | Page title |
| content | TEXT | Full page content |
| snippet | TEXT | Meta description |
| domain | VARCHAR | Domain name |
| page_rank | FLOAT | Authority score |
| visit_count | INT | Crawl frequency |

---

## Security

### Implemented Measures

✅ **Input Sanitization**: All queries sanitized with `strip_tags()` and length limits  
✅ **Parameterized Queries**: SQL injection prevention via PDO prepared statements  
✅ **Rate Limiting**: Throttled API endpoints (10-120 req/min depending on endpoint)  
✅ **Authentication**: Protected routes require valid session  
✅ **CORS Headers**: Configured in `.htaccess`  
✅ **Security Headers**: X-Frame-Options, XSS-Protection, Content-Type-Options  
✅ **File Protection**: `.htaccess` blocks access to `.env`, `.sqlite`, config files  
✅ **HTTPS Enforcement**: Redirect rules (uncomment when SSL active)  

### Rate Limits

| Endpoint | Limit | Window |
|----------|-------|--------|
| `/api/search/suggestions` | 60 | 1 minute |
| `/search/track-click` | 120 | 1 minute |
| `/search/voice` | 10 | 1 minute |
| `/search/lucky` | 30 | 1 minute |

### Best Practices

- Never commit `.env` file
- Use strong `APP_KEY` (auto-generated)
- Enable HTTPS in production
- Set `APP_DEBUG=false` in production
- Regularly rotate `YG_AI_API_KEY`
- Monitor rate limit violations in logs

---

## Performance Optimization

### Current Optimizations

- **SQLite Indexes**: Proper indexing on frequently queried columns
- **Query Limits**: Max 10 web results, 20 ecosystem results per query
- **Lazy Loading**: Results loaded on-demand
- **Skeleton UI**: Perceived performance during loading
- **Sync Queue**: No background workers needed (cPanel compatible)

### Recommended Enhancements

1. **Enable Redis Cache** (if available):
   ```env
   CACHE_STORE=redis
   QUEUE_CONNECTION=redis
   ```

2. **Add Result Caching**:
   ```php
   Cache::remember("search:{$query}:{$type}", 300, function() {
       return $this->searchService->search($query, ['type' => $type]);
   });
   ```

3. **Implement Pagination**:
   - Add `?page=2` support for large result sets
   - Use cursor-based pagination for better performance

4. **Database Migration to MySQL**:
   - Better concurrency handling
   - Read replicas for scaling
   - Advanced full-text search capabilities

---

## Troubleshooting

### Common Issues

#### Search Returns No Results

**Problem**: Empty search results despite having data.

**Solution**:
```bash
# 1. Check if modules are synced
php artisan search:sync

# 2. Verify database paths in .env
cat .env | grep DB_PATH

# 3. Check indexed_items table
php artisan tinker
>>> App\Models\IndexedItem::count();

# 4. Rebuild Scout index
php artisan scout:import "App\Models\IndexedItem"
```

#### Slow Search Performance

**Problem**: Search takes >2 seconds.

**Solution**:
```bash
# 1. Check database size
ls -lh database/database.sqlite

# 2. Optimize SQLite
php artisan tinker
>>> DB::statement('VACUUM');

# 3. Enable query logging to identify bottlenecks
# In .env: LOG_LEVEL=debug
```

#### AI Features Not Working

**Problem**: AI answer/suggestions not appearing.

**Solution**:
```bash
# 1. Verify YG AI connectivity
curl http://localhost/yg-ai/api/up

# 2. Check API key configuration
grep YG_AI .env

# 3. Review error logs
tail -f storage/logs/laravel.log
```

#### Permission Errors

**Problem**: "Permission denied" errors.

**Solution**:
```bash
# cPanel deployment
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
find storage/ -type f -exec chmod 664 {} \;
```

### Logs Location

```bash
# Application logs
storage/logs/laravel.log

# Server logs (cPanel)
/home/username/access-logs/ygxone.com

# PHP error log (check php.ini)
/var/log/php_errors.log
```

---

## Testing

### Manual Testing Checklist

- [ ] Homepage loads without errors
- [ ] Search query returns relevant results
- [ ] Autocomplete shows suggestions after 2+ characters
- [ ] Click tracking updates database
- [ ] "I'm Feeling Lucky" redirects correctly
- [ ] Cross-module search includes Mail/Drive/Docs
- [ ] AI answer appears for complex queries
- [ ] Rate limiting triggers after threshold
- [ ] Mobile responsive design works

### Automated Tests (Future)

```bash
# Run PHPUnit tests (when implemented)
php artisan test

# Check code style
./vendor/bin/pint

# Static analysis
./vendor/bin/phpstan analyse
```

---

## Monitoring & Analytics

### Key Metrics to Track

1. **Search Volume**: Queries per day/hour
2. **Zero-Result Rate**: Percentage of searches with no results
3. **Click-Through Rate**: % of searches resulting in clicks
4. **Average Position Clicked**: Which results users prefer
5. **AI Answer Acceptance**: How often AI answers are useful
6. **Response Time**: P50, P95, P99 latency

### Accessing Analytics

```sql
-- Top trending queries today
SELECT query, count FROM trending_searches 
WHERE trend_date = CURDATE() 
ORDER BY count DESC LIMIT 20;

-- Zero-result queries (improvement opportunities)
SELECT query, COUNT(*) as occurrences 
FROM search_training_data 
WHERE result_count = 0 
GROUP BY query 
ORDER BY occurrences DESC 
LIMIT 50;

-- Most clicked results
SELECT url, result_type, click_count 
FROM search_result_rankings 
ORDER BY click_count DESC 
LIMIT 20;
```

---

## Development Guidelines

### Adding New Searchable Module

1. **Create migration** for new service (if needed)
2. **Update `SearchIndexerService::syncAll()`**:
   ```php
   public function syncAll(): array
   {
       return [
           'mail'     => $this->syncMail(),
           'drive'    => $this->syncDrive(),
           'docs'     => $this->syncDocs(),
           'contacts' => $this->syncContacts(),
           'calendar' => $this->syncCalendar(), // NEW
       ];
   }
   ```

3. **Add sync method**:
   ```php
   private function syncCalendar(): int
   {
       // Implementation following existing patterns
   }
   ```

4. **Update icon mapping** in `UnifiedSearchService::getServiceIcon()`

5. **Run sync**:
   ```bash
   php artisan search:sync
   ```

### Code Style

- Follow PSR-12 coding standards
- Use type hints for all method parameters
- Document public methods with PHPDoc
- Keep methods under 100 lines (refactor if longer)
- Use meaningful variable names (avoid `$data`, `$temp`)

---

## Contributing

1. Fork the repository
2. Create feature branch: `git checkout -b feature/amazing-feature`
3. Commit changes: `git commit -m 'Add amazing feature'`
4. Push to branch: `git push origin feature/amazing-feature`
5. Open Pull Request

### Commit Message Convention

```
feat: add calendar search support
fix: resolve null pointer in AI answer generation
docs: update installation instructions
perf: optimize TNTSearch indexing performance
```

---

## License

MIT License - See LICENSE file for details

---

## Support

- **Documentation**: https://docs.ygxone.com/search
- **Issues**: https://github.com/ygxone/yg-home/issues
- **Email**: support@ygxone.com
- **Community**: https://community.ygxone.com

---

## Acknowledgments

- Laravel Framework
- TNTSearch Engine
- YGXONE Ecosystem Team
- Open Source Contributors

---

**Last Updated:** 2026-05-07  
**Maintained By:** YGXONE Engineering Team
