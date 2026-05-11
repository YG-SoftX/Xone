# 🚀 YG Console - Quick Start Guide

## ⚡ 5-Minute Setup

### **Step 1: Install Dependencies**

```bash
cd "c:\Users\ASUS\Downloads\YG Soft1\yg-console"

# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### **Step 2: Configure Environment**

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

Edit `.env` and configure:

```env
APP_NAME="YG Console"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yg_console
DB_USERNAME=root
DB_PASSWORD=your_password

# Optional: Redis for caching
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### **Step 3: Setup Database**

```bash
# Run migrations
php artisan migrate

# (Optional) Seed test data
php artisan db:seed
```

### **Step 4: Install Filament Admin Panel**

```bash
# Install Filament
php artisan filament:install --panels

# Create admin user
php artisan make:filament-user
# Email: admin@ygxone.com
# Password: (choose secure password)
```

### **Step 5: Build Frontend Assets**

```bash
npm run build
```

### **Step 6: Start Development Server**

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**Visit:** http://localhost:8000

---

## 🎯 First Steps After Installation

### **1. Create Your First Project**

1. Login to admin panel: http://localhost:8000/admin
2. Navigate to Projects
3. Click "New Project"
4. Enter project name and description
5. Save

### **2. Generate API Key**

1. Go to your project
2. Click "API Keys" tab
3. Click "Generate New Key"
4. Copy the key (you won't see it again!)
5. Store securely

### **3. Create OAuth Application**

1. Go to your project
2. Click "OAuth Apps" tab
3. Click "New OAuth App"
4. Enter app name
5. Add redirect URIs
6. Save and copy client_id & client_secret

---

## 📊 Database Schema Overview

```
projects
├── id, user_id, name, slug, status
└── Relationships: teamMembers, oauthApplications, apiKeys, etc.

oauth_applications
├── id, project_id, client_id, client_secret
└── Used for OAuth 2.0 authentication

api_keys
├── id, project_id, key, rate_limit
└── Used for API authentication

play_store_apps
├── id, project_id, package_name, version
└── Manage mobile apps

subscriptions
├── id, project_id, plan_id, status
└── Track billing subscriptions

billing_invoices
├── id, project_id, amount, status
└── Invoice history

ai_usage_logs
├── id, project_id, model, tokens, cost
└── Track AI API usage

webhook_endpoints
├── id, project_id, url, events
└── Webhook configuration

webhook_deliveries
├── id, webhook_endpoint_id, payload, status
└── Webhook delivery logs

team_members
├── id, project_id, user_id, role
└── Team collaboration
```

---

## 🔧 Configuration Files

### **config/ai.php** (Create this)

```php
<?php

return [
    'pricing' => [
        'gpt-4' => [
            'prompt' => 0.00003,      // $0.03 per 1K tokens
            'completion' => 0.00006,   // $0.06 per 1K tokens
        ],
        'gpt-3.5-turbo' => [
            'prompt' => 0.0000015,     // $0.0015 per 1K tokens
            'completion' => 0.000002,  // $0.002 per 1K tokens
        ],
        'claude-2' => [
            'prompt' => 0.000008,
            'completion' => 0.000024,
        ],
    ],
];
```

### **config/services.php** (Add these)

```php
'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],

'paypal' => [
    'client_id' => env('PAYPAL_CLIENT_ID'),
    'secret' => env('PAYPAL_SECRET'),
    'mode' => env('PAYPAL_MODE', 'sandbox'),
],
```

---

## 🛠️ Useful Artisan Commands

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild optimizations
php artisan optimize

# View routes
php artisan route:list

# Check database status
php artisan db:show

# Create new migration
php artisan make:migration create_table_name

# Create new model
php artisan make:model ModelName

# Create new controller
php artisan make:controller ControllerName

# Create new Filament resource
php artisan make:filament-resource ResourceName

# Queue worker (background jobs)
php artisan queue:work

# Schedule runner (cron jobs)
php artisan schedule:run
```

---

## 🐛 Troubleshooting

### **Issue: "Database connection refused"**

```bash
# Check MySQL is running
net start MySQL80  # Windows
sudo systemctl start mysql  # Linux/Mac

# Verify credentials in .env
grep DB_ .env
```

### **Issue: "Class not found"**

```bash
# Regenerate autoloader
composer dump-autoload
```

### **Issue: "Permission denied"**

```bash
# Fix storage permissions (Linux/Mac)
chmod -R 775 storage bootstrap/cache

# Windows: Run as Administrator or adjust folder permissions
```

### **Issue: "npm run build fails"**

```bash
# Clear node_modules and reinstall
rm -rf node_modules package-lock.json
npm install
npm run build
```

---

## 📚 Next Steps

1. **Read Full Documentation:** See `README.md`
2. **Explore Admin Panel:** Visit `/admin` after login
3. **Check API Docs:** Review routes with `php artisan route:list`
4. **Join Community:** https://community.ygxone.com
5. **Report Issues:** https://github.com/ygxone/console/issues

---

## 🎉 You're Ready!

Your YG Console platform is now set up and ready for development!

**Access Points:**
- Main Site: http://localhost:8000
- Admin Panel: http://localhost:8000/admin
- API Endpoints: http://localhost:8000/api/v1/*

**Default Admin:**
- Email: admin@ygxone.com
- Password: (what you set during setup)

Happy coding! 🚀
