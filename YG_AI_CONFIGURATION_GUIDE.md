# YG AI Configuration - Admin Panel Management Guide

**Date**: May 4, 2026  
**Status**: ✅ **FULLY MANAGEABLE FROM ADMIN PANEL**  

---

## 🎯 Overview

YG AI service configuration is now **fully manageable from the admin panel**. No manual `.env` editing required!

**Admin Panel Location**: `https://account.ygxone.com/admin/environment-config`

---

## ⚙️ Configuration Fields

### 1. **AI Service URL** (Required)
- **Field**: `YG_AI_URL`
- **Default**: `https://ai.ygxone.com`
- **Validation**: Must be valid URL
- **Purpose**: Base URL for all AI API calls

**Example Values**:
```
Production: https://ai.ygxone.com
Staging: https://ai-staging.ygxone.com
Development: http://localhost:8080
```

---

### 2. **API Key** (Optional)
- **Field**: `YG_AI_API_KEY`
- **Default**: Empty (no authentication)
- **Security**: Masked in UI (password field)
- **Purpose**: Authentication token if AI service requires it

**When to Use**:
- If your YG AI deployment uses API key authentication
- Leave blank for public/self-hosted instances

---

### 3. **Request Timeout** (Optional)
- **Field**: `YG_AI_TIMEOUT`
- **Default**: `5` seconds
- **Range**: 1-30 seconds
- **Purpose**: Maximum wait time for AI responses

**Recommendations**:
- **Fast network**: 3-5 seconds
- **Standard**: 5-10 seconds
- **Slow/unreliable**: 10-15 seconds
- **Max**: 30 seconds (prevents hanging requests)

---

## 🔧 How to Configure

### Step 1: Access Admin Panel
1. Log in as Super Admin
2. Navigate to: **Admin → System Configuration**
3. Or direct URL: `/admin/environment-config`

---

### Step 2: Locate YG AI Section
Scroll down to **"YG AI Service"** section (purple gradient card with robot icon)

You'll see:
```
┌─────────────────────────────────────────────┐
│  🤖 YG AI Service                           │
│  Artificial Intelligence integration        │
├─────────────────────────────────────────────┤
│  ℹ️ YG AI Features:                         │
│  • Smart email reply suggestions            │
│  • Automatic email categorization           │
│  • Document writing assistance              │
│  • Natural language calendar events         │
│  • Contact enrichment                       │
│  • Text summarization & sentiment analysis  │
└─────────────────────────────────────────────┘
```

---

### Step 3: Enter Configuration
Fill in the fields:

**AI Service URL**:
```
https://ai.ygxone.com
```

**API Key** (if required):
```
your_api_key_here
```

**Request Timeout**:
```
5
```

---

### Step 4: Save Configuration
Click **"Save Configuration"** button at bottom of page

**System will automatically**:
1. ✅ Update `.env` file
2. ✅ Clear config cache (`php artisan config:clear`)
3. ✅ Clear application cache (`php artisan cache:clear`)
4. ✅ Apply changes immediately (no restart needed!)

---

## 📊 Integration Status Dashboard

The admin panel shows real-time integration status:

```
Integration Status:
┌──────────┬──────────┬──────────┬──────────┐
│ YG Mail  │ YG DocX  │ Calendar │ Contacts │
│SmartReply│Writing   │ NLP      │Enrichment│
│          │ Help     │ Events   │          │
└──────────┴──────────┴──────────┴──────────┘
```

All services automatically use the configured YG AI URL.

---

## 🧪 Testing Configuration

### Test 1: Verify Connectivity

After saving, test the connection:

```bash
# From server terminal
curl https://ai.ygxone.com/api/?action=status
```

Expected response:
```json
{
  "status": "ok",
  "model": "default",
  "version": "1.0"
}
```

---

### Test 2: Test Smart Reply

In YG Mail, open any email and click **"Load Suggestions"**.

If working correctly, you'll see 3 reply suggestions appear within 2-3 seconds.

---

### Test 3: Check Logs

Monitor AI service calls:

```bash
tail -f storage/logs/laravel.log | grep "YG AI"
```

Look for:
```
[2026-05-04 10:30:15] INFO: YG AI API Call {"method":"generateSmartReply","duration_ms":1234,"cache_hit":false}
```

---

## 🔐 Security Best Practices

### 1. **Use HTTPS Only**
- ❌ Never use `http://` in production
- ✅ Always use `https://ai.ygxone.com`
- ✅ Ensure SSL certificate is valid

---

### 2. **Protect API Keys**
- Keys are masked in admin panel (password field)
- Never share API keys via email/chat
- Rotate keys every 90 days
- Store backup keys securely (password manager)

---

### 3. **Rate Limiting**
Configure rate limits on YG AI service:

```php
// In YG AI service .env
RATE_LIMIT_PER_MINUTE=60
RATE_LIMIT_PER_HOUR=1000
```

Prevents abuse and ensures fair usage.

---

### 4. **Access Control**
Only Super Admins can modify YG AI configuration:

```php
// Middleware check in controller
public function __construct()
{
    $this->middleware('auth');
    $this->middleware('role:super-admin');
}
```

---

## 🚀 Deployment Scenarios

### Scenario 1: Self-Hosted YG AI

**Configuration**:
```
YG_AI_URL: http://ai.internal.local:8080
YG_AI_API_KEY: (leave blank)
YG_AI_TIMEOUT: 3
```

**Benefits**:
- Full data privacy
- No external dependencies
- Lower latency (internal network)

---

### Scenario 2: Cloud-Hosted YG AI

**Configuration**:
```
YG_AI_URL: https://ai.ygxone.com
YG_AI_API_KEY: sk_live_xxxxxxxxxxxx
YG_AI_TIMEOUT: 5
```

**Benefits**:
- Managed infrastructure
- Auto-scaling
- High availability

---

### Scenario 3: Multi-Environment Setup

**Development**:
```
YG_AI_URL: http://localhost:8080
YG_AI_TIMEOUT: 10
```

**Staging**:
```
YG_AI_URL: https://ai-staging.ygxone.com
YG_AI_TIMEOUT: 5
```

**Production**:
```
YG_AI_URL: https://ai.ygxone.com
YG_AI_TIMEOUT: 5
```

---

## 📈 Monitoring & Analytics

### Track These Metrics

**1. API Call Volume**
```sql
SELECT COUNT(*) as total_calls, 
       DATE(created_at) as date
FROM ai_api_logs
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

**2. Average Response Time**
```sql
SELECT AVG(duration_ms) as avg_response_time,
       method
FROM ai_api_logs
WHERE created_at >= NOW() - INTERVAL 24 HOUR
GROUP BY method;
```

**3. Cache Hit Rate**
```sql
SELECT 
    SUM(CASE WHEN cache_hit = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as cache_hit_rate
FROM ai_api_logs
WHERE created_at >= NOW() - INTERVAL 24 HOUR;
```

**Target**: >70% cache hit rate

---

### Admin Dashboard Widget

Add this to your admin dashboard:

```blade
<!-- resources/views/filament/widgets/ai-stats-widget.blade.php -->
<div class="bg-white rounded-xl shadow p-6">
    <h3 class="text-lg font-semibold mb-4">🤖 YG AI Stats</h3>
    
    <div class="grid grid-cols-2 gap-4">
        <div class="text-center">
            <p class="text-3xl font-bold text-purple-600">{{ $totalCalls }}</p>
            <p class="text-sm text-gray-500">Today's Calls</p>
        </div>
        
        <div class="text-center">
            <p class="text-3xl font-bold text-green-600">{{ $avgResponseTime }}ms</p>
            <p class="text-sm text-gray-500">Avg Response</p>
        </div>
        
        <div class="text-center">
            <p class="text-3xl font-bold text-blue-600">{{ $cacheHitRate }}%</p>
            <p class="text-sm text-gray-500">Cache Hit Rate</p>
        </div>
        
        <div class="text-center">
            <p class="text-3xl font-bold text-red-600">{{ $errorRate }}%</p>
            <p class="text-sm text-gray-500">Error Rate</p>
        </div>
    </div>
</div>
```

---

## 🛠️ Troubleshooting

### Problem 1: "Connection Timed Out"

**Symptoms**:
- Error: `cURL error 28: Connection timed out`
- Slow or no AI responses

**Solutions**:
1. Increase timeout in admin panel (try 10-15 seconds)
2. Check network connectivity: `ping ai.ygxone.com`
3. Verify firewall allows outbound HTTPS
4. Check YG AI service is running

---

### Problem 2: "Invalid API Key"

**Symptoms**:
- Error: `401 Unauthorized`
- AI features not working

**Solutions**:
1. Verify API key in admin panel matches YG AI service
2. Check for extra spaces in key
3. Regenerate API key in YG AI admin panel
4. Ensure key hasn't expired

---

### Problem 3: "Service Unavailable"

**Symptoms**:
- Error: `503 Service Unavailable`
- All AI calls failing

**Solutions**:
1. Check YG AI service status page
2. Verify DNS resolution: `nslookup ai.ygxone.com`
3. Check SSL certificate validity
4. Contact YG AI service administrator

---

### Problem 4: Configuration Not Saving

**Symptoms**:
- Changes revert after save
- Old values persist

**Solutions**:
1. Check file permissions on `.env`: `chmod 644 .env`
2. Verify web server user owns `.env` file
3. Clear browser cache (Ctrl+F5)
4. Check Laravel logs for errors

---

## 📝 Audit Trail

All configuration changes are logged:

**Log Location**: `storage/logs/audit.log`

**Example Entry**:
```
[2026-05-04 10:30:15] AUDIT: Environment Config Updated
User: admin@ygxone.com
IP: 192.168.1.100
Changes:
  - YG_AI_URL: https://old.ai.ygxone.com → https://ai.ygxone.com
  - YG_AI_TIMEOUT: 3 → 5
Action: config:clear executed
Cache cleared: Yes
```

---

## 🔄 Backup & Restore

### Backup Configuration

Before making changes, backup current settings:

```bash
# Backup .env file
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Export current config
php artisan env:export > config_backup.json
```

---

### Restore Configuration

If something goes wrong:

```bash
# Restore from backup
cp .env.backup.20260504_103015 .env

# Clear cache
php artisan config:clear
php artisan cache:clear

# Restart queue workers
php artisan queue:restart
```

---

## 🎯 Best Practices

### 1. **Test Before Production**
- Always test new configuration in staging first
- Verify all AI features work after changes
- Monitor error rates for 24 hours

---

### 2. **Document Changes**
Keep a changelog:

```markdown
## YG AI Configuration Changes

### 2026-05-04
- Changed YG_AI_URL to https://ai.ygxone.com
- Increased timeout from 3s to 5s
- Reason: Improved reliability
- Tested by: John Doe
- Approved by: Jane Smith
```

---

### 3. **Monitor Performance**
Set up alerts for:
- Response time > 5 seconds
- Error rate > 5%
- Cache hit rate < 50%

---

### 4. **Regular Maintenance**
- Review configuration monthly
- Rotate API keys quarterly
- Update timeout based on performance data
- Clean up old audit logs (>90 days)

---

## 📞 Support

**Issues?** Contact:
- **Email**: ai-support@ygxone.com
- **Documentation**: https://ai.ygxone.com/docs
- **Status Page**: https://status.ygxone.com
- **Emergency**: +1-XXX-XXX-XXXX

---

## ✅ Checklist

Before going live with YG AI:

- [ ] YG AI service URL configured in admin panel
- [ ] API key set (if required)
- [ ] Timeout optimized for your network
- [ ] Connectivity tested (`curl` test)
- [ ] Smart reply working in YG Mail
- [ ] Email categorization active
- [ ] Monitoring dashboard configured
- [ ] Alert thresholds set
- [ ] Backup created
- [ ] Team trained on admin panel usage

---

**Configuration Method**: ✅ **Admin Panel Only** (No manual `.env` editing!)  
**Last Updated**: May 4, 2026  
**Version**: 1.0.0
