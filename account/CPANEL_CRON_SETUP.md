# ╔══════════════════════════════════════════════════════╗
# ║  YG Account - cPanel Cron Job Setup Guide          ║
# ║  Essential for scheduled tasks on shared hosting    ║
# ╚══════════════════════════════════════════════════════╝

## 📍 Where to Add Cron Jobs

1. Login to cPanel
2. Navigate to: **Advanced** → **Cron Jobs**
3. Add each command below

---

## ⏰ Required Cron Jobs

### 1. Laravel Scheduler (CRITICAL - Run Every Minute)

This runs all scheduled tasks (cleanup, analytics, invoices, etc.)

```bash
* * * * * /usr/bin/php /home/YOUR_USERNAME/yg-account-core/artisan schedule:run >> /dev/null 2>&1
```

**Replace:** `YOUR_USERNAME` with your actual cPanel username

**Example:** If your cPanel login is `ygxone`, use:
```bash
* * * * * /usr/bin/php /home/ygxone/yg-account-core/artisan schedule:run >> /dev/null 2>&1
```

---

### 2. Queue Worker (OPTIONAL - Only if using database queue)

If you changed `QUEUE_CONNECTION=database` in `.env`, add this:

```bash
*/5 * * * * /usr/bin/php /home/YOUR_USERNAME/yg-account-core/artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

**Note:** For most cPanel setups, keep `QUEUE_CONNECTION=sync` and skip this cron job.

---

### 3. Log Rotation (Weekly Cleanup)

Prevents disk space issues from large log files:

```bash
0 0 * * 0 find /home/YOUR_USERNAME/yg-account-core/storage/logs -name "*.log" -mtime +7 -delete
```

This deletes log files older than 7 days every Sunday at midnight.

---

### 4. Session Cleanup (Daily)

Removes expired database sessions:

```bash
0 2 * * * /usr/bin/php /home/YOUR_USERNAME/yg-account-core/artisan session:table >> /dev/null 2>&1
```

Runs daily at 2 AM to clean up old sessions.

---

## 🔍 Finding Your cPanel Username

1. Look at the top-right corner of cPanel dashboard
2. Or check the URL: `https://yourdomain.com:2083/home/YOUR_USERNAME`
3. Or via FTP: The root directory is usually `/home/YOUR_USERNAME/`

---

## 📁 Finding PHP Path

Most cPanel hosts use one of these paths:

```bash
/usr/bin/php           # Most common
/usr/local/bin/php     # Alternative
/opt/cpanel/ea-php82/root/usr/bin/php  # EasyApache PHP 8.2
/opt/cpanel/ea-php83/root/usr/bin/php  # EasyApache PHP 8.3
```

To find the exact path, create a test file `test.php` in public_html:
```php
<?php echo PHP_BINARY; ?>
```

Visit `https://yourdomain.com/test.php` to see the path.

---

## ✅ Testing Cron Jobs

After adding cron jobs, verify they work:

### Test Laravel Scheduler:
```bash
# Manually run once to test
/usr/bin/php /home/YOUR_USERNAME/yg-account-core/artisan schedule:run
```

Expected output:
```
Running scheduled command: Queuing JobName
No scheduled commands are ready to run.
```

### Check Cron Logs:
In cPanel → **Cron Jobs** → Scroll down to see "Current Cron Jobs" and last execution times.

---

## 🛠️ Troubleshooting

### Problem: Cron job not running

**Solutions:**
1. Verify PHP path is correct
2. Check file permissions (artisan should be executable)
3. Ensure full path to artisan is used (not relative)
4. Check cPanel email for error notifications

### Problem: Permission denied

**Solution:**
```bash
chmod +x /home/YOUR_USERNAME/yg-account-core/artisan
```

### Problem: Memory limit errors

**Solution:** Increase memory in cron command:
```bash
* * * * * /usr/bin/php -d memory_limit=512M /home/YOUR_USERNAME/yg-account-core/artisan schedule:run >> /dev/null 2>&1
```

### Problem: Too many emails from cron

**Solution:** The `>> /dev/null 2>&1` at the end suppresses output. If you want emails only on errors:
```bash
* * * * * /usr/bin/php /home/YOUR_USERNAME/yg-account-core/artisan schedule:run >> /dev/null 2>&1
```

---

## 📊 Monitoring Cron Execution

### Option 1: Check Laravel Logs
```bash
tail -f /home/YOUR_USERNAME/yg-account-core/storage/logs/laravel.log
```

Look for entries like:
```
[2026-05-05 12:00:01] local.INFO: Running scheduled command
```

### Option 2: Create a Test Command

Create `app/Console/Commands/TestCron.php`:
```php
<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestCron extends Command
{
    protected $signature = 'test:cron';
    protected $description = 'Test cron job execution';

    public function handle()
    {
        \Log::info('Cron job executed at: ' . now()->toDateTimeString());
        return Command::SUCCESS;
    }
}
```

Register in `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('test:cron')->everyMinute();
}
```

Then check `storage/logs/laravel.log` for entries.

---

## 🎯 Summary Checklist

- [ ] Found cPanel username
- [ ] Verified PHP binary path
- [ ] Added Laravel Scheduler cron job (every minute)
- [ ] Added log rotation cron job (weekly)
- [ ] Added session cleanup cron job (daily)
- [ ] Tested cron execution manually
- [ ] Verified logs show scheduled tasks running
- [ ] Set up email notifications for cron errors (optional)

---

## 📞 Support

If cron jobs still don't work:
1. Contact your cPanel hosting provider
2. Ask if cron jobs are enabled on your plan
3. Request the correct PHP binary path
4. Ask about any restrictions on cron frequency

Some budget hosts limit cron jobs to once per hour on shared plans.
