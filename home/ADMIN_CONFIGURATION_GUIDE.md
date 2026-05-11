# 🔐 YG Home - Admin User Configuration Guide

## Overview

This guide explains how to configure admin users for the YG Home admin dashboard (`/admin/dashboard`).

**Version:** 1.2.1  
**Last Updated:** 2026-05-07

---

## 📋 Quick Setup (3 Steps)

### **Step 1: Edit .env File**

Open `.env` file in the `yg-home` directory and locate the **Admin Configuration** section:

```env
# ── Admin Configuration ───────────────────────────────────────────────────────
ADMIN_EMAILS=admin@ygxone.com
ADMIN_USER_IDS=1
```

### **Step 2: Add Your Email**

Replace `admin@ygxone.com` with your actual email address:

```env
# For single admin
ADMIN_EMAILS=your.email@example.com

# For multiple admins (comma-separated, no spaces)
ADMIN_EMAILS=admin@ygxone.com,superadmin@company.com,dev@team.org
```

### **Step 3: Generate APP_KEY & Restart**

```bash
# Generate application key (required for sessions/encryption)
php artisan key:generate

# Clear config cache
php artisan config:clear

# Start/restart server
php artisan serve --host=0.0.0.0 --port=8001
```

**Done!** You can now access the admin dashboard.

---

## 🔧 Detailed Configuration Options

### **Method 1: Email-Based Authorization (Recommended)**

Configure admin users by their email addresses:

```env
# Single admin user
ADMIN_EMAILS=john.doe@ygxone.com

# Multiple admin users (comma-separated)
ADMIN_EMAILS=john@ygxone.com,jane@ygxone.com,admin@ygxone.com

# Team of admins
ADMIN_EMAILS=ceo@company.com,cto@company.com,it-admin@company.com,support@company.com
```

**How it works:**
- When a user logs in, their email is checked against this list
- If match found → Admin access granted ✅
- If no match → Access denied (403 Forbidden) ❌

**Advantages:**
- ✅ Easy to manage (just edit emails)
- ✅ No database changes needed
- ✅ Works immediately after config change
- ✅ Clear audit trail (email-based logging)

---

### **Method 2: User ID-Based Authorization**

Configure admin users by their database user IDs:

```env
# Single admin (user ID from 'users' table)
ADMIN_USER_IDS=1

# Multiple admins (comma-separated IDs)
ADMIN_USER_IDS=1,2,5,10

# Large team
ADMIN_USER_IDS=1,2,3,4,5,6,7,8,9,10
```

**How to find User IDs:**

```bash
# Option 1: Via Tinker
php artisan tinker
>>> DB::table('users')->select('id', 'name', 'email')->get();
>>> exit

# Option 2: Direct SQL query
mysql -u username -p
USE yg_home;
SELECT id, name, email FROM users;
```

**Example Output:**
```
+----+--------------+---------------------+
| id | name         | email               |
+----+--------------+---------------------+
|  1 | John Doe     | john@ygxone.com     |
|  2 | Jane Smith   | jane@ygxone.com     |
|  3 | Bob Wilson   | bob@company.com     |
+----+--------------+---------------------+
```

To make John and Jane admins:
```env
ADMIN_USER_IDS=1,2
```

**Advantages:**
- ✅ More secure (IDs don't change like emails)
- ✅ Faster lookup (integer comparison)
- ✅ Useful when email system unavailable

**Disadvantages:**
- ⚠️ Requires database access to find IDs
- ⚠️ Must update if users deleted/recreated

---

### **Method 3: Combined Approach (Most Flexible)**

Use both email AND user ID methods together:

```env
# Primary admins by email
ADMIN_EMAILS=ceo@company.com,cto@company.com

# Additional admins by ID (e.g., service accounts)
ADMIN_USER_IDS=1,2,99
```

**How it works:**
- User is admin if their email is in `ADMIN_EMAILS` **OR** their ID is in `ADMIN_USER_IDS`
- Provides flexibility for different scenarios

---

## 👥 Creating Admin Users

If you don't have users yet, create them:

### **Option A: Using Laravel Tinker**

```bash
php artisan tinker
```

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Create first admin user
User::create([
    'name' => 'John Doe',
    'email' => 'john@ygxone.com',
    'password' => Hash::make('SecurePassword123!'),
]);

// Create second admin user
User::create([
    'name' => 'Jane Smith',
    'email' => 'jane@ygxone.com',
    'password' => Hash::make('AnotherSecurePass456!'),
]);

exit
```

Then configure:
```env
ADMIN_EMAILS=john@ygxone.com,jane@ygxone.com
```

### **Option B: Using Database Seeder**

Create seeder file:
```bash
php artisan make:seeder AdminUserSeeder
```

Edit `database/seeders/AdminUserSeeder.php`:
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'System Administrator',
            'email' => 'admin@ygxone.com',
            'password' => Hash::make(env('ADMIN_PASSWORD', 'ChangeMe123!')),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@ygxone.com',
            'password' => Hash::make(env('SUPERADMIN_PASSWORD', 'SuperSecure456!')),
            'email_verified_at' => now(),
        ]);
    }
}
```

Run seeder:
```bash
php artisan db:seed --class=AdminUserSeeder
```

Configure:
```env
ADMIN_EMAILS=admin@ygxone.com,superadmin@ygxone.com
```

### **Option C: Using Registration System (if available)**

If you have Laravel Breeze/Jetstream installed:

```bash
# Visit registration page
http://localhost:8001/register

# Create user account
# Then add email to ADMIN_EMAILS in .env
```

---

## 🔒 Security Best Practices

### **1. Use Strong Passwords**

When creating admin users:
```php
// BAD - Weak password
Hash::make('password123')

// GOOD - Strong password (12+ chars, mixed case, numbers, symbols)
Hash::make('Kj8#mN2$pL9@qR5!')
```

### **2. Limit Admin Access**

**Principle of Least Privilege:**
- Only grant admin access to those who need it
- Regularly review admin user list
- Remove access when no longer needed

```env
# Review quarterly - remove inactive admins
ADMIN_EMAILS=active1@company.com,active2@company.com
# NOT: ADMIN_EMAILS=old_admin@company.com,left_employee@company.com
```

### **3. Environment Variable Protection**

**NEVER commit `.env` to version control:**

```bash
# Verify .gitignore includes .env
cat .gitignore | grep ".env"

# Should show:
# .env
# .env.backup
# .env.production
```

**On cPanel:**
- Store `.env` outside web root if possible
- Set file permissions to `600` (owner read/write only)

```bash
chmod 600 .env
```

### **4. Rotate Credentials Regularly**

**Every 90 days:**
1. Change admin user passwords
2. Update `ADMIN_PASSWORD` in .env (if used)
3. Test new credentials
4. Document rotation date

### **5. Monitor Admin Access**

Check admin access logs:

```bash
# View recent admin logins
tail -f storage/logs/laravel.log | grep "Admin dashboard accessed"

# Example output:
# [2026-05-07 14:30:25] local.INFO: Admin dashboard accessed 
# {"user_id":1,"email":"admin@ygxone.com","ip":"192.168.1.100"}
```

**Suspicious activity indicators:**
- Multiple failed login attempts
- Access from unknown IP addresses
- Unusual access times (e.g., 3 AM)
- Multiple admins accessing simultaneously

---

## 🧪 Testing Admin Configuration

### **Test 1: Verify Configuration Loaded**

```bash
php artisan tinker
>>> config('app.name');
# Should return: "YGXONE Search"

>>> env('ADMIN_EMAILS');
# Should return: "your@email.com"

>>> exit
```

### **Test 2: Check Middleware Registration**

```bash
php artisan route:list | grep admin

# Expected output:
# GET|HEAD  admin/dashboard .............. admin.dashboard
# POST      admin/cache/clear ............ admin.cache.clear
# POST      admin/rebuild-index .......... admin.rebuild
# POST      admin/sync-modules ........... admin.sync
```

### **Test 3: Access Without Authentication**

```bash
# Try accessing admin dashboard without login
curl -I http://localhost:8001/admin/dashboard

# Expected: HTTP/2 302 Found (redirect to login)
# Location: /sso/initiate or /login
```

### **Test 4: Login as Admin**

1. **Start server:**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8001
   ```

2. **Visit login page:**
   ```
   http://localhost:8001/login
   # or
   http://localhost:8001/sso/initiate
   ```

3. **Login with admin credentials:**
   - Email: `admin@ygxone.com` (or your configured email)
   - Password: Your password

4. **Access admin dashboard:**
   ```
   http://localhost:8001/admin/dashboard
   ```

5. **Expected result:**
   - ✅ Dashboard loads successfully
   - ✅ Shows 6 KPI cards
   - ✅ Charts render correctly
   - ✅ Management buttons visible

### **Test 5: Verify Non-Admin Access Denied**

1. **Create regular user:**
   ```bash
   php artisan tinker
   >>> User::create(['name' => 'Regular User', 'email' => 'user@example.com', 'password' => Hash::make('password')]);
   >>> exit
   ```

2. **Login as regular user**

3. **Try accessing admin dashboard:**
   ```
   http://localhost:8001/admin/dashboard
   ```

4. **Expected result:**
   - ❌ 403 Forbidden error
   - Message: "Unauthorized access. Admin privileges required."

---

## 🐛 Troubleshooting

### **Issue 1: "403 Forbidden" Even Though Email is Configured**

**Possible Causes:**
1. Email mismatch (typo in .env)
2. Config cache not cleared
3. User not authenticated

**Solution:**
```bash
# 1. Verify email in .env matches exactly
grep ADMIN_EMAILS .env

# 2. Check logged-in user's email
php artisan tinker
>>> auth()->user()->email;
>>> exit

# 3. Clear config cache
php artisan config:clear
php artisan cache:clear

# 4. Ensure emails match exactly (case-sensitive)
# .env: ADMIN_EMAILS=John@Example.com
# User: john@example.com ❌ MISMATCH
# User: John@Example.com ✅ MATCH
```

### **Issue 2: "Page Not Found" (404)**

**Cause:** Routes not registered

**Solution:**
```bash
# Clear route cache
php artisan route:clear

# Verify routes exist
php artisan route:list | grep admin

# If empty, check bootstrap/app.php has middleware registered
cat bootstrap/app.php | grep "AdminMiddleware"
```

### **Issue 3: Admin Dashboard Shows Blank/White Screen**

**Cause:** JavaScript errors or missing dependencies

**Solution:**
```bash
# Check browser console (F12 → Console tab)
# Common fixes:

# 1. Build frontend assets
npm install
npm run build

# 2. Verify Chart.js CDN loads
# Check network tab for chart.js loading errors

# 3. Clear view cache
php artisan view:clear
```

### **Issue 4: Changes to .env Not Taking Effect**

**Cause:** Config cached

**Solution:**
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Restart server
# Ctrl+C to stop, then:
php artisan serve --host=0.0.0.0 --port=8001
```

### **Issue 5: Too Many Redirects**

**Cause:** SSO/Login loop

**Solution:**
```bash
# Check redirect configuration
cat bootstrap/app.php | grep "redirectGuestsTo"

# Should be:
$middleware->redirectGuestsTo('/sso/initiate');

# If using simple auth instead:
$middleware->redirectGuestsTo('/login');
```

---

## 📊 Monitoring Admin Activity

### **View Admin Access Logs**

```bash
# Real-time monitoring
tail -f storage/logs/laravel.log | grep "Admin"

# Last 100 admin actions
grep "Admin dashboard accessed" storage/logs/laravel.log | tail -100

# Filter by specific admin
grep "admin@ygxone.com" storage/logs/laravel.log
```

### **Log Format**

```json
{
  "message": "Admin dashboard accessed",
  "context": {
    "user_id": 1,
    "email": "admin@ygxone.com",
    "ip": "192.168.1.100",
    "user_agent": "Mozilla/5.0...",
    "timestamp": "2026-05-07T14:30:25+00:00"
  }
}
```

### **Setup Log Rotation**

Prevent log files from growing too large:

```bash
# Create logrotate config
sudo nano /etc/logrotate.d/yg-home

# Add:
/var/www/yg-home/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0644 www-data www-data
}
```

---

## 🔄 Updating Admin Users

### **Add New Admin**

1. **Edit .env:**
   ```env
   # Before
   ADMIN_EMAILS=admin@ygxone.com
   
   # After (add new admin)
   ADMIN_EMAILS=admin@ygxone.com,newadmin@ygxone.com
   ```

2. **Clear cache:**
   ```bash
   php artisan config:clear
   ```

3. **No restart needed** - changes take effect immediately

### **Remove Admin**

1. **Edit .env:**
   ```env
   # Before
   ADMIN_EMAILS=admin@ygxone.com,oldadmin@ygxone.com
   
   # After (remove old admin)
   ADMIN_EMAILS=admin@ygxone.com
   ```

2. **Clear cache:**
   ```bash
   php artisan config:clear
   ```

3. **Old admin immediately loses access**

### **Temporary Admin Access**

For temporary access (e.g., contractor):

```env
# Add with expiration note in comment
ADMIN_EMAILS=admin@ygxone.com,contractor@external.com # REMOVE AFTER 2026-06-07
```

Set calendar reminder to remove after project completion.

---

## 🚀 Production Deployment

### **Pre-Deployment Checklist**

- [ ] Admin emails configured in `.env`
- [ ] Strong passwords set for all admin users
- [ ] `.env` file NOT committed to git
- [ ] File permissions set to `600` on `.env`
- [ ] HTTPS enabled (SESSION_SECURE_COOKIE=true)
- [ ] Rate limiting active on admin routes
- [ ] Audit logging verified working
- [ ] Backup of current admin list documented

### **Production .env Settings**

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error

SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Admin configuration
ADMIN_EMAILS=ceo@company.com,cto@company.com,it-security@company.com
ADMIN_USER_IDS=1,2,3
```

### **Post-Deployment Verification**

```bash
# 1. Verify admin access works
curl -b admin_cookies.txt https://ygxone.com/admin/dashboard
# Expected: HTTP/2 200 OK

# 2. Verify non-admin blocked
curl -b user_cookies.txt https://ygxone.com/admin/dashboard
# Expected: HTTP/2 403 Forbidden

# 3. Check logs for errors
tail -n 50 storage/logs/laravel.log

# 4. Test rate limiting
for i in {1..6}; do
    curl -X POST https://ygxone.com/admin/cache/clear
done
# Expected: 6th request returns 429 Too Many Requests
```

---

## 📞 Support & Resources

### **Quick Reference**

| Task | Command/Action |
|------|----------------|
| Configure admin email | Edit `ADMIN_EMAILS` in `.env` |
| Find user IDs | `php artisan tinker` → `DB::table('users')->get()` |
| Create admin user | Use seeder or tinker (see above) |
| Clear config cache | `php artisan config:clear` |
| View admin logs | `tail -f storage/logs/laravel.log \| grep Admin` |
| Test admin access | Visit `/admin/dashboard` after login |

### **Related Documentation**

- [`TESTING_AND_DEPLOYMENT_GUIDE.md`](TESTING_AND_DEPLOYMENT_GUIDE.md) - Complete testing procedures
- [`DEPLOYMENT_CHECKLIST.md`](DEPLOYMENT_CHECKLIST.md) - Production deployment steps
- [`PAGINATION_AND_ADMIN_GUIDE.md`](PAGINATION_AND_ADMIN_GUIDE.md) - Admin dashboard features

### **Common Commands**

```bash
# Generate app key (first time setup)
php artisan key:generate

# Create database tables
php artisan migrate --force

# Clear all caches
php artisan optimize:clear

# View all routes
php artisan route:list

# Check environment
php artisan about
```

---

## ✨ Summary

**Configuring admin users is simple:**

1. **Edit `.env`** → Add your email to `ADMIN_EMAILS`
2. **Create user** → Use tinker or seeder to create account
3. **Clear cache** → Run `php artisan config:clear`
4. **Login & test** → Access `/admin/dashboard`

**Security reminders:**
- 🔒 Use strong passwords
- 🔒 Limit admin access to essential personnel
- 🔒 Monitor admin activity logs
- 🔒 Rotate credentials every 90 days
- 🔒 Never commit `.env` to version control

**Need help?** Check troubleshooting section above or review logs in `storage/logs/laravel.log`.

---

**Version:** 1.2.1  
**Last Updated:** 2026-05-07  
**Status:** ✅ Ready for Configuration
