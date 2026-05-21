# Login & Browser Issues - Troubleshooting Guide

## Issues Identified
1. **Browser not loading google.com** - Proxy may have configuration or timeout issues
2. **Cannot login to any service module** - SSO routes were missing (now fixed)

---

## ✅ Fixes Applied

### 1. **Restored SSO Routes** (`home/routes/web.php`)
- Added back all SSO authentication routes
- Fixed redirects to use `browser.home` instead of `search.home`

### 2. **Updated AdminMiddleware** (`AdminMiddleware.php`)
- Changed redirect from `search.home` to `browser.home`

### 3. **Updated SSOController Redirects**
- All callback redirects now point to root `/` (browser home)

---

## 🔧 Browser Not Loading External Sites (google.com)

### Possible Causes & Solutions

#### A. **Check Proxy Timeout Configuration**
The proxy has a default 15-second timeout. For slow sites, increase it:

**In `.env`:**
```env
BROWSER_PROXY_TIMEOUT=30
```

**Or in `config/browser.php` (if exists):**
```php
'proxy_timeout' => env('BROWSER_PROXY_TIMEOUT', 30),
```

#### B. **Check SSL Verification**
Some sites may have SSL issues. Try disabling SSL verification temporarily:

**In `.env`:**
```env
BROWSER_VERIFY_SSL=false
```

#### C. **Check cURL is Enabled**
Verify PHP cURL extension is installed:
```bash
php -m | grep curl
```

If not installed (cPanel):
1. Go to cPanel → Select PHP Version
2. Enable `curl` extension
3. Save

#### D. **Test Proxy Manually**
```bash
# Test if browse endpoint works
curl -I "https://ygxone.com/browse?url=https://www.google.com"
```

Expected: Should return HTML content with status 200

#### E. **Check Error Logs**
```bash
tail -f storage/logs/laravel.log
```

Look for:
- "BrowserProxy: cURL error"
- "Browser proxy failed"
- Timeout errors

---

## 🔐 Login/Register Not Working

### Step 1: Verify Account Service URL

**Check `.env` file:**
```env
YG_ACCOUNT_URL=https://account.ygxone.com
```

For local development:
```env
YG_ACCOUNT_URL=http://localhost:8000
```

### Step 2: Clear All Caches
```bash
cd /path/to/home
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
```

### Step 3: Verify Routes Are Registered
```bash
php artisan route:list | grep sso
```

Expected output:
```
GET|HEAD  login .............. login › SSOController@initiate
GET|HEAD  sso/callback ....... sso.callback › SSOController@callback
GET|HEAD  sso/initiate ....... sso.initiate › SSOController@initiate
GET|HEAD  sso/logout ......... sso.logout › SSOController@logout
```

### Step 4: Test SSO Flow

#### Test Initiate:
```bash
curl -I https://ygxone.com/sso/initiate
```

Should redirect to account service:
```
Location: https://account.ygxone.com/sso/initiate?service=...&callback=...
```

#### Test Callback Route Exists:
```bash
curl -I https://ygxone.com/sso/callback
```

Should return 200 or redirect (depends on token presence)

### Step 5: Check Database Session Configuration

SSO requires database sessions. Verify `.env`:
```env
SESSION_DRIVER=database
SESSION_DOMAIN=.ygxone.com
SESSION_LIFETIME=120
```

Run session migration if needed:
```bash
php artisan session:table
php artisan migrate
```

### Step 6: Verify Account Service is Running

Test account service health:
```bash
curl -I https://account.ygxone.com/up
```

Should return:
```
HTTP/2 200
{"status":"healthy"}
```

### Step 7: Check CORS Configuration

If account service is on different domain, verify CORS in `config/cors.php`:
```php
'allowed_origins' => [
    'https://ygxone.com',
    'https://*.ygxone.com',
],
```

---

## 🚀 Quick Fix Checklist

Execute these commands in order:

```bash
# 1. Navigate to home module
cd /path/to/home

# 2. Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 3. Verify routes
php artisan route:list | grep -E "(sso|login|browse)"

# 4. Check logs for errors
tail -50 storage/logs/laravel.log

# 5. Test browser proxy
curl -s "https://ygxone.com/browse?url=https://www.google.com" | head -20

# 6. Test SSO initiate
curl -I https://ygxone.com/sso/initiate
```

---

## 📊 Diagnostic Commands

### Check if cURL Works:
```php
// Create test file: public/test-curl.php
<?php
$ch = curl_init('https://www.google.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($result) {
    echo "✓ cURL works! Fetched " . strlen($result) . " bytes";
} else {
    echo "✗ cURL error: " . $error;
}
```

Access: `https://ygxone.com/test-curl.php`

### Check PHP Extensions:
```bash
php -m | grep -E "(curl|openssl|mbstring)"
```

Required: curl, openssl, mbstring

### Check Connectivity to Account Service:
```bash
ping account.ygxone.com
curl -I https://account.ygxone.com/up
```

---

## 🐛 Common Error Messages & Solutions

### Error: "Failed to fetch page: Could not resolve host"
**Solution:** DNS issue. Check server's DNS settings or try using IP directly.

### Error: "SSL certificate problem"
**Solution:** Set `BROWSER_VERIFY_SSL=false` in `.env` temporarily.

### Error: "Connection timed out"
**Solution:** Increase timeout: `BROWSER_PROXY_TIMEOUT=30`

### Error: "Login failed: could not reach account service"
**Solution:** 
1. Verify `YG_ACCOUNT_URL` in `.env`
2. Check account service is running
3. Test connectivity: `curl https://account.ygxone.com/up`

### Error: "Route [search.home] not defined"
**Solution:** Already fixed! Routes now use `browser.home`

---

## 📝 Testing After Fixes

### Test Browser:
1. Open `https://ygxone.com/`
2. Type `google.com` in address bar
3. Press Enter
4. Should load Google homepage in iframe

### Test Login:
1. Click "Login with YG" button
2. Should redirect to account.ygxone.com
3. Enter credentials
4. Should redirect back to ygxone.com with success message
5. User name should appear in top right

### Test Other Modules:
Each module (mail, drive, calendar, etc.) should:
1. Have same SSO configuration
2. Use `YG_ACCOUNT_URL` pointing to account service
3. Share same database for sessions

---

## 🔍 Advanced Debugging

### Enable Detailed Logging:

**In `.env`:**
```env
LOG_LEVEL=debug
```

**Monitor logs:**
```bash
tail -f storage/logs/laravel.log | grep -i "sso\|browser\|proxy"
```

### Test Proxy Service Directly:

Create test script `public/test-proxy.php`:
```php
<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$proxy = new \App\Services\BrowserProxyService();
$result = $proxy->fetch('https://www.google.com');

echo "Status: " . $result['statusCode'] . "\n";
echo "Content Length: " . strlen($result['content']) . "\n";
echo "Title: " . ($result['title'] ?? 'N/A') . "\n";
```

Access: `https://ygxone.com/test-proxy.php`

---

## ⚠️ Important Notes

1. **All modules must share the same database** for SSO sessions to work
2. **SESSION_DOMAIN** must be set to `.ygxone.com` for cross-subdomain auth
3. **Account service must be accessible** from all module servers
4. **Firewall rules** must allow outbound HTTPS connections for proxy
5. **cPanel shared hosting** may have cURL restrictions - contact support if issues persist

---

## 📞 If Issues Persist

1. Check server error logs: `/var/log/apache2/error.log` or cPanel error log
2. Verify PHP version >= 8.0
3. Ensure all required PHP extensions are enabled
4. Check memory limit: `memory_limit` should be >= 256M
5. Contact hosting provider about cURL/fopen restrictions