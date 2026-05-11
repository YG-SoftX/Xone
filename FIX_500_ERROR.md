# 🔧 FIX 500 ERROR - Missing Vendor Directory

**Your ygxone.com site is showing "500 Server Error" because the vendor directory is missing on the production server.**

---

##  THE PROBLEM

The error log shows:
```
Failed opening required '/home4/ygmarket/ygxone.com/yg-home/public/../../vendor/autoload.php'
```

This means:
- ✅ Your local project has the vendor folder (at `d:\YG SoftX\Xone\home\vendor`)
- ❌ The production server does NOT have the vendor folder
-  PHP cannot find the Composer autoload file

---

## ✅ SOLUTIONS (Choose One)

### **OPTION 1: SSH/Terminal Access (RECOMMENDED - EASIEST)**

If you have SSH access to your cPanel account:

1. **Login to cPanel**
2. Go to **Advanced → Terminal** (or use SSH client)
3. Run these commands:

```bash
# Navigate to your home directory
cd /home4/ygmarket/ygxone.com/yg-home

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Clear cache (optional but recommended)
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

**This will download and install all required packages automatically.**

---

### **OPTION 2: Upload Vendor Folder via FTP/cPanel File Manager**

If you don't have SSH access:

1. **Create a ZIP file of your vendor folder:**
   - Go to `d:\YG SoftX\Xone\home\vendor`
   - Right-click → Send to → Compressed (zipped) folder
   - Name it `vendor.zip`

2. **Upload to server:**
   - Login to cPanel → File Manager
   - Navigate to `/home4/ygmarket/ygxone.com/yg-home`
   - Upload `vendor.zip`
   - Extract the ZIP file

3. **Verify permissions:**
   - The `vendor` folder should be readable (755 permissions)

---

### **OPTION 3: Use Deployment Script**

Run the deployment script which includes composer install:

```powershell
# On your local Windows machine
cd "d:\YG SoftX\Xone"
.\launch-prepare.ps1
```

Then re-upload the entire project including vendor folder.

---

## 🔍 VERIFY THE FIX

After applying one of the solutions above:

1. Visit your site: https://ygxone.com
2. You should see the home page instead of 500 error
3. If still getting errors, check the error log again:
   - cPanel → File Manager → `/home4/ygmarket/ygxone.com/yg-home/public/error_log`

---

## 📋 PREVENTION FOR FUTURE DEPLOYMENTS

When deploying updates in the future:

### Using SSH:
```bash
cd /home4/ygmarket/ygxone.com/yg-home
git pull  # or upload new files
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan cache:clear
```

### Using File Upload:
- Always include the `vendor` folder in your uploads
- Or run `composer install` on the server after uploading

---

## 🆘 STILL HAVING ISSUES?

If the error persists after trying the solutions:

1. **Check PHP version:**
   - Should be PHP 8.2 or 8.3
   - cPanel → Software → Select PHP Version

2. **Check file permissions:**
   - `storage` folder: 775
   - `bootstrap/cache` folder: 775
   - `vendor` folder: 755

3. **Check .env file:**
   - Make sure `.env` exists on the server
   - `APP_KEY` should be set
   - Database credentials should be correct

4. **Review error log:**
   - cPanel → File Manager → `/home4/ygmarket/ygxone.com/yg-home/public/error_log`
   - Look for new error messages

---

## 📞 QUICK REFERENCE

- **Local vendor path:** `d:\YG SoftX\Xone\home\vendor`
- **Server path:** `/home4/ygmarket/ygxone.com/yg-home`
- **Composer command:** `composer install --no-dev --optimize-autoloader`
- **Error log:** `/home4/ygmarket/ygxone.com/yg-home/public/error_log`

---

**Created:** 2026-01-08
**Issue:** 500 Server Error - Missing vendor/autoload.php
**Status:** Solution provided above
