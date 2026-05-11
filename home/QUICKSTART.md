# 🚀 YG Home - Quick Start Guide

## ⚡ 5-Minute Setup (Development)

```bash
# 1. Navigate to project
cd yg-home

# 2. Install dependencies
composer install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Create SQLite database
touch database/database.sqlite

# 5. Run migrations
php artisan migrate --force

# 6. Build search index
php artisan scout:import "App\Models\IndexedItem"

# 7. Sync ecosystem modules
php artisan search:sync

# 8. Start server
php artisan serve --host=0.0.0.0 --port=8001
```

**Access:** http://localhost:8001

---

## 🔧 Common Commands

### Search Management

```bash
# Sync all modules (Mail, Drive, Docs, Contacts)
php artisan search:sync

# View search analytics (last 7 days)
php artisan search:analytics

# View analytics for last 30 days
php artisan search:analytics --days=30

# Clear search cache
php artisan search:cache:clear

# Rebuild entire search index
php artisan scout:import "App\Models\IndexedItem"
```

### System Maintenance

```bash
# Check system health
curl http://localhost:8001/up

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📊 Monitoring

### View Analytics Dashboard

```bash
php artisan search:analytics
```

**Output Example:**
```
📊 YG Home Search Analytics (Last 7 Days)

🔍 Total Searches: 1,247

❌ Zero-Result Searches: 89 (7.14%)

✅ Click-Through Rate: 456 (36.57%)

🔥 Top 10 Trending Queries:
+------+--------------------------+-------+
| Rank | Query                    | Count |
+------+--------------------------+-------+
| 1    | project deadline         | 45    |
| 2    | invoice template         | 38    |
| 3    | team meeting             | 32    |
+------+--------------------------+-------+

⚠️  Top 10 Zero-Result Queries (Add Content):
+------+--------------------------+-------------+
| Rank | Query                    | Occurrences |
+------+--------------------------+-------------+
| 1    | blockchain integration   | 12          |
| 2    | API documentation        | 8           |
+------+--------------------------+-------------+

🎯 Top 10 Most Clicked Results:
+-------------------------------------+------+--------+
| URL                                 | Type | Clicks |
+-------------------------------------+------+--------+
| /mail/view/123                      | mail | 156    |
| /drive/files/456                    | drive| 134    |
+-------------------------------------+------+--------+

📦 Index Statistics:
   Total Indexed Items: 2,847
   - mail: 1,247 (43.8%)
   - drive: 823 (28.9%)
   - docs: 456 (16.0%)
   - contacts: 321 (11.3%)

✨ Analytics complete!
```

---

## 🐛 Troubleshooting

### Problem: Search Returns No Results

**Quick Fix:**
```bash
# 1. Check if modules are synced
php artisan search:sync

# 2. Verify indexed items count
php artisan tinker
>>> App\Models\IndexedItem::count();

# 3. If zero, rebuild index
php artisan scout:import "App\Models\IndexedItem"
```

### Problem: Slow Performance

**Quick Fix:**
```bash
# 1. Clear cache
php artisan cache:clear

# 2. Optimize database
php artisan tinker
>>> DB::statement('VACUUM');  # For SQLite

# 3. Check query logs
tail -f storage/logs/laravel.log | grep "query"
```

### Problem: AI Features Not Working

**Quick Fix:**
```bash
# 1. Test YG AI connection
curl http://localhost/yg-ai/api/up

# 2. Verify configuration
grep YG_AI .env

# 3. Check error logs
tail -n 50 storage/logs/laravel.log
```

---

## 📁 Important Files

| File | Purpose |
|------|---------|
| `.env` | Environment configuration |
| `database/database.sqlite` | SQLite database (dev) |
| `storage/logs/laravel.log` | Application logs |
| `storage/indexes/` | TNTSearch index files |
| `config/scout.php` | Search engine settings |
| `routes/web.php` | HTTP routes |

---

## 🔐 Security Checklist

Before going live:

- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Generate strong `APP_KEY` (already done via `key:generate`)
- [ ] Enable HTTPS (uncomment rules in `.htaccess`)
- [ ] Set proper file permissions:
  ```bash
  chmod -R 775 storage/
  chmod -R 775 bootstrap/cache/
  ```
- [ ] Block directory listing (already in `.htaccess`)
- [ ] Review rate limits in routes
- [ ] Rotate `YG_AI_API_KEY` regularly

---

## 🎯 Next Steps

1. **Customize Search**: Edit `config/scout.php` to tune search behavior
2. **Add Modules**: Follow README.md "Adding New Searchable Module" section
3. **Monitor Analytics**: Run `php artisan search:analytics` weekly
4. **Optimize Performance**: Consider Redis cache for production
5. **Setup Monitoring**: Configure uptime monitoring for `/up` endpoint

---

## 📞 Support

- **Documentation**: See `README.md` for detailed docs
- **Issues**: Report bugs on GitHub
- **Community**: Join YGXONE Discord/Slack

**Happy Searching! 🔍**
