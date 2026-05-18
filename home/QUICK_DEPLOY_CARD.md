# 🚀 Quick Deploy Card - YGXONE Home Auth & SSO Fix

## ⚡ One-Command Deployment

### Windows
```powershell
cd "d:\YG SoftX\Xone\home" && deploy-auth-fix.bat
```

### Linux/Mac
```bash
cd /path/to/home && chmod +x deploy-auth-fix.sh && ./deploy-auth-fix.sh
```

---

## 📋 What Was Fixed

| Issue | Fix | Status |
|-------|-----|--------|
| ❌ No `/login` route | Created `routes/auth.php` | ✅ Done |
| ❌ Admin middleware error | Added Log import | ✅ Done |
| ❌ SSO not working | Changed to database sessions | ✅ Done |
| ❌ Sessions not shared | Set `SESSION_DOMAIN=.ygxone.com` | ✅ Done |

---

## ✅ Post-Deploy Verification (2 minutes)

```bash
# 1. Test login page
curl -I https://ygxone.com/login
# Expected: HTTP 200

# 2. Check session config
php artisan tinker --execute="echo config('session.driver') . ' | ' . config('session.domain');"
# Expected: database | .ygxone.com

# 3. Verify sessions table
php artisan tinker --execute="echo Schema::hasTable('sessions') ? 'YES' : 'NO';"
# Expected: YES

# 4. Test admin route (should redirect if not logged in)
curl -I https://ygxone.com/admin/dashboard
# Expected: HTTP 302 (redirect to login)
```

---

## 🔍 Browser Test Checklist

- [ ] Visit `https://ygxone.com/login` → Login form appears
- [ ] Login with valid credentials → Redirects to dashboard
- [ ] Visit `https://ygxone.com/admin/dashboard` → Shows admin panel (if admin email configured)
- [ ] Open new tab → Visit `https://mail.ygxone.com` → Still logged in
- [ ] Check cookies → `laravel_session` domain is `.ygxone.com`

---

## 🆘 Emergency Rollback

If something breaks:

```bash
# Revert .env changes
git checkout home/.env

# Clear caches
php artisan config:clear
php artisan cache:clear

# Drop sessions table (if needed)
php artisan migrate:rollback --step=1
```

---

## 📞 Critical Info

- **Sessions Table:** `ygmarket_account.sessions`
- **Cookie Domain:** `.ygxone.com` (all sub-domains)
- **Session Driver:** `database`
- **Admin Emails:** Check `.env` → `ADMIN_EMAILS=`

---

**Time to deploy:** ~30 seconds  
**Downtime:** None (zero-downtime deployment)  
**Risk Level:** Low (backward compatible)
