# ⚡ CPANEL DEPLOYMENT - 30 MINUTE QUICK GUIDE

**Get your YG ecosystem live on cPanel in 30 minutes!**

---

## 🎯 PRE-FLIGHT CHECKLIST (5 minutes)

Before you start, make sure you have:

- [ ] cPanel login credentials
- [ ] Domain name configured (e.g., ygxone.com)
- [ ] FTP client installed (FileZilla recommended)
- [ ] SSH access enabled (or use cPanel Terminal)
- [ ] Deployment package ready (run `DEPLOY_TODAY.ps1` first)

---

## 📋 STEP-BY-STEP DEPLOYMENT

### Step 1: Create Databases (5 minutes)

1. **Login to cPanel**
2. Go to **Databases → MySQL® Databases**
3. Create these databases:
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
   *(Replace `{username}` with your cPanel username)*

4. **Create Database Users:**
   - Click "Add New User"
   - Username: `{username}_yg_admin`
   - Password: Generate strong password (SAVE IT!)
   - Add user to ALL databases with **ALL PRIVILEGES**

---

### Step 2: Create Subdomains (5 minutes)

1. Go to **Domains → Subdomains**
2. Create these subdomains:

| Subdomain | Document Root |
|-----------|---------------|
| account | `/public_html/account/public` |
| mail | `/public_html/mail/public` |
| dev | `/public_html/dev/public` |
| calendar | `/public_html/calendar/public` |
| chat | `/public_html/chat/public` |
| contacts | `/public_html/contacts/public` |
| drive | `/public_html/drive/public` |
| notes | `/public_html/notes/public` |
| xcel | `/public_html/xcel/public` |
| docx | `/public_html/docx/public` |
| db | `/public_html/db/public` |
| collect | `/public_html/collect/public` |

**IMPORTANT:** Set document root to the `public` folder for each!

---

### Step 3: Upload Files (10 minutes)

#### Method A: Using File Manager (Easier)

1. Go to **Files → File Manager**
2. Navigate to `/public_html`
3. Create folders for each module:
   ```
   /public_html/account
   /public_html/mail
   /public_html/dev
   ... (and so on)
   ```

4. For each module:
   - Click "Upload"
   - Select the module folder from your deployment package
   - Wait for upload to complete
   - Extract if uploaded as ZIP

#### Method B: Using FTP (Faster for large files)

1. Connect via FTP:
   - Host: `ftp.yourdomain.com`
   - Username: Your cPanel username
   - Password: Your cPanel password
   - Port: 21

2. Upload each module to its respective folder

---

### Step 4: Configure Environment Files (5 minutes)

For **EACH** module, do this:

1. Navigate to module folder in File Manager
2. Find `.env.production` file
3. Click "Edit" and update these values:

```env
DB_DATABASE={username}_yg_account    # Use correct DB name
DB_USERNAME={username}_yg_admin      # Your DB username
DB_PASSWORD=your_actual_password     # Your DB password

MAIL_HOST=mail.yourdomain.com
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password

APP_URL=https://account.yourdomain.com  # Use correct URL
YG_ACCOUNT_URL=https://account.yourdomain.com  # For other modules
```

4. Save the file
5. Rename `.env.production` to `.env`

**Do this for all 13 modules!**

---

### Step 5: Run Installation Commands (5 minutes)

Use **cPanel Terminal** or **SSH**:

For **EACH** module, run:

```bash
# Navigate to module
cd ~/public_html/account

# Install dependencies
composer install --no-dev --optimize-autoloader

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Optimize application
php artisan optimize

# Set permissions
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

**Repeat for all modules:**
```bash
cd ~/public_html/mail && composer install --no-dev && php artisan key:generate && php artisan migrate --force && php artisan storage:link && php artisan optimize && chmod -R 775 storage/ bootstrap/cache/

cd ~/public_html/dev && composer install --no-dev && php artisan key:generate && php artisan migrate --force && php artisan storage:link && php artisan optimize && chmod -R 775 storage/ bootstrap/cache/

# ... continue for all modules
```

---

### Step 6: Setup Cron Job (2 minutes)

1. Go to **Advanced → Cron Jobs**
2. Add this cron job:
   ```
   * * * * * cd /home/{username}/public_html/account && php artisan schedule:run >> /dev/null 2>&1
   ```
3. Click "Add New Cron Job"

This runs Laravel scheduler every minute for background tasks.

---

### Step 7: Enable SSL (Optional but Recommended)

1. Go to **Security → SSL/TLS Status**
2. Select all subdomains
3. Click "Run AutoSSL"
4. Wait for certificates to be issued (~5 minutes)

---

## ✅ VERIFICATION CHECKLIST

Test each subdomain:

- [ ] https://account.yourdomain.com → Login page appears
- [ ] https://mail.yourdomain.com → Mail interface loads
- [ ] https://dev.yourdomain.com → Developer portal loads
- [ ] Register new account works
- [ ] Login works
- [ ] Can access dashboard
- [ ] SSO redirect works (login on mail → goes to account)
- [ ] No errors in browser console
- [ ] CSS/JS files load correctly

---

## 🔧 TROUBLESHOOTING

### Problem: "500 Internal Server Error"

**Solution:**
1. Check error log: `~/public_html/account/storage/logs/laravel.log`
2. Verify `.htaccess` exists in public folder
3. Check file permissions: `chmod -R 775 storage/`
4. Clear cache: `php artisan cache:clear`

### Problem: "Database connection failed"

**Solution:**
1. Verify database name in `.env` matches cPanel
2. Check database user has privileges
3. Test connection:
   ```bash
   mysql -u {username}_yg_admin -p {username}_yg_account
   ```

### Problem: "Class not found" errors

**Solution:**
```bash
composer dump-autoload
composer install --no-dev
```

### Problem: Assets not loading (CSS/JS broken)

**Solution:**
```bash
npm install
npm run build
php artisan storage:link
```

### Problem: Permission denied

**Solution:**
```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chown -R {username}:{username} storage/ bootstrap/cache/
```

---

## 📝 QUICK REFERENCE COMMANDS

**Clear all caches:**
```bash
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear
```

**Regenerate app key:**
```bash
php artisan key:generate
```

**Check routes:**
```bash
php artisan route:list
```

**View logs:**
```bash
tail -f storage/logs/laravel.log
```

**Optimize application:**
```bash
php artisan optimize
```

---

## 🎉 YOU'RE LIVE!

Once all checks pass:

✅ Share your domain with users  
✅ Start collecting feedback  
✅ Monitor error logs daily  
✅ Setup automated backups  
✅ Celebrate your success! 🎊  

---

## 📞 Need Help?

**Common Issues & Solutions:**

| Issue | Quick Fix |
|-------|-----------|
| White screen | Check `storage/logs/laravel.log` |
| 404 errors | Verify document root points to `/public` |
| Database error | Check credentials in `.env` |
| CSS not loading | Run `npm run build` |
| Session issues | Clear cookies, check `SESSION_DOMAIN` |

---

## ⏱️ Time Breakdown

| Task | Time |
|------|------|
| Create databases | 5 min |
| Create subdomains | 5 min |
| Upload files | 10 min |
| Configure .env files | 5 min |
| Run commands | 5 min |
| Testing | 5 min |
| **TOTAL** | **~35 minutes** |

---

**🚀 You can deploy TODAY! Let's do this! 💪**
