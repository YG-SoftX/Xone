# 🚀 YG Console - Quick Reference Card

## 📦 Core Services Created (6 Total)

| Service | File | Purpose |
|---------|------|---------|
| **ProjectService** | `app/Services/ProjectService.php` | Project CRUD, stats |
| **ApiKeyService** | `app/Services/ApiKeyService.php` | API key management, rate limiting |
| **OAuthService** | `app/Services/OAuthService.php` | OAuth 2.0 app management |
| **BillingService** | `app/Services/BillingService.php` | YG Pay integration, invoicing |
| **PlayStoreService** | `app/Services/PlayStoreService.php` | Mobile app submissions |
| **AiService** | `app/Services/AiService.php` | YG AI integration, usage tracking |
| **WebhookService** | `app/Services/WebhookService.php` | Webhook delivery & verification |

---

## 🔑 Key Methods Cheat Sheet

### **ProjectService**
```php
$service = new ProjectService();
$project = $service->createProject($user, ['name' => 'My App']);
$stats = $service->getProjectStats($project);
```

### **ApiKeyService**
```php
$service = new ApiKeyService();
$key = $service->generateApiKey($project, ['name' => 'Production Key', 'rate_limit' => 1000]);
$valid = $service->validateApiKey('yg_abc123...');
$remaining = $service->getRemainingRequests($key);
```

### **OAuthService**
```php
$service = new OAuthService();
$app = $service->createApplication($project, [
    'name' => 'My OAuth App',
    'redirect_uris' => ['https://myapp.com/callback'],
    'scopes' => ['read', 'write']
]);
```

### **BillingService**
```php
$service = new BillingService();
$sub = $service->createSubscription($project, 'pro', ['type' => 'card']);
$invoice = $service->generateInvoice($project);
$service->handleWebhook($payload); // From YG Pay
```

### **PlayStoreService**
```php
$service = new PlayStoreService();
$app = $service->submitApp($project, [
    'package_name' => 'com.example.app',
    'app_name' => 'My App',
    'price' => 0
]);
$service->uploadBinary($app, $apkFile);
$service->submitForReview($app);
```

### **AiService**
```php
$service = new AiService();
$response = $service->makeRequest($project, [
    'model' => 'gpt-4',
    'messages' => [['role' => 'user', 'content' => 'Hello']],
]);
$stats = $service->getUsageStats($project, 'month');
```

### **WebhookService**
```php
$service = new WebhookService();
$endpoint = $service->createEndpoint($project, [
    'url' => 'https://myapp.com/webhook',
    'events' => ['payment.succeeded', 'subscription.updated']
]);
$result = $service->testDelivery($endpoint);
```

---

## ⚙️ Environment Variables

```env
# YG Pay
YG_PAY_URL=https://pay.ygxone.com
YG_PAY_API_KEY=your_key_here
YG_PAY_WEBHOOK_SECRET=your_secret_here

# YG AI
YG_AI_URL=https://ai.ygxone.com
YG_AI_API_KEY=your_ai_key

# YG Account SSO
YG_ACCOUNT_URL=https://account.ygxone.com
YG_ACCOUNT_CLIENT_ID=your_client_id
YG_ACCOUNT_CLIENT_SECRET=your_client_secret
YG_ACCOUNT_REDIRECT_URI=https://console.ygxone.com/auth/callback

# YG Drive
YG_DRIVE_URL=https://drive.ygxone.com
YG_DRIVE_API_KEY=your_drive_key
```

---

## 💳 Pricing Reference

### **Subscription Plans**
- Free: $0/month
- Basic: $9.99/month
- Pro: $29.99/month
- Enterprise: $99.99/month

### **Usage-Based**
- API Calls: $0.001 per call
- Storage: $0.10 per GB/month
- AI Tokens: Varies by model (see config/ai.php)

### **AI Model Pricing (per 1K tokens)**
| Model | Prompt | Completion |
|-------|--------|------------|
| GPT-4 | $0.03 | $0.06 |
| GPT-3.5 Turbo | $0.0015 | $0.002 |
| Claude 3 Opus | $0.015 | $0.075 |
| Gemini Pro | $0.0005 | $0.0015 |

---

## 🔐 Security Checklist

✅ API keys: 256-bit random generation  
✅ OAuth secrets: 40-character random strings  
✅ Webhook signatures: HMAC SHA256  
✅ Rate limiting: Redis-backed counters  
✅ IP restrictions: Configurable per key  
✅ Referrer validation: Domain matching  
✅ Token expiration: Automatic validation  

---

## 📊 Database Tables (10 Total)

1. `projects` - Main projects
2. `oauth_applications` - OAuth apps
3. `api_keys` - API authentication
4. `play_store_apps` - Mobile apps
5. `subscriptions` - Billing subscriptions
6. `billing_invoices` - Invoice history
7. `ai_usage_logs` - AI usage tracking
8. `webhook_endpoints` - Webhook config
9. `webhook_deliveries` - Delivery logs
10. `team_members` - Team collaboration

---

## 🚀 Quick Start Commands

```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Install Filament
php artisan filament:install --panels
php artisan make:filament-user

# Build assets
npm run build

# Start server
php artisan serve --host=0.0.0.0 --port=8000
```

---

## 🎯 Next Steps

1. ✅ ~~Core services~~ DONE
2. ⏳ Install Laravel & dependencies
3. ⏳ Configure `.env`
4. ⏳ Create Filament resources
5. ⏳ Build API controllers
6. ⏳ Add frontend UI
7. ⏳ Write tests
8. ⏳ Deploy to production

---

## 📞 Support

- **Docs:** See `README.md`, `QUICKSTART.md`
- **Implementation:** See `IMPLEMENTATION_COMPLETE.md`
- **TODO:** See `TODO.md` for full checklist

---

**Status:** ✅ Core Platform Complete  
**Version:** 1.0.0-alpha  
**Ready for:** UI Development & Integration
