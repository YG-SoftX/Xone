# 🔑 YG Home - Admin Configuration Quick Reference

## ⚡ 30-Second Setup

### **Windows:**
```cmd
setup-admin.bat
```

### **Linux/Mac:**
```bash
chmod +x setup-admin.sh
./setup-admin.sh
```

---

## 📝 Manual Configuration

### **1. Edit .env file:**
```env
ADMIN_EMAILS=your.email@example.com
ADMIN_USER_IDS=1
```

### **2. Generate key & clear cache:**
```bash
php artisan key:generate
php artisan config:clear
```

### **3. Start server:**
```bash
php artisan serve --host=0.0.0.0 --port=8001
```

### **4. Access dashboard:**
```
http://localhost:8001/admin/dashboard
```

---

## 👥 Common Configurations

### **Single Admin:**
```env
ADMIN_EMAILS=admin@ygxone.com
```

### **Multiple Admins:**
```env
ADMIN_EMAILS=admin@ygxone.com,superadmin@ygxone.com,dev@team.com
```

### **By User ID:**
```env
ADMIN_USER_IDS=1,2,3
```

### **Combined:**
```env
ADMIN_EMAILS=ceo@company.com,cto@company.com
ADMIN_USER_IDS=1,2
```

---

## 🔧 Useful Commands

```bash
# Create admin user
php artisan tinker
>>> use App\Models\User;
>>> use Illuminate\Support\Facades\Hash;
>>> User::create(['name'=>'Admin','email'=>'admin@test.com','password'=>Hash::make('pass123')]);
>>> exit

# Find user IDs
php artisan tinker
>>> DB::table('users')->select('id','email')->get();
>>> exit

# View admin logs
tail -f storage/logs/laravel.log | grep "Admin"

# Clear all caches
php artisan optimize:clear

# Check routes
php artisan route:list | grep admin
```

---

## 🐛 Quick Troubleshooting

| Problem | Solution |
|---------|----------|
| 403 Forbidden | Check email matches exactly in .env |
| 404 Not Found | Run `php artisan route:clear` |
| Changes not working | Run `php artisan config:clear` |
| Blank page | Run `npm run build` |
| Redirect loop | Check bootstrap/app.php redirect settings |

---

## 📚 Full Documentation

- **Complete Guide:** [`ADMIN_CONFIGURATION_GUIDE.md`](ADMIN_CONFIGURATION_GUIDE.md)
- **Testing Procedures:** [`TESTING_AND_DEPLOYMENT_GUIDE.md`](TESTING_AND_DEPLOYMENT_GUIDE.md)
- **Deployment Steps:** [`DEPLOYMENT_CHECKLIST.md`](DEPLOYMENT_CHECKLIST.md)

---

**Need help?** Check `storage/logs/laravel.log` for error details.
