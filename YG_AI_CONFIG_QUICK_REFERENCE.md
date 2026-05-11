# YG AI Configuration - Quick Reference Card

## 🚀 Quick Setup (2 Minutes)

### 1. Access Admin Panel
```
URL: https://account.ygxone.com/admin/environment-config
Login: Super Admin credentials
```

---

### 2. Find YG AI Section
Scroll to **"YG AI Service"** (purple robot icon 🤖)

---

### 3. Enter Settings

| Field | Value | Required? |
|-------|-------|-----------|
| **AI Service URL** | `https://ai.ygxone.com` | ✅ Yes |
| **API Key** | *(leave blank)* | ❌ Optional |
| **Timeout** | `5` seconds | ❌ Optional |

---

### 4. Save & Done!
Click **"Save Configuration"** → System auto-applies changes!

✅ No restart needed  
✅ Cache cleared automatically  
✅ Changes immediate  

---

## 🎯 Default Values

```env
YG_AI_URL=https://ai.ygxone.com
YG_AI_API_KEY=
YG_AI_TIMEOUT=5
```

---

## 🧪 Quick Test

After saving, test in YG Mail:
1. Open any email
2. Click "Load Suggestions"
3. See 3 smart replies appear ✅

---

## 🔍 Verify Configuration

Check current values:
```bash
php artisan tinker
>>> config('services.yg_ai.url')
=> "https://ai.ygxone.com"
```

---

## ⚠️ Common Issues

| Problem | Solution |
|---------|----------|
| Timeout error | Increase timeout to 10s |
| 401 Unauthorized | Add API key |
| Not saving | Check `.env` file permissions |
| Slow responses | Reduce timeout to 3s |

---

## 📞 Support

- **Docs**: [`YG_AI_CONFIGURATION_GUIDE.md`](c:\Users\ASUS\Downloads\YG Soft1\YG_AI_CONFIGURATION_GUIDE.md)
- **Email**: ai-support@ygxone.com
- **Status**: https://status.ygxone.com

---

**Remember**: All configuration is done via **Admin Panel Only**!  
Never edit `.env` manually for YG AI settings.
