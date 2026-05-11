# YG Master - Quick Start Guide

## ⚠️ Database Not Running - Fix First!

Before running migrations, you need to start MySQL:

### Windows
```powershell
# Start MySQL service
net start MySQL80

# Or use XAMPP/WAMP control panel to start MySQL
```

### Linux/Mac
```bash
# Start MySQL
sudo systemctl start mysql

# Or
sudo service mysql start
```

---

## 🚀 Quick Setup (After Database is Running)

```bash
cd "c:\Users\ASUS\Downloads\YG Soft1\yg-master"

# 1. Run migrations and seeders
php artisan migrate:fresh --seed

# Expected output:
# ✅ Tables created
# ✅ Super admin user created (admin@ygxone.com)
# ✅ 13 services registered
# ✅ 5 infrastructure nodes seeded

# 2. Update .env with your API keys
# Edit the file and add all 13 service API keys

# 3. Start queue worker
php artisan queue:work redis --sleep=3 --tries=3 --timeout=90

# 4. Access admin panel
# URL: https://master.ygxone.com/admin
# Email: admin@ygxone.com
# Password: YgMaster@2026!Secure
```

---

## 📋 Verification Checklist

After setup, verify everything works:

```bash
# Check services are seeded
php artisan tinker
>>> App\Models\AppModule::count()  # Should return 13

# Check admin user exists
>>> App\Models\User::where('email', 'admin@ygxone.com')->count()  # Should return 1

# Test health check endpoint
curl http://localhost:8000/api/health/status

# List available backups (should be empty initially)
curl http://localhost:8000/api/backups/list
```

---

## 🔧 Common Issues

### Issue: "No connection could be made because the target machine actively refused it"

**Solution**: MySQL is not running. Start MySQL service first.

### Issue: "Class 'App\Models\AppModule' not found"

**Solution**: Run `composer dump-autoload`

### Issue: Queue worker not processing jobs

**Solution**: 
```bash
# Check Redis is running
redis-cli ping  # Should return PONG

# Restart queue worker
php artisan queue:restart
php artisan queue:work
```

---

## 📊 What You Get

### Admin Panel Features
- ✅ Service management dashboard
- ✅ Real-time health monitoring
- ✅ One-click deployments
- ✅ Automated backups
- ✅ User & subscription management
- ✅ Infrastructure monitoring
- ✅ Advanced analytics widgets

### API Endpoints
- ✅ Health checks (manual + automated)
- ✅ Deployment triggers (single + bulk)
- ✅ Backup operations (create, list, download, delete)
- ✅ Ecosystem status overview
- ✅ Maintenance mode toggle

### Automation
- ✅ Health checks every minute
- ✅ Metrics aggregation hourly/daily
- ✅ Daily database backups
- ✅ Weekly cache optimization
- ✅ Automatic alert notifications

---

## 🎯 Next Steps After Setup

1. **Change admin password** immediately after first login
2. **Configure service API keys** in `.env`
3. **Test health checks** for all 13 services
4. **Set up supervisor** for production queue workers
5. **Configure cron jobs** for scheduled tasks
6. **Enable SSL** for production deployment

---

**Status**: Ready to deploy once database is running!  
**Support**: See `PRODUCTION_DEPLOYMENT_GUIDE.md` for full instructions
