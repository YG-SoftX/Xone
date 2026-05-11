# 🚀 YG LAUNCH CHECKLIST - Print This!

**Date:** _______________  
**Launch Manager:** _______________  
**Target Go-Live Time:** _______________

---

## ⚡ PRE-LAUNCH (Do This Morning)

### **Database & Environment**
- [ ] YG Account database migrations run (`php artisan migrate --force`)
- [ ] YG Home database migrations run (`php artisan migrate --force`)
- [ ] Session/cache tables created for YG Home
- [ ] APP_KEY generated for both systems
- [ ] .env files configured with production settings
- [ ] Admin user created in YG Account
- [ ] Search indexes synced (`php artisan search:sync`)

### **Code & Dependencies**
- [ ] Composer dependencies installed (`composer install --no-dev`)
- [ ] Frontend assets built (`npm run build`)
- [ ] File permissions set (storage/, bootstrap/cache/)
- [ ] Production caches cleared and rebuilt

---

## 🔧 SERVER SETUP

### **Web Server Configuration**
- [ ] Nginx/Apache document root set to `public/` folder
- [ ] PHP-FPM configured and running
- [ ] URL rewriting enabled (.htaccess or nginx config)
- [ ] Static file caching configured

### **SSL Certificates**
- [ ] SSL certificate installed for account.ygxone.com
- [ ] SSL certificate installed for ygxone.com
- [ ] HTTPS redirect configured
- [ ] Certificate auto-renewal setup (certbot cron)

### **DNS Records**
- [ ] A record: account.ygxone.com → server IP
- [ ] A record: ygxone.com → server IP
- [ ] WWW record: www.ygxone.com → server IP
- [ ] DNS propagation verified (use whatsmydns.net)

---

## 🔐 SECURITY HARDENING

### **Application Security**
- [ ] APP_DEBUG=false in both .env files
- [ ] APP_ENV=production in both .env files
- [ ] SESSION_SECURE_COOKIE=true
- [ ] Rate limiting configured
- [ ] CORS properly configured
- [ ] .env files NOT accessible via web

### **Server Security**
- [ ] Firewall rules configured (only ports 80, 443, 22 open)
- [ ] Fail2ban installed and configured
- [ ] SSH key-based authentication only
- [ ] Root login disabled

---

## ⚙️ BACKGROUND SERVICES

### **Cron Jobs**
```bash
* * * * * cd /var/www/yg-account && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /var/www/yg-home && php artisan schedule:run >> /dev/null 2>&1
```
- [ ] Crontab entries added
- [ ] Scheduler tested and working

### **Queue Workers** (if using async queues)
```bash
nohup php artisan queue:work --sleep=3 --tries=3 --timeout=90 > /dev/null 2>&1 &
```
- [ ] Queue workers started
- [ ] Supervisor configured for auto-restart (recommended)

---

## ✅ FUNCTIONAL TESTING

### **YG Account Testing**
- [ ] Homepage loads: https://account.ygxone.com
- [ ] Registration page accessible
- [ ] Can create new user account
- [ ] Email verification works
- [ ] Login with admin credentials successful
- [ ] Dashboard displays correctly
- [ ] Logout works properly

### **YG Home (Search) Testing**
- [ ] Homepage loads: https://ygxone.com
- [ ] Search box functional
- [ ] Search returns results for test query
- [ ] Pagination works (try ?page=2)
- [ ] Admin dashboard accessible (/admin/dashboard)
- [ ] AI suggestions appear
- [ ] Autocomplete works

### **Cross-System Integration**
- [ ] SSO between YG Account and YG Home works
- [ ] User session persists across services
- [ ] Logout from one logs out from both

---

## 📊 PERFORMANCE VERIFICATION

### **Response Times** (should be < 500ms)
- [ ] YG Account homepage: ______ ms
- [ ] YG Home homepage: ______ ms
- [ ] Search query response: ______ ms
- [ ] Admin dashboard load: ______ ms

### **Resource Usage**
- [ ] CPU usage normal (< 70%)
- [ ] Memory usage acceptable
- [ ] Disk space sufficient (> 10GB free)
- [ ] Database connections stable

---

## 🛡️ MONITORING & ALERTS

### **Logging**
- [ ] Error logs being written to storage/logs/
- [ ] Log rotation configured
- [ ] No critical errors in logs

### **Uptime Monitoring**
- [ ] UptimeRobot/Pingdom monitoring setup
- [ ] Alerts configured for downtime
- [ ] Health check endpoints responding:
  - [ ] https://account.ygxone.com/up → 200 OK
  - [ ] https://ygxone.com/up → 200 OK

### **Error Tracking** (optional but recommended)
- [ ] Sentry/Bugsnag integrated
- [ ] Error notifications configured

---

## 💾 BACKUP STRATEGY

### **Database Backups**
```bash
mysqldump -u user -p'password' yg_account > /backups/yg_account_$(date +%Y%m%d).sql
mysqldump -u user -p'password' yg_home > /backups/yg_home_$(date +%Y%m%d).sql
```
- [ ] Backup script created
- [ ] Cron job scheduled (daily at 2 AM)
- [ ] Test restore performed successfully
- [ ] Backup retention policy defined (keep 30 days)

### **File Backups**
- [ ] .env files backed up securely
- [ ] SSL certificates backed up
- [ ] Custom configurations documented

---

## 📱 MOBILE & BROWSER TESTING

### **Browser Compatibility**
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)

### **Mobile Responsiveness**
- [ ] iPhone (iOS Safari)
- [ ] Android (Chrome)
- [ ] Tablet view acceptable

---

## 🎯 GO/NO-GO DECISION

**Review all items above. If ANY critical item is unchecked, DO NOT LAUNCH.**

### **Critical Items (Must ALL be checked):**
- [ ] Both databases migrated successfully
- [ ] SSL certificates valid and not expiring
- [ ] Admin can login to both systems
- [ ] Search functionality working
- [ ] HTTPS enforced on both domains
- [ ] Backups configured and tested
- [ ] Monitoring active

### **Decision:**
- [ ] **GO** - All critical items complete, ready to launch!
- [ ] **NO-GO** - Critical issues remain, postpone launch

**Decision Maker:** _______________  
**Time:** _______________

---

## 🚀 LAUNCH EXECUTION

### **T-Minus 30 Minutes**
- [ ] Clear all caches: `php artisan optimize:clear`
- [ ] Rebuild caches: `php artisan optimize`
- [ ] Restart web server: `systemctl restart nginx php8.2-fpm`
- [ ] Final health check on both systems

### **T-Minus 15 Minutes**
- [ ] Update DNS records if changing servers
- [ ] Notify team of imminent launch
- [ ] Prepare rollback plan (backup current state)

### **LAUNCH TIME!** 🎉
- [ ] Update DNS TTL to low value (300s) for quick rollback if needed
- [ ] Point DNS to production server
- [ ] Monitor propagation (whatsmydns.net)

### **T-Plus 15 Minutes**
- [ ] Verify both sites loading from new server
- [ ] Test critical user flows
- [ ] Check error logs for issues
- [ ] Monitor performance metrics

### **T-Plus 1 Hour**
- [ ] Review analytics for traffic
- [ ] Check for error spikes
- [ ] Verify backups running
- [ ] Team standup to discuss any issues

---

## 🐛 POST-LAUNCH MONITORING (First 24 Hours)

### **Hourly Checks**
- [ ] Site accessibility (both domains)
- [ ] Error log review
- [ ] Performance metrics
- [ ] User registration/login counts

### **Daily Checks**
- [ ] Database backup completed
- [ ] SSL certificate status
- [ ] Disk space usage
- [ ] CPU/Memory trends

### **Issue Response Plan**
If critical issue discovered:
1. **Assess severity** (user-facing vs internal)
2. **Enable maintenance mode** if needed: `php artisan down`
3. **Rollback** to previous version if necessary
4. **Fix issue** in staging environment first
5. **Redeploy** after testing
6. **Document** incident and resolution

---

## 📞 EMERGENCY CONTACTS

**Technical Lead:** _______________ Phone: _______________  
**DevOps Engineer:** _______________ Phone: _______________  
**Database Admin:** _______________ Phone: _______________  

**Escalation Procedure:**
1. Check logs immediately
2. Attempt quick fix if obvious issue
3. Enable maintenance mode if user-facing problem
4. Contact technical lead
5. Rollback if fix not found within 30 minutes

---

## ✨ SUCCESS CRITERIA

Launch is considered successful if after 24 hours:
- [ ] Zero critical bugs reported
- [ ] Uptime > 99.9%
- [ ] Average response time < 500ms
- [ ] User registrations working
- [ ] Search returning results
- [ ] No data loss incidents
- [ ] Customer support tickets < 5

---

## 📝 NOTES & OBSERVATIONS

Use this space to document any issues, observations, or lessons learned:

_____________________________________________________________________

_____________________________________________________________________

_____________________________________________________________________

_____________________________________________________________________

_____________________________________________________________________

---

## 🎉 LAUNCH COMPLETE!

**Launch Date:** _______________  
**Go-Live Time:** _______________  
**Issues Encountered:** _______________  
**Resolution Time:** _______________  

**Team Celebration:** ☕ Coffee / 🍕 Pizza / 🎊 Party

---

**Remember:** Launch day is just the beginning. Continue monitoring, optimizing, and improving based on user feedback!

**Good luck! You've got this!** 🚀💪
