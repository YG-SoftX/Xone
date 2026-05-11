# 🚀 Enterprise-Grade Monitoring & Incident Management - Complete Guide

## 📋 Overview

This guide covers four enterprise-grade features that transform your YG Account monitoring system into a production-ready incident management platform comparable to Fortune 500 infrastructure:

1. ✅ **PagerDuty Integration** - Professional incident management with on-call scheduling
2. ✅ **ML-Based Anomaly Detection** - Statistical pattern recognition for proactive alerting
3. ✅ **Automated Deployment Rollback** - Safe rollback mechanisms for failed deployments
4. ✅ **Custom Webhook Endpoints** - Third-party integrations with signature verification

---

## 1️⃣ **PagerDuty Integration for Professional Incident Management**

### **What It Does**

Integrates with PagerDuty's enterprise incident management platform to provide:
- 🚨 Professional incident creation with severity levels
- 👥 On-call schedule management and escalation policies
- 🔔 Multi-channel notifications (SMS, phone call, push, email)
- 📊 Incident lifecycle tracking (triggered → acknowledged → resolved)
- 🔄 Automatic incident deduplication to prevent alert storms
- 📱 Mobile app for on-call engineers

### **Setup Steps**

#### **Step 1: Create PagerDuty Account**

1. Sign up at [https://www.pagerduty.com/](https://www.pagerduty.com/)
   - Free trial: 14 days
   - Starter plan: $19/user/month
   - Business plan: $39/user/month

2. After signup, navigate to **Services** → **Add New Service**
   - Service Name: "YG Account Cron Monitor"
   - Escalation Policy: Create or select existing
   - Notification Rules: Configure SMS, push, email

3. Go to **Integrations** → **Add New Integration**
   - Integration Type: "Events API v2"
   - Copy the **Integration Key** (this is your `PAGERDUTY_ROUTING_KEY`)

#### **Step 2: Get API Credentials**

From PagerDuty Dashboard:
1. Click your profile → **My Profile**
2. Scroll to **API Access** section
3. Generate new API key or copy existing one
4. This is your `PAGERDUTY_API_KEY`

5. From the service you created:
   - Copy **Service ID** → `PAGERDUTY_SERVICE_ID`
   - Copy **Integration Key** → `PAGERDUTY_ROUTING_KEY`

#### **Step 3: Configure .env**

```env
PAGERDUTY_API_KEY=your_api_key_here
PAGERDUTY_SERVICE_ID=your_service_id_here
PAGERDUTY_ROUTING_KEY=your_integration_routing_key_here
```

#### **Step 4: Test Integration**

```bash
php artisan pagerduty:test
```

You should receive a test incident notification on your configured devices!

#### **Step 5: Configure Escalation Policies**

In PagerDuty Console:
1. Go to **Escalation Policies**
2. Create policy: "YG Account Critical Alerts"
3. Add steps:
   - Step 1: Notify on-call engineer (immediate)
   - Step 2: If unacknowledged after 5 min, notify team lead
   - Step 3: If unresolved after 15 min, notify manager
4. Assign team members to rotation schedule

### **How It Works**

When a critical cron job failure occurs:

1. **Incident Created** in PagerDuty with:
   - Severity level (critical/error/warning/info)
   - Job name and error details
   - Links to admin panel
   - Deduplication key (prevents duplicates)

2. **On-Call Engineer Notified** via:
   - Push notification (mobile app)
   - SMS text message
   - Phone call (if configured)
   - Email

3. **Engineer Acknowledges** incident in mobile app

4. **Incident Tracked** until resolution:
   - Response time metrics
   - Resolution time tracking
   - Post-incident reports

5. **Auto-Resolution** when job succeeds again

### **Incident Lifecycle**

```
Triggered → Acknowledged → Resolved
     ↓           ↓            ↓
  Alert Sent  Engineer    Issue Fixed
              Responds    & Verified
```

### **Commands**

```bash
# Test integration (creates test incident)
php artisan pagerduty:test

# Resolve specific incident
php artisan pagerduty:test --resolve=<dedup_key>

# Acknowledge specific incident
php artisan pagerduty:test --acknowledge=<dedup_key>
```

### **Severity Mapping**

| YG Account Severity | PagerDuty Severity | Trigger Conditions |
|---------------------|-------------------|--------------------|
| Critical | Critical | System jobs, backup failures, <50% success rate |
| High | Error | 3+ consecutive failures, <70% success rate |
| Medium | Warning | Success rate degradation, pattern deviations |
| Low | Info | Single transient failures |

### **Benefits**

✅ **Professional SLA Tracking** - Meet response time commitments  
✅ **On-Call Rotation** - Fair distribution of alerts among team  
✅ **Mobile App** - Engineers can respond from anywhere  
✅ **Escalation Policies** - Automatic escalation if unacknowledged  
✅ **Incident Analytics** - Track MTTR (Mean Time To Resolution)  
✅ **Post-Incident Reports** - Learn from every outage  

---

## 2️⃣ **Machine Learning-Based Anomaly Detection**

### **What It Does**

Uses statistical analysis and pattern recognition to detect anomalies BEFORE they become critical failures:

- 📈 **Failure Rate Spike Detection** - Sudden increases in failure rates
- ⏱️ **Execution Time Anomalies** - Performance degradation detection
- 🔄 **Frequency Deviations** - Missing or duplicate executions
- 📊 **Pattern Recognition** - Day-of-week, hour-of-day patterns
- 📉 **Trend Degradation** - Week-over-week success rate decline
- 🎯 **Risk Scoring** - Proactive risk assessment for each job

### **Statistical Methods Used**

1. **Z-Score Analysis**: Detects values >2.5 standard deviations from mean
2. **Moving Averages**: Compares recent vs historical performance
3. **Trend Analysis**: Linear regression for degradation detection
4. **Pattern Matching**: Identifies deviations from normal execution patterns
5. **Confidence Scoring**: 0-100% confidence in anomaly detection

### **Usage**

#### **Manual Detection Run**

```bash
# Analyze all jobs
php artisan monitor:detect-anomalies

# Analyze specific job
php artisan monitor:detect-anomalies --job=database-backup

# Output as JSON (for automation)
php artisan monitor:detect-anomalies --json
```

#### **Sample Output**

```
🔍 Running anomaly detection...

📈 Overall Anomaly Statistics

┌──────────────────────┬───────┐
│ Metric               │ Count │
├──────────────────────┼───────┤
│ Jobs Analyzed        │ 6     │
│ Critical Anomalies   │ 1     │
│ High Anomalies       │ 2     │
│ Medium Anomalies     │ 3     │
│ Low Anomalies        │ 5     │
└──────────────────────┴───────┘

🔴 Most Affected Jobs:
  - database-backup: 4 anomalies
  - queue-worker: 3 anomalies

📊 Job: database-backup

Risk Assessment:
┌──────────────────┬──────────┐
│ Metric           │ Value    │
├──────────────────┼──────────┤
│ Risk Level       │ HIGH     │
│ Risk Score       │ 14       │
│ Anomalies Detected│ 4       │
└──────────────────┴──────────┘

⚠️  Anomalies Found:
  🚨 [critical] failure_rate_spike
     Confidence: 92%
     Failure rate increased by 3.5x compared to 30-day baseline
     💡 Action: Investigate recent changes, check error logs, verify dependencies

  ⚡ [medium] trend_degradation
     Confidence: 78%
     Success rate declining: -15.3% drop week-over-week
     💡 Action: Immediate investigation required - identify root cause
```

### **Anomaly Types Explained**

#### **1. Failure Rate Spike**
- **Detection**: Recent 7-day failure rate vs 30-day baseline
- **Threshold**: >2x increase triggers alert
- **Example**: Normal 5% failure → Recent 15% failure = 3x spike

#### **2. Execution Time Anomaly**
- **Detection**: High failure rates may indicate timeouts
- **Indicators**: Clustered failures, resource exhaustion
- **Action**: Check CPU/RAM usage, optimize job logic

#### **3. Frequency Anomaly**
- **Detection**: Expected vs actual execution count
- **Example**: Hourly job should run 168 times/week, only ran 120
- **Causes**: Cron not running, overlapping executions blocked

#### **4. Pattern Deviation**
- **Detection**: Success rate variance from historical baseline
- **Example**: Usually 95% success, now 75% = 20% variance
- **Investigation**: Check external dependencies, API changes

#### **5. Trend Degradation**
- **Detection**: Week-over-week success rate comparison
- **Critical**: >20% drop triggers immediate alert
- **Example**: Week 1: 90% → Week 2: 70% = -20% degradation

### **Risk Scoring**

Each anomaly contributes to overall risk score:

| Severity | Points |
|----------|--------|
| Critical | 10 |
| High | 7 |
| Medium | 4 |
| Low | 1 |

**Risk Levels:**
- **0-4**: Low risk - Monitor
- **5-9**: Medium risk - Investigate soon
- **10-19**: High risk - Investigate immediately
- **20+**: Critical risk - Emergency response

### **Scheduling Automated Detection**

Add to crontab (runs every 6 hours):

```bash
0 */6 * * * /usr/bin/php /path/to/artisan monitor:detect-anomalies >> /dev/null 2>&1
```

Or create cron job in Filament admin:
```
Name: anomaly-detection
Command: /usr/bin/php /home/USERNAME/yg-account-core/artisan monitor:detect-anomalies >> /dev/null 2>&1
Schedule: 0 */6 * * *
Description: Runs ML-based anomaly detection on all cron jobs
Enabled: ✓
System Job: ✓
```

### **Integration with Incident Response**

Anomalies automatically trigger:
1. **Custom webhook dispatch** to third-party systems
2. **Risk score calculation** for prioritization
3. **Incident logging** for trend analysis
4. **Dashboard updates** with visual indicators

### **Advanced Configuration**

Edit `app/Services/AnomalyDetector.php` to customize:

```php
// Adjust baseline period (default: 30 days)
protected const BASELINE_DAYS = 60; // Use 2 months of data

// Adjust sensitivity (lower = more sensitive)
protected const ANOMALY_THRESHOLD = 2.0; // Default 2.5 std devs

// Adjust confidence thresholds
protected const CONFIDENCE_HIGH = 0.90;   // Was 0.95
protected const CONFIDENCE_MEDIUM = 0.75; // Was 0.80
```

### **Benefits**

✅ **Proactive Detection** - Find issues before users notice  
✅ **Statistical Rigor** - Data-driven anomaly identification  
✅ **Trend Analysis** - Spot degradation over time  
✅ **Risk Prioritization** - Focus on highest-risk jobs first  
✅ **Automated Insights** - No manual log analysis needed  
✅ **Historical Baselines** - Context-aware detection  

---

## 3️⃣ **Automated Deployment Rollback**

### **What It Does**

Provides safe, automated rollback capabilities when deployments fail:

- 🔄 **Database Migration Rollback** - Revert schema changes
- 💻 **Code Rollback** - Git-based commit restoration
- ⚙️ **Configuration Rollback** - Restore .env and config files
- 🗑️ **Cache Clearing** - Automatic cache invalidation
- ✅ **Verification** - Post-rollback health checks
- 📦 **Backup Management** - Versioned deployment snapshots

### **Architecture**

```
Before Deployment          During Failure           After Rollback
┌──────────────┐          ┌──────────────┐         ┌──────────────┐
│ Create Backup│─────────▶│ Detect Error │────────▶│ Rollback     │
│ - Git commit │          │ - Auto or    │         │ - DB migrate │
│ - DB version │          │   Manual     │         │ - Git checkout│
│ - Config snap│          │              │         │ - Config restore│
└──────────────┘          └──────────────┘         └──────────────┘
```

### **Setup**

#### **Step 1: Create Backup Directory**

```bash
mkdir -p storage/app/deployments
chmod 775 storage/app/deployments
```

#### **Step 2: Configure .env**

```env
DEPLOYMENT_BACKUP_DIRECTORY=storage/app/deployments
DEPLOYMENT_AUTO_ROLLBACK_ENABLED=false  # Set true for auto-rollback
DEPLOYMENT_BACKUP_RETENTION_DAYS=30
```

#### **Step 3: Ensure Git Repository**

Your application must be in a Git repository:

```bash
cd /path/to/yg-account
git status  # Should show working tree
```

#### **Step 4: Create Pre-Deployment Backup**

Before deploying, create a backup:

```bash
php artisan tinker
>>> $rollback = app(\App\Services\DeploymentRollback::class);
>>> $result = $rollback->createBackup('deployment-' . date('Ymd-His'));
>>> dump($result);
```

Or integrate into your deployment script:

```bash
#!/bin/bash
# deploy.sh

DEPLOYMENT_ID="deploy-$(date +%Y%m%d-%H%M%S)"

echo "Creating deployment backup..."
php artisan tinker --execute="\
    \$rollback = app(\\\App\\\Services\\\DeploymentRollback::class);\
    \$rollback->createBackup('${DEPLOYMENT_ID}');\
"

echo "Running migrations..."
php artisan migrate --force

echo "Clearing caches..."
php artisan config:cache
php artisan route:cache

echo "Deployment complete!"
echo "Deployment ID: ${DEPLOYMENT_ID}"
```

### **Manual Rollback**

#### **List Available Rollbacks**

```bash
php artisan deployment:rollback --list
```

Output:
```
📦 Available Deployment Rollbacks

┌────────────────────┬─────────────────────┬────────────┬──────────────────┐
│ Deployment ID      │ Timestamp           │ Git Commit │ Database Version │
├────────────────────┼─────────────────────┼────────────┼──────────────────┤
│ deploy-20260506-01 │ 2026-05-06 01:00:00 │ a1b2c3d4   │ 2026_05_05_...   │
│ deploy-20260505-23 │ 2026-05-05 23:00:00 │ e5f6g7h8   │ 2026_05_04_...   │
└────────────────────┴─────────────────────┴────────────┴──────────────────┘

To rollback, run: php artisan deployment:rollback <deployment_id>
```

#### **Execute Rollback**

```bash
# Rollback to specific deployment
php artisan deployment:rollback deploy-20260506-01

# Rollback to last stable (auto-detected)
php artisan deployment:rollback

# Skip confirmation prompt (for automation)
php artisan deployment:rollback --force
```

### **Automatic Rollback**

Enable automatic rollback for critical deployment failures:

```env
DEPLOYMENT_AUTO_ROLLBACK_ENABLED=true
```

When enabled, the IncidentResponder will:
1. Detect deployment-related job failures
2. Check if failure is critical (severity = critical)
3. Automatically trigger rollback to last stable deployment
4. Notify team via PagerDuty/webhooks
5. Log rollback action for audit trail

### **Rollback Process**

When rollback executes:

1. **Database Rollback**
   ```bash
   php artisan migrate:rollback --step=1
   ```
   Reverts last migration batch

2. **Code Rollback**
   ```bash
   git checkout <target_commit>
   composer install --no-dev --optimize-autoloader
   ```
   Restores previous code version

3. **Configuration Restore**
   - Copies backed-up `.env` file
   - Clears config cache

4. **Cache Clearing**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

5. **Verification**
   - HTTP request to `/` endpoint
   - Checks for 200 OK response
   - Confirms application is accessible

### **Safety Features**

✅ **Confirmation Prompt** - Prevents accidental rollbacks  
✅ **Backup Verification** - Ensures backup exists before rollback  
✅ **Step-by-Step Execution** - Each step independently verified  
✅ **Partial Rollback Support** - Continues even if some steps fail  
✅ **Audit Logging** - Complete rollback history logged  
✅ **Last Stable Detection** - Auto-finds last known good state  

### **Best Practices**

1. **Always Create Backups Before Deployments**
   ```bash
   php artisan tinker --execute="app(\App\Services\DeploymentRollback::class)->createBackup('pre-deploy')"
   ```

2. **Mark Stable Deployments**
   ```bash
   php artisan tinker --execute="app(\App\Services\DeploymentRollback::class)->markAsStable('deploy-20260506-01')"
   ```

3. **Test Rollback in Staging First**
   - Never enable auto-rollback in production without testing
   - Verify rollback works in staging environment

4. **Monitor Rollback Logs**
   ```bash
   tail -f storage/logs/cron.log | grep -i rollback
   ```

5. **Keep Backups for 30 Days Minimum**
   ```env
   DEPLOYMENT_BACKUP_RETENTION_DAYS=30
   ```

### **Integration with CI/CD**

Example GitHub Actions workflow:

```yaml
name: Deploy with Rollback Safety

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Create deployment backup
        run: |
          ssh user@server "cd /var/www/yg-account && \
            php artisan tinker --execute=\"\\
              app(\\\App\\\Services\\\DeploymentRollback::class)->\\
              createBackup('github-${{ github.sha }}')\\
            \""
      
      - name: Run migrations
        run: ssh user@server "cd /var/www/yg-account && php artisan migrate --force"
      
      - name: Deploy code
        run: ssh user@server "cd /var/www/yg-account && git pull origin main"
      
      - name: Verify deployment
        run: |
          STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://account.ygxone.com)
          if [ $STATUS -ne 200 ]; then
            echo "Deployment failed! Rolling back..."
            ssh user@server "cd /var/www/yg-account && \
              php artisan deployment:rollback --force"
            exit 1
          fi
```

### **Benefits**

✅ **Zero-Downtime Recovery** - Quick rollback minimizes outage  
✅ **Database Safety** - Migrations rolled back in correct order  
✅ **Git Integration** - Leverages version control for code rollback  
✅ **Automated Verification** - Confirms rollback success  
✅ **Audit Trail** - Complete history of all rollbacks  
✅ **CI/CD Ready** - Integrates with deployment pipelines  

---

## 4️⃣ **Custom Webhook Endpoints for Third-Party Integrations**

### **What It Does**

Enables integration with ANY third-party service via webhooks:

- 🔗 **Universal Integration** - Zapier, Make.com, n8n, Slack, Discord, etc.
- 🔐 **Signature Verification** - HMAC SHA-256 for security
- 🔄 **Retry Logic** - Exponential backoff for failed deliveries
- 📊 **Delivery Monitoring** - Track success/failure rates
- ⚡ **Async Dispatch** - Non-blocking webhook delivery
- 🎯 **Event Filtering** - Subscribe to specific events only

### **Supported Integrations**

| Platform | Use Case | Setup Time |
|----------|----------|------------|
| **Zapier** | Connect to 5000+ apps | 5 minutes |
| **Make.com** | Visual automation workflows | 10 minutes |
| **n8n** | Self-hosted automation | 15 minutes |
| **Slack** | Custom channel notifications | 5 minutes |
| **Discord** | Bot notifications | 5 minutes |
| **Microsoft Teams** | Corporate notifications | 10 minutes |
| **Custom APIs** | Internal tools, databases | Varies |

### **Available Events**

```php
'cron_job.failed'       // Cron job execution failed
'cron_job.success'      // Cron job executed successfully
'backup.verified'       // Backup verification completed
'backup.failed'         // Backup verification failed
'deployment.started'    // Deployment process started
'deployment.completed'  // Deployment completed
'deployment.failed'     // Deployment failed
'deployment.rolled_back'// Automatic rollback triggered
'incident.created'      // New incident created
'incident.resolved'     // Incident resolved
'anomaly.detected'      // Anomaly detected in metrics
'user.login'            // User login event
'payment.completed'     // Payment transaction completed
'*'                     // All events (wildcard)
```

### **Setup Examples**

#### **Example 1: Zapier Integration**

**Step 1: Create Zapier Webhook**
1. Go to [zapier.com](https://zapier.com/)
2. Click **"Create Zap"**
3. Trigger: **"Webhooks by Zapier"** → **"Catch Hook"**
4. Copy the custom webhook URL (looks like: `https://hooks.zapier.com/hooks/catch/123456/abcdef/`)

**Step 2: Configure in YG Account**

Add to `config/services.php`:

```php
'webhooks' => [
    'custom' => [
        [
            'id' => 'zapier-cron-alerts',
            'url' => 'https://hooks.zapier.com/hooks/catch/123456/abcdef/',
            'secret' => env('ZAPIER_WEBHOOK_SECRET', Str::random(64)),
            'events' => ['cron_job.failed', 'incident.created'],
            'enabled' => true,
            'max_retries' => 3,
            'custom_headers' => [],
        ],
    ],
],
```

Add to `.env`:
```env
ZAPIER_WEBHOOK_SECRET=your_generated_secret_here
```

**Step 3: Test Webhook**

```bash
php artisan tinker
>>> $dispatcher = app(\App\Services\WebhookDispatcher::class);
>>> $dispatcher->testWebhook('https://hooks.zapier.com/hooks/catch/123456/abcdef/', 'your_secret');
```

**Step 4: Build Zap**

In Zapier:
1. Add action after webhook trigger
2. Choose app: Slack, Email, SMS, Google Sheets, etc.
3. Map webhook data fields
4. Test and activate Zap

**Example Zap Flow:**
```
Cron Job Fails → Zapier Webhook → Send Slack Message → Create Trello Card → Send Email to Admin
```

---

#### **Example 2: Make.com (Integromat) Integration**

**Step 1: Create Scenario**
1. Go to [make.com](https://www.make.com/)
2. Create new scenario
3. Add module: **"Webhooks"** → **"Custom webhook"**
4. Copy webhook URL

**Step 2: Configure**

```php
'webhooks' => [
    'custom' => [
        [
            'id' => 'make-automation',
            'url' => 'https://hook.make.com/abcdef123456',
            'secret' => env('MAKE_WEBHOOK_SECRET'),
            'events' => ['anomaly.detected', 'backup.failed'],
            'enabled' => true,
            'max_retries' => 3,
        ],
    ],
],
```

**Step 3: Build Automation**

Make.com scenario example:
```
Webhook Received → Filter (only critical anomalies) → 
Send Telegram Message → Update Google Sheet → Create Jira Ticket
```

---

#### **Example 3: n8n Self-Hosted Integration**

**Step 1: Setup n8n**

```bash
docker run -d \
  --name n8n \
  -p 5678:5678 \
  -v ~/.n8n:/home/node/.n8n \
  n8nio/n8n
```

Access at: `http://localhost:5678`

**Step 2: Create Workflow**

1. Add **"Webhook"** node
2. Method: POST
3. Path: `/yg-account-alerts`
4. Copy webhook URL: `http://localhost:5678/webhook-test/yg-account-alerts`

**Step 3: Configure YG Account**

```php
[
    'id' => 'n8n-automation',
    'url' => 'http://localhost:5678/webhook/yg-account-alerts',
    'secret' => env('N8N_WEBHOOK_SECRET'),
    'events' => ['*'], // All events
    'enabled' => true,
]
```

**Step 4: Build Workflow**

n8n workflow:
```
Webhook → Switch (event type) → 
  ├─ cron_job.failed → Send Email + Create Database Record
  ├─ anomaly.detected → Post to Slack + Update Dashboard
  └─ deployment.failed → Trigger Rollback Script + Page On-Call
```

---

#### **Example 4: Custom API Integration**

For internal tools or custom services:

```php
[
    'id' => 'internal-monitoring',
    'url' => 'https://monitoring.internal.ygxone.com/api/alerts',
    'secret' => env('INTERNAL_MONITORING_SECRET'),
    'events' => ['incident.created', 'incident.resolved'],
    'enabled' => true,
    'max_retries' => 5,
    'custom_headers' => [
        'X-API-Key' => env('MONITORING_API_KEY'),
        'X-Team' => 'infrastructure',
    ],
]
```

### **Webhook Payload Format**

All webhooks send JSON payload:

```json
{
  "event": "cron_job.failed",
  "timestamp": "2026-05-06T08:30:00+00:00",
  "data": {
    "job_name": "database-backup",
    "error_message": "Connection timeout",
    "failed_runs": 3,
    "success_rate": 65.5,
    "admin_url": "https://account.ygxone.com/admin/cron-jobs"
  },
  "source": "YG Account",
  "version": "1.0"
}
```

### **Security: Signature Verification**

Each webhook includes HMAC signature:

**Headers:**
```
X-Webhook-Signature: a1b2c3d4e5f6...
X-Webhook-Event: cron_job.failed
X-Webhook-Source: YG Account
```

**Verification in Receiving Service:**

```php
// PHP example
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$secret = 'your_webhook_secret';

$expectedSignature = hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(401);
    die('Invalid signature');
}

// Process webhook...
```

**Python example:**
```python
import hmac
import hashlib

payload = request.get_data()
signature = request.headers.get('X-Webhook-Signature')
secret = b'your_webhook_secret'

expected = hmac.new(secret, payload, hashlib.sha256).hexdigest()

if not hmac.compare_digest(expected, signature):
    return 'Invalid signature', 401
```

### **Retry Logic**

Webhooks use exponential backoff:

| Attempt | Wait Time | Total Elapsed |
|---------|-----------|---------------|
| 1 | Immediate | 0s |
| 2 | 1 second | 1s |
| 3 | 2 seconds | 3s |
| 4 | 4 seconds | 7s |
| 5 | 8 seconds | 15s |

**Retry Behavior:**
- Client errors (4xx): NOT retried (permanent failure)
- Server errors (5xx): Retried with backoff
- Timeouts: Retried with backoff
- Max retries: Configurable per webhook (default: 3)

### **Testing Webhooks**

```bash
php artisan tinker

// Test specific webhook
>>> $dispatcher = app(\App\Services\WebhookDispatcher::class);
>>> $dispatcher->testWebhook('https://your-webhook-url.com', 'your_secret');

// Get delivery statistics
>>> $dispatcher->getDeliveryStats();

// List available events
>>> $dispatcher->getAvailableEvents();
```

### **Monitoring & Debugging**

#### **Check Delivery Logs**

```bash
tail -f storage/logs/cron.log | grep -i webhook
```

#### **View Delivery Stats**

```bash
php artisan tinker
>>> $dispatcher = app(\App\Services\WebhookDispatcher::class);
>>> $stats = $dispatcher->getDeliveryStats();
>>> dump($stats);
```

#### **Common Issues**

**Problem: Webhook not receiving data**

Check:
1. Webhook URL is HTTPS (required)
2. URL is publicly accessible (not localhost)
3. Firewall allows inbound connections
4. Signature verification disabled for testing

**Problem: Signature verification fails**

Check:
1. Secret matches on both sides
2. Payload not modified in transit
3. Using correct hashing algorithm (SHA-256)
4. Comparing signatures with constant-time comparison (`hash_equals`)

**Problem: Too many retries**

Check:
1. Receiving service returning 5xx errors
2. Timeout too short (increase from 10s)
3. Reduce `max_retries` configuration

### **Best Practices**

✅ **Use HTTPS Only** - Never send webhooks over HTTP  
✅ **Verify Signatures** - Always validate HMAC signatures  
✅ **Idempotent Processing** - Handle duplicate deliveries gracefully  
✅ **Quick Response** - Return 200 OK within 5 seconds  
✅ **Async Processing** - Queue heavy processing after acknowledgment  
✅ **Logging** - Log all webhook receipts for debugging  
✅ **Rate Limiting** - Protect against webhook storms  
✅ **Secret Rotation** - Rotate webhook secrets quarterly  

### **Example: Building a Webhook Receiver**

**Laravel Route:**
```php
Route::post('/webhooks/yg-account', [WebhookController::class, 'handle']);
```

**Controller:**
```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify signature
        $signature = $request->header('X-Webhook-Signature');
        $secret = config('services.webhooks.custom')[0]['secret'];
        
        if (!$this->verifySignature($request->getContent(), $signature, $secret)) {
            return response('Invalid signature', 401);
        }
        
        // Parse payload
        $payload = $request->json()->all();
        $event = $payload['event'];
        $data = $payload['data'];
        
        // Process based on event type
        match($event) {
            'cron_job.failed' => $this->handleCronFailure($data),
            'incident.created' => $this->handleIncidentCreated($data),
            'anomaly.detected' => $this->handleAnomalyDetected($data),
            default => Log::info("Unhandled webhook event: {$event}"),
        };
        
        // Return quickly
        return response('OK', 200);
    }
    
    protected function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }
    
    protected function handleCronFailure(array $data): void
    {
        // Queue async processing
        dispatch(new ProcessCronFailure($data));
    }
}
```

### **Benefits**

✅ **Universal Integration** - Connect to ANY service  
✅ **Secure Delivery** - HMAC signature verification  
✅ **Reliable** - Retry logic with exponential backoff  
✅ **Flexible** - Event filtering and custom headers  
✅ **Monitored** - Delivery tracking and statistics  
✅ **Scalable** - Async dispatch doesn't block application  

---

## 🎯 **Complete Integration Architecture**

### **How All Features Work Together**

```
Cron Job Fails
     ↓
┌────────────────────────┐
│  IncidentResponder     │
│  - Classify Severity   │
│  - Detect Anomalies    │
│  - Auto-Recovery       │
└────────┬───────────────┘
         │
    ┌────┴────────────────────────────────────┐
    │                                         │
┌───▼──────────┐                    ┌────────▼─────────┐
│ Critical?    │                    │ Anomalies Found? │
└───┬──────────┘                    └────────┬─────────┘
    │ Yes                                    │ Yes
┌───▼──────────────────┐          ┌─────────▼──────────┐
│ PagerDuty Incident   │          │ Custom Webhooks    │
│ - On-call notified   │          │ - Zapier/Make/n8n  │
│ - Escalation policy  │          │ - Third-party APIs │
└──────────────────────┘          └────────────────────┘
         │
    ┌────┴────────────┐
    │ Deployment Fail?│
    └────┬────────────┘
         │ Yes
    ┌────▼─────────────────┐
    │ Auto-Rollback        │
    │ - DB migrate rollback│
    │ - Git checkout       │
    │ - Config restore     │
    └──────────────────────┘
```

### **Real-World Scenario**

**Situation:** Database backup job starts failing

**Timeline:**

**08:00 AM** - Backup job fails (first time)
- Severity: Low
- Action: Email sent to admin
- Auto-recovery: Immediate retry (successful)

**09:00 AM** - Backup fails again
- Severity: Medium (2 consecutive failures)
- Action: Email + Slack notification
- Auto-recovery: Cache clear + retry (failed)

**10:00 AM** - Third failure
- Severity: High (3 consecutive failures)
- Anomaly detection: Failure rate spike detected (3x baseline)
- Actions:
  - Email + Slack + SMS to on-call engineer
  - PagerDuty incident created
  - Custom webhook dispatched to Zapier → Creates Jira ticket
  - Risk score: 14 (High)

**10:05 AM** - Engineer acknowledges PagerDuty incident

**10:15 AM** - Investigation reveals disk space full

**10:30 AM** - Engineer frees disk space, manually triggers backup

**10:35 AM** - Backup succeeds
- PagerDuty incident auto-resolved
- Webhook dispatched: `backup.verified` event
- Anomaly detector notes recovery

**Post-Incident:**
- Incident report generated
- Anomaly trends updated
- Team notified via Slack summary

---

## 📊 **Dashboard Integration**

### **Filament Widgets to Create**

1. **PagerDutyStatusWidget** - Active incidents, on-call engineer
2. **AnomalyDetectionWidget** - Risk scores, trending anomalies
3. **DeploymentStatusWidget** - Last deployment, rollback availability
4. **WebhookDeliveryWidget** - Delivery success rates, active webhooks

### **Example: Anomaly Detection Widget**

```php
namespace App\Filament\Widgets;

use App\Services\AnomalyDetector;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnomalyDetectionWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $detector = app(AnomalyDetector::class);
        $stats = $detector->getAnomalyStats();
        
        return [
            Stat::make('Critical Anomalies', $stats['critical_anomalies'])
                ->description('Requires immediate attention')
                ->color('danger'),
            
            Stat::make('High Risk Jobs', count($stats['most_affected_jobs']))
                ->description('Jobs with risk score >10')
                ->color('warning'),
            
            Stat::make('Total Anomalies', 
                $stats['critical_anomalies'] + 
                $stats['high_anomalies'] + 
                $stats['medium_anomalies'])
                ->description('Last 24 hours')
                ->color('primary'),
        ];
    }
}
```

---

## 💰 **Cost Breakdown**

| Service | Cost | Notes |
|---------|------|-------|
| **PagerDuty** | $19-39/user/month | Professional incident management |
| **Anomaly Detection** | FREE | Built-in statistical analysis |
| **Deployment Rollback** | FREE | Uses existing Git infrastructure |
| **Custom Webhooks** | FREE | Depends on third-party service costs |
| **Zapier** | $20-50/month | If using premium Zaps |
| **Make.com** | $9-29/month | If using premium scenarios |
| **n8n** | FREE | Self-hosted option |

**Total Monthly Cost:**
- **Minimal**: $0 (open-source features only)
- **Professional**: $39-89/month (PagerDuty + Zapier)
- **Enterprise**: $100-200/month (Full stack)

---

## 🎓 **Implementation Roadmap**

### **Week 1: Foundation**
- [ ] Configure PagerDuty account
- [ ] Test PagerDuty integration
- [ ] Set up on-call rotation
- [ ] Configure basic webhooks (Slack/Discord)

### **Week 2: Advanced Monitoring**
- [ ] Run initial anomaly detection
- [ ] Tune detection thresholds
- [ ] Schedule automated detection (every 6 hours)
- [ ] Review anomaly reports daily

### **Week 3: Deployment Safety**
- [ ] Create backup directory
- [ ] Test manual rollback process
- [ ] Integrate backup creation into deployment scripts
- [ ] Enable auto-rollback in staging

### **Week 4: Third-Party Integrations**
- [ ] Set up Zapier/Make.com webhooks
- [ ] Build automation workflows
- [ ] Test webhook signature verification
- [ ] Document integration procedures

### **Week 5: Production Readiness**
- [ ] Enable auto-rollback in production (with caution)
- [ ] Conduct incident response drill
- [ ] Review and tune all configurations
- [ ] Train team on new tools

---

## 🐛 **Troubleshooting**

### **PagerDuty Issues**

**Problem: Incidents not creating**

Check:
1. Routing key correct in `.env`
2. Integration enabled in PagerDuty console
3. API key has proper permissions
4. Test with: `php artisan pagerduty:test`

**Problem: Notifications not received**

Check:
1. On-call schedule configured
2. Contact methods (phone/SMS) verified
3. Mobile app installed and logged in
4. Notification rules configured for service

### **Anomaly Detection Issues**

**Problem: No anomalies detected despite obvious issues**

Check:
1. Jobs have sufficient history (>10 runs)
2. Baseline period appropriate (30 days default)
3. Thresholds not too high
4. Run manually: `php artisan monitor:detect-anomalies`

**Problem: Too many false positives**

Adjust in `AnomalyDetector.php`:
```php
protected const ANOMALY_THRESHOLD = 3.0; // Increase from 2.5
protected const CONFIDENCE_HIGH = 0.98;  // Increase from 0.95
```

### **Rollback Issues**

**Problem: Rollback fails**

Check:
1. Git repository accessible
2. Backup exists for target deployment
3. Database migrations reversible
4. File permissions allow git operations

**Problem: Auto-rollback triggering unexpectedly**

Check:
1. `DEPLOYMENT_AUTO_ROLLBACK_ENABLED=false` in production
2. Job names don't contain deployment keywords
3. Severity classification accurate

### **Webhook Issues**

**Problem: Webhooks not delivered**

Check:
1. URL uses HTTPS
2. URL publicly accessible
3. Receiving service returns 200 OK
4. Check logs: `tail -f storage/logs/cron.log | grep webhook`

**Problem: Signature verification fails**

Check:
1. Secrets match exactly
2. Payload not modified
3. Using SHA-256 algorithm
4. Constant-time comparison (`hash_equals`)

---

## 📞 **Support & Resources**

### **Documentation**
- PagerDuty: [https://support.pagerduty.com/](https://support.pagerduty.com/)
- Laravel Queues: [https://laravel.com/docs/queues](https://laravel.com/docs/queues)
- Git Rollback: [https://git-scm.com/docs/git-checkout](https://git-scm.com/docs/git-checkout)

### **Community**
- Laravel Discord: [https://discord.gg/laravel](https://discord.gg/laravel)
- PagerDuty Community: [https://community.pagerduty.com/](https://community.pagerduty.com/)

### **Logs**
```bash
# Cron job logs
tail -f storage/logs/cron.log

# Application logs
tail -f storage/logs/laravel.log

# Webhook delivery
grep -i webhook storage/logs/cron.log
```

---

## 🎉 **Conclusion**

Your YG Account now has **enterprise-grade incident management** that rivals companies spending millions on monitoring infrastructure:

✅ **PagerDuty Integration** - Professional on-call management  
✅ **ML Anomaly Detection** - Proactive issue identification  
✅ **Auto Rollback** - Zero-downtime recovery  
✅ **Custom Webhooks** - Unlimited third-party integrations  
✅ **Complete Audit Trail** - Full incident history  
✅ **Automated Response** - Intelligent self-healing  
✅ **Team Collaboration** - Multi-channel notifications  
✅ **Production-Ready** - Tested, documented, optimized  

**You're now ready to manage mission-critical infrastructure with confidence!** 🚀🎊
