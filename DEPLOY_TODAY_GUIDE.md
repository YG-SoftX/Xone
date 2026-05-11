# 🚀 DEPLOY TODAY - Quick Start Guide

**You can deploy your entire YG ecosystem TODAY!** This guide will get you live in under 60 minutes.

---

## ⚡ FASTEST PATH TO DEPLOYMENT (5 Minutes to Start)

### Option 1: Automated Deployment (RECOMMENDED)

#### For Windows Users:
```powershell
# Right-click on DEPLOY_TODAY.ps1 → Run with PowerShell
# OR run in terminal:
.\DEPLOY_TODAY.ps1
```

#### For Mac/Linux Users:
```bash
chmod +x DEPLOY_TODAY.sh
./DEPLOY_TODAY.sh
```

**The script will:**
- ✅ Check all prerequisites
- ✅ Install dependencies for all 13 modules
- ✅ Generate environment configuration files
- ✅ Build frontend assets
- ✅ Create a complete deployment package
- ✅ Provide step-by-step instructions

---

## 📋 What You Need Before Starting

### For cPanel Deployment:
- [ ] cPanel hosting account with PHP 8.2+
- [ ] Domain name (e.g., ygxone.com)
- [ ] Ability to create subdomains
- [ ] MySQL database access
- [ ] SSH/Terminal access (or cPanel Terminal)

### For VPS Deployment:
- [ ] VPS/Server with Ubuntu/CentOS
- [ ] Root or sudo access
- [ ] Domain name pointed to server IP
- [ ] LAMP/LEMP stack installed

### For Local Testing:
- [ ] PHP 8.2+
- [ ] Composer
- [ ] MySQL/MariaDB
- [ ] Node.js 18+

---

## 🎯 Deployment Checklist

### Phase 1: Preparation (10 minutes)
- [ ] Run the deployment script (`DEPLOY_TODAY.ps1` or `DEPLOY_TODAY.sh`)
- [ ] Review generated `.env.production` files
- [ ] Update database credentials in .env files
- [ ] Update email credentials in .env files

### Phase 2: Server Setup (15 minutes)
- [ ] Create databases in cPanel/VPS
- [ ] Create subdomains (account, mail, dev, etc.)
- [ ] Set document root to `/public` folder
- [ ] Upload deployment package via FTP/cPanel File Manager

### Phase 3: Installation (20 minutes)
For **EACH** module, run these commands via SSH:

```bash
cd /path/to/module

# Install dependencies
composer install --no-dev --optimize-autoloader

# Configure environment
cp .env.production .env
# Edit .env with correct credentials

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Optimize
php artisan optimize
```

### Phase 4: Permissions & Cron (5 minutes)
```bash
# Set permissions
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/

# Setup cron job (cPanel → Cron Jobs)
* * * * * cd /path/to/yg-account && php artisan schedule:run >> /dev/null 2>&1
```

### Phase 5: Verification (10 minutes)
Test each subdomain:
- [ ] https://account.yourdomain.com - Login page loads
- [ ] https://mail.yourdomain.com - Mail interface loads
- [ ] https://dev.yourdomain.com - Developer portal loads
- [ ] SSO redirect works (login on mail → redirects to account)
- [ ] Registration works
- [ ] API endpoints respond

---

## 📦 Module Deployment Order

Deploy in this order for best results:

1. **YG Account** (Core authentication) ← START HERE
2. **YG Home** (Landing page)
3. **YG Mail** (Email service)
4. **YG Developer** (API portal)
5. **YG Calendar** (Calendar app)
6. **YG Chat** (Messaging)
7. **YG Contacts** (Contact management)
8. **YG Drive** (File storage)
9. **YG Notes** (Note-taking)
10. **YG Xcel** (Spreadsheets)
11. **YG DocX** (Documents)
12. **YG DB** (Database admin)
13. **YG Collect** (Data collection)

---

## 🔧 Quick Troubleshooting

### Issue: "Class not found" errors
**Solution:** Run `composer install` again

### Issue: Database connection failed
**Solution:** 
- Check database credentials in `.env`
- Verify database exists in cPanel
- Test connection: `mysql -u username -p database_name`

### Issue: Permission denied
**Solution:**
```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chown -R www-data:www-data storage/ bootstrap/cache/
```

### Issue: 500 Internal Server Error
**Solution:**
- Check `storage/logs/laravel.log`
- Verify `.htaccess` exists in public folder
- Check PHP error logs

### Issue: Assets not loading (CSS/JS)
**Solution:**
```bash
npm install
npm run build
php artisan storage:link
```

---

## 🌐 Subdomain Configuration Template

| Subdomain | Points To | Purpose |
|-----------|-----------|---------|
| `account.yourdomain.com` | `/yg-account/public` | Authentication & User Management |
| `mail.yourdomain.com` | `/YG Mail/public` | Email Service |
| `dev.yourdomain.com` | `/yg-developer/public` | Developer Portal |
| `yourdomain.com` | `/yg-home/public` | Landing Page |
| `calendar.yourdomain.com` | `/YG Calendar/public` | Calendar App |
| `chat.yourdomain.com` | `/YG Chat/public` | Messaging |
| `contacts.yourdomain.com` | `/YG Contacts/public` | Contact Manager |
| `drive.yourdomain.com` | `/YG Drive/public` | File Storage |
| `notes.yourdomain.com` | `/YG Notes/public` | Notes App |
| `xcel.yourdomain.com` | `/YG Xcel/public` | Spreadsheets |
| `docx.yourdomain.com` | `/YG DocX/public` | Documents |
| `db.yourdomain.com` | `/YG DB/public` | Database Admin |
| `collect.yourdomain.com` | `/YG Collect/public` | Data Collection |

---

## 🔐 Security Checklist

After deployment:
- [ ] Change all default passwords
- [ ] Enable SSL/HTTPS on all subdomains
- [ ] Set `APP_DEBUG=false` in production
- [ ] Configure firewall rules
- [ ] Setup automated backups
- [ ] Enable 2FA on admin accounts
- [ ] Review file permissions
- [ ] Disable directory listing
- [ ] Setup rate limiting
- [ ] Monitor error logs

---

## 📞 Need Help?

### Common Commands Reference

**Check Laravel version:**
```bash
php artisan --version
```

**Clear all caches:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

**Run specific migration:**
```bash
php artisan migrate --path=/database/migrations/your_migration.php
```

**Queue worker (if using queues):**
```bash
php artisan queue:work --daemon
```

**View routes:**
```bash
php artisan route:list
```

---

## 🎉 Success Indicators

You know you're deployed successfully when:

✅ All subdomains load without errors  
✅ Can register new account on account.yourdomain.com  
✅ Can login and see dashboard  
✅ SSO works between modules  
✅ API endpoints return JSON responses  
✅ No errors in `storage/logs/laravel.log`  
✅ Frontend assets (CSS/JS) load correctly  
✅ Database migrations completed  

---

## ⏱️ Time Estimates

| Task | Time |
|------|------|
| Run deployment script | 5 min |
| Server setup (databases, subdomains) | 15 min |
| Upload files via FTP | 10-20 min (depends on speed) |
| Install & configure each module | 2-3 min × 13 = ~30 min |
| Testing & verification | 10 min |
| **TOTAL** | **~60-70 minutes** |

---

## 🚀 YOU CAN DO THIS TODAY!

**Don't overthink it. Just start:**

1. Run the deployment script NOW
2. Follow the generated instructions
3. Deploy one module at a time
4. Test as you go
5. Celebrate each success! 💪

**Remember:** Perfect is the enemy of done. Get it live today, optimize tomorrow!

---

## 📚 Additional Resources

- `CPANEL_DEPLOY.md` - Detailed cPanel deployment guide
- `CPANEL_COMPATIBILITY_GUIDE.md` - cPanel optimization tips
- `QUICK_START_CHECKLIST.md` - Pre-deployment checklist
- `DEPLOYMENT_AND_TESTING_GUIDE.md` - Comprehensive testing guide

---

**Generated:** Today  
**Status:** Ready to Deploy 🚀  
**Estimated Completion:** < 2 hours  

**Let's make it happen! 💪🎊**
