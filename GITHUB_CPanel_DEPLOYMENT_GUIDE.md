# 🚀 GitHub to cPanel Deployment Guide for YGXone

## ⚡ Quick Deployment Steps

### **Step 1: Push Code to GitHub** (From Your Local Machine)

```powershell
cd "d:\YG SoftX\Xone"
git add .
git commit -m "Update with latest changes and bug fixes"
git push origin main
```

---

### **Step 2: Connect to cPanel via SSH/Terminal**

You have **3 options** to access your server:

#### **Option A: cPanel Terminal** (Easiest)
1. Login to cPanel at `https://your-hosting.com/cpanel`
2. Go to **ADVANCED → Terminal**
3. You're now in your server's command line

#### **Option B: SSH Access** (Recommended)
```bash
ssh your_username@ygxone.com
# Or use the server IP provided by your host
```

#### **Option C: Git Version Control in cPanel**
1. cPanel → **Version Control → Git™ Version Control**
2. Clone your repository:
   ```
   Repository URL: https://github.com/your-username/xone.git
   Directory: /home4/ygmarket/ygxone.com/yg-home
   Branch: main
   ```
3. Enable **"Automatically deploy from repository"**

---

### **Step 3: Pull Latest Code & Deploy** (On Server)

Once connected via SSH/Terminal, run these commands:

```bash
# Navigate to your application directory
cd /home4/ygmarket/ygxone.com/yg-home

# Pull latest code from GitHub
git pull origin main

# Run the post-deployment script
chmod +x deploy.sh
./deploy.sh
```

The `deploy.sh` script will automatically:
- ✅ Install Composer dependencies (vendor folder)
- ✅ Generate APP_KEY if missing
- ✅ Configure environment variables
- ✅ Run database migrations safely
- ✅ Clear and rebuild caches
- ✅ Set correct permissions

---

## 🔧 Alternative: Using cPanel Git Version Control

If your cPanel has **Git™ Version Control**:

### **Setup (One-Time)**

1. **cPanel → Version Control → Git™ Version Control**
2. Click **"Create"**
3. Fill in:
   - **Repository Path:** `/home4/ygmarket/ygxone.com/yg-home`
   - **Repository URL:** `https://github.com/your-username/xone.git`
   - **Branch:** `main`
   - ✅ Check **"Clone Repository"**
   - ✅ Check **"Deploy From Repository"**

### **Deploy Updates**

1. Go to **Git™ Version Control**
2. Select your repository
3. Click **"Pull or Deploy"**
4. Click **"Pull HEAD"** to get latest code
5. **Then run the deploy script via SSH:**
   ```bash
   cd /home4/ygmarket/ygxone.com/yg-home
   ./deploy.sh
   ```

---

## ⚠️ Important Notes

### **1. Database Configuration**

Before deploying, ensure your `.env` file has correct database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ygmarket_account
DB_USERNAME=ygmarket_account
DB_PASSWORD=your_actual_password
```

**To edit .env on server:**
```bash
nano .env
# or use cPanel File Manager → .env → Edit
```

### **2. APP_KEY (Encryption Key)**

The error log showed: `No application encryption key has been specified`

**Fix:** After pulling code, run:
```bash
php artisan key:generate
```

This is already included in `deploy.sh`!

### **3. Vendor Directory**

GitHub ignores the `vendor/` folder (it's in `.gitignore`). The `deploy.sh` script will install it automatically using Composer.

---

## 🐛 Troubleshooting Common Errors

### **Error 1: "500 Server Error"**
**Cause:** Missing vendor dependencies

**Fix:**
```bash
cd /home4/ygmarket/ygxone.com/yg-home
composer install --no-dev --optimize-autoloader
# OR simply run:
./deploy.sh
```

---

### **Error 2: "No application encryption key has been specified"**
**Cause:** Missing APP_KEY in .env

**Fix:**
```bash
php artisan key:generate
```

---

### **Error 3: "Call to undefined method UnifiedSearchService::getUserSearchHistory()"**
**Status:** ✅ **FIXED** in the latest code push

This method visibility was changed from `private` to `public`.

---

### **Error 4: "syntax error, unexpected token 'private'"**
**Status:** ✅ **FIXED** in the latest code push

Fixed undefined `$domain` variable in `UnifiedSearchService.php`.

---

### **Error 5: "table 'trending_searches' already exists"**
**Cause:** Migration conflict

**Fix:**
```bash
# Option 1: Skip existing tables
php artisan migrate --force

# Option 2: If you want fresh tables (WARNING: Deletes data!)
php artisan migrate:fresh --force
```

---

### **Error 6: "Method Laravel\Scout\Builder::limit does not exist"**
**Cause:** Laravel Scout version incompatibility

**Fix:** Update Scout in `composer.json`:
```bash
composer require laravel/scout:^10.0
```

---

### **Error 7: "cURL error 6: Could not resolve host: ai.ygxone.com"**
**Cause:** DNS issue with AI service subdomain

**Fix:**
1. Create the subdomain `ai.ygxone.com` in cPanel
2. Or disable AI service temporarily in `.env`:
   ```env
   YG_AI_API_URL=
   ```

---

## 📋 Pre-Deployment Checklist

Before pushing to GitHub:

- [ ] Test code locally (`php artisan serve`)
- [ ] Run `composer install` locally (no errors)
- [ ] Check `.env.example` is up to date
- [ ] Commit all changes including `.env.example`
- [ ] Push to GitHub

---

## 📋 Post-Deployment Checklist

After running `deploy.sh` on server:

- [ ] Visit `https://ygxone.com` - no 500 errors
- [ ] Check search functionality works
- [ ] Verify database connection
- [ ] Check logs: `storage/logs/laravel.log`
- [ ] Test admin panel (if applicable)
- [ ] Clear browser cache (Ctrl+F5)

---

## 🎯 One-Command Deployment Script

For convenience, here's a one-command solution:

```bash
cd /home4/ygmarket/ygxone.com/yg-home && git pull origin main && chmod +x deploy.sh && ./deploy.sh
```

This single command:
1. Navigates to the app directory
2. Pulls latest code from GitHub
3. Makes deploy script executable
4. Runs the deployment script

---

## 📞 Need Help?

If you encounter issues:

1. **Check the logs:**
   ```bash
   tail -f storage/logs/laravel.log
   # or
   cat error_log
   ```

2. **Verify PHP version:**
   ```bash
   php -v  # Should be 8.1+
   ```

3. **Test manually:**
   ```bash
   php artisan route:list
   php artisan config:clear
   ```

---

## 🔄 Automated Deployment (Optional)

Want fully automatic deployments? Set up a **webhook**:

1. **GitHub → Settings → Webhooks**
2. Add webhook: `https://ygxone.com/webhook/deploy`
3. Create webhook handler in Laravel:
   ```php
   Route::post('/webhook/deploy', function () {
       exec('cd /home4/ygmarket/ygxone.com/yg-home && git pull && ./deploy.sh > /dev/null 2>&1 &');
       return response()->json(['status' => 'deploying']);
   });
   ```

Now every push to GitHub automatically deploys! 🎉

---

## ✅ What Was Fixed

Your recent 500 errors were caused by:

1. ❌ **Missing vendor/autoload.php** → Fixed by `composer install` in deploy.sh
2. ❌ **Undefined `$domain` variable** → Fixed in `UnifiedSearchService.php`
3. ❌ **Private methods not accessible** → Changed to `public`
4. ❌ **Missing APP_KEY** → Auto-generated by deploy.sh

All these are now handled automatically! 🚀
