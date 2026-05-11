# 🚀 DEPLOY TODAY - QUICK REFERENCE CARD

**Print this and keep it handy during deployment!**

---

## ⚡ 30-SECOND START

```powershell
# Windows
.\DEPLOY_TODAY.ps1

# Mac/Linux  
chmod +x DEPLOY_TODAY.sh && ./DEPLOY_TODAY.sh
```

---

## 📋 ESSENTIAL COMMANDS

### For Each Module (via SSH/Terminal)

```bash
cd /path/to/module
composer install --no-dev --optimize-autoloader
cp .env.production .env
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize
chmod -R 775 storage/ bootstrap/cache/
```

### Quick Fixes

```bash
# Clear all caches
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear

# Regenerate app key
php artisan key:generate

# Fix permissions
chmod -R 775 storage/ bootstrap/cache/

# View logs
tail -f storage/logs/laravel.log

# Check routes
php artisan route:list
```

---

## 🔑 CRITICAL .ENV SETTINGS

### YG Account
```env
APP_ENV=production
APP_DEBUG=false
DB_DATABASE={username}_yg_account
DB_USERNAME={username}_yg_admin
DB_PASSWORD=your_password
SESSION_DOMAIN=.yourdomain.com
YG_ACCOUNT_URL=https://account.yourdomain.com
```

### YG Mail
```env
APP_ENV=production
APP_DEBUG=false
DB_DATABASE={username}_yg_mail
YG_ACCOUNT_URL=https://account.yourdomain.com
SESSION_DOMAIN=.yourdomain.com
```

### All Other Modules
```env
APP_ENV=production
APP_DEBUG=false
DB_DATABASE={username}_yg_{module}
YG_ACCOUNT_URL=https://account.yourdomain.com
SESSION_DOMAIN=.yourdomain.com
```

---

## 🌐 SUBDOMAIN QUICK LIST

| Subdomain | Folder | Purpose |
|-----------|--------|---------|
| account.yourdomain.com | /account/public | Authentication |
| mail.yourdomain.com | /mail/public | Email |
| dev.yourdomain.com | /dev/public | Developer Portal |
| calendar.yourdomain.com | /calendar/public | Calendar |
| chat.yourdomain.com | /chat/public | Chat |
| contacts.yourdomain.com | /contacts/public | Contacts |
| drive.yourdomain.com | /drive/public | File Storage |
| notes.yourdomain.com | /notes/public | Notes |
| xcel.yourdomain.com | /xcel/public | Spreadsheets |
| docx.yourdomain.com | /docx/public | Documents |
| db.yourdomain.com | /db/public | DB Admin |
| collect.yourdomain.com | /collect/public | Data Collection |

---

## 🗄️ DATABASE NAMES

```
{username}_yg_account
{username}_yg_mail
{username}_yg_developer
{username}_yg_calendar
{username}_yg_chat
{username}_yg_contacts
{username}_yg_drive
{username}_yg_notes
{username}_yg_xcel
{username}_yg_docx
{username}_yg_db
{username}_yg_collect
{username}_yg_home
```

---

## 🔧 CPANEL QUICK STEPS

1. **Create Databases** → MySQL® Databases
2. **Create Subdomains** → Domains → Subdomains
3. **Upload Files** → File Manager or FTP
4. **Set Document Root** → Point to `/public` folder
5. **Configure .env** → Edit in File Manager
6. **Run Commands** → Terminal or SSH
7. **Setup Cron** → Cron Jobs → `* * * * * cd /path && php artisan schedule:run`
8. **Enable SSL** → SSL/TLS Status → Run AutoSSL

---

## ✅ VERIFICATION URLS

Test these after deployment:

```
https://account.yourdomain.com/up
https://mail.yourdomain.com/up
https://dev.yourdomain.com/up
https://account.yourdomain.com/register
https://account.yourdomain.com/login
```

All should return 200 OK or show the expected page.

---

## 🐛 TROUBLESHOOTING CHEAT SHEET

| Problem | Solution |
|---------|----------|
| 500 Error | Check `storage/logs/laravel.log` |
| Database Error | Verify credentials in `.env` |
| Class Not Found | Run `composer install` |
| CSS/JS Broken | Run `npm run build` |
| Permission Denied | `chmod -R 775 storage/` |
| 404 Errors | Check document root points to `/public` |
| Session Issues | Clear cookies, check `SESSION_DOMAIN` |
| White Screen | Enable debug temporarily: `APP_DEBUG=true` |

---

## 📞 EMERGENCY CONTACTS

**Error Logs Location:**
```
/path/to/module/storage/logs/laravel.log
/var/log/apache2/error.log
/var/log/nginx/error.log
```

**Quick Rollback:**
```bash
# Restore from backup
cp -r backup_folder current_folder

# Clear everything
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 🎯 DEPLOYMENT ORDER

1. ✅ YG Account (MUST be first!)
2. ✅ YG Home
3. ✅ YG Mail
4. ✅ YG Developer
5. ✅ YG Calendar
6. ✅ YG Chat
7. ✅ YG Contacts
8. ✅ YG Drive
9. ✅ YG Notes
10. ✅ YG Xcel
11. ✅ YG DocX
12. ✅ YG DB
13. ✅ YG Collect

---

## 💡 PRO TIPS

✅ Deploy one module at a time  
✅ Test each module before moving to next  
✅ Keep error logs open in separate tab  
✅ Use browser DevTools to check for errors  
✅ Take screenshots of successful deployments  
✅ Document any custom changes you make  
✅ Backup before making major changes  

---

## 🚨 DON'T FORGET!

- [ ] Change all default passwords
- [ ] Set `APP_DEBUG=false`
- [ ] Enable SSL on all subdomains
- [ ] Setup automated backups
- [ ] Configure cron jobs
- [ ] Test SSO between modules
- [ ] Monitor error logs daily (first week)

---

## 🎉 YOU GOT THIS!

**Remember:**
- Start with YG Account
- Take it one module at a time
- Test as you go
- Don't panic if errors occur - check logs!
- You can deploy TODAY! 💪

---

**Need Help?** Check:
- `DEPLOY_TODAY_GUIDE.md` - Full guide
- `CPANEL_QUICK_DEPLOY.md` - cPanel steps
- `DEPLOYMENT_TRACKER.md` - Progress tracker

---

**Print this page and keep it nearby during deployment!** 📄✨
