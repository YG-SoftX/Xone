# ⚡ Quick Fix - Production Errors

## 🚀 **ONE COMMAND DEPLOYMENT** (SSH into your server)

```bash
# For account application
cd /home4/ygmarket/ygxone.com/account && git pull origin main && chmod +x deploy.sh && ./deploy.sh

# For yg-account application  
cd /home4/ygmarket/ygxone.com/yg-account && git pull origin main && chmod +x deploy.sh && ./deploy.sh
```

---

## 🔧 **What This Fixes:**

✅ Vite manifest errors (login page won't load)  
✅ Missing jenssegers/agent package  
✅ Database migrations not run  
✅ Stale cached views (malformed @foreach)  
✅ Device fingerprinting issues  
✅ Permission problems  

---

## 📋 **Manual Steps (If Deploy Script Fails):**

### 1. Install Dependencies
```bash
composer install --no-dev
composer require jenssegers/agent
npm install && npm run build
```

### 2. Run Migrations
```bash
php artisan migrate --force
```

### 3. Clear Caches
```bash
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
```

### 4. Set Permissions
```bash
chmod -R 775 storage bootstrap/cache
```

---

## 🐛 **Still Having Issues?**

### Check Logs:
```bash
tail -f storage/logs/laravel.log
```

### Common Fixes:

**Vite Error?**
```bash
npm run build
```

**Database Error?**
```bash
# Edit .env with correct credentials
nano .env
php artisan migrate --force
```

**Permission Error?**
```bash
chmod -R 775 storage bootstrap/cache
```

**Old Cached Views?**
```bash
php artisan view:clear
php artisan cache:clear
```

---

## ✅ **Verify It Works:**

1. Visit login page → Should load without errors
2. Try user registration → Should complete successfully
3. Access dashboard → Should display properly
4. Check logs → No new errors

---

## 📞 **Need More Help?**

- **Detailed Guide:** [`PRODUCTION_ERRORS_RESOLVED.md`](PRODUCTION_ERRORS_RESOLVED.md)
- **Fix Documentation:** [`PRODUCTION_ERROR_FIXES.md`](PRODUCTION_ERROR_FIXES.md)
- **Deployment Guide:** [`GITHUB_CPanel_DEPLOYMENT_GUIDE.md`](GITHUB_CPanel_DEPLOYMENT_GUIDE.md)
