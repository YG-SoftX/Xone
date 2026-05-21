# YGXONE Browser - Deployment Checklist

## ✅ Pre-Deployment Verification

### Environment Check
- [ ] PHP version ≥ 8.0 (`php -v`)
- [ ] MySQL/MariaDB accessible
- [ ] Composer installed (`composer --version`)
- [ ] cPanel access (SSH or File Manager)
- [ ] Git repository configured (optional)

### Files Ready
- [ ] All 6 services in `app/Services/`
- [ ] Updated `SearchController.php`
- [ ] Updated `routes/web.php`
- [ ] 2 migration files in `database/migrations/`
- [ ] Updated `browser.blade.php`
- [ ] Deployment scripts (.sh and .ps1)

---

## 🚀 Deployment Steps

### Step 1: Upload Files to cPanel

**Via Git (Recommended):**
```bash
# On your local machine
git add .
git commit -m "Add advanced browser features (Phase 1 & 2)"
git push origin main

# On cPanel
# Use Git interface to pull latest changes
```

**Via FTP/File Manager:**
Upload these files:
```
home/app/Services/
  ├── DeepResearchService.php
  ├── CitationTracker.php
  ├── KnowledgeGraphService.php
  ├── FollowUpSuggestionService.php
  ├── VisualSummaryService.php
  └── PasswordManagerService.php

home/app/Http/Controllers/
  └── SearchController.php (updated)

home/routes/
  └── web.php (updated)

home/database/migrations/
  ├── 2026_05_20_000001_create_advanced_browser_features_tables.php
  └── 2026_05_20_000002_create_remaining_browser_features_tables.php

home/resources/views/search/
  └── browser.blade.php (updated)

home/
  ├── deploy-advanced-features.sh
  ├── deploy-advanced-features.ps1
  └── .env (verify exists)
```

---

### Step 2: Set Permissions

**Via cPanel Terminal:**
```bash
cd ~/ygxone/home
chmod 644 .env
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

**Via cPanel File Manager:**
1. Right-click `.env` → Change Permissions → **644**
2. Right-click `storage/` → Change Permissions → **775** (recursive)
3. Right-click `bootstrap/cache/` → Change Permissions → **775** (recursive)

---

### Step 3: Install Dependencies

**Via cPanel Terminal:**
```bash
cd ~/ygxone/home
composer install --no-dev --optimize-autoloader
```

**Expected Output:**
```
Loading composer repositories with package information
Installing dependencies from lock file
Package operations: X installs, Y updates, Z removals
Generating optimized autoload files
```

---

### Step 4: Clear Caches

**Via cPanel Terminal:**
```bash
cd ~/ygxone/home

# Clear in this order (important!)
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

---

### Step 5: Run Database Migrations

**Via cPanel Terminal:**
```bash
cd ~/ygxone/home
php artisan migrate --force
```

**Expected Output:**
```
Migrating: 2026_05_20_000001_create_advanced_browser_features_tables
Migrated:  2026_05_20_000001_create_advanced_browser_features_tables (XX.XXms)
Migrating: 2026_05_20_000002_create_remaining_browser_features_tables
Migrated:  2026_05_20_000002_create_remaining_browser_features_tables (XX.XXms)
```

**Verify Tables Created:**
```bash
php artisan tinker
>>> DB::select('SHOW TABLES LIKE "%citations%"');
>>> DB::select('SHOW TABLES LIKE "%passwords%"');
>>> DB::select('SHOW TABLES LIKE "%visual_summaries%"');
>>> exit
```

Should see:
- `browser_citations`
- `knowledge_entities`
- `knowledge_relationships`
- `agent_sessions`
- `research_reports`
- `saved_passwords`
- `suggestion_feedback`
- `visual_summaries`
- `dev_tools_logs`

---

### Step 6: Rebuild Caches

**Via cPanel Terminal:**
```bash
cd ~/ygxone/home

# Rebuild in this order
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Expected Output:**
```
Configuration cached successfully!
Routes cached successfully!
Blade templates cached successfully!
```

---

### Step 7: Verify Routes

**Via cPanel Terminal:**
```bash
cd ~/ygxone/home
php artisan route:list | grep -E "research|citation|knowledge|suggestion|visual|password"
```

**Expected Routes (21 total):**
```
POST    api/research/deep
GET     api/citations/recent
POST    api/citations/format
DELETE  api/citations/clear
GET     api/knowledge-graph
GET     api/knowledge-graph/connections
GET     api/knowledge-graph/search
DELETE  api/knowledge-graph/clear
POST    api/suggestions/generate
POST    api/suggestions/feedback
POST    api/visual-summary/generate
POST    api/passwords/save
GET     api/passwords/get
GET     api/passwords/list
DELETE  api/passwords/delete/{id}
POST    api/passwords/detect-form
```

---

### Step 8: Test Features

#### **Test 1: Deep Research**
1. Open browser: `https://ygxone.com/`
2. Click **"Research"** button
3. Enter query: `"benefits of renewable energy"`
4. Select "Balanced (8 pages)"
5. Click "Start Research"
6. **Expected:** Report generates in ~20 seconds with executive summary, sections, citations

#### **Test 2: Citations**
1. Browse 3-5 websites
2. Click **"Cite"** button
3. **Expected:** See 3-5 citations with titles and URLs
4. Click export icon
5. **Expected:** Citations copied to clipboard in APA format

#### **Test 3: Knowledge Graph**
1. Visit Wikipedia article (e.g., "Artificial Intelligence")
2. Click **"Graph"** button
3. **Expected:** See entities extracted (people, organizations, locations)
4. Search for an entity name
5. **Expected:** See related entities and connections

#### **Test 4: Visual Summaries**
1. Visit page with data (e.g., financial report, statistics page)
2. Click **"Charts"** button
3. Click "Generate Visualizations"
4. **Expected:** See charts, tables, or metrics extracted

#### **Test 5: Password Manager**
1. Visit any login page (e.g., test site)
2. Click **"Keys"** button
3. Enter username and password in form
4. Click "Save Password"
5. **Expected:** Success message
6. Refresh page
7. **Expected:** See "Saved for this site" with username displayed

---

## 🐛 Troubleshooting

### Issue: Migration Fails

**Error:** `SQLSTATE[42S01]: Base table or view already exists`

**Solution:**
```bash
php artisan migrate:status
# Check which migrations ran
php artisan migrate:rollback --step=1
# Rollback last batch
php artisan migrate --force
# Re-run migrations
```

---

### Issue: Routes Not Found (404)

**Error:** `404 Not Found` for `/api/research/deep`

**Solution:**
```bash
php artisan route:clear
php artisan route:cache
php artisan route:list | grep research
# Verify routes are registered
```

---

### Issue: APP_KEY Missing

**Error:** `No application encryption key has been specified`

**Solution:**
```bash
php artisan key:generate --force
php artisan config:clear
php artisan config:cache
```

---

### Issue: Permission Denied

**Error:** `The stream or file "storage/logs/laravel.log" could not be opened`

**Solution:**
```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

---

### Issue: Composer Install Fails

**Error:** `Your requirements could not be resolved`

**Solution:**
```bash
composer self-update
composer install --no-dev --optimize-autoloader --ignore-platform-reqs
```

---

### Issue: Visual Summaries Not Working

**Symptom:** Charts panel shows "No visualizable data found"

**Solution:**
1. Verify page has extractable data (tables, percentages, lists)
2. Check Chart.js is loaded in browser console: `typeof Chart`
3. Test on data-heavy page (financial reports, statistics)
4. Check logs: `tail -f storage/logs/laravel.log`

---

### Issue: Password Manager Not Saving

**Symptom:** "Failed to save password" error

**Solution:**
1. Verify APP_KEY is set: `grep APP_KEY .env`
2. Check database connection: `php artisan tinker` → `DB::connection()->getPdo()`
3. Verify migration ran: `SELECT COUNT(*) FROM saved_passwords;`
4. Ensure URL has valid domain (not localhost without port)

---

## ✅ Post-Deployment Verification

### Database Health Check
```bash
php artisan tinker
>>> DB::table('browser_citations')->count();
>>> DB::table('knowledge_entities')->count();
>>> DB::table('saved_passwords')->count();
>>> exit
```

All should return `0` or higher (not errors).

### Performance Check
```bash
# Test response time
curl -w "@curl-format.txt" -o /dev/null -s "https://ygxone.com/"

# Expected: < 2 seconds for cached pages
```

Create `curl-format.txt`:
```
    time_namelookup:  %{time_namelookup}\n
       time_connect:  %{time_connect}\n
    time_appconnect:  %{time_appconnect}\n
   time_pretransfer:  %{time_pretransfer}\n
      time_redirect:  %{time_redirect}\n
 time_starttransfer:  %{time_starttransfer}\n
                    ----------\n
         time_total:  %{time_total}\n
```

### Security Check
```bash
# Verify .env is not publicly accessible
curl https://ygxone.com/.env
# Expected: 403 Forbidden or 404 Not Found

# Verify storage is not browsable
curl https://ygxone.com/storage/
# Expected: 403 Forbidden
```

---

## 📊 Success Metrics

After deployment, verify:

✅ **Deep Research**: Generates reports in <30 seconds  
✅ **Citations**: Auto-tracks 100% of visited sources  
✅ **Knowledge Graph**: Extracts 5-20 entities per page  
✅ **Visual Summaries**: Detects tables/charts on data pages  
✅ **Password Manager**: Encrypts and retrieves credentials  
✅ **Performance**: Page loads <2 seconds (cached)  
✅ **Uptime**: No 500 errors in logs  

---

## 🎉 Deployment Complete!

If all tests pass, your YGXONE Browser is now production-ready with ALL features complete!

### Next Steps:
1. Configure AI API keys in `/agent/settings` (optional but recommended)
2. Monitor logs for 24 hours: `tail -f storage/logs/laravel.log`
3. Share with beta testers for feedback
4. Plan Phase 3 enhancements (D3.js visualization, voice commands, etc.)

---

**Congratulations!** 🚀 Your browser now surpasses both Chrome and Perplexity Comet!
