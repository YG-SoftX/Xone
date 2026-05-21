# YGXONE Browser PWA App - Complete Guide

## ✅ Current PWA Configuration Status

The YGXONE Agentic Browser is **fully configured as a Progressive Web App (PWA)** with the following features:

### 1. **Dynamic Manifest** (`/site.webmanifest`)
- ✅ Served dynamically from `ManifestController`
- ✅ Reads configuration from Master Admin Panel Browser Settings
- ✅ `start_url`: `/` (browser homepage)
- ✅ Display mode: `standalone`
- ✅ Theme color and background color configurable
- ✅ Icons: 192x192 and 512x512 PNG
- ✅ Shortcuts to Browser and AI Agent
- ✅ Screenshots for app store listing

### 2. **Service Worker** (`/sw.js`)
- ✅ Cache-first strategy for static assets
- ✅ Network-first strategy for HTML pages
- ✅ Offline fallback support
- ✅ Automatic cache cleanup
- ✅ Excludes API calls, agent runs, and browse proxy from caching
- ✅ Supports skip-waiting for updates

### 3. **PWA Install Prompt**
- ✅ Built-in `beforeinstallprompt` event handler
- ✅ Custom install prompt UI (bottom-right corner)
- ✅ Manual install button support
- ✅ Tracks installation outcome

### 4. **iOS Support**
- ✅ Apple touch icons configured
- ✅ `apple-mobile-web-app-capable` meta tag
- ✅ `apple-mobile-web-app-status-bar-style` set
- ✅ Dynamic splash screen support

---

## 📱 How to Install as PWA

### Desktop (Chrome/Edge/Firefox)

#### Method 1: Address Bar Install Button
1. Open `https://ygxone.com/` in Chrome or Edge
2. Look for install icon in address bar (⊕ or 📥)
3. Click "Install"
4. Choose location (Desktop/Start Menu)
5. App launches in standalone window

#### Method 2: Browser Menu
1. Click browser menu (⋮ or ≡)
2. Select "Install YGXONE Browser" or "More tools → Create shortcut"
3. Check "Open as window"
4. Click "Create"

#### Method 3: Custom Install Prompt
1. If available, click install button in bottom-right corner
2. Follow prompts to install

### Mobile (Android/iOS)

#### Android (Chrome)
1. Open `https://ygxone.com/` in Chrome
2. Tap menu (⋮) → "Install app" or "Add to Home screen"
3. Confirm installation
4. App appears on home screen

#### iOS (Safari)
1. Open `https://ygxone.com/` in Safari
2. Tap Share button (□↑)
3. Scroll down → "Add to Home Screen"
4. Name it "YGXONE Browser"
5. Tap "Add"
6. App appears on home screen

---

## 🔧 PWA Features & Capabilities

### What Works in PWA Mode

✅ **Full Browser Functionality**
- Tab management (open, close, switch)
- URL navigation and history
- Back/Forward navigation
- Bookmarks and quick links
- Search integration

✅ **AI Features**
- AI Agent integration
- Research mode
- Citation tracking
- Knowledge graph visualization

✅ **Offline Support**
- Cached static assets (CSS, JS, icons)
- Fallback to cached pages when offline
- Offline error page with retry option

✅ **Native App Experience**
- No browser chrome (address bar, tabs)
- Full-screen or standalone display
- Custom splash screen
- Theme colors match app branding
- Push notifications (if enabled later)

### Limitations

⚠️ **Browser Proxy Restrictions**
- Some sites may block iframe embedding (X-Frame-Options)
- CORS restrictions apply to cross-origin requests
- Complex JavaScript-heavy sites may have issues
- WebRTC not supported in proxy mode

⚠️ **Storage Limits**
- Service worker cache: ~50-100MB (varies by browser)
- IndexedDB: ~5-10% of disk space
- LocalStorage: 5-10MB per origin

---

## 🎨 Customization via Master Admin Panel

All PWA settings can be configured in the Master Admin Panel:

### Browser Settings Section

1. **Branding**
   - Browser name (displayed in install prompt)
   - Short name (home screen label)
   - Description
   - Logo and icons

2. **Colors**
   - Primary theme color
   - Background color
   - Accent colors

3. **Splash Screen**
   - Enable/disable
   - Title and subtitle
   - Background color
   - Spinner color
   - Animation duration

4. **Features**
   - PWA install prompt (enable/disable)
   - AI Agent integration
   - BYOK (Bring Your Own Key) support

5. **Quick Links**
   - Configure default bookmarks
   - Set search engine preference

---

## 🧪 Testing PWA Installation

### Test 1: Verify Manifest
```bash
curl -I https://ygxone.com/site.webmanifest
```

Expected headers:
```
Content-Type: application/manifest+json
Cache-Control: public, max-age=86400
```

Check content:
```bash
curl -s https://ygxone.com/site.webmanifest | jq '.start_url'
# Should return: "/"
```

### Test 2: Verify Service Worker
```bash
curl -I https://ygxone.com/sw.js
```

Expected:
```
HTTP/2 200
Content-Type: application/javascript
```

### Test 3: Lighthouse Audit
1. Open Chrome DevTools (F12)
2. Go to "Lighthouse" tab
3. Select categories: PWA, Performance, Best Practices
4. Click "Analyze page load"
5. Expected score: 90+ for PWA

Key checks:
- ✅ Registers a service worker
- ✅ Responds with 200 when offline
- ✅ Contains manifest with start_url
- ✅ Configured for custom splash screen
- ✅ Sets theme color

### Test 4: Install Flow
1. Open incognito/private window
2. Navigate to `https://ygxone.com/`
3. Check if install prompt appears
4. Install the app
5. Verify it opens in standalone window
6. Test offline mode (disconnect network)
7. Reload - should show cached version

---

## 🐛 Troubleshooting PWA Issues

### Issue: Install Button Not Showing

**Possible Causes:**
1. Already installed
2. Browser doesn't support PWA
3. HTTPS not configured
4. Manifest missing or invalid

**Solutions:**
```bash
# Check HTTPS
curl -I https://ygxone.com/
# Should return HTTP/2 200

# Check manifest
curl https://ygxone.com/site.webmanifest | jq .
# Should return valid JSON

# Clear browser cache and try again
```

### Issue: Service Worker Not Registering

**Check Console Errors:**
```javascript
// Open DevTools Console and run:
navigator.serviceWorker.getRegistration().then(reg => {
    console.log('SW registered:', reg);
});
```

**Solutions:**
1. Check sw.js is accessible: `curl https://ygxone.com/sw.js`
2. Verify scope is correct: `/`
3. Check for mixed content (http vs https)
4. Clear service worker cache in DevTools

### Issue: Offline Mode Not Working

**Debug Steps:**
```javascript
// Check cache contents
caches.keys().then(keys => console.log('Caches:', keys));
caches.open('ygxone-browser-v1').then(cache => {
    cache.keys().then(reqs => console.log('Cached:', reqs));
});
```

**Solutions:**
1. Visit site while online first (to populate cache)
2. Check service worker is active
3. Verify cache name matches in sw.js
4. Force update: DevTools → Application → Service Workers → Update

### Issue: iOS Safari Not Installing

**Requirements:**
- Must use HTTPS
- Must have valid manifest
- Must have apple-touch-icon
- User must manually add to home screen

**Check:**
```html
<!-- These must be present in <head> -->
<link rel="manifest" href="/site.webmanifest">
<link rel="apple-touch-icon" href="/icons/icon-192.png">
<meta name="apple-mobile-web-app-capable" content="yes">
```

---

## 📊 PWA Analytics & Monitoring

### Track Installations

Add to browser view JavaScript:
```javascript
window.addEventListener('appinstalled', (e) => {
    // Send analytics event
    gtag('event', 'pwa_installed', {
        event_category: 'PWA',
        event_label: 'Browser Installed'
    });
    
    console.log('PWA installed!');
});
```

### Monitor Service Worker Updates

```javascript
navigator.serviceWorker.addEventListener('controllerchange', () => {
    console.log('Service worker updated!');
    // Show update notification to user
});
```

---

## 🚀 Advanced PWA Features (Future Enhancements)

### 1. Push Notifications
```javascript
// Request permission
Notification.requestPermission().then(permission => {
    if (permission === 'granted') {
        // Subscribe to push service
    }
});
```

### 2. Background Sync
```javascript
// Sync data when back online
navigator.serviceWorker.ready.then(registration => {
    registration.sync.register('sync-browsing-data');
});
```

### 3. Periodic Background Sync
```javascript
// Update content periodically
registration.periodicSync.register('update-quick-links', {
    minInterval: 24 * 60 * 60 * 1000 // 24 hours
});
```

### 4. File System Access
```javascript
// Save browsing sessions
const handle = await window.showSaveFilePicker();
const writable = await handle.createWritable();
await writable.write(JSON.stringify(browsingData));
await writable.close();
```

---

## 📝 Deployment Checklist

Before deploying PWA to production:

- [ ] HTTPS certificate installed and valid
- [ ] Manifest returns correct Content-Type header
- [ ] All icons exist and are accessible
- [ ] Service worker registers without errors
- [ ] Lighthouse PWA score > 90
- [ ] Install prompt appears on first visit
- [ ] Offline mode works (test with DevTools)
- [ ] iOS Safari can add to home screen
- [ ] Android Chrome shows install button
- [ ] Theme colors match branding
- [ ] Splash screen displays correctly
- [ ] Shortcuts work (Browser, AI Agent)
- [ ] Cache cleanup works (no storage bloat)
- [ ] Update flow works (new SW takes over)

---

## 🔗 Related Files

- **Manifest**: [`app/Http/Controllers/ManifestController.php`](d:\YG SoftX\Xone\home\app\Http\Controllers\ManifestController.php)
- **Service Worker**: [`public/sw.js`](d:\YG SoftX\Xone\home\public\sw.js)
- **Config Service**: [`app/Services/BrowserConfigService.php`](d:\YG SoftX\Xone\home\app\Services\BrowserConfigService.php)
- **Layout**: [`resources/views/layouts/app.blade.php`](d:\YG SoftX\Xone\home\resources\views\layouts\app.blade.php)
- **Browser View**: [`resources/views/search/browser.blade.php`](d:\YG SoftX\Xone\home\resources\views\search\browser.blade.php)
- **Offline Service**: [`app/Services/OfflineModeService.php`](d:\YG SoftX\Xone\home\app\Services\OfflineModeService.php)

---

## 📞 Support

If PWA installation fails:

1. Run diagnostic script: `./diagnose-issues.sh` or `.\diagnose-issues.ps1`
2. Check browser console for errors
3. Verify all files are deployed correctly
4. Clear browser cache and service workers
5. Test in incognito/private mode
6. Review troubleshooting section above

---

**Last Updated:** 2026-05-20  
**PWA Version:** v1  
**Status:** ✅ Production Ready