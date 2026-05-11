# 🚀 Quick Deploy Reference Card

## One-Command Deployment (SSH/Terminal)

```bash
cd /home4/ygmarket/ygxone.com/yg-home && git pull origin main && chmod +x deploy.sh && ./deploy.sh
```

---

## Manual Steps (If Preferred)

```bash
# 1. Navigate to app directory
cd /home4/ygmarket/ygxone.com/yg-home

# 2. Pull latest code from GitHub
git pull origin main

# 3. Install dependencies & configure
./deploy.sh

# OR if deploy.sh doesn't exist yet:
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan config:cache
```

---

## Common Commands

| Task | Command |
|------|---------|
| **Deploy everything** | `./deploy.sh` |
| **Install vendor only** | `composer install --no-dev` |
| **Generate APP_KEY** | `php artisan key:generate` |
| **Run migrations** | `php artisan migrate --force` |
| **Clear all caches** | `php artisan optimize:clear` |
| **Check logs** | `tail -f storage/logs/laravel.log` |
| **View PHP version** | `php -v` |

---

## File Locations

- **App Root:** `/home4/ygmarket/ygxone.com/yg-home`
- **Error Log:** `/home4/ygmarket/ygxone.com/yg-home/storage/logs/laravel.log`
- **cPanel Error Log:** cPanel → Metrics → Errors
- **.env File:** `/home4/ygmarket/ygxone.com/yg-home/.env`

---

## Emergency Fixes

### Site down with 500 error?
```bash
composer install
```

### "No encryption key" error?
```bash
php artisan key:generate
```

### Database errors?
Edit `.env` with correct credentials, then:
```bash
php artisan migrate --force
```

### Still having issues?
```bash
chmod -R 775 storage bootstrap/cache
php artisan optimize:clear
```

---

## Need Help?

1. Check logs: `storage/logs/laravel.log`
2. Run deploy script: `./deploy.sh`
3. Verify .env database credentials
4. Clear browser cache: Ctrl+F5
