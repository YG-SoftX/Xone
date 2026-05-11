# 🔗 YG Console ↔ YG Master Integration Guide

## ✅ **COMPLETE INTEGRATION BUILT**

I have successfully built the complete integration between **YG Console** and **YG Master** for centralized ecosystem management.

---

## 📊 **INTEGRATION ARCHITECTURE**

```
┌─────────────────────────────────────────┐
│         YG MASTER (Central Hub)         │
│       master.ygxone.com                 │
├─────────────────────────────────────────┤
│  • Service Registry                     │
│  • Health Monitoring                    │
│  • Metrics Aggregation                  │
│  • Deployment Commands                  │
│  • Configuration Management             │
└──────────────┬──────────────────────────┘
               │ HTTPS API
               │ (Bearer Token Auth)
               ▼
┌─────────────────────────────────────────┐
│        YG CONSOLE (This Platform)       │
│       console.ygxone.com                │
├─────────────────────────────────────────┤
│  • Auto-registration                    │
│  • Heartbeat (every 5 min)              │
│  • Metrics reporting (hourly)           │
│  • Command execution                    │
│  • Health checks                        │
└─────────────────────────────────────────┘
```

---

## 🎯 **WHAT'S BEEN BUILT**

### **1. YgMasterService** ✅
**File:** `app/Services/YgMasterService.php`

**Features:**
- ✅ Service registration with YG Master
- ✅ Heartbeat monitoring (system health status)
- ✅ Comprehensive metrics reporting
- ✅ Configuration fetching from Master
- ✅ Deployment command handling
- ✅ Automatic retry logic
- ✅ Detailed logging

**Methods:**
```php
registerConsole()          // Register this instance
sendHeartbeat()            // Send health status
reportMetrics()            // Report usage statistics
fetchConfiguration()       // Get config from Master
handleDeploymentCommand()  // Execute remote commands
```

### **2. YgMasterController** ✅
**File:** `app/Http/Controllers/Api/YgMasterController.php`

**Endpoints:**
- `POST /api/v1/master/commands` - Receive deployment commands
- `GET /api/v1/health` - Health check for monitoring

**Security:**
- ✅ HMAC SHA256 signature verification
- ✅ IP whitelisting support
- ✅ Timestamp validation
- ✅ Detailed audit logging

### **3. Artisan Commands** ✅

#### **Register Command:**
```bash
php artisan yg-master:register
```
Registers this YG Console instance with YG Master

#### **Heartbeat Command:**
```bash
php artisan yg-master:heartbeat
```
Sends health status to YG Master

#### **Metrics Command:**
```bash
php artisan yg-master:report-metrics
```
Reports comprehensive usage statistics

### **4. Scheduled Tasks** ✅
**File:** `routes/console.php`

**Schedule:**
- ⏰ **Every 5 minutes:** Send heartbeat
- ⏰ **Every hour:** Report metrics
- ⏰ **Daily at midnight:** Clean expired API keys
- ⏰ **Monthly on 1st:** Generate invoices
- ⏰ **Weekly:** Clean webhook logs
- ⏰ **Monthly:** Archive inactive projects

---

## 📈 **METRICS REPORTED TO YG MASTER**

### **Projects:**
- Total projects count
- Active projects count
- Projects created today

### **API Keys:**
- Total API keys
- Active API keys
- Keys revoked today

### **Billing:**
- Revenue today
- Pending invoices
- Active subscriptions

### **AI Usage:**
- Total tokens processed today
- Total cost today
- AI requests today

### **Webhooks:**
- Total endpoints configured
- Active endpoints
- Deliveries today
- Failed deliveries today

### **System Health:**
- Queue size
- Cache hit rate
- Average response time
- Service uptime

---

## 🔐 **SECURITY FEATURES**

### **Authentication:**
✅ Bearer token authentication  
✅ HMAC SHA256 signature verification  
✅ Timestamp-based replay protection  
✅ IP whitelist validation  

### **Authorization:**
✅ Role-based command execution  
✅ Audit logging for all commands  
✅ Rate limiting on endpoints  

### **Data Protection:**
✅ HTTPS-only communication  
✅ Encrypted sensitive data  
✅ No credentials in logs  

---

## ⚙️ **CONFIGURATION REQUIRED**

Add these variables to your `.env` file:

```env
# YG Master Integration
YG_MASTER_URL=https://master.ygxone.com
YG_MASTER_API_KEY=your_master_api_key_here
YG_MASTER_WEBHOOK_SECRET=your_webhook_secret_here
YG_MASTER_TIMEOUT=10
```

---

## 🚀 **DEPLOYMENT STEPS**

### **Step 1: Configure Environment**
```bash
# Edit .env file
nano .env

# Add YG Master credentials
YG_MASTER_URL=https://master.ygxone.com
YG_MASTER_API_KEY=mk_live_xxxxxxxxxxxx
YG_MASTER_WEBHOOK_SECRET=whsec_xxxxxxxxxxxx
```

### **Step 2: Register with YG Master**
```bash
php artisan yg-master:register
```

**Expected Output:**
```
Registering YG Console with YG Master...
✓ Successfully registered with YG Master
Service ID: svc_console_abc123
```

### **Step 3: Verify Scheduling**
```bash
php artisan schedule:list
```

**Should show:**
```
* * * * *  php artisan schedule:run >> /dev/null 2>&1
*/5 * * * *  yg-master:heartbeat
0 * * * *    yg-master:report-metrics
0 0 * * *    api-keys:cleanup
0 0 1 * *    billing:generate-invoices
0 0 * * 0    webhooks:cleanup
0 0 1 * *    projects:archive-inactive
```

### **Step 4: Setup Cron Job**
```bash
# Edit crontab
crontab -e

# Add this line
* * * * * cd /path/to/yg-console && php artisan schedule:run >> /dev/null 2>&1
```

### **Step 5: Test Health Endpoint**
```bash
curl https://console.ygxone.com/api/v1/health
```

**Expected Response:**
```json
{
  "status": "healthy",
  "timestamp": "2026-05-07T23:59:59Z",
  "version": "1.0.0",
  "uptime_seconds": 86400,
  "metrics": {
    "active_projects": 42,
    "api_calls_today": 1523,
    "queue_size": 3
  }
}
```

---

## 📊 **MONITORING DASHBOARD (YG Master)**

Once integrated, YG Master will display:

### **Service Status:**
- 🟢 Healthy / 🔴 Down / 🟡 Degraded
- Uptime percentage
- Last heartbeat timestamp

### **Performance Metrics:**
- Requests per second
- Average response time
- Error rate
- Queue depth

### **Usage Statistics:**
- Active developers
- Total API calls
- Revenue generated
- Storage used

### **Alerts:**
- Service downtime
- High error rates
- Queue backlog
- Resource exhaustion

---

## 🔧 **DEPLOYMENT COMMANDS FROM YG MASTER**

YG Master can send these commands:

### **1. Restart Service:**
```json
{
  "action": "restart"
}
```

### **2. Clear Cache:**
```json
{
  "action": "clear_cache"
}
```

### **3. Run Migrations:**
```json
{
  "action": "run_migrations"
}
```

### **4. Update Configuration:**
```json
{
  "action": "update_config",
  "config": {
    "services.ai.rate_limit": 1000,
    "mail.driver": "smtp"
  }
}
```

---

## 🧪 **TESTING THE INTEGRATION**

### **Test Registration:**
```bash
php artisan yg-master:register
```

### **Test Heartbeat:**
```bash
php artisan yg-master:heartbeat
```

### **Test Metrics Reporting:**
```bash
php artisan yg-master:report-metrics
```

### **Test Webhook Signature:**
```bash
curl -X POST https://console.ygxone.com/api/v1/master/commands \
  -H "Content-Type: application/json" \
  -H "X-Master-Signature: invalid_signature" \
  -d '{"action":"test"}'
```

**Expected:** `401 Unauthorized`

### **Test Health Check:**
```bash
curl https://console.ygxone.com/api/v1/health
```

**Expected:** `200 OK` with JSON response

---

## 📝 **LOGGING & AUDITING**

All YG Master interactions are logged:

### **Log Location:**
```
storage/logs/laravel.log
```

### **Log Entries:**
```
[2026-05-07 23:59:59] INFO: YG Console registered with YG Master {"service_id":"svc_console_abc123"}
[2026-05-08 00:04:59] INFO: Heartbeat sent to YG Master
[2026-05-08 01:00:00] INFO: Metrics reported to YG Master
[2026-05-08 02:15:30] WARNING: Received deployment command from YG Master {"command":{"action":"clear_cache"}}
[2026-05-08 02:15:31] INFO: Cache cleared by YG Master command
```

---

## 🔄 **AUTOMATED WORKFLOW**

```
┌─────────────┐
│ YG Console  │
│   Starts    │
└──────┬──────┘
       │
       ▼
┌─────────────────────┐
│ Register with YG    │
│ Master (on boot)    │
└──────┬──────────────┘
       │
       ▼
┌─────────────────────┐
│ Every 5 minutes:    │
│ Send Heartbeat      │◄────────┐
└──────┬──────────────┘         │
       │                        │
       ▼                        │
┌─────────────────────┐         │
│ Every hour:         │         │
│ Report Metrics      │         │
└──────┬──────────────┘         │
       │                        │
       ▼                        │
┌─────────────────────┐         │
│ YG Master monitors  │         │
│ health & metrics    │         │
└──────┬──────────────┘         │
       │                        │
       │ If issue detected      │
       ▼                        │
┌─────────────────────┐         │
│ YG Master sends     │         │
│ remediation command │─────────┘
└─────────────────────┘
```

---

## ✅ **INTEGRATION CHECKLIST**

- [ ] `.env` configured with YG Master credentials
- [ ] Service registered: `php artisan yg-master:register`
- [ ] Cron job setup for scheduler
- [ ] Health endpoint accessible
- [ ] Webhook signature verification working
- [ ] Heartbeat running every 5 minutes
- [ ] Metrics reporting hourly
- [ ] Logs showing successful communication
- [ ] YG Master dashboard showing service as healthy

---

## 🎊 **BENEFITS OF YG MASTER INTEGRATION**

### **For Developers:**
✅ Centralized monitoring across all YG services  
✅ Automated health checks and alerts  
✅ One-click deployments from Master panel  
✅ Unified metrics dashboard  

### **For Operations:**
✅ Real-time service status visibility  
✅ Automated incident response  
✅ Performance trend analysis  
✅ Capacity planning insights  

### **For Business:**
✅ Consolidated revenue tracking  
✅ Cross-service user analytics  
✅ Ecosystem-wide usage patterns  
✅ Strategic decision-making data  

---

## 🚦 **STATUS**

| Component | Status |
|-----------|--------|
| YgMasterService | ✅ COMPLETE |
| YgMasterController | ✅ COMPLETE |
| Artisan Commands | ✅ COMPLETE (3 commands) |
| Scheduled Tasks | ✅ COMPLETE (6 tasks) |
| API Endpoints | ✅ COMPLETE (2 endpoints) |
| Security | ✅ COMPLETE (HMAC + Bearer) |
| Logging | ✅ COMPLETE |
| Documentation | ✅ COMPLETE |

---

## 🎯 **NEXT STEPS**

1. **Get YG Master API credentials** from your YG Master admin panel
2. **Configure `.env`** with the credentials
3. **Run registration command:** `php artisan yg-master:register`
4. **Verify in YG Master dashboard** that YG Console appears as healthy
5. **Monitor logs** for successful heartbeats and metrics reports

---

**Your YG Console is now fully integrated with YG Master!** 🎉

The platform will automatically:
- 📡 Report health every 5 minutes
- 📊 Send metrics every hour
- 🔧 Accept deployment commands
- 📋 Maintain audit logs

**Ready for production deployment!** 🚀✨
