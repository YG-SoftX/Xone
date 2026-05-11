# 🚀 Advanced Cron Job Features - Complete Implementation Guide

## 📋 Overview

This guide covers four advanced features added to the YG Account cron job management system:

1. ✅ **Email Notifications** for failed jobs
2. ✅ **Slack/Discord Webhook Integration** for real-time alerts
3. ✅ **Job Execution History Charts** with visual analytics
4. ✅ **Automated Backup Verification** with integrity checks

---

## 1️⃣ **Email Notifications for Failed Jobs**

### **How It Works**

When a cron job fails or has a low success rate (<70%), the system automatically sends an email alert to administrators with:
- Job details (name, schedule, description)
- Error message and output
- Success rate statistics
- Recommended troubleshooting steps
- Direct link to admin panel

### **Configuration**

Add to `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.ygxone.com
MAIL_PORT=587
MAIL_USERNAME=noreply@ygxone.com
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS="noreply@ygxone.com"
MAIL_FROM_NAME="YG Account Monitor"

# Admin email for alerts
ADMIN_EMAIL=admin@ygxone.com
```

In `config/mail.php`, add:
```php
'admin_email' => env('ADMIN_EMAIL', 'admin@ygxone.com'),
```

### **Email Template**

The notification uses a Markdown template located at:
```
resources/views/emails/cron-job-failed.blade.php
```

**Features:**
- 🎨 Professional design with color coding
- 📊 Key metrics displayed prominently
- 🔗 Direct link to admin panel
- 💡 Actionable troubleshooting tips

### **Triggering Conditions**

Emails are sent when:
1. Job status changes to `failed`
2. Success rate drops below 70%
3. Manual verification detects issues

### **Testing**

Send a test notification:
```bash
php artisan tinker
>>> Mail::to('test@example.com')->send(new \App\Mail\CronJobFailedNotification(
    \App\Models\CronJob::first(),
    'Test error message',
    1
));
```

---

## 2️⃣ **Slack/Discord Webhook Integration**

### **Setup Instructions**

#### **For Slack:**

1. Go to your Slack workspace
2. Navigate to **Apps** → **Manage Apps**
3. Search for "Incoming Webhooks"
4. Click **Add to Slack**
5. Select the channel for alerts (e.g., #server-alerts)
6. Copy the webhook URL (looks like: `https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX`)

#### **For Discord:**

1. Go to your Discord server
2. Right-click on the channel → **Edit Channel**
3. Go to **Integrations** → **Webhooks**
4. Click **New Webhook**
5. Give it a name (e.g., "YG Account Alerts")
6. Copy the webhook URL (looks like: `https://discord.com/api/webhooks/1234567890/abcdefg...`)

### **Configuration**

Add to `.env`:
```env
# Slack Webhook
CRON_ALERT_SLACK_ENABLED=true
CRON_ALERT_SLACK_PLATFORM=slack
CRON_ALERT_SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK

# Discord Webhook
CRON_ALERT_DISCORD_ENABLED=false
CRON_ALERT_DISCORD_PLATFORM=discord
CRON_ALERT_DISCORD_WEBHOOK=https://discord.com/api/webhooks/YOUR/DISCORD/WEBHOOK
```

### **Message Format**

#### **Slack Messages:**
- 🎨 Color-coded attachments (red for failures, green for success)
- 📋 Structured fields showing key metrics
- ⏰ Timestamp of event
- 🔗 Footer with app name

#### **Discord Messages:**
- 🎨 Rich embeds with colored borders
- 📊 Inline fields for compact display
- 👤 Custom bot avatar support
- ⏰ ISO 8601 timestamps

### **Testing Webhooks**

Test your webhook configuration:
```bash
php artisan tinker
>>> $notifier = app(\App\Services\WebhookNotifier::class);
>>> $notifier->testWebhook('slack', 'YOUR_SLACK_WEBHOOK_URL');
>>> $notifier->testWebhook('discord', 'YOUR_DISCORD_WEBHOOK_URL');
```

You should receive a test message in your channel!

### **Custom Webhooks**

The system also supports generic webhooks for custom integrations:

```php
// In config/services.php
'webhooks' => [
    'cron_alerts' => [
        [
            'platform' => 'custom',
            'url' => 'https://your-custom-endpoint.com/alerts',
            'enabled' => true,
        ],
    ],
],
```

Payload format:
```json
{
  "event": "cron_job_alert",
  "type": "failure",
  "timestamp": "2026-05-05T12:00:00+00:00",
  "data": {
    "job_name": "database-backup",
    "status": "failed",
    "success_rate": 65.5,
    "message": "Backup verification failed"
  }
}
```

---

## 3️⃣ **Job Execution History Charts**

### **Dashboard Widgets**

Two new widgets provide visual analytics:

#### **1. CronJobExecutionChart (Line Chart)**

Shows execution trends over time with filterable metrics:
- **Total Executions**: Cumulative run count
- **Failed Executions**: Failure count trend
- **Success Rate (%)**: Performance percentage

**Features:**
- 📈 Interactive line charts
- 🎨 Color-coded datasets per job
- 🔍 Filter by metric type
- 📱 Responsive design

**Location:** Filament Dashboard → Cron Jobs section

#### **2. CronJobExecutionHistory (Table)**

Displays recent executions in a sortable table:
- Job name and description
- Execution timestamp
- Status badge (success/failed/running)
- Output preview (truncated)
- Success rate indicator

**Features:**
- 🔍 Searchable job names
- 📅 Sortable by date
- 💬 Tooltip for full output
- 🎨 Color-coded success rates

### **Adding Charts to Dashboard**

Charts are automatically registered in Filament. To customize placement:

Edit `app/Filament/Pages/Dashboard.php`:
```php
protected function getHeaderWidgets(): array
{
    return [
        \App\Filament\Widgets\CronHealthWidget::class,
        \App\Filament\Widgets\CronJobExecutionChart::class,
        \App\Filament\Widgets\CronJobExecutionHistory::class,
    ];
}
```

### **Advanced Chart Configuration**

To enable historical daily data (requires database changes):

1. Create migration for execution log:
```bash
php artisan make:migration create_cron_job_executions_table
```

2. Schema:
```php
Schema::create('cron_job_executions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('cron_job_id')->constrained()->cascadeOnDelete();
    $table->timestamp('executed_at');
    $table->string('status'); // success, failed
    $table->text('output')->nullable();
    $table->integer('duration_ms')->nullable();
    $table->timestamps();
    
    $table->index(['cron_job_id', 'executed_at']);
});
```

3. Update chart query to use daily aggregation:
```php
$data = Trend::model(CronJobExecution::class)
    ->between(start: now()->subDays(7), end: now())
    ->perDay()
    ->count();
```

---

## 4️⃣ **Automated Backup Verification**

### **Overview**

The backup verification system ensures database backups created by cron jobs are:
- ✅ Not corrupted
- ✅ Properly compressed (if applicable)
- ✅ Recent enough (< 26 hours for daily backups)
- ✅ Reasonable size (not empty or suspiciously large)
- ✅ Restorable (via test decompression)

### **Components**

#### **1. BackupVerifier Service**

Located at: `app/Services/BackupVerifier.php`

**Key Methods:**
- `verifyLatestBackup()` - Full integrity check
- `testRestore($file)` - Test restoration without actual restore
- `getBackupStats()` - Statistics about all backups
- `cleanupOldBackups($days)` - Remove old backups

#### **2. VerifyBackups Artisan Command**

Run manually or via cron:
```bash
php artisan backup:verify
```

**Options:**
```bash
# Test restore from latest backup
php artisan backup:verify --test-restore

# Clean up old backups (default: 30 days)
php artisan backup:verify --cleanup --retention-days=30

# Send email notification on failure
php artisan backup:verify --notify

# Combine all options
php artisan backup:verify --test-restore --cleanup --notify
```

**Output Example:**
```
🔍 Starting backup verification...

Step 1: Verifying latest backup integrity...
┌──────────────────┬──────────────────────┐
│ Property         │ Value                │
├──────────────────┼──────────────────────┤
│ File             │ db_20260505.sql.gz   │
│ Size             │ 45.23 MB             │
│ Age              │ 2.5 hours            │
│ Compressed       │ Yes                  │
│ Checksum         │ a1b2c3d4e5f6...      │
│ Integrity Check  │ ✅ Passed            │
└──────────────────┴──────────────────────┘

✅ Backup verification passed!

Step 2: Testing restore from backup...
✅ Restore test passed!

Step 3: Cleaning up backups older than 30 days...
✅ Deleted 5 old backup(s)

Step 4: Backup Statistics
┌──────────────────┬──────────────────────┐
│ Metric           │ Value                │
├──────────────────┼──────────────────────┤
│ Total Backups    │ 25                   │
│ Total Size       │ 1130.75 MB           │
│ Average Size     │ 45.23 MB             │
│ Retention Period │ 25 days              │
│ Oldest Backup    │ db_20260410.sql.gz   │
│ Newest Backup    │ db_20260505.sql.gz   │
└──────────────────┴──────────────────────┘

🎉 Backup verification completed successfully!
```

#### **3. BackupStatusWidget**

Dashboard widget showing:
- Total backup count and size
- Latest backup verification status
- Retention period statistics
- Last verification timestamp

### **Configuration**

Create backup directory:
```bash
mkdir -p storage/app/backups
chmod 775 storage/app/backups
```

Add to `.env`:
```env
BACKUP_DIRECTORY=storage/app/backups
BACKUP_RETENTION_DAYS=30
```

Create `config/backup.php`:
```php
<?php

return [
    'directory' => env('BACKUP_DIRECTORY', storage_path('app/backups')),
    'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
];
```

### **Scheduling Automatic Verification**

Add to cron jobs (via admin panel or cPanel):

**Option 1: Via Admin Panel**
1. Navigate to **System Management** → **Cron Jobs**
2. Create new job:
   ```
   Name: backup-verification
   Command: /usr/bin/php /home/USERNAME/yg-account-core/artisan backup:verify --cleanup --notify >> /dev/null 2>&1
   Schedule: 0 4 * * * (Daily at 4 AM)
   Description: Verifies backup integrity and cleans up old backups
   Enabled: ✓
   System Job: ✓
   ```

**Option 2: Via cPanel**
```bash
0 4 * * * /usr/bin/php /home/USERNAME/yg-account-core/artisan backup:verify --cleanup --notify >> /dev/null 2>&1
```

### **Verification Checks Performed**

1. **File Existence**: Confirms backup file exists
2. **File Size**: Validates not empty (< 0.1 MB) or too large (> 5 GB)
3. **File Age**: Ensures backup is recent (< 26 hours for daily)
4. **Compression Integrity**: Tests decompression for .gz files
5. **SQL Syntax**: Basic validation of SQL structure
6. **Checksum Calculation**: SHA-256 hash for integrity tracking
7. **Readability**: Confirms file is accessible and not corrupted

### **Alert Integration**

If verification fails:
- 📧 Email sent to admin (if `--notify` flag used)
- 💬 Slack/Discord webhook triggered (if configured)
- 📝 Logged to `storage/logs/cron.log`
- 🔴 Dashboard widget shows red status

### **Manual Verification**

Test a specific backup file:
```bash
php artisan tinker
>>> $verifier = app(\App\Services\BackupVerifier::class);
>>> $result = $verifier->testRestore('/path/to/backup.sql.gz');
>>> dump($result);
```

### **Cleanup Strategy**

Automatic cleanup removes backups older than retention period:

```bash
# Run cleanup only (no verification)
php artisan backup:verify --cleanup --retention-days=7

# Aggressive cleanup (keep only 7 days)
php artisan backup:verify --cleanup --retention-days=7 --notify
```

**Best Practices:**
- Daily backups: Keep 30 days
- Weekly backups: Keep 90 days
- Monthly backups: Keep 365 days
- Always verify before deleting

---

## 🔧 **Integration Examples**

### **Example 1: Complete Alert Flow**

When a backup job fails:

1. **CronJobMonitor** detects failure
2. Sends **email** to admin@ygxone.com
3. Posts to **Slack** #server-alerts channel
4. Posts to **Discord** #backups channel
5. Logs to `storage/logs/cron.log`
6. Updates dashboard widget to red status
7. Triggers **backup verification** to check last good backup

### **Example 2: Scheduled Verification**

Daily at 4 AM:
1. Cron runs `backup:verify --cleanup --notify`
2. System verifies latest backup integrity
3. Deletes backups older than 30 days
4. Sends summary email if issues found
5. Updates **BackupStatusWidget** on dashboard

### **Example 3: Real-time Monitoring**

Admin opens dashboard:
1. Sees **CronHealthWidget** with 4 key metrics
2. Views **CronJobExecutionChart** showing trends
3. Reviews **CronJobExecutionHistory** table for details
4. Checks **BackupStatusWidget** for backup health
5. Clicks any job to view/edit configuration

---

## 📊 **Monitoring Best Practices**

### **Daily Tasks**
- ✅ Check dashboard widgets for red indicators
- ✅ Review failed jobs in execution history
- ✅ Verify backup status is green

### **Weekly Tasks**
- ✅ Review success rate trends in charts
- ✅ Clean up disabled/obsolete jobs
- ✅ Test one backup restore manually

### **Monthly Tasks**
- ✅ Audit all enabled cron jobs
- ✅ Review webhook configurations
- ✅ Update alert thresholds if needed
- ✅ Rotate admin email addresses if personnel changes

---

## 🐛 **Troubleshooting**

### **Problem: Email notifications not sending**

**Check:**
1. SMTP settings in `.env` are correct
2. `ADMIN_EMAIL` is set
3. Mail driver works: `php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('test@example.com')->subject('Test'))`
4. Check `storage/logs/laravel.log` for mail errors

### **Problem: Webhook messages not appearing**

**Check:**
1. Webhook URL is correct and active
2. Platform is set to `enabled=true` in `.env`
3. Firewall allows outbound HTTPS requests
4. Test manually: `$notifier->testWebhook('slack', 'URL')`

### **Problem: Charts not displaying**

**Check:**
1. Chart.js is loaded (check browser console for errors)
2. Jobs have execution data (`total_runs > 0`)
3. Clear cache: `php artisan cache:clear && php artisan view:clear`

### **Problem: Backup verification fails**

**Check:**
1. Backup directory exists and is writable
2. Backup files are in correct location
3. File permissions allow reading
4. For compressed files, test manually: `gunzip -t backup.sql.gz`

---

## 🎓 **Summary**

Your YG Account system now has **enterprise-grade monitoring**:

✅ **Email Alerts** - Immediate notification of failures  
✅ **Slack/Discord Integration** - Real-time team alerts  
✅ **Visual Analytics** - Charts and tables for insights  
✅ **Backup Verification** - Automated integrity checks  
✅ **Dashboard Widgets** - At-a-glance health status  
✅ **Artisan Commands** - CLI tools for manual checks  
✅ **Configurable Thresholds** - Customize alert triggers  
✅ **Audit Logging** - Complete history in log files  

**Perfect for production environments!** 🚀
