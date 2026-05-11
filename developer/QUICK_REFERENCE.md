# YG Developer Portal - Quick Reference Card

## 🌐 Official URL
**`https://developer.ygxone.com`**

---

## 📁 Project Location
```
c:\Users\ASUS\Downloads\YG Soft1\yg-developer\
```

---

## 🔑 Key Features (ALL here)

✅ API Project Management  
✅ Credential Generation (Client ID/Secret)  
✅ API Documentation & Explorer  
✅ Usage Analytics Dashboard  
✅ Webhook Configuration  
✅ Quota Management  
✅ Billing & Invoices  
✅ Team Collaboration  
✅ Application Gallery  
✅ Support Center  

---

## 🔗 Integration Points

### Authentication
- **Provider**: `account.ygxone.com` (YG Account)
- **Flow**: OAuth 2.0 Authorization Code
- **Callback**: `https://developer.ygxone.com/auth/sso/callback`

### Database
- **Shared with**: YG Account
- **Database name**: `ygmarket_account`
- **Tables**: 14 developer-specific tables

### APIs Accessed
All YG ecosystem services via their respective APIs:
- YG Account (SSO, User Management)
- YG Mail (Email API)
- YG Drive (Storage API)
- YG Pay (Payment API)
- YG Chat (Messaging API)
- etc.

---

## 🚀 Quick Commands

### Development
```bash
cd yg-developer
php artisan serve --host=0.0.0.0 --port=8000
```

### Production Deployment
```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Queue Workers
```bash
# Webhooks
php artisan queue:work redis --queue=webhooks

# Billing
php artisan queue:work redis --queue=billing
```

### Cron Jobs
```bash
# Add to crontab
0 * * * * cd /path/to/yg-developer && php artisan schedule:run
```

---

## 📊 Environment Variables

```env
APP_URL=https://developer.ygxone.com
YG_ACCOUNT_URL=https://account.ygxone.com
YG_ACCOUNT_CLIENT_ID=your_client_id
YG_ACCOUNT_CLIENT_SECRET=your_secret
YG_ACCOUNT_REDIRECT_URI=https://developer.ygxone.com/auth/sso/callback
```

---

## 🔐 Security Checklist

- [ ] HTTPS enabled (SSL certificate)
- [ ] APP_DEBUG=false in production
- [ ] CORS configured for ygxone.com subdomains only
- [ ] Rate limiting active on all endpoints
- [ ] Webhook signature verification enabled
- [ ] API tokens use Sanctum authentication
- [ ] Database credentials rotated regularly

---

## 🧪 Testing URLs

| Environment | URL | Purpose |
|-------------|-----|---------|
| **Production** | `https://developer.ygxone.com` | Live portal |
| **Staging** | `https://staging-developer.ygxone.com` | Pre-production testing |
| **Local** | `http://localhost:8000` | Development |

---

## 📞 Support

- **Documentation**: https://developer.ygxone.com/docs
- **API Status**: https://status.ygxone.com
- **Support Email**: dev-support@ygxone.com
- **Community**: https://community.ygxone.com

---

## ⚠️ Important Notes

1. **NO developer routes in `yg-account`** - All features at `developer.ygxone.com`
2. **Shared database** with YG Account - coordinate migrations
3. **SSO required** - No standalone authentication
4. **Queue workers required** - For webhooks and billing automation
5. **Cron jobs required** - For usage aggregation and invoice generation

---

**Status**: ✅ **PRODUCTION READY**  
**Last Updated**: May 4, 2026
