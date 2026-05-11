# YG Developer Portal - Complete Configuration Guide

## 🎯 Architecture Decision: Centralized Developer Portal

**Decision**: All developer features consolidated at **`https://developer.ygxone.com`**  
**Status**: ✅ **CONFIRMED**  
**Date**: May 4, 2026  

---

## 📋 What This Means

### ✅ **Developer Features AT developer.ygxone.com**

The following features are **exclusively** hosted on the standalone `yg-developer` application:

1. **API Project Management**
   - Create/manage API projects
   - Team collaboration
   - Environment configurations (staging/production)

2. **Credential Management**
   - Generate Client ID & Secret
   - Rotate credentials
   - Revoke access
   - View credential usage

3. **API Documentation**
   - Interactive API explorer
   - Code examples (PHP, Python, JavaScript, etc.)
   - SDK downloads
   - OpenAPI/Swagger specs

4. **Usage Analytics**
   - Real-time API call monitoring
   - Quota utilization charts
   - Geographic distribution
   - Performance metrics

5. **Webhook Configuration**
   - Register webhook endpoints
   - Test webhook delivery
   - View delivery logs
   - Retry failed webhooks

6. **Quota Management**
   - View current usage
   - Request quota increases
   - Set rate limits
   - Monitor throttling

7. **Billing & Subscriptions**
   - View pricing plans
   - Subscribe to API products
   - Download invoices
   - Payment history

8. **Team Management**
   - Invite developers
   - Assign roles (admin, developer, viewer)
   - Manage permissions
   - Activity logs

9. **Application Gallery**
   - Browse available API products
   - Subscribe to services (SSO, Mail, Drive, Pay, etc.)
   - View service status

10. **Support & Resources**
    - Submit support tickets
    - Access knowledge base
    - Community forums
    - Status page

---

### ❌ **NOT in YG Account (account.ygxone.com)**

The following routes/features should **NOT** exist in `yg-account`:

- ~~`/developer`~~ - Does not exist ✅
- ~~`/developer-console`~~ - Does not exist ✅
- ~~`/api/developer/*`~~ - Does not exist ✅
- ~~Developer dashboard~~ - Redirects to developer.ygxone.com ✅

**YG Account only provides**:
- OAuth 2.0 authentication endpoint (`/oauth/*`)
- User profile management
- SSO redirect for developer portal login

---

## 🔗 Integration Flow

```
┌─────────────────────────────────────────────────────────┐
│  Developer visits https://developer.ygxone.com          │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ↓
        ┌──────────────────────┐
        │  yg-developer app    │
        │  Checks auth status  │
        └──────────┬───────────┘
                   │
         Not authenticated?
                   ↓
        ┌──────────────────────┐
        │  Redirect to:        │
        │  account.ygxone.com  │
        │  /oauth/authorize    │
        └──────────┬───────────┘
                   │
                   ↓
        ┌──────────────────────┐
        │  User logs in with   │
        │  YG Account creds    │
        └──────────┬───────────┘
                   │
                   │ OAuth callback with token
                   ↓
        ┌──────────────────────┐
        │  Back to             │
        │  developer.ygxone.com│
        │  (authenticated)     │
        └──────────────────────┘
                   │
                   ↓
        ┌──────────────────────┐
        │  Full developer      │
        │  portal features     │
        │  accessible          │
        └──────────────────────┘
```

---

## ⚙️ Configuration Checklist

### 1. **Domain Setup**

```nginx
# Nginx configuration for developer.ygxone.com
server {
    listen 443 ssl http2;
    server_name developer.ygxone.com;
    
    root /var/www/yg-developer/public;
    index index.php;
    
    ssl_certificate /etc/ssl/certs/ygxone.com.pem;
    ssl_certificate_key /etc/ssl/private/ygxone.com.key;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 2. **Environment Variables** (`yg-developer/.env`)

```env
APP_NAME="YG Developer Portal"
APP_ENV=production
APP_URL=https://developer.ygxone.com

# YG Account Integration
YG_ACCOUNT_URL=https://account.ygxone.com
YG_ACCOUNT_CLIENT_ID=your_developer_portal_client_id
YG_ACCOUNT_CLIENT_SECRET=your_secret_here
YG_ACCOUNT_REDIRECT_URI=https://developer.ygxone.com/auth/sso/callback

# Database (shared with YG Account)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ygmarket_account
DB_USERNAME=ygmarket_account
DB_PASSWORD=secure_password

# Session Configuration
SESSION_DRIVER=database
SESSION_DOMAIN=.ygxone.com
SESSION_PATH=/
```

### 3. **OAuth Application Registration**

In **YG Account Admin Panel** → **Applications**:

```
Application Name: YG Developer Portal
Redirect URI: https://developer.ygxone.com/auth/sso/callback
Scopes: openid, profile, email, developer.read, developer.write
Client Type: Confidential
Grant Types: Authorization Code
```

### 4. **CORS Configuration** (`yg-developer/config/cors.php`)

```php
<?php

return [
    'paths' => ['api/*', 'auth/sso/*'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => [
        'https://developer.ygxone.com',
        'https://account.ygxone.com',
    ],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => [],
    
    'max_age' => 0,
    
    'supports_credentials' => true,
];
```

---

## 🗄️ Database Schema

All developer portal tables exist in **shared database** (`ygmarket_account`):

### Core Tables

| Table | Purpose |
|-------|---------|
| `api_products` | Available API services (SSO, Mail, Drive, Pay, etc.) |
| `developer_projects` | User-created API projects |
| `project_members` | Team members per project |
| `api_credentials` | OAuth client IDs & secrets |
| `product_subscriptions` | Which APIs each project uses |
| `project_quotas` | Usage limits & current consumption |
| `api_usage_logs` | Detailed API call tracking |
| `webhooks` | Registered webhook endpoints |
| `webhook_deliveries` | Webhook delivery attempts & status |
| `billing_accounts` | Payment information |
| `project_billing` | Billing per project |
| `developer_invoices` | Monthly invoices |
| `pricing_plans` | Subscription tiers (Free, Pro, Enterprise) |
| `project_plan_subscriptions` | Active plan per project |

---

## 🚀 Deployment Steps

### Step 1: Verify Domain DNS

```bash
# Check DNS records
dig developer.ygxone.com

# Should resolve to your server IP
```

### Step 2: Deploy Application

```bash
cd /var/www/yg-developer

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Generate app key (if new installation)
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed initial data
php artisan db:seed --class=ApiProductsSeeder
php artisan db:seed --class=PricingPlansSeeder
```

### Step 3: Configure Queue Workers

```bash
# Supervisor configuration for background jobs
# /etc/supervisor/conf.d/yg-developer.conf

[program:yg-developer-webhooks]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/yg-developer/artisan queue:work redis --queue=webhooks --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
stopwaitsecs=3600
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/yg-developer-webhooks.log

[program:yg-developer-billing]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/yg-developer/artisan queue:work redis --queue=billing --sleep=5 --tries=1 --timeout=300
autostart=true
autorestart=true
stopwaitsecs=3600
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/yg-developer-billing.log
```

### Step 4: Setup Cron Jobs

```bash
# crontab -e

# Aggregate API usage stats every hour
0 * * * * cd /var/www/yg-developer && php artisan schedule:run >> /dev/null 2>&1

# Generate monthly invoices on 1st of month
0 0 1 * * cd /var/www/yg-developer && php artisan billing:generate-invoices

# Clean old API logs (keep 90 days)
0 2 * * * cd /var/www/yg-developer && php artisan logs:cleanup --days=90
```

### Step 5: SSL Certificate

```bash
# Using Let's Encrypt
certbot --nginx -d developer.ygxone.com

# Auto-renewal
echo "0 0 1 * * certbot renew --quiet" | crontab -
```

---

## 🔐 Security Configuration

### 1. **Rate Limiting** (`yg-developer/app/Http/Kernel.php`)

```php
protected $middlewareGroups = [
    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

### 2. **API Authentication** (Sanctum)

```php
// Routes requiring API token authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/credentials', [CredentialController::class, 'store']);
    // ...
});
```

### 3. **Webhook Signature Verification**

All incoming webhooks from YG services must be verified:

```php
public function verifySignature(Request $request, string $secret): bool
{
    $signature = $request->header('X-YG-Signature');
    $payload = $request->getContent();
    
    $expectedSignature = hash_hmac('sha256', $payload, $secret);
    
    return hash_equals($expectedSignature, $signature);
}
```

---

## 📊 Monitoring & Logging

### 1. **Application Logs**

```bash
# View real-time logs
tail -f /var/www/yg-developer/storage/logs/laravel.log

# Filter by level
grep "ERROR" /var/www/yg-developer/storage/logs/laravel.log
```

### 2. **Queue Monitoring**

```bash
# Check queue status
php artisan queue:monitor

# Failed jobs
php artisan queue:failed
php artisan queue:retry all
```

### 3. **Database Query Logging**

Enable in `.env` for debugging:

```env
DB_LOG_QUERIES=true
```

---

## 🧪 Testing Checklist

### Pre-Launch Tests

- [ ] Domain resolves correctly: `https://developer.ygxone.com`
- [ ] SSL certificate valid
- [ ] SSO login works (redirects to account.ygxone.com)
- [ ] Can create new API project
- [ ] Can generate credentials (Client ID/Secret)
- [ ] Can subscribe to API products
- [ ] Webhook registration works
- [ ] Usage analytics display data
- [ ] Quota enforcement active
- [ ] Billing integration functional

### API Endpoint Tests

```bash
# Test dashboard endpoint
curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://developer.ygxone.com/api/dashboard

# Test project creation
curl -X POST https://developer.ygxone.com/api/projects \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"name":"Test Project","description":"Testing"}'

# Test webhook delivery
curl -X POST https://developer.ygxone.com/api/webhooks/test \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -d '{"url":"https://example.com/webhook","event":"test"}'
```

---

## 🔄 Migration from YG Account (If Needed)

If any developer routes currently exist in `yg-account`, remove them:

```php
// REMOVE from yg-account/routes/api.php or routes/web.php
// These routes should NOT exist:

// Route::prefix('developer')->group(function () {
//     Route::get('/dashboard', ...);
//     Route::get('/projects', ...);
//     // ... etc
// });

// Instead, add redirect:
Route::get('/developer', function () {
    return redirect('https://developer.ygxone.com');
});
```

---

## 📞 Support & Maintenance

### Regular Tasks

| Task | Frequency | Command |
|------|-----------|---------|
| Clear cache | Daily | `php artisan cache:clear` |
| Optimize | Weekly | `php artisan optimize` |
| Backup database | Daily | Automated via cron |
| Review error logs | Daily | Check `storage/logs/` |
| Update dependencies | Monthly | `composer update` |
| Security scan | Weekly | Automated tool |

### Emergency Contacts

- **DevOps**: devops@ygxone.com
- **Security**: security@ygxone.com
- **Support**: dev-support@ygxone.com

---

## ✅ Final Verification

Run this checklist before going live:

```bash
#!/bin/bash
# verify_developer_portal.sh

echo "🔍 Verifying YG Developer Portal..."

# 1. Check domain
echo -n "Domain resolves: "
ping -c 1 developer.ygxone.com > /dev/null 2>&1 && echo "✅" || echo "❌"

# 2. Check HTTPS
echo -n "HTTPS working: "
curl -I https://developer.ygxone.com 2>/dev/null | grep "200" > /dev/null && echo "✅" || echo "❌"

# 3. Check SSO redirect
echo -n "SSO configured: "
grep "YG_ACCOUNT_URL" .env > /dev/null && echo "✅" || echo "❌"

# 4. Check database
echo -n "Database connected: "
php artisan db:show > /dev/null 2>&1 && echo "✅" || echo "❌"

# 5. Check queues
echo -n "Queues running: "
ps aux | grep "queue:work" | grep -v grep > /dev/null && echo "✅" || echo "❌"

# 6. Check cron
echo -n "Cron configured: "
crontab -l | grep "schedule:run" > /dev/null && echo "✅" || echo "❌"

echo ""
echo "✅ Verification complete!"
```

---

## 🎯 Summary

**Developer Portal URL**: `https://developer.ygxone.com`  
**Application**: `yg-developer` (standalone Laravel app)  
**Authentication**: SSO via `account.ygxone.com`  
**Database**: Shared with YG Account (`ygmarket_account`)  
**Features**: Complete API management platform  
**Status**: ✅ **READY FOR PRODUCTION**

All developer features are **consolidated** at `developer.ygxone.com`.  
No developer routes exist in `yg-account`.  
Clean separation of concerns achieved! 🎉

---

**Last Updated**: May 4, 2026  
**Version**: 1.0.0  
**Maintained by**: YG Platform Engineering Team
