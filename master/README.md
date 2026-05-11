# YG Master — Super Admin Control Panel for YGXone SaaS Ecosystem

## 🎯 Overview

**YG Master** is the centralized super admin control panel that manages, monitors, and controls the entire YGXone SaaS ecosystem. It provides real-time visibility into all services, centralized management capabilities, and automated deployment workflows.

---

## 🏗️ Architecture

### Position in Ecosystem

```
┌─────────────────────────────────────────────────────────┐
│              YG Master (Super Admin)                     │
│         master.ygxone.com / Filament Admin               │
├─────────────────────────────────────────────────────────┤
│  ┌──────────┐ ┌────────┐ ┌──────┐ ┌──────┐ ┌────────┐ │
│  │ YG       │ │ YG     │ │ YG   │ │ YG   │ │ YG     │ │
│  │ Account  │ │ Mail   │ │Drive │ │ Chat │ │ Pay    │ │
│  └──────────┘ └────────┘ └──────┘ └──────┘ └────────┘ │
│  ┌──────────┐ ┌────────┐ ┌──────┐ ┌──────┐ ┌────────┐ │
│  │ YG Meet  │ │ YG     │ │ YG   │ │ YG   │ │ YG     │ │
│  │          │ │ Notes  │ │DocX  │ │Xcel  │ │Calendar│ │
│  └──────────┘ └────────┘ └──────┘ └──────┘ └────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Technology Stack

- **Framework**: Laravel 12 + Filament PHP (Admin Panel)
- **Frontend**: Livewire (Real-time updates)
- **Database**: MySQL (Centralized monitoring data)
- **Queue**: Redis (Background job processing)
- **Monitoring**: Custom health check endpoints + HTTP polling

---

## 📊 Dashboard Features

### 1. **Ecosystem-Wide Statistics**

Real-time metrics across all services:

- **Total Users**: Aggregate user count from YG Account
- **Active Users**: Currently active users (last 30 days)
- **Monthly Revenue**: Subscription revenue this month
- **Active Subscriptions**: Number of paid subscriptions
- **Service Status**: Active/Inactive service count
- **Critical Alerts**: System alerts requiring attention

### 2. **Service Health Monitor**

Automated health checking for all 13+ services:

```php
// Health Check Endpoint Pattern
GET https://{service}.ygxone.com/api/health

Response:
{
  "status": "healthy",
  "version": "2.1.0",
  "uptime": "15 days",
  "database": "connected",
  "cache": "operational",
  "queue": "running"
}
```

**Health Status Indicators:**
- 🟢 **Healthy**: Service responding normally
- 🟡 **Degraded**: Service operational but with issues
- 🔴 **Unhealthy**: Service down or critical errors
- ⚫ **Unreachable**: Cannot connect to service

### 3. **Service Management Actions**

For each service, admins can:

#### Deployment & Updates
- **Update**: Pull latest code, run migrations, rebuild caches
- **Rollback**: Revert to previous stable version
- **Git Pull**: Fetch latest changes without full update
- **Migrate**: Run pending database migrations
- **Rebuild Caches**: Clear and rebuild all caches

#### Maintenance
- **Maintenance Mode**: Toggle service on/off for maintenance
- **Backup**: Create database and file backups
- **Restart Queue**: Restart background job workers
- **Clear Cache**: Flush all cached data

#### Monitoring
- **View Logs**: Access service-specific log files
- **Edit .env**: Modify environment variables
- **Health Check**: Manual health verification

### 4. **Bulk Operations**

- **Update All Services**: Sequentially update all services in dependency order
- **Health Check All**: Verify all services simultaneously
- **Emergency Mode**: Enable maintenance mode on ALL services immediately

---

## 🔧 Configuration

### Environment Variables

```env
# Ecosystem Root Directory
ECOSYSTEM_ROOT=/home/cpaneluser

# Service URLs (for health checks)
YG_ACCOUNT_URL=https://account.ygxone.com
YG_MAIL_URL=https://mail.ygxone.com
YG_DRIVE_URL=https://drive.ygxone.com
YG_CHAT_URL=https://chat.ygxone.com
YG_NOTES_URL=https://notes.ygxone.com
YG_MEET_URL=https://meet.ygxone.com
YG_PAY_URL=https://pay.ygxone.com
YG_CALENDAR_URL=https://calendar.ygxone.com
YG_CONTACTS_URL=https://contacts.ygxone.com
YG_DOCX_URL=https://docx.ygxone.com
YG_XCEL_URL=https://xcel.ygxone.com
YG_DEVELOPER_URL=https://developer.ygxone.com

# Master Control Panel
APP_URL=https://master.ygxone.com
APP_ENV=production
APP_DEBUG=false

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yg_master
DB_USERNAME=root
DB_PASSWORD=secret

# Redis (Queue)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# Mail (Alerts)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=alerts@ygxone.com
MAIL_PASSWORD=app-password
```

### Service Registry

Located in `config/ecosystem.php`:

```php
'apps' => [
    'yg-account' => [
        'name'   => 'YG Account',
        'path'   => '/home/cpaneluser/yg-account',
        'type'   => 'laravel',
        'url'    => 'https://account.ygxone.com',
        'health' => '/api/health',
        'icon'   => '🔐',
        'order'  => 2,
    ],
    // ... other services
]
```

---

## 🛡️ Security

### Access Control

- **Filament Authentication**: Built-in admin authentication
- **Role-Based Access**: Super Admin vs. Support roles
- **IP Whitelisting**: Restrict access to trusted IPs
- **Two-Factor Authentication**: Required for all admin accounts

### Command Safety

- **Allowlist Only**: Only pre-approved artisan commands can execute
- **No Shell Injection**: All inputs escaped with `escapeshellarg()`
- **Audit Logging**: Every command execution logged
- **Confirmation Dialogs**: Destructive actions require confirmation

### Allowed Artisan Commands

```php
const ALLOWED_ARTISAN_COMMANDS = [
    'migrate', 'migrate:fresh', 'migrate:rollback', 'migrate:status',
    'down', 'up',
    'optimize', 'optimize:clear',
    'config:cache', 'config:clear',
    'route:cache', 'route:clear',
    'view:cache', 'view:clear',
    'event:cache', 'event:clear',
    'queue:restart',
    'storage:link',
    'schedule:run',
];
```

---

## 📁 Project Structure

```
yg-master/
├── app/
│   ├── Filament/
│   │   ├── Pages/
│   │   │   ├── EcosystemDashboard.php      ← Main dashboard
│   │   │   ├── BackupManager.php           ← Backup management
│   │   │   ├── EnvEditor.php               ← Environment editor
│   │   │   ├── LogViewer.php               ← Log viewer
│   │   │   └── MigrationManager.php        ← Migration manager
│   │   ├── Resources/                      ← CRUD resources
│   │   │   ├── AppModules/                 ← Module management
│   │   │   ├── AuditLogs/                  ← Audit trail
│   │   │   ├── Developers/                 ← Developer accounts
│   │   │   ├── InfrastructureNodes/        ← Server nodes
│   │   │   ├── MobileDevices/              ← Device tracking
│   │   │   ├── PaymentProfiles/            ← Payment configs
│   │   │   ├── Subscriptions/              ← Subscription mgmt
│   │   │   ├── Tenants/                    ← Tenant management
│   │   │   ├── ThirdPartyApps/             ← OAuth apps
│   │   │   └── Users/                      ← User management
│   │   └── Widgets/                        ← Dashboard widgets
│   ├── Models/                             ← Database models
│   ├── Services/
│   │   ├── AppRegistryService.php          ← Service registry
│   │   ├── AppStatusService.php            ← Health checks
│   │   ├── EcosystemCommandService.php     ← Command execution
│   │   └── EnvEditorService.php            ← .env management
│   └── Providers/                          ← Service providers
├── config/
│   ├── ecosystem.php                       ← Service registry
│   └── filament.php                        ← Admin panel config
├── database/
│   └── migrations/                         ← Database schema
├── resources/
│   └── views/
│       └── filament/
│           └── pages/
│               └── ecosystem-dashboard.blade.php
├── routes/
│   └── web.php                             ← Routes
└── scripts/
    ├── update.sh                           ← Update script
    ├── rollback.sh                         ← Rollback script
    └── backup.sh                           ← Backup script
```

---

## 🚀 Deployment

### Prerequisites

1. **Server Requirements**:
   - PHP 8.2+
   - Composer
   - MySQL 8.0+
   - Redis
   - Git
   - SSH access

2. **Permissions**:
   ```bash
   chmod -R 755 yg-master/
   chmod -R 775 yg-master/storage/
   chmod -R 775 yg-master/bootstrap/cache/
   ```

### Installation

```bash
# Clone repository
git clone https://github.com/ygxone/yg-master.git
cd yg-master

# Install dependencies
composer install --optimize-autoloader --no-dev

# Setup environment
cp .env.example .env
nano .env  # Configure settings

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Create admin user
php artisan make:filament-user

# Setup queues
php artisan queue:work --daemon

# Optimize
php artisan optimize
```

### Cron Jobs

```cron
# Run scheduler every minute
* * * * * cd /home/cpaneluser/yg-master && php artisan schedule:run >> /dev/null 2>&1

# Health checks every 5 minutes
*/5 * * * * curl -s https://master.ygxone.com/api/health-check > /dev/null

# Daily backup at 2 AM
0 2 * * * bash /home/cpaneluser/yg-master/scripts/backup.sh
```

---

## 🔄 Update Workflow

### Automated Update Process

When clicking "Update" on a service:

1. **Enable Maintenance Mode**: Prevents user access during update
2. **Git Pull**: Fetches latest code from repository
3. **Composer Install**: Updates PHP dependencies
4. **Database Migrations**: Applies schema changes
5. **Cache Rebuild**: Clears and rebuilds all caches
6. **Disable Maintenance Mode**: Restores service availability
7. **Health Check**: Verifies service is operational

### Dependency Order

Services are updated in this order to prevent breaking dependencies:

1. YG Account (Authentication provider)
2. YG DB (Database service)
3. YG Mail, Drive, Chat, etc. (Dependent services)
4. YG Developer (API documentation)

---

## 📈 Monitoring & Alerts

### Health Check Endpoints

Each service must implement:

```php
// routes/api.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'version' => config('app.version'),
        'timestamp' => now()->toIso8601String(),
        'checks' => [
            'database' => DB::connection()->getPdo() ? 'ok' : 'fail',
            'cache' => Cache::get('health_check') === false ? 'ok' : 'fail',
            'queue' => Queue::size() < 1000 ? 'ok' : 'warn',
        ]
    ]);
});
```

### Alert Types

- **Critical**: Service down, data loss risk
- **Warning**: Degraded performance, high resource usage
- **Info**: Routine maintenance, updates available

### Notification Channels

- Email alerts to admin team
- Slack/Discord webhook notifications
- SMS for critical alerts (Twilio integration)

---

## 🎯 Best Practices

### For Administrators

1. **Regular Backups**: Schedule daily backups before updates
2. **Test Updates**: Test on staging environment first
3. **Monitor Logs**: Check logs after each update
4. **Rollback Plan**: Always have a rollback strategy
5. **Communication**: Notify users before major updates

### For Developers

1. **Health Endpoints**: Implement `/api/health` in all services
2. **Version Tracking**: Use semantic versioning
3. **Migration Safety**: Write reversible migrations
4. **Error Handling**: Log errors properly for monitoring
5. **Documentation**: Update API docs after changes

---

## 🆘 Troubleshooting

### Common Issues

**1. Service Shows "Unreachable"**
```bash
# Check if service is running
ps aux | grep php

# Check firewall rules
sudo ufw status

# Test connectivity
curl -I https://service.ygxone.com/api/health
```

**2. Update Fails**
```bash
# Check logs
tail -f storage/logs/laravel.log

# Manual update via SSH
cd /home/cpaneluser/yg-service
bash scripts/update.sh

# Rollback if needed
bash scripts/rollback.sh
```

**3. High Memory Usage**
```bash
# Restart PHP-FPM
sudo systemctl restart php-fpm

# Clear caches
php artisan optimize:clear

# Check queue workers
php artisan queue:restart
```

---

## 📞 Support

- **Documentation**: https://docs.ygxone.com/master
- **Internal Wiki**: [Link to internal docs]
- **Slack Channel**: #yg-master-support
- **Emergency Contact**: ops@ygxone.com

---

*Last Updated: April 27, 2026*  
*Version: 2.0.0*
