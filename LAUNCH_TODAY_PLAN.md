# 🚀 YG LAUNCH TODAY - Emergency Deployment Plan

## ⚡ Mission: Launch YG Search + YG Account TODAY

**Target:** Get both systems live and accessible by end of day  
**Date:** 2026-05-07  
**Status:** 🟡 IN PROGRESS → 🟢 LIVE

---

## 📋 CRITICAL CHECKLIST (Must Complete)

### **Phase 1: Database Setup (30 minutes)**

#### **YG Account Database**
```bash
cd /path/to/yg-account

# 1. Run all migrations
php artisan migrate --force

# 2. Generate app key
php artisan key:generate

# 3. Create admin user
php artisan tinker
```

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Create super admin
User::create([
    'name' => 'System Administrator',
    'email' => 'admin@ygxone.com',
    'password' => Hash::make('SecureLaunch2026!'),
    'email_verified_at' => now(),
    'account_type' => 'individual',
]);

exit
```

```bash
# 4. Seed essential data (if seeders exist)
php artisan db:seed --force
```

#### **YG Home (Search) Database**
```bash
cd /path/to/yg-home

# 1. Run migrations
php artisan migrate --force

# 2. Generate app key
php artisan key:generate

# 3. Create session/cache tables
php artisan session:table
php artisan cache:table
php artisan migrate --force

# 4. Sync search indexes
php artisan search:sync
```

---

### **Phase 2: Environment Configuration (15 minutes)**

#### **YG Account .env**
```env
APP_NAME="YG Account"
APP_ENV=production
APP_KEY=base64:GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://account.ygxone.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yg_account
DB_USERNAME=yg_account_user
DB_PASSWORD=StrongPassword123!

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_CHANNEL=single
LOG_LEVEL=error

# Security
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

#### **YG Home .env**
```env
APP_NAME="YGXONE Search"
APP_ENV=production
APP_KEY=base64:GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://ygxone.com

DB_CONNECTION=sqlite
# Or MySQL for production:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=yg_home

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync

# Admin Configuration
ADMIN_EMAILS=admin@ygxone.com
ADMIN_USER_IDS=1

# Service URLs
YG_ACCOUNT_URL=https://account.ygxone.com
YG_AI_API_URL=https://ai.ygxone.com
YG_MAIL_URL=https://mail.ygxone.com
YG_DRIVE_URL=https://drive.ygxone.com
YG_DOCX_URL=https://docx.ygxone.com
```

---

### **Phase 3: Build & Optimize (20 minutes)**

#### **Both Systems:**
```bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# Build frontend assets
npm ci && npm run build

# Set permissions
chmod -R 775 storage/ bootstrap/cache/
find storage/ -type f -exec chmod 664 {} \;

# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

### **Phase 4: Server Configuration (30 minutes)**

#### **Nginx Configuration**

**YG Account (account.ygxone.com):**
```nginx
server {
    listen 80;
    server_name account.ygxone.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name account.ygxone.com;

    ssl_certificate /etc/letsencrypt/live/account.ygxone.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/account.ygxone.com/privkey.pem;

    root /var/www/yg-account/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**YG Home (ygxone.com):**
```nginx
server {
    listen 80;
    server_name ygxone.com www.ygxone.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name ygxone.com www.ygxone.com;

    ssl_certificate /etc/letsencrypt/live/ygxone.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ygxone.com/privkey.pem;

    root /var/www/yg-home/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### **SSL Certificates:**
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Get certificates
sudo certbot --nginx -d account.ygxone.com
sudo certbot --nginx -d ygxone.com -d www.ygxone.com

# Test auto-renewal
sudo certbot renew --dry-run
```

---

### **Phase 5: Cron Jobs & Queues (10 minutes)**

#### **Cron Setup:**
```bash
crontab -e
```

Add these lines:
```cron
# Laravel Scheduler (both systems)
* * * * * cd /var/www/yg-account && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /var/www/yg-home && php artisan schedule:run >> /dev/null 2>&1

# Queue workers (if using database queue)
* * * * * cd /var/www/yg-account && php artisan queue:work --sleep=3 --tries=3 --timeout=90 >> /dev/null 2>&1
```

#### **Start Queue Workers:**
```bash
# YG Account
cd /var/www/yg-account
nohup php artisan queue:work --sleep=3 --tries=3 --timeout=90 > /dev/null 2>&1 &

# YG Home (if needed)
cd /var/www/yg-home
nohup php artisan queue:work --sleep=3 --tries=3 --timeout=90 > /dev/null 2>&1 &
```

---

### **Phase 6: Final Validation (15 minutes)**

#### **Health Checks:**
```bash
# Test YG Account
curl -I https://account.ygxone.com/up
# Expected: HTTP/2 200 OK

# Test YG Home
curl -I https://ygxone.com/up
# Expected: HTTP/2 200 OK

# Test search functionality
curl -s "https://ygxone.com/search?q=test" | grep -o "<title>.*</title>"

# Check database connectivity
cd /var/www/yg-account && php artisan db:show
cd /var/www/yg-home && php artisan db:show
```

#### **Admin Access Test:**
```bash
# Login to YG Account
# URL: https://account.ygxone.com/login
# Email: admin@ygxone.com
# Password: SecureLaunch2026!

# Access YG Home Admin Dashboard
# URL: https://ygxone.com/admin/dashboard
```

---

## 🎯 QUICK LAUNCH SCRIPT

Save as `launch-today.sh`:

```bash
#!/bin/bash
set -e

echo "🚀 Starting YG Launch Sequence..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Step 1: YG Account Setup
echo -e "${YELLOW}[1/6]${NC} Setting up YG Account..."
cd /var/www/yg-account
php artisan migrate --force
php artisan key:generate --force
php artisan config:cache
php artisan route:cache
echo -e "${GREEN}✓ YG Account Ready${NC}"
echo ""

# Step 2: YG Home Setup
echo -e "${YELLOW}[2/6]${NC} Setting up YG Home..."
cd /var/www/yg-home
php artisan migrate --force
php artisan key:generate --force
php artisan search:sync
php artisan config:cache
php artisan route:cache
echo -e "${GREEN}✓ YG Home Ready${NC}"
echo ""

# Step 3: Permissions
echo -e "${YELLOW}[3/6]${NC} Setting permissions..."
chmod -R 775 /var/www/yg-account/storage /var/www/yg-account/bootstrap/cache
chmod -R 775 /var/www/yg-home/storage /var/www/yg-home/bootstrap/cache
echo -e "${GREEN}✓ Permissions Set${NC}"
echo ""

# Step 4: SSL Certificates
echo -e "${YELLOW}[4/6]${NC} Checking SSL..."
if [ ! -f "/etc/letsencrypt/live/account.ygxone.com/fullchain.pem" ]; then
    echo "Installing SSL for account.ygxone.com..."
    certbot --nginx -d account.ygxone.com --non-interactive --agree-tos -m admin@ygxone.com
fi

if [ ! -f "/etc/letsencrypt/live/ygxone.com/fullchain.pem" ]; then
    echo "Installing SSL for ygxone.com..."
    certbot --nginx -d ygxone.com -d www.ygxone.com --non-interactive --agree-tos -m admin@ygxone.com
fi
echo -e "${GREEN}✓ SSL Configured${NC}"
echo ""

# Step 5: Restart Services
echo -e "${YELLOW}[5/6]${NC} Restarting services..."
systemctl restart nginx
systemctl restart php8.2-fpm
echo -e "${GREEN}✓ Services Restarted${NC}"
echo ""

# Step 6: Health Check
echo -e "${YELLOW}[6/6]${NC} Running health checks..."
ACCOUNT_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://account.ygxone.com/up)
HOME_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://ygxone.com/up)

if [ "$ACCOUNT_STATUS" = "200" ] && [ "$HOME_STATUS" = "200" ]; then
    echo -e "${GREEN}✓ All Systems Operational!${NC}"
    echo ""
    echo "=========================================="
    echo "  🎉 LAUNCH SUCCESSFUL!"
    echo "=========================================="
    echo ""
    echo "YG Account: https://account.ygxone.com"
    echo "YG Search:  https://ygxone.com"
    echo ""
    echo "Admin Credentials:"
    echo "Email: admin@ygxone.com"
    echo "Password: SecureLaunch2026!"
    echo ""
    echo "⚠️  IMPORTANT: Change password immediately!"
    echo ""
else
    echo -e "${RED}✗ Health check failed${NC}"
    echo "Account Status: $ACCOUNT_STATUS"
    echo "Home Status: $HOME_STATUS"
    exit 1
fi
```

Make executable and run:
```bash
chmod +x launch-today.sh
sudo ./launch-today.sh
```

---

## 🔥 EMERGENCY MINIMUM VIABLE LAUNCH

If time is critical, do ONLY these steps:

### **Absolute Minimum (1 hour):**

1. **Database Setup (15 min)**
   ```bash
   # Both systems
   php artisan migrate --force
   php artisan key:generate
   ```

2. **Create Admin User (5 min)**
   ```bash
   php artisan tinker
   >>> User::create(['name'=>'Admin','email'=>'admin@ygxone.com','password'=>bcrypt('ChangeMe123!')]);
   >>> exit
   ```

3. **Basic Nginx Config (20 min)**
   - Point document root to `public/` folder
   - Enable PHP-FPM

4. **Get SSL (10 min)**
   ```bash
   certbot --nginx -d account.ygxone.com -d ygxone.com
   ```

5. **Test (10 min)**
   - Visit both URLs
   - Login as admin
   - Verify search works

---

## 📊 LAUNCH STATUS TRACKER

Copy this checklist and track progress:

```
[ ] Phase 1: Database Setup (30 min)
    [ ] YG Account migrations
    [ ] YG Home migrations
    [ ] Admin user created
    [ ] Search indexes synced

[ ] Phase 2: Environment Config (15 min)
    [ ] YG Account .env configured
    [ ] YG Home .env configured
    [ ] APP_KEY generated for both
    [ ] Production settings applied

[ ] Phase 3: Build & Optimize (20 min)
    [ ] Composer install (both)
    [ ] npm build (both)
    [ ] Permissions set
    [ ] Caches cleared

[ ] Phase 4: Server Config (30 min)
    [ ] Nginx configured
    [ ] SSL certificates installed
    [ ] DNS records pointing correctly
    [ ] Firewall rules set

[ ] Phase 5: Cron & Queues (10 min)
    [ ] Crontab entries added
    [ ] Queue workers started
    [ ] Scheduler verified

[ ] Phase 6: Validation (15 min)
    [ ] Health checks pass
    [ ] Login works
    [ ] Search functional
    [ ] Admin dashboard accessible

TOTAL TIME: ~2 hours
```

---

## 🐛 TROUBLESHOOTING

### **Common Issues & Quick Fixes:**

**Issue: "500 Internal Server Error"**
```bash
# Check logs
tail -f /var/www/yg-account/storage/logs/laravel.log
tail -f /var/www/yg-home/storage/logs/laravel.log

# Common fix: Clear caches
php artisan optimize:clear
```

**Issue: "Database connection refused"**
```bash
# Verify MySQL running
systemctl status mysql

# Check credentials in .env
grep DB_ .env

# Test connection
mysql -u username -p -h 127.0.0.1
```

**Issue: "Permission denied"**
```bash
# Fix permissions
chown -R www-data:www-data /var/www/yg-account /var/www/yg-home
chmod -R 775 storage/ bootstrap/cache/
```

**Issue: "SSL certificate error"**
```bash
# Reinstall certificate
certbot certonly --nginx -d ygxone.com --force-renewal
systemctl reload nginx
```

**Issue: "Search not working"**
```bash
# Rebuild search index
cd /var/www/yg-home
php artisan search:sync
php artisan scout:import "App\Models\IndexedItem"
```

---

## 🎯 POST-LAUNCH TASKS (First 24 Hours)

### **Immediately After Launch:**

1. **Change Default Password**
   ```bash
   php artisan tinker
   >>> $user = User::where('email', 'admin@ygxone.com')->first();
   >>> $user->password = bcrypt('NewSecurePassword!');
   >>> $user->save();
   >>> exit
   ```

2. **Monitor Logs**
   ```bash
   tail -f /var/www/*/storage/logs/laravel.log
   ```

3. **Check Performance**
   ```bash
   # Response times
   curl -w "@curl-format.txt" -o /dev/null -s https://ygxone.com/
   
   # Database queries
   php artisan db:monitor
   ```

4. **Setup Monitoring**
   - Install Sentry/Bugsnag for error tracking
   - Configure uptime monitoring (UptimeRobot, Pingdom)
   - Set up alerts for downtime

5. **Backup Strategy**
   ```bash
   # Daily database backup
   mysqldump -u user -p'password' yg_account > /backups/yg_account_$(date +%Y%m%d).sql
   mysqldump -u user -p'password' yg_home > /backups/yg_home_$(date +%Y%m%d).sql
   ```

---

## 📞 EMERGENCY CONTACTS

**If something breaks during launch:**

1. **Check logs first:**
   ```bash
   tail -n 100 /var/www/yg-account/storage/logs/laravel.log
   tail -n 100 /var/www/yg-home/storage/logs/laravel.log
   ```

2. **Rollback if needed:**
   ```bash
   # Rollback last migration
   php artisan migrate:rollback
   
   # Restore from backup
   mysql -u user -p yg_account < /backups/yg_account_backup.sql
   ```

3. **Quick disable:**
   ```bash
   # Put site in maintenance mode
   php artisan down --message="System maintenance. Back soon!"
   
   # Bring back up
   php artisan up
   ```

---

## ✅ LAUNCH VERIFICATION CHECKLIST

After launch, verify each item:

```
Website Accessibility:
[ ] https://account.ygxone.com loads
[ ] https://ygxone.com loads
[ ] HTTPS working on both
[ ] No mixed content warnings

Functionality:
[ ] Can register new account
[ ] Can login with admin credentials
[ ] Search returns results
[ ] Admin dashboard accessible
[ ] Pagination works

Performance:
[ ] Page load < 2 seconds
[ ] Search response < 500ms
[ ] No timeout errors

Security:
[ ] SSL valid and not expiring
[ ] .env files not publicly accessible
[ ] Debug mode disabled
[ ] Rate limiting active

Monitoring:
[ ] Error logs clean
[ ] Uptime monitoring active
[ ] Backup scheduled
```

---

## 🚀 READY TO LAUNCH?

### **Final Pre-Launch Commands:**

```bash
# Run this 30 minutes before launch
cd /var/www/yg-account && php artisan optimize:clear
cd /var/www/yg-home && php artisan optimize:clear

# Then rebuild caches
cd /var/www/yg-account && php artisan optimize
cd /var/www/yg-home && php artisan optimize

# Restart services
systemctl restart nginx php8.2-fpm

# Final health check
curl https://account.ygxone.com/up
curl https://ygxone.com/up
```

---

## 🎉 LAUNCH DAY TIMELINE

**Morning (9:00 AM):**
- [ ] Database setup complete
- [ ] Environment configured

**Mid-Morning (10:30 AM):**
- [ ] Code deployed
- [ ] Dependencies installed

**Late Morning (11:30 AM):**
- [ ] SSL certificates installed
- [ ] Nginx configured

**Noon (12:00 PM):**
- [ ] Testing complete
- [ ] Issues resolved

**Early Afternoon (1:00 PM):**
- [ ] DNS updated
- [ ] Propagation checked

**Afternoon (2:00 PM):**
- [ ] **GO LIVE!** 🚀
- [ ] Monitor for issues

**Evening (6:00 PM):**
- [ ] Performance review
- [ ] Backup verification
- [ ] Documentation update

---

## 💡 PRO TIPS FOR SUCCESS

1. **Have rollback plan ready** - Keep backups accessible
2. **Monitor continuously** - Watch logs in real-time
3. **Communicate clearly** - Inform team of launch window
4. **Document everything** - Record what worked/didn't work
5. **Celebrate milestones** - Each phase completed is progress!

---

**You've got this! Let's make today the day YG goes live!** 🚀🎉

**Questions?** Check troubleshooting section above or review logs immediately.

**Good luck with your launch!** 🍀
