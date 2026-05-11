# 📋 YG Home - Production Deployment Checklist

## Pre-Deployment Verification

### Environment Setup
- [ ] PHP 8.2+ installed and configured
- [ ] Composer dependencies installed (`composer install --no-dev`)
- [ ] `.env` file configured with production values
- [ ] `APP_KEY` generated and stored securely
- [ ] Database credentials verified (MySQL recommended for production)
- [ ] SSL certificate installed and HTTPS enabled

### Configuration Review
```bash
# Verify critical settings in .env
grep APP_DEBUG .env          # Should be: false
grep APP_ENV .env            # Should be: production
grep QUEUE_CONNECTION .env   # Should be: sync (for cPanel)
grep CACHE_STORE .env        # Should be: database or redis
grep LOG_LEVEL .env          # Should be: error
```

### Database Preparation
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Generate session table: `php artisan session:table && php artisan migrate --force`
- [ ] Generate cache table: `php artisan cache:table && php artisan migrate --force`
- [ ] Seed initial data (if applicable)
- [ ] Create database backup before deployment

### File Permissions
```bash
# Set correct permissions
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
find storage/ -type f -exec chmod 664 {} \;
find bootstrap/cache/ -type f -exec chmod 664 {} \;
```

---

## Deployment Steps

### 1. Upload Files
```bash
# Option A: Git deployment
git pull origin main

# Option B: Manual upload via FTP/cPanel File Manager
# Upload all files except:
# - node_modules/
# - vendor/ (run composer install on server)
# - .git/
# - storage/logs/*.log
```

### 2. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### 3. Build Frontend Assets
```bash
npm ci
npm run build
```

### 4. Configure Environment
```bash
# Copy and edit .env
cp .env.example .env
nano .env  # Edit with production values

# Generate application key
php artisan key:generate
```

### 5. Run Migrations
```bash
php artisan migrate --force
```

### 6. Build Search Indexes
```bash
# Import existing content into search index
php artisan scout:import "App\Models\IndexedItem"

# Sync ecosystem modules
php artisan search:sync
```

### 7. Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 8. Setup Cron Jobs (cPanel)
```bash
# Add to cPanel > Cron Jobs
* * * * * cd /home/username/yg-home && php artisan schedule:run >> /dev/null 2>&1
```

### 9. Configure Web Server

#### Apache (.htaccess already configured)
- [ ] Document root set to `public/` directory
- [ ] mod_rewrite enabled
- [ ] HTTPS redirect uncommented in `.htaccess`

#### Nginx (if using VPS)
```nginx
server {
    listen 80;
    server_name ygxone.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name ygxone.com;
    
    root /var/www/yg-home/public;
    index index.php;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
    
    location ~ /\.(env|sqlite|db)$ {
        deny all;
    }
}
```

### 10. Final Verification
```bash
# Test health endpoint
curl https://ygxone.com/up

# Expected response:
# {"status":"healthy","timestamp":"...","checks":{"database":true,"cache":true,"search_index":true}}

# Test search functionality
curl "https://ygxone.com/search?q=test"

# Check error logs
tail -n 50 storage/logs/laravel.log
```

---

## Post-Deployment Tasks

### Monitoring Setup
- [ ] Configure uptime monitoring (UptimeRobot, Pingdom, etc.)
- [ ] Setup error tracking (Sentry, Bugsnag)
- [ ] Enable log rotation
- [ ] Configure email alerts for critical errors

### Performance Optimization
- [ ] Enable OPcache in PHP configuration
- [ ] Configure Redis if available (recommended)
- [ ] Setup CDN for static assets
- [ ] Enable Gzip compression

### Security Hardening
- [ ] Disable directory listing
- [ ] Remove installation files
- [ ] Block access to `.git/` directory
- [ ] Setup firewall rules
- [ ] Enable fail2ban (if on VPS)
- [ ] Regular security updates schedule

### Backup Strategy
- [ ] Database backup cron job:
  ```bash
  0 2 * * * mysqldump -u user -p'password' yg_home > /backups/yg_home_$(date +\%Y\%m\%d).sql
  ```
- [ ] File backup schedule
- [ ] Test restore procedure
- [ ] Store backups offsite

---

## Rollback Plan

If deployment fails:

1. **Stop Services**
   ```bash
   # Put site in maintenance mode
   php artisan down
   ```

2. **Restore Database**
   ```bash
   mysql -u user -p yg_home < /backups/yg_home_YYYYMMDD.sql
   ```

3. **Restore Files**
   ```bash
   # Restore from git
   git reset --hard HEAD~1
   
   # Or restore from backup tarball
   tar -xzf /backups/yg-home-backup.tar.gz
   ```

4. **Clear Caches**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

5. **Bring Site Back Up**
   ```bash
   php artisan up
   ```

---

## Validation Checklist

After deployment, verify:

- [ ] Homepage loads correctly
- [ ] Search returns results
- [ ] Autocomplete works (type 2+ characters)
- [ ] Click tracking records data
- [ ] Cross-module search includes Mail/Drive/Docs
- [ ] AI features respond (if configured)
- [ ] Mobile responsive design works
- [ ] HTTPS redirects properly
- [ ] Error pages display correctly (test 404)
- [ ] Health check returns healthy status
- [ ] No errors in logs

```bash
# Quick validation script
echo "Testing homepage..."
curl -s -o /dev/null -w "%{http_code}" https://ygxone.com/

echo "Testing search..."
curl -s -o /dev/null -w "%{http_code}" "https://ygxone.com/search?q=test"

echo "Testing health check..."
curl -s https://ygxone.com/up | jq '.status'

echo "Checking logs for errors..."
tail -n 100 storage/logs/laravel.log | grep -i "error" | wc -l
```

---

## Maintenance Schedule

### Daily
- [ ] Check error logs
- [ ] Monitor disk space
- [ ] Verify backups completed

### Weekly
- [ ] Run `php artisan search:analytics`
- [ ] Review trending queries
- [ ] Check zero-result searches
- [ ] Update dependencies (minor versions)

### Monthly
- [ ] Full system update
- [ ] Security audit
- [ ] Performance review
- [ ] Database optimization
- [ ] Review and rotate API keys

### Quarterly
- [ ] Disaster recovery test
- [ ] Load testing
- [ ] Security penetration test
- [ ] Architecture review

---

## Emergency Contacts

| Role | Contact | Phone |
|------|---------|-------|
| DevOps Lead | [Name] | [Number] |
| Database Admin | [Name] | [Number] |
| Security Officer | [Name] | [Number] |
| Project Manager | [Name] | [Number] |

---

**Deployment Date:** _______________  
**Deployed By:** _______________  
**Verified By:** _______________  

✅ **All items checked = Ready for production!**
