# 🚀 Advanced Monitoring & Alerting Setup Guide

## 📋 Overview

This guide covers the setup and configuration of four advanced monitoring features for your YG Account cron job management system:

1. ✅ **SMS Alerts via Twilio** - Critical alerts to mobile phones
2. ✅ **Mobile Push Notifications via Firebase** - Real-time push to mobile devices
3. ✅ **Advanced Analytics with Grafana** - Professional dashboards and visualization
4. ✅ **Automated Incident Response** - Intelligent auto-recovery and escalation workflows

---

## 1️⃣ **SMS Alerts via Twilio**

### **What It Does**

Sends SMS text messages to administrators when critical cron jobs fail, ensuring immediate awareness even when away from computers.

### **Setup Steps**

#### **Step 1: Create Twilio Account**

1. Go to [https://www.twilio.com/](https://www.twilio.com/)
2. Sign up for a free account
3. Verify your phone number
4. Navigate to **Console Dashboard**

#### **Step 2: Get Credentials**

From Twilio Console:
- **Account SID**: Found on dashboard (starts with `AC...`)
- **Auth Token**: Click "Show" next to Auth Token
- **Phone Number**: Buy a phone number ($1/month) or use trial number

#### **Step 3: Configure .env**

```env
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_FROM_NUMBER=+1234567890  # Your Twilio number in E.164 format

# Admin phone numbers to receive alerts
TWILIO_ADMIN_PHONES=+1234567890,+0987654321

# On-call engineer for escalations
TWILIO_ONCALL_PHONE=+1234567890
```

**E.164 Format Examples:**
- US: `+1234567890`
- UK: `+447911123456`
- India: `+919876543210`

#### **Step 4: Test SMS**

```bash
php artisan tinker
>>> $twilio = app(\App\Services\TwilioNotifier::class);
>>> $twilio->testSms('+1234567890');
```

You should receive a test SMS!

### **Pricing**

- **Free Trial**: $15 credit (enough for ~100 SMS)
- **Pay-as-you-go**: ~$0.0079 per SMS (US)
- **Monthly**: Varies by volume

### **When SMS is Sent**

- **Critical severity**: System job failures, backup failures
- **High severity**: 3+ consecutive failures
- **Escalation**: On-call engineer notified for unresolved critical incidents

---

## 2️⃣ **Mobile Push Notifications via Firebase**

### **What It Does**

Sends push notifications directly to mobile devices (iOS/Android) for instant alerts, even when the app is closed.

### **Setup Steps**

#### **Step 1: Create Firebase Project**

1. Go to [https://console.firebase.google.com/](https://console.firebase.google.com/)
2. Click **"Add project"**
3. Enter project name: `YG Account Alerts`
4. Enable Google Analytics (optional)
5. Click **"Create project"**

#### **Step 2: Get Server Key**

1. In Firebase Console, click gear icon → **Project settings**
2. Go to **Cloud Messaging** tab
3. Under **Project credentials**, copy:
   - **Server key** (long string starting with `AAAA...`)
   - **Sender ID** (numeric)

#### **Step 3: Configure .env**

```env
FCM_SERVER_KEY=AAAAxxxxxxxxx:APA91bxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
FIREBASE_PROJECT_ID=yg-account-alerts

# Device tokens for admin mobile devices
FIREBASE_ADMIN_DEVICES=token1,token2,token3
```

#### **Step 4: Mobile App Integration**

Your mobile app must integrate Firebase Cloud Messaging (FCM):

**For React Native:**
```javascript
import messaging from '@react-native-firebase/messaging';

// Request permission
await messaging().requestPermission();

// Get device token
const token = await messaging().getToken();
console.log('Device token:', token);

// Send this token to your backend to store in FIREBASE_ADMIN_DEVICES
```

**For Flutter:**
```dart
import 'package:firebase_messaging/firebase_messaging.dart';

FirebaseMessaging messaging = FirebaseMessaging.instance;

// Get token
String? token = await messaging.getToken();
print('Device token: $token');
```

**For iOS (Swift):**
```swift
import FirebaseMessaging

Messaging.messaging().delegate = self
Messaging.messaging().token { token, error in
    if let token = token {
        print("Device token: \(token)")
    }
}
```

#### **Step 5: Test Push Notification**

```bash
php artisan tinker
>>> $push = app(\App\Services\FirebasePushNotifier::class);
>>> $push->testPush('YOUR_DEVICE_TOKEN');
```

You should receive a push notification on your mobile device!

### **Features**

- ✅ Works when app is closed (background notifications)
- ✅ Custom sounds and badges
- ✅ Deep linking to specific admin pages
- ✅ Batch sending to multiple devices
- ✅ Topic subscriptions for group alerts

### **Pricing**

- **FREE**: Firebase Cloud Messaging is completely free
- No limits on message volume
- No monthly fees

---

## 3️⃣ **Advanced Analytics with Grafana**

### **What It Does**

Exports cron job metrics to InfluxDB time-series database, enabling professional-grade dashboards in Grafana with real-time visualizations, trends, and alerts.

### **Architecture**

```
Cron Jobs → Laravel → InfluxDB → Grafana Dashboards
                ↓
         Export Metrics Every 5 Minutes
```

### **Setup Steps**

#### **Option A: Self-Hosted (Recommended for Production)**

##### **Step 1: Install InfluxDB**

**Using Docker:**
```bash
docker run -d \
  --name influxdb \
  -p 8086:8086 \
  -v influxdb-data:/var/lib/influxdb2 \
  -e DOCKER_INFLUXDB_INIT_MODE=setup \
  -e DOCKER_INFLUXDB_INIT_USERNAME=admin \
  -e DOCKER_INFLUXDB_INIT_PASSWORD=secure_password \
  -e DOCKER_INFLUXDB_INIT_ORG=yg_account \
  -e DOCKER_INFLUXDB_INIT_BUCKET=cron_metrics \
  influxdb:2.7
```

**Or download from:** [https://portal.influxdata.com/downloads/](https://portal.influxdata.com/downloads/)

##### **Step 2: Install Grafana**

**Using Docker:**
```bash
docker run -d \
  --name grafana \
  -p 3000:3000 \
  -v grafana-data:/var/lib/grafana \
  grafana/grafana:latest
```

**Or download from:** [https://grafana.com/get](https://grafana.com/get)

##### **Step 3: Create InfluxDB API Token**

1. Open InfluxDB UI: `http://localhost:8086`
2. Login with admin credentials
3. Go to **Load Data** → **API Tokens**
4. Click **"Generate API Token"** → **Read/Write Token**
5. Copy the token (starts with `...`)

##### **Step 4: Configure .env**

```env
INFLUXDB_URL=http://localhost:8086
INFLUXDB_TOKEN=your_api_token_here
INFLUXDB_BUCKET=cron_metrics
INFLUXDB_ORG=yg_account
GRAFANA_DASHBOARD_URL=http://localhost:3000
```

##### **Step 5: Add InfluxDB Data Source to Grafana**

1. Open Grafana: `http://localhost:3000` (admin/admin)
2. Go to **Connections** → **Data sources**
3. Click **"Add data source"**
4. Search for **"InfluxDB"**
5. Configure:
   - **URL**: `http://influxdb:8086` (or your server IP)
   - **Organization**: `yg_account`
   - **Token**: Your API token
   - **Default bucket**: `cron_metrics`
6. Click **"Save & test"**

##### **Step 6: Import Dashboard**

1. Download our pre-built dashboard JSON (see below)
2. In Grafana: **Dashboards** → **Import**
3. Upload JSON file
4. Select InfluxDB data source
5. Click **Import**

##### **Step 7: Schedule Metric Exports**

Add to crontab (via cPanel or admin panel):
```bash
*/5 * * * * /usr/bin/php /path/to/artisan grafana:export-metrics >> /dev/null 2>&1
```

Or create a cron job in Filament admin:
```
Name: grafana-metric-export
Command: /usr/bin/php /home/USERNAME/yg-account-core/artisan grafana:export-metrics >> /dev/null 2>&1
Schedule: */5 * * * * (Every 5 minutes)
Description: Exports cron job metrics to Grafana for analytics
Enabled: ✓
System Job: ✓
```

#### **Option B: Cloud Services (Easier Setup)**

**InfluxDB Cloud:**
1. Sign up at [https://cloud2.influxdata.com/](https://cloud2.influxdata.com/)
2. Free tier: 10K writes/day, 30-day retention
3. Get credentials from dashboard
4. Use cloud URL in `.env`

**Grafana Cloud:**
1. Sign up at [https://grafana.com/products/cloud/](https://grafana.com/products/cloud/)
2. Free tier available
3. Connect to InfluxDB Cloud

### **Pre-Built Dashboard JSON**

Create file `grafana-cron-dashboard.json`:

```json
{
  "dashboard": {
    "title": "Cron Job Monitoring",
    "timezone": "browser",
    "panels": [
      {
        "title": "Total Cron Jobs",
        "type": "stat",
        "targets": [
          {
            "query": "from(bucket: \"cron_metrics\")\n  |> range(start: -1h)\n  |> last()\n  |> count()",
            "datasource": "InfluxDB"
          }
        ]
      },
      {
        "title": "Success Rate Over Time",
        "type": "graph",
        "targets": [
          {
            "query": "from(bucket: \"cron_metrics\")\n  |> range(start: -24h)\n  |> filter(fn: (r) => r._field == \"success_rate\")\n  |> aggregateWindow(every: 1h, fn: mean)",
            "datasource": "InfluxDB"
          }
        ]
      },
      {
        "title": "Failed Jobs",
        "type": "table",
        "targets": [
          {
            "query": "from(bucket: \"cron_metrics\")\n  |> range(start: -1h)\n  |> filter(fn: (r) => r._field == \"failed_runs\" and r._value > 0)",
            "datasource": "InfluxDB"
          }
        ]
      }
    ]
  }
}
```

### **Test Connection**

```bash
# Test InfluxDB connection
php artisan grafana:export-metrics --test

# Export metrics manually
php artisan grafana:export-metrics

# View dashboard URL
php artisan grafana:export-metrics --dashboard-url
```

### **Dashboard Features**

- 📊 Real-time success rate trends
- 📈 Execution frequency heatmaps
- 🔴 Failed job alerts table
- ⏱️ Average execution duration
- 🌍 Geographic distribution (if tracked)
- 📉 Failure rate predictions (ML-based)

### **Pricing**

**Self-Hosted:**
- InfluxDB: FREE (open source)
- Grafana: FREE (open source)
- Server costs: ~$5-20/month (VPS)

**Cloud:**
- InfluxDB Cloud: FREE tier (10K writes/day)
- Grafana Cloud: FREE tier (3 users, 50 dashboards)

---

## 4️⃣ **Automated Incident Response Workflows**

### **What It Does**

Intelligent system that automatically responds to cron job failures with:
- **Severity Classification**: Low, Medium, High, Critical
- **Auto-Recovery Actions**: Cache clearing, queue restarts, job retries
- **Multi-Channel Notifications**: Email, SMS, Slack, Push based on severity
- **Escalation Workflows**: On-call engineer notification for critical issues
- **Incident Logging**: Complete audit trail for post-mortem analysis

### **How It Works**

```
Job Fails → Classify Severity → Attempt Auto-Recovery → Notify → Escalate if Needed
     ↓
  Log Incident → Update Dashboard → Track Resolution
```

### **Severity Levels**

| Level | Criteria | Actions | Notifications |
|-------|----------|---------|---------------|
| **Low** | Single failure, good history | Immediate retry | Email only |
| **Medium** | Success rate < 70% | Cache clear, retry | Email + Webhook |
| **High** | 3+ consecutive failures | Queue restart, cache clear | Email + Webhook + SMS |
| **Critical** | System job or backup failure | All actions + auto-disable if >50% fail rate | Email + Webhook + SMS + Push + Escalation |

### **Auto-Recovery Actions**

#### **1. Cache Clearing**
For cache/session-related jobs:
```php
Cache::clear();
```

#### **2. Queue Worker Restart**
For queue jobs (requires manual setup):
```bash
supervisorctl restart queue-worker
# or
systemctl restart queue-worker
```

#### **3. Backup Verification**
For backup jobs:
```php
$verifier->verifyLatestBackup();
```

#### **4. Immediate Retry**
For transient errors (low severity only):
```php
exec($job->command);
```

#### **5. Auto-Disable**
For critically failing jobs (>50% failure rate, 5+ failures):
```php
$job->update(['is_enabled' => false]);
```

### **Notification Routing**

| Severity | Email | Slack/Discord | SMS | Push | Escalation |
|----------|-------|---------------|-----|------|------------|
| Low | ✅ | ❌ | ❌ | ❌ | ❌ |
| Medium | ✅ | ✅ | ❌ | ❌ | ❌ |
| High | ✅ | ✅ | ✅ | ❌ | ❌ |
| Critical | ✅ | ✅ | ✅ | ✅ | ✅ |

### **Escalation Workflow**

When incident requires escalation:

1. **Immediate SMS** to on-call engineer
2. **Create incident ticket** (Jira/Linear/GitHub Issues integration ready)
3. **Alert logged** with full context
4. **Dashboard updated** to show active incidents

### **Configuration**

No additional configuration needed! The IncidentResponder automatically uses:
- Email settings from `.env`
- Twilio config for SMS
- Firebase config for push
- Webhook config for Slack/Discord

### **Testing**

Simulate a failure to test the workflow:

```bash
php artisan tinker
>>> $job = \App\Models\CronJob::where('name', 'laravel-scheduler')->first();
>>> $responder = app(\App\Services\IncidentResponder::class);
>>> $responder->handleFailure($job, 'Test failure message');
```

Check:
- ✅ Email received
- ✅ Slack/Discord message posted (if configured)
- ✅ SMS sent (if severity is high/critical)
- ✅ Push notification received (if critical)
- ✅ Logs written to `storage/logs/cron.log`

### **Customizing Recovery Actions**

Edit `app/Services/IncidentResponder.php` → `attemptAutoRecovery()` method to add custom actions:

```php
// Example: Restart PHP-FPM for web server issues
if (str_contains($job->name, 'web')) {
    exec('systemctl restart php-fpm');
    $actions[] = ['action' => 'php_fpm_restart', 'status' => 'success'];
}
```

### **Integration with External Systems**

#### **Jira Integration**

Edit `createIncidentTicket()` method:

```php
protected function createIncidentTicket(CronJob $job, string $severity, string $errorMessage): void
{
    Http::withBasicAuth('jira_user', 'jira_token')
        ->post('https://your-domain.atlassian.net/rest/api/3/issue', [
            'fields' => [
                'project' => ['key' => 'INC'],
                'summary' => "Cron Job Failure: {$job->name}",
                'description' => $errorMessage,
                'issuetype' => ['name' => 'Incident'],
                'priority' => ['name' => ucfirst($severity)],
            ]
        ]);
}
```

#### **PagerDuty Integration**

```php
Http::post('https://events.pagerduty.com/v2/enqueue', [
    'routing_key' => 'your_pagerduty_key',
    'event_action' => 'trigger',
    'payload' => [
        'summary' => "Critical: {$job->name} failed",
        'severity' => 'critical',
        'source' => 'YG Account',
    ]
]);
```

---

## 🎯 **Complete Setup Checklist**

### **Phase 1: Basic Setup (Required)**

- [ ] Configure `ADMIN_EMAIL` in `.env`
- [ ] Set up backup directory: `mkdir storage/app/backups`
- [ ] Run migrations: `php artisan migrate`
- [ ] Seed default jobs: `php artisan db:seed --class=CronJobSeeder`
- [ ] Test email notifications work

### **Phase 2: Enhanced Alerts (Recommended)**

- [ ] Set up Twilio account and configure SMS
- [ ] Test SMS delivery
- [ ] Configure Slack or Discord webhook
- [ ] Test webhook notifications

### **Phase 3: Mobile Integration (Optional)**

- [ ] Create Firebase project
- [ ] Integrate FCM into mobile app
- [ ] Collect device tokens
- [ ] Configure `.env` with FCM credentials
- [ ] Test push notifications

### **Phase 4: Advanced Analytics (Power Users)**

- [ ] Install InfluxDB (Docker or cloud)
- [ ] Install Grafana (Docker or cloud)
- [ ] Configure InfluxDB data source in Grafana
- [ ] Import dashboard JSON
- [ ] Schedule metric exports via cron
- [ ] Verify data flowing to dashboards

### **Phase 5: Incident Response (Production)**

- [ ] Configure on-call phone number
- [ ] Test escalation workflow
- [ ] Integrate with Jira/PagerDuty (optional)
- [ ] Review auto-recovery actions
- [ ] Customize severity thresholds if needed

---

## 📊 **Monitoring Dashboard Overview**

After complete setup, your admin dashboard shows:

1. **CronHealthWidget** - 4 key metrics
2. **CronJobExecutionChart** - Visual trends
3. **CronJobExecutionHistory** - Recent executions
4. **BackupStatusWidget** - Backup health
5. **Grafana Link** - Advanced analytics (external)

Plus you receive:
- 📧 Email for all failures
- 💬 Slack/Discord for medium+
- 📱 SMS for high+
- 🔔 Push for critical
- 🚨 Escalation calls for unresolved critical

---

## 🐛 **Troubleshooting**

### **SMS Not Sending**

**Check:**
1. Twilio credentials correct in `.env`
2. Phone numbers in E.164 format (+1234567890)
3. Twilio account has balance
4. Check `storage/logs/laravel.log` for errors

**Test:**
```bash
php artisan tinker
>>> $twilio = app(\App\Services\TwilioNotifier::class);
>>> $twilio->getBalance(); // Should return balance
```

### **Push Notifications Not Received**

**Check:**
1. Firebase server key correct
2. Device tokens valid and registered
3. Mobile app has proper permissions
4. Firebase project configured correctly

**Debug:**
```bash
php artisan tinker
>>> $push = app(\App\Services\FirebasePushNotifier::class);
>>> $push->testPush('YOUR_TOKEN');
```

### **Grafana Shows No Data**

**Check:**
1. InfluxDB running and accessible
2. API token has read/write permissions
3. Cron export job running every 5 minutes
4. Check `storage/logs/cron.log` for export errors

**Verify:**
```bash
# Test connection
php artisan grafana:export-metrics --test

# Manual export
php artisan grafana:export-metrics

# Check logs
tail -f storage/logs/cron.log | grep "Export"
```

### **Auto-Recovery Not Working**

**Check:**
1. IncidentResponder integrated into CronJobMonitor
2. Job names match expected patterns (e.g., contains "cache", "backup")
3. Permissions allow executing recovery commands
4. Review `storage/logs/cron.log` for recovery action logs

---

## 🎓 **Best Practices**

### **SMS Usage**
- Only enable for critical alerts to avoid spam
- Use short, actionable messages
- Include job name and immediate action required
- Rotate on-call schedule weekly

### **Push Notifications**
- Register all admin devices
- Use topic subscriptions for team alerts
- Test on both iOS and Android
- Set custom notification sounds for urgency

### **Grafana Dashboards**
- Export metrics every 5 minutes (balance between freshness and load)
- Set up Grafana alerts for threshold breaches
- Share dashboards with stakeholders
- Archive old data periodically

### **Incident Response**
- Review auto-recovery actions monthly
- Adjust severity thresholds based on experience
- Document common failure patterns
- Conduct post-mortems for critical incidents
- Update escalation contacts quarterly

---

## 📞 **Support**

For issues or questions:
- Check logs: `storage/logs/cron.log`
- Review documentation: `ADVANCED_CRON_FEATURES.md`
- Test individual services via Artisan commands
- Contact system administrator for credential issues

---

**Congratulations!** Your YG Account now has **enterprise-grade monitoring** comparable to Fortune 500 companies! 🎉
