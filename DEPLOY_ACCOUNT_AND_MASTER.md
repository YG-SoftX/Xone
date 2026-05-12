# 🚀 YG Account & Master - cPanel Deployment Guide

**Date:** 2026-05-12  
**Modules:** YG Account + YG Master (Core Services)  
**Status:** Ready for Deployment ✅

---

## ✅ Pre-Deployment Checklist

### Configuration Status:
- ✅ **Database:** Unified database configured (`ygmarket_account`)
- ✅ **APP_KEYs:** Generated for both modules
- ✅ **Dependencies:** Installed (vendor directories present)
- ✅ **.env files:** Created and configured

### Current Configuration:
```env
DB_HOST=127.0.0.1          # Change to 'localhost' on cPanel
DB_PORT=3306
DB_DATABASE=ygmarket_account
DB_USERNAME=ygmarket_account
DB_PASSWORD='Ygaccount@2.0##2026'
```

---

## 📋 Step-by-Step Deployment

### STEP 1: Prepare cPanel (5 minutes)

#### A. Create Database
1. Login to cPanel
2. Go to **MySQL® Databases**
3. Create database: `{username}_ygmarket_account`
4. Create user: `{username}_ygmarket_account`
5. Set password: (use strong password, save it!)
6. Grant **ALL PRIVILEGES** to the user on the database

#### B. Create Subdomains
Go to **Domains → Subdomains**:

| Subdomain | Document Root |
|-----------|---------------|
| account | `/public_html/account/public` |
| master | `/public_html/master/public` |

⚠️ **IMPORTANT:** Document root MUST point to the `public` folder!

---

### STEP 2: Upload Files (10 minutes)

#### Option A: Using cPanel Git (RECOMMENDED)

1. Login to cPanel → **Files → Git™ Version Control**
2. Click **"Create"**
3. Fill in:
   - **Repository Name:** `Xone`
   - **Clone URL:** `https://github.com/YG-SoftX/Xone.git`
   - **Repository Path:** `/home/username/repositories/Xone`
4. Click **"Create"**
5. After cloning, click **"Manage"** → **"Deploy HEAD Commit"**

This will deploy ALL modules. Then you can focus on account and master.

---

#### Option B: Manual Upload via File Manager

1. **Compress modules locally:**
   ```powershell
   # In PowerShell
   cd "d:\YG SoftX\Xone"
   Compress-Archive -Path account,master -DestinationPath YG-Core-Modules.zip
   ```

2. **Upload to cPanel:**
   - Go to **File Manager**
   - Navigate to `/public_html`
   - Click **"Upload"**
   - Select `YG-Core-Modules.zip`
   - Extract after upload

3. **Verify structure:**
   ```
   /public_html/account/
   /public_html/master/
   ```

---

### STEP 3: Configure .env Files (5 minutes)

For **EACH module** (account and master), update `.env`:

#### Via cPanel File Manager:
1. Navigate to `/public_html/account`
2. Edit `.env` file
3. Update these values:

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://account.ygxone.com

# Database (cPanel)
DB_HOST=localhost
DB_DATABASE={username}_ygmarket_account
DB_USERNAME={username}_ygmarket_account
DB_PASSWORD=your_cpanel_db_password

# Session & Cache
CACHE_STORE=file
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Mail (update with your SMTP settings)
MAIL_MAILER=smtp
MAIL_HOST=mail.ygxone.com
MAIL_PORT=587
MAIL_USERNAME=noreply@ygxone.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@ygxone.com"
MAIL_FROM_NAME="${APP_NAME}"
```

4. Save the file
5. Repeat for `/public_html/master` with `APP_URL=https://master.ygxone.com`

**Replace `{username}` with your actual cPanel username!**

---

### STEP 4: Run Installation Commands (10 minutes)

Connect via **cPanel Terminal** or **SSH**:

```bash
# ========================================
# ACCOUNT MODULE
# ========================================
cd ~/public_html/account

# Clear all caches first
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Generate APP_KEY (if not already set)
php artisan key:generate --force

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Set permissions
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chmod 644 .env

# Optimize for production
php artisan optimize
php artisan event:cache

echo "✅ Account module deployed!"


# ========================================
# MASTER MODULE
# ========================================
cd ~/public_html/master

# Clear all caches first
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Generate APP_KEY (if not already set)
php artisan key:generate --force

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Set permissions
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chmod 644 .env

# Optimize for production
php artisan optimize
php artisan event:cache

echo "✅ Master module deployed!"
```

---

### STEP 5: Test Your Deployment (5 minutes)

Visit these URLs in your browser:

```
✅ https://account.ygxone.com       - Account service
✅ https://account.ygxone.com/admin - Admin panel
✅ https://master.ygxone.com        - Master dashboard
✅ https://master.ygxone.com/admin  - Guardian dashboard
```

**Check for:**
- ✅ Homepage loads without errors
- ✅ No 500 Internal Server Error
- ✅ Can access admin panels
- ✅ Database connection works

---

## 🔧 Troubleshooting

### Common Issues:

| Problem | Solution |
|---------|----------|
| **500 Error** | Check `.env` exists, verify APP_KEY is set, check logs |
| **Database Connection Failed** | Verify DB credentials match cPanel settings |
| **Permission Denied** | Run: `chmod -R 775 storage/ bootstrap/cache/` |
| **Missing APP_KEY** | Run: `php artisan key:generate --force` |
| **Session Errors** | Change `SESSION_DRIVER=file` in `.env` |
| **Cache Errors** | Change `CACHE_STORE=file` in `.env` |
| **Migration Errors** | Check database exists and user has privileges |

### Check Logs:

```bash
# Account module logs
tail -f ~/public_html/account/storage/logs/laravel.log

# Master module logs
tail -f ~/public_html/master/storage/logs/laravel.log

# cPanel error log
tail -f ~/public_html/account/error_log
tail -f ~/public_html/master/error_log
```

---

## 🔐 Security Checklist

After successful deployment:

- [ ] Change database password to strong unique password
- [ ] Set `APP_DEBUG=false` in both `.env` files
- [ ] Set `APP_ENV=production` in both `.env` files
- [ ] Remove installation files if any exist
- [ ] Enable HTTPS (SSL certificate)
- [ ] Set up regular backups
- [ ] Configure firewall rules if needed
- [ ] Review file permissions

---

## 📊 Post-Deployment Verification

Run these checks:

```bash
# Check PHP version (should be 8.2+)
php -v

# Verify Laravel version
cd ~/public_html/account && php artisan --version
cd ~/public_html/master && php artisan --version

# Check database connection
cd ~/public_html/account && php artisan migrate:status
cd ~/public_html/master && php artisan migrate:status

# List routes
cd ~/public_html/account && php artisan route:list | head -20
cd ~/public_html/master && php artisan route:list | head -20
```

---

## 🎯 What's Deployed

### YG Account Module:
- User authentication & management
- Session handling
- Payment integration (YGPAY)
- Admin panel with Filament
- API endpoints
- Health monitoring
- Cron job management

### YG Master Module:
- Centralized service management
- Guardian dashboard
- Ecosystem status monitoring
- Deployment management
- Backup system
- AI training interface
- Service health checks

---

## 📞 Need Help?

Reference these guides:
- `account/FINAL_IMPLEMENTATION_GUIDE.md` - Account module details
- `master/PRODUCTION_DEPLOYMENT_GUIDE.md` - Master deployment guide
- `UNIFIED_DATABASE_CONFIG.md` - Database configuration
- `CPANEL_QUICK_DEPLOY.md` - General cPanel deployment

---

## ✅ Deployment Complete!

Once both modules are running:
1. Test user registration/login on Account
2. Access admin panels
3. Verify database tables created
4. Check ecosystem status in Master
5. Monitor logs for any errors

**Congratulations! Your core YG services are now live! 🎉**
