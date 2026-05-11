# YG Pay Configuration - Admin Panel Guide

## 📍 Location

**Admin Panel** → **System Configuration** → **Environment Configuration**  
URL: `/admin/environment-config`

---

## 🔧 Configuration Section

The **YG Pay (Primary Payment Service)** section is located in the **Payment Gateways** card, highlighted with a blue border.

### Fields Available:

#### 1. **Enable YG Pay** ✅
- **Type**: Checkbox
- **Default**: Checked (Enabled)
- **Purpose**: Toggle YG Pay as the active payment provider
- **Recommendation**: Keep enabled for unified payment processing

#### 2. **API Key** 🔑
- **Type**: Password field (masked)
- **Required**: Yes ⭐
- **Where to get it**: 
  1. Login to YG Pay Admin (`pay.ygxone.com/admin`)
  2. Navigate to **Developer Portal** → **API Keys**
  3. Click "Generate New API Key"
  4. Copy the key and paste here
- **Security**: Stored masked in UI, encrypted in `.env`

#### 3. **API URL** 🌐
- **Type**: URL input
- **Default**: `https://pay.ygxone.com/api/v1`
- **Custom URLs**:
  - Production: `https://pay.ygxone.com/api/v1`
  - Staging: `https://staging-pay.ygxone.com/api/v1`
  - Local: `http://localhost:8000/api/v1`

---

## 🚀 Setup Steps

### Step 1: Generate API Key in YG Pay

1. Access YG Pay Admin Panel: `https://pay.ygxone.com/admin`
2. Go to **Developer Portal** → **API Keys**
3. Click **"Generate New API Key"**
4. Fill in details:
   - **Application Name**: "YG Account"
   - **Description**: "Main account service integration"
   - **Permissions**: Select all required scopes
5. Click **Generate**
6. **Copy the API key immediately** (it won't be shown again)

### Step 2: Configure in YG Account Admin

1. Login to YG Account Admin: `https://account.ygxone.com/admin`
2. Navigate to **System Configuration** → **Environment Configuration**
3. Scroll to **Payment Gateways** section
4. Find **YG Pay (Primary Payment Service)** box
5. Paste the API key into **API Key** field
6. Verify **API URL** is correct (default should work)
7. Ensure **Enable YG Pay** checkbox is checked
8. Click **"Save Configuration"** at the bottom

### Step 3: Verify Configuration

After saving, the system will:
1. Update `.env` file automatically
2. Clear configuration cache
3. Clear application cache

**Test the integration**:
```bash
# From terminal
cd /path/to/yg-account
php artisan tinker

# Test YG Pay connection
$paymentService = app(\App\Services\PaymentService::class);
$health = $paymentService->healthCheck();
dd($health); // Should return true if healthy
```

---

## 🎨 Visual Appearance

The YG Pay section features:
- **Blue border** (2px solid) to distinguish from other gateways
- **"RECOMMENDED" badge** in blue
- **Info icon** explaining YG Pay benefits
- **Lightbulb tip box** with bullet points
- **Password masking** for API key security

---

## ⚙️ What Happens When You Save

1. **`.env` file updated**:
   ```env
   YGPAY_API_KEY=your_api_key_here
   YGPAY_API_URL=https://pay.ygxone.com/api/v1
   PAYMENT_YGPAY_ENABLED=true
   ```

2. **Cache cleared**:
   - Config cache: `php artisan config:clear`
   - Application cache: `php artisan cache:clear`

3. **PaymentService reinitialized**:
   - Loads new API key
   - Connects to specified URL
   - Ready for transactions

---

## 🔒 Security Notes

### API Key Protection:
- ✅ Stored in `.env` file (not in database)
- ✅ Masked in admin panel (password field type)
- ✅ Never logged or displayed in plaintext
- ✅ Rotated regularly (generate new key every 90 days)

### Best Practices:
1. **Use environment-specific keys**:
   - Production key for production
   - Staging key for staging
   - Test key for development

2. **Restrict API key permissions**:
   - Only grant necessary scopes
   - Set IP restrictions if possible
   - Enable usage alerts

3. **Monitor usage**:
   - Check YG Pay dashboard for unusual activity
   - Review webhook delivery logs
   - Set up billing alerts

---

## 🆘 Troubleshooting

### Issue: "YG Pay not available" error

**Possible Causes**:
1. API key is incorrect or expired
2. API URL is wrong
3. Network connectivity issue
4. YG Pay service is down

**Solutions**:
```bash
# 1. Test connectivity
curl https://pay.ygxone.com/api/v1/health

# 2. Verify API key format (should be 64 characters)
echo -n "your_api_key" | wc -c

# 3. Check Laravel logs
tail -f storage/logs/laravel.log | grep -i ygpay

# 4. Regenerate API key in YG Pay admin
```

### Issue: Transactions failing after configuration change

**Solution**:
```bash
# Clear all caches completely
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Restart queue workers if running
php artisan queue:restart
```

### Issue: Can't see YG Pay section in admin panel

**Possible Causes**:
1. Not logged in as super admin
2. View cache needs clearing
3. File permissions issue

**Solutions**:
```bash
# Clear view cache
php artisan view:clear

# Check file permissions
ls -la resources/views/admin/environment-config/

# Verify admin role
php artisan tinker
>>> auth()->user()->is_admin; // Should be true
```

---

## 📊 Monitoring & Analytics

After configuration, monitor:

1. **YG Pay Dashboard** (`pay.ygxone.com/admin/analytics`):
   - Transaction volume
   - Success/failure rates
   - Gateway distribution
   - Revenue metrics

2. **YG Account Logs** (`storage/logs/laravel.log`):
   - API call errors
   - Webhook failures
   - Payment intent creation

3. **Admin Panel** (`/admin/services/analytics`):
   - Service health status
   - Uptime monitoring
   - Response times

---

## 🔄 Migration from Direct Gateways

If you were previously using Stripe/PayPal directly:

### Before:
```env
STRIPE_KEY=pk_live_xxx
STRIPE_SECRET=sk_live_xxx
PAYMENT_DEFAULT_PROVIDER=stripe
```

### After:
```env
YGPAY_API_KEY=ygp_xxx
YGPAY_API_URL=https://pay.ygxone.com/api/v1
PAYMENT_DEFAULT_PROVIDER=ygpay
PAYMENT_YGPAY_ENABLED=true
```

**Benefits**:
- ✅ Single API key instead of multiple
- ✅ Automatic gateway failover
- ✅ Centralized compliance (PCI DSS)
- ✅ Unified reporting

---

## 📞 Support

For issues related to YG Pay configuration:

- **Technical Support**: dev-support@ygxone.com
- **API Documentation**: https://pay.ygxone.com/docs
- **Status Page**: https://status.ygxone.com
- **Emergency**: Contact DevOps team via Slack #payments-channel

---

## ✅ Checklist

Before going live:

- [ ] API key generated from YG Pay admin
- [ ] API key configured in YG Account admin
- [ ] API URL verified (production vs staging)
- [ ] YG Pay enabled (checkbox checked)
- [ ] Configuration saved successfully
- [ ] Health check passes (`healthCheck()` returns true)
- [ ] Test transaction completed
- [ ] Webhook endpoint configured
- [ ] Monitoring alerts set up
- [ ] Team notified of changes

---

**Last Updated**: 2026-05-02  
**Version**: 1.0.0  
**Maintained by**: Platform Engineering Team
