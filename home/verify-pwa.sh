#!/bin/bash
# PWA Browser App Verification Script
# Run this to verify PWA is properly configured

echo "========================================"
echo "YGXONE Browser PWA Verification"
echo "========================================"
echo ""

BASE_URL="https://ygxone.com"

# Test 1: Check manifest
echo "[Test 1] Checking PWA Manifest..."
MANIFEST=$(curl -s "$BASE_URL/site.webmanifest")
if [ $? -eq 0 ] && [ -n "$MANIFEST" ]; then
    echo "  ✓ Manifest accessible"
    
    START_URL=$(echo "$MANIFEST" | grep -o '"start_url":"[^"]*"' | cut -d'"' -f4)
    NAME=$(echo "$MANIFEST" | grep -o '"name":"[^"]*"' | cut -d'"' -f4)
    SHORT_NAME=$(echo "$MANIFEST" | grep -o '"short_name":"[^"]*"' | cut -d'"' -f4)
    DISPLAY=$(echo "$MANIFEST" | grep -o '"display":"[^"]*"' | cut -d'"' -f4)
    THEME_COLOR=$(echo "$MANIFEST" | grep -o '"theme_color":"[^"]*"' | cut -d'"' -f4)
    
    echo "    Name: $NAME"
    echo "    Short Name: $SHORT_NAME"
    echo "    Start URL: $START_URL"
    
    if [ "$START_URL" = "/" ]; then
        echo "    ✓ Start URL is correct (root)"
    else
        echo "    ✗ Start URL should be '/' but is '$START_URL'"
    fi
    
    echo "    Display Mode: $DISPLAY"
    echo "    Theme Color: $THEME_COLOR"
else
    echo "  ✗ Failed to fetch manifest"
fi
echo ""

# Test 2: Check service worker
echo "[Test 2] Checking Service Worker..."
SW_CONTENT=$(curl -s "$BASE_URL/sw.js")
if [ $? -eq 0 ] && [ -n "$SW_CONTENT" ]; then
    echo "  ✓ Service Worker accessible"
    
    if echo "$SW_CONTENT" | grep -q "CACHE_NAME.*ygxone-browser-v"; then
        echo "    ✓ Cache versioning implemented"
    fi
    
    if echo "$SW_CONTENT" | grep -q "addEventListener('install'"; then
        echo "    ✓ Install event handler present"
    fi
    
    if echo "$SW_CONTENT" | grep -q "addEventListener('fetch'"; then
        echo "    ✓ Fetch event handler present"
    fi
    
    if echo "$SW_CONTENT" | grep -q "caches.match('/')"; then
        echo "    ✓ Offline fallback configured"
    fi
else
    echo "  ✗ Failed to fetch service worker"
fi
echo ""

# Test 3: Check icons
echo "[Test 3] Checking PWA Icons..."
for icon in "/icons/icon-192.png" "/icons/icon-512.png"; do
    STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL$icon" --max-time 5)
    if [ "$STATUS" = "200" ]; then
        SIZE=$(curl -s -o /dev/null -w "%{size_download}" "$BASE_URL$icon" --max-time 5)
        echo "  ✓ $icon exists ($SIZE bytes)"
    else
        echo "  ✗ $icon not found (Status: $STATUS)"
    fi
done
echo ""

# Test 4: Check meta tags in HTML
echo "[Test 4] Checking HTML Meta Tags..."
HTML=$(curl -s "$BASE_URL/")
if [ $? -eq 0 ]; then
    if echo "$HTML" | grep -q 'rel="manifest"'; then
        echo "  ✓ Manifest link present"
    else
        echo "  ✗ Manifest link missing"
    fi
    
    if echo "$HTML" | grep -q 'name="theme-color"'; then
        echo "  ✓ Theme color meta tag present"
    else
        echo "  ✗ Theme color meta tag missing"
    fi
    
    if echo "$HTML" | grep -q 'apple-mobile-web-app-capable'; then
        echo "  ✓ Apple touch capable meta tag present"
    else
        echo "  ✗ Apple touch capable meta tag missing"
    fi
    
    if echo "$HTML" | grep -q 'serviceWorker.register'; then
        echo "  ✓ Service worker registration present"
    else
        echo "  ✗ Service worker registration missing"
    fi
else
    echo "  ✗ Failed to fetch homepage"
fi
echo ""

# Test 5: Check HTTPS
echo "[Test 5] Checking HTTPS Configuration..."
SCHEME=$(curl -s -o /dev/null -w "%{scheme}" "$BASE_URL" --max-time 5)
if [ "$SCHEME" = "https" ]; then
    echo "  ✓ HTTPS enabled"
else
    echo "  ✗ Not using HTTPS (required for PWA)"
fi
echo ""

# Test 6: Check install prompt configuration
echo "[Test 6] Checking Install Prompt..."
if echo "$HTML" | grep -q "beforeinstallprompt"; then
    echo "  ✓ Install prompt event handler present"
else
    echo "  ⚠ Install prompt event handler not found"
fi

if echo "$HTML" | grep -qE "pwa-install-prompt|Install.*App|Add.*Home"; then
    echo "  ✓ Install UI elements present"
else
    echo "  ⚠ Install UI elements may be missing"
fi
echo ""

echo "========================================"
echo "PWA Verification Complete!"
echo "========================================"
echo ""
echo "Summary:"
echo "- If all tests pass, PWA is ready for installation"
echo "- Open https://ygxone.com/ in Chrome/Edge to test install"
echo "- For iOS: Use Safari → Share → Add to Home Screen"
echo "- For Android: Chrome menu → Install app"
echo ""
echo "Next Steps:"
echo "1. Review full guide: PWA_BROWSER_GUIDE.md"
echo "2. Test installation on multiple devices"
echo "3. Run Lighthouse audit for PWA score"