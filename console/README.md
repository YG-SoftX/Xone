# 🚀 YG Console - Developer Platform

## Overview

**YG Console** is a comprehensive developer platform similar to Google Cloud Console, providing developers with tools to manage their applications, API keys, billing, and integrations across the entire YGXone ecosystem.

**URL:** `console.ygxone.com`  
**Technology:** Laravel 12 + Filament PHP + Alpine.js + Tailwind CSS

---

## 🎯 Platform Features

### **1. Project Management**
- Create and manage multiple projects
- Team collaboration with role-based access
- Project-level settings and configurations
- Resource usage tracking per project

### **2. API & Services**
- OAuth application management (client_id, client_secret)
- API key generation and rotation
- Rate limit configuration
- Usage statistics and analytics
- Service enablement/disablement

### **3. YG Play Store Integration**
- App submission and management
- Version control and release tracking
- In-app purchase configuration
- Analytics and crash reports
- User reviews management

### **4. Billing & Subscriptions**
- Multiple payment methods (Stripe, PayPal)
- Usage-based billing
- Invoice generation and history
- Budget alerts and notifications
- Subscription plan management

### **5. AI Services**
- Model selection and configuration
- Token usage tracking
- Cost optimization recommendations
- Prompt template library
- Fine-tuning job management

### **6. Webhooks & Events**
- Webhook endpoint registration
- Event subscription management
- Delivery logs and retry mechanisms
- Signature verification
- Real-time event monitoring

### **7. Analytics & Monitoring**
- Real-time dashboards
- API performance metrics
- Error rate monitoring
- Custom alert rules
- Export capabilities

### **8. Security & IAM**
- Team member management
- Role-based permissions
- Audit logging
- IP whitelisting
- Two-factor authentication

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────┐
│         YG CONSOLE PLATFORM             │
│       console.ygxone.com                │
├─────────────────────────────────────────┤
│                                         │
│  Frontend Layer                         │
│  ├─ Blade Templates                     │
│  ├─ Alpine.js (Interactivity)           │
│  ├─ Tailwind CSS (Styling)              │
│  └─ Chart.js (Visualizations)           │
│                                         │
│  Backend Layer                          │
│  ├─ Laravel 12 Framework                │
│  ├─ Filament Admin Panel                │
│  ├─ RESTful APIs                        │
│  └─ Queue Workers                       │
│                                         │
│  Service Layer                          │
│  ├─ ProjectService                      │
│  ├─ BillingService                      │
│  ├─ APIKeyService                       │
│  ├─ OAuthService                        │
│  ├─ WebhookService                      │
│  └─ AnalyticsService                    │
│                                         │
│  Integration Layer                      │
│  ├─ YG Account (SSO/Auth)               │
│  ├─ YG Pay (Payments)                   │
│  ├─ YG AI (AI Services)                 │
│  ├─ YG Play Store (Apps)                │
│  └─ External Providers (Stripe/PayPal)  │
│                                         │
│  Data Layer                             │
│  ├─ MySQL Database                      │
│  ├─ Redis Cache                         │
│  └─ File Storage                        │
│                                         │
└─────────────────────────────────────────┘
```

---

## 📊 Database Schema

### **Core Tables**

#### **projects**
```sql
id                  BIGINT UNSIGNED PRIMARY KEY
user_id             BIGINT UNSIGNED (FK -> users.id)
name                VARCHAR(255)
slug                VARCHAR(255) UNIQUE
description         TEXT NULLABLE
status              ENUM('active', 'suspended', 'archived')
settings            JSON NULLABLE
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

#### **oauth_applications**
```sql
id                  BIGINT UNSIGNED PRIMARY KEY
project_id          BIGINT UNSIGNED (FK -> projects.id)
name                VARCHAR(255)
client_id           VARCHAR(255) UNIQUE
client_secret       VARCHAR(255)
redirect_uris       JSON NULLABLE
scopes              JSON NULLABLE
is_confidential     BOOLEAN DEFAULT TRUE
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

#### **api_keys**
```sql
id                  BIGINT UNSIGNED PRIMARY KEY
project_id          BIGINT UNSIGNED (FK -> projects.id)
key                 VARCHAR(255) UNIQUE
name                VARCHAR(255)
restrictions        JSON NULLABLE
rate_limit          INTEGER DEFAULT 1000
last_used_at        TIMESTAMP NULLABLE
expires_at          TIMESTAMP NULLABLE
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

#### **play_store_apps**
```sql
id                  BIGINT UNSIGNED PRIMARY KEY
project_id          BIGINT UNSIGNED (FK -> projects.id)
package_name        VARCHAR(255) UNIQUE
app_name            VARCHAR(255)
version             VARCHAR(50)
status              ENUM('draft', 'review', 'published', 'rejected')
metadata            JSON NULLABLE
price               DECIMAL(10,2) DEFAULT 0
is_paid             BOOLEAN DEFAULT FALSE
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

#### **subscriptions**
```sql
id                  BIGINT UNSIGNED PRIMARY KEY
project_id          BIGINT UNSIGNED (FK -> projects.id)
plan_id             VARCHAR(255)
provider            VARCHAR(50)
provider_subscription_id VARCHAR(255)
status              ENUM('trialing', 'active', 'past_due', 'canceled')
current_period_start TIMESTAMP
current_period_end   TIMESTAMP
trial_ends_at       TIMESTAMP NULLABLE
canceled_at         TIMESTAMP NULLABLE
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

#### **billing_invoices**
```sql
id                  BIGINT UNSIGNED PRIMARY KEY
project_id          BIGINT UNSIGNED (FK -> projects.id)
invoice_number      VARCHAR(255) UNIQUE
amount              DECIMAL(10,2)
currency            VARCHAR(3) DEFAULT 'USD'
status              ENUM('pending', 'paid', 'failed', 'refunded')
due_date            TIMESTAMP
paid_at             TIMESTAMP NULLABLE
line_items          JSON
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

#### **ai_usage_logs**
```sql
id                  BIGINT UNSIGNED PRIMARY KEY
project_id          BIGINT UNSIGNED (FK -> projects.id)
model               VARCHAR(100)
prompt_tokens       INTEGER
completion_tokens   INTEGER
cost                DECIMAL(10,6)
metadata            JSON NULLABLE
created_at          TIMESTAMP
```

#### **webhook_endpoints**
```sql
id                  BIGINT UNSIGNED PRIMARY KEY
project_id          BIGINT UNSIGNED (FK -> projects.id)
url                 VARCHAR(2048)
events              JSON
secret              VARCHAR(255)
active              BOOLEAN DEFAULT TRUE
max_retries         INTEGER DEFAULT 3
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

#### **webhook_deliveries**
```sql
id                  BIGINT UNSIGNED PRIMARY KEY
webhook_endpoint_id BIGINT UNSIGNED (FK -> webhook_endpoints.id)
event_type          VARCHAR(100)
payload             JSON
status_code         INTEGER NULLABLE
response_body       TEXT NULLABLE
attempt             INTEGER DEFAULT 1
delivered_at        TIMESTAMP NULLABLE
created_at          TIMESTAMP
```

#### **team_members**
```sql
id                  BIGINT UNSIGNED PRIMARY KEY
project_id          BIGINT UNSIGNED (FK -> projects.id)
user_id             BIGINT UNSIGNED (FK -> users.id)
role                ENUM('owner', 'admin', 'developer', 'viewer')
created_at          TIMESTAMP
updated_at          TIMESTAMP

UNIQUE INDEX (project_id, user_id)
```

---

## 🔧 Installation

### **Prerequisites**
- PHP 8.2+
- Composer
- MySQL 8.0+ or MariaDB 10.5+
- Node.js 18+ & NPM
- Redis (optional but recommended)

### **Setup Steps**

```bash
# 1. Navigate to project
cd yg-console

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install && npm run build

# 4. Setup environment
cp .env.example .env
php artisan key:generate

# 5. Configure database in .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=yg_console
# DB_USERNAME=root
# DB_PASSWORD=

# 6. Run migrations
php artisan migrate

# 7. Install Filament
php artisan filament:install --panels

# 8. Create admin user
php artisan make:filament-user
# Email: admin@ygxone.com
# Password: (set secure password)

# 9. Seed initial data (optional)
php artisan db:seed

# 10. Start development server
php artisan serve --host=0.0.0.0 --port=8000
```

---

## 🚀 Deployment

### **Nginx Configuration**

```nginx
server {
    listen 80;
    server_name console.ygxone.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name console.ygxone.com;

    ssl_certificate /etc/letsencrypt/live/console.ygxone.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/console.ygxone.com/privkey.pem;

    root /var/www/yg-console/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### **SSL Certificate**

```bash
sudo certbot --nginx -d console.ygxone.com
```

### **Cron Jobs**

```bash
# Laravel Scheduler
* * * * * cd /var/www/yg-console && php artisan schedule:run >> /dev/null 2>&1

# Queue Worker (if using queues)
* * * * * cd /var/www/yg-console && php artisan queue:work --sleep=3 --tries=3 --timeout=90 >> /dev/null 2>&1
```

---

## 📚 API Documentation

### **Authentication**

All API requests require authentication via API key:

```http
GET /api/v1/projects
Authorization: Bearer yg_abc123...
```

### **Endpoints**

#### **Projects**
- `GET /api/v1/projects` - List all projects
- `POST /api/v1/projects` - Create new project
- `GET /api/v1/projects/{id}` - Get project details
- `PUT /api/v1/projects/{id}` - Update project
- `DELETE /api/v1/projects/{id}` - Delete project

#### **API Keys**
- `GET /api/v1/api-keys` - List API keys
- `POST /api/v1/api-keys` - Generate new API key
- `DELETE /api/v1/api-keys/{id}` - Revoke API key

#### **OAuth Applications**
- `GET /api/v1/oauth-apps` - List OAuth apps
- `POST /api/v1/oauth-apps` - Create OAuth app
- `PUT /api/v1/oauth-apps/{id}` - Update OAuth app
- `DELETE /api/v1/oauth-apps/{id}` - Delete OAuth app

#### **Billing**
- `GET /api/v1/invoices` - List invoices
- `POST /api/v1/subscriptions` - Create subscription
- `GET /api/v1/usage` - Get usage statistics

---

## 🔐 Security

### **Best Practices**

1. **API Key Security**
   - Keys are hashed before storage
   - Use environment variables for sensitive data
   - Rotate keys regularly
   - Set expiration dates

2. **OAuth Security**
   - Implement PKCE flow
   - Validate redirect URIs
   - Use state parameter for CSRF protection
   - Short-lived access tokens

3. **Rate Limiting**
   - Per-key rate limits
   - Burst protection
   - Gradual backoff on violations

4. **Webhook Security**
   - HMAC signature verification
   - HTTPS-only endpoints
   - Retry with exponential backoff
   - Idempotency keys

---

## 📈 Monitoring

### **Metrics to Track**

- API request volume
- Error rates by endpoint
- Average response time
- Active projects count
- Revenue metrics
- Webhook delivery success rate

### **Alerts**

- High error rate (>5%)
- Slow response times (>1s)
- Unusual traffic spikes
- Payment failures
- Webhook delivery failures

---

## 🎯 Development Roadmap

### **Phase 1: Core Platform (Week 1-2)**
- [ ] Project management CRUD
- [ ] API key system
- [ ] OAuth application management
- [ ] Basic dashboard

### **Phase 2: Billing Integration (Week 3-4)**
- [ ] Stripe integration
- [ ] PayPal integration
- [ ] Invoice generation
- [ ] Usage tracking

### **Phase 3: Advanced Features (Week 5-6)**
- [ ] Play Store app management
- [ ] AI usage tracking
- [ ] Webhook system
- [ ] Team collaboration

### **Phase 4: Polish & Launch (Week 7-8)**
- [ ] Analytics dashboards
- [ ] Mobile responsiveness
- [ ] Performance optimization
- [ ] Security audit
- [ ] Production deployment

---

## 📞 Support

- **Documentation:** https://developer.ygxone.com/docs/console
- **API Reference:** https://developer.ygxone.com/api-reference
- **Community Forum:** https://community.ygxone.com
- **Email Support:** dev-support@ygxone.com
- **Status Page:** https://status.ygxone.com

---

**Version:** 1.0.0  
**Last Updated:** 2026-05-07  
**Status:** 🟡 In Development
