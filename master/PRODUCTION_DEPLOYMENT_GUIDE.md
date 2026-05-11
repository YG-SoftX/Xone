# YG Master - Production Deployment Guide

## 🚀 Complete Setup Instructions

### Prerequisites
- PHP 8.2+ installed
- MySQL/MariaDB running
- Redis installed and running
- Composer installed
- Node.js & NPM (for asset compilation)

---

## Step 1: Environment Configuration

Edit `.env` file with your production settings:

```env
APP_NAME="YGXONE Master Architect"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://master.ygxone.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ygmarket_account
DB_USERNAME=ygmarket_account
DB_PASSWORD='Ygaccount@2.0##2026'

# Queue Configuration (Redis recommended for production)
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Service API Keys (Get from each service's admin panel)
YG_ACCOUNT_API_KEY=your_account_api_key_here
YG_MAIL_API_KEY=your_mail_api_key_here
YG_DRIVE_API_KEY=your_drive_api_key_here
YG_DOCX_API_KEY=your_docx_api_key_here
YG_CHAT_API_KEY=your_chat_api_key_here
YG_PAY_API_KEY=your_pay_api_key_here
YG_MEET_API_KEY=your_meet_api_key_here
YG_NOTES_API_KEY=your_notes_api_key_here
YG_XCEL_API_KEY=your_xcel_api_key_here
YG_CALENDAR_API_KEY=your_calendar_api_key_here
YG_CONTACTS_API_KEY=your_contacts_api_key_here
YG_AI_API_KEY=your_ai_api_key_here
YG_MASTER_API_KEY=your_master_api_key_here

# Cloud Storage for Backups (Optional)
AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=yg-master-backups
```

---

## Step 2: Database Migration & Seeding

```bash
cd /path/to/yg-master

# Run migrations and seeders
php artisan migrate:fresh --seed

# Verify seeding
php artisan tinker
>>> App\Models\AppModule::count()  # Should return 13
>>> App\Models\User::where('role', 'super_admin')->count()  # Should return 1
```

**Default Admin Credentials:**
- Email: `admin@ygxone.com`
- Password: `YgMaster@2026!Secure` (change immediately!)

---

## Step 3: Start Queue Workers (Horizontal Scaling)

### Option A: Single Queue Worker (Development)

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

### Option B: Multiple Queue Workers (Production)

Start separate workers for different job types:

```bash
# Terminal 1: Health Check Workers (high priority, fast execution)
php artisan queue:work redis --queue=health-checks --sleep=1 --tries=2 --timeout=30 --max-jobs=1000

# Terminal 2: Deployment Workers (medium priority)
php artisan queue:work redis --queue=deployments --sleep=3 --tries=1 --timeout=300 --max-jobs=100

# Terminal 3: Backup Workers (low priority, long-running)
php artisan queue:work redis --queue=backups --sleep=5 --tries=1 --timeout=600 --max-jobs=50

# Terminal 4: General Workers (default queue)
php artisan queue:work redis --queue=default --sleep=3 --tries=3 --timeout=90 --max-jobs=1000
```

### Option C: Supervisor Configuration (Recommended for Production)

Create `/etc/supervisor/conf.d/yg-master.conf`:

```ini
[program:yg-master-health-checks]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/yg-master/artisan queue:work redis --queue=health-checks --sleep=1 --tries=2 --timeout=30 --max-jobs=1000
autostart=true
autorestart=true
stopwaitsecs=3600
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/yg-master/storage/logs/health-checks.log

[program:yg-master-deployments]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/yg-master/artisan queue:work redis --queue=deployments --sleep=3 --tries=1 --timeout=300 --max-jobs=100
autostart=true
autorestart=true
stopwaitsecs=3600
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/yg-master/storage/logs/deployments.log

[program:yg-master-backups]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/yg-master/artisan queue:work redis --queue=backups --sleep=5 --tries=1 --timeout=600 --max-jobs=50
autostart=true
autorestart=true
stopwaitsecs=3600
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/yg-master/storage/logs/backups.log

[program:yg-master-default]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/yg-master/artisan queue:work redis --queue=default --sleep=3 --tries=3 --timeout=90 --max-jobs=1000
autostart=true
autorestart=true
stopwaitsecs=3600
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/yg-master/storage/logs/default.log
```

Then run:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

---

## Step 4: Configure Cron Jobs

```bash
crontab -e
```

Add:
```bash
# Laravel Scheduler (runs every minute)
* * * * * cd /path/to/yg-master && php artisan schedule:run >> /dev/null 2>&1

# Queue Worker Monitor (every 5 minutes)
*/5 * * * * /usr/bin/php /path/to/yg-master/artisan queue:monitor >> /path/to/yg-master/storage/logs/queue-monitor.log 2>&1
```

---

## Step 5: Clear & Optimize Caches

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## Step 6: Access Admin Panel

**URL**: `https://master.ygxone.com/admin`  
**Email**: `admin@ygxone.com`  
**Password**: `YgMaster@2026!Secure`

⚠️ **Change password immediately after first login!**

---

## 🔧 Multi-Region Deployment Strategy

### Architecture Overview

```
Region 1 (US-East)          Region 2 (EU-West)
┌─────────────────┐        ┌─────────────────┐
│  Load Balancer  │        │  Load Balancer  │
└────────┬────────┘        └────────┬────────┘
         │                          │
    ┌────┴────┐               ┌────┴────┐
    │ App     │               │ App     │
    │ Servers │               │ Servers │
    └────┬────┘               └────┬────┘
         │                          │
    ┌────┴────┐               ┌────┴────┐
    │ Primary │◄──Replication─►│ Replica │
    │   DB    │                │   DB    │
    └─────────┘                └─────────┘
```

### Implementation Steps

#### 1. Database Replication

Set up MySQL master-slave replication between regions.

**Primary Region (US-East) - my.cnf:**
```ini
[mysqld]
server-id=1
log-bin=mysql-bin
binlog-format=ROW
```

**Secondary Region (EU-West) - my.cnf:**
```ini
[mysqld]
server-id=2
relay-log=mysql-relay-bin
read-only=1
```

#### 2. Update .env for Each Region

**US-East (.env):**
```env
DB_HOST=us-east-db.ygxone.com
REDIS_HOST=us-east-redis.ygxone.com
REGION=us-east-1
```

**EU-West (.env):**
```env
DB_HOST=eu-west-db.ygxone.com
REDIS_HOST=eu-west-redis.ygxone.com
REGION=eu-west-1
```

#### 3. Deploy Code to Both Regions

```bash
# US-East
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache

# EU-West (same commands)
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
```

#### 4. Configure Global Load Balancer

Use AWS Route 53 or CloudFlare for geo-based routing:
- US users → us-east-1
- EU users → eu-west-1
- Asia users → ap-southeast-1 (if available)

---

## 🚨 Advanced Alerting Rules

### 1. Service Health Alerts

Create `app/Notifications/ServiceHealthAlert.php`:

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ServiceHealthAlert extends Notification
{
    use Queueable;

    protected $service;
    protected $status;
    protected $error;

    public function __construct($service, $status, $error = null)
    {
        $this->service = $service;
        $this->status = $status;
        $this->error = $error;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("🚨 Service Alert: {$this->service->name}")
            ->line("Service '{$this->service->name}' is now: {$this->status}")
            ->line($this->error ? "Error: {$this->error}" : '')
            ->action('View Dashboard', url('/admin'))
            ->line('Please investigate immediately.');
    }

    public function toArray($notifiable): array
    {
        return [
            'service_id' => $this->service->id,
            'service_name' => $this->service->name,
            'status' => $this->status,
            'error' => $this->error,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
```

### 2. Update HealthCheckJob to Send Alerts

Modify `app/Jobs/HealthCheckJob.php`:

```php
protected function triggerAlert(AppModule $service, string $error): void
{
    // Create alert record
    \App\Models\AuditLog::create([
        'type' => 'service_alert',
        'severity' => 'critical',
        'message' => "Service {$service->name} has failed {$service->consecutive_failures} times",
        'metadata' => json_encode([
            'service_id' => $service->id,
            'error' => $error,
        ]),
    ]);

    // Send notification to admins
    $admins = \App\Models\User::where('role', 'super_admin')->get();
    
    foreach ($admins as $admin) {
        $admin->notify(new \App\Notifications\ServiceHealthAlert($service, 'unhealthy', $error));
    }

    // Optional: Send to Slack/Teams webhook
    if (config('services.slack.webhook_url')) {
        Http::post(config('services.slack.webhook_url'), [
            'text' => "🚨 CRITICAL: {$service->name} is unhealthy\nError: {$error}",
        ]);
    }
}
```

### 3. Configure Notification Channels

Add to `.env`:
```env
# Slack Webhook for Alerts
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL

# Microsoft Teams Webhook
TEAMS_WEBHOOK_URL=https://outlook.office.com/webhook/YOUR/WEBHOOK/URL

# SMS Alerts (Twilio)
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_PHONE=+1234567890
ADMIN_PHONE=+0987654321
```

---

## 📊 Monitoring & Observability

### 1. Log Aggregation

Configure centralized logging with ELK Stack or Papertrail:

```env
LOG_CHANNEL=papertrail
PAPERTRAIL_URL=tcp://logs.papertrailapp.com:12345
```

### 2. Performance Monitoring

Install Laravel Telescope for development:
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### 3. Uptime Monitoring

Use external services like:
- **UptimeRobot**: Free monitoring every 5 minutes
- **Pingdom**: Professional uptime monitoring
- **Datadog**: Full-stack observability

---

## ✅ Pre-Launch Checklist

- [ ] Database migrated and seeded
- [ ] All 13 services registered
- [ ] Queue workers running (supervisor)
- [ ] Cron jobs configured
- [ ] SSL certificate installed
- [ ] Firewall rules configured
- [ ] Backup system tested
- [ ] Health checks passing
- [ ] Alert notifications working
- [ ] Admin password changed
- [ ] API keys configured
- [ ] Cache optimized
- [ ] Error tracking setup (Sentry/Bugsnag)
- [ ] Performance testing completed

---

## 🆘 Troubleshooting

### Queue Workers Not Running
```bash
# Check worker status
php artisan queue:monitor

# Restart workers
sudo supervisorctl restart all

# Check logs
tail -f storage/logs/laravel.log
```

### Database Connection Issues
```bash
# Test connection
php artisan tinker
>>> DB::connection()->getPdo()

# Check credentials
cat .env | grep DB_
```

### Health Checks Failing
```bash
# Manual health check
curl https://account.ygxone.com/api/health

# Check service logs
ssh account-server
tail -f /var/log/nginx/error.log
```

---

**Status**: ✅ **READY FOR PRODUCTION DEPLOYMENT**  
**Last Updated**: May 2, 2026  
**Version**: 1.0.0
