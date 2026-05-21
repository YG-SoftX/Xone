#!/bin/bash
# Browser Migration Verification Script
# Run this after deployment to verify all changes are working correctly

echo "========================================"
echo "YGXONE Browser Migration Verification"
echo "========================================"
echo ""

BASE_URL="https://ygxone.com"

# Test 1: Root URL loads browser
echo "[Test 1] Checking root URL (/)..."
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/")
if [ "$RESPONSE" = "200" ]; then
    echo "✓ Root URL returns 200 OK"
else
    echo "✗ Root URL returned status: $RESPONSE"
fi
echo ""

# Test 2: PWA Manifest has correct start_url
echo "[Test 2] Checking PWA Manifest..."
MANIFEST=$(curl -s "$BASE_URL/site.webmanifest")
START_URL=$(echo "$MANIFEST" | grep -o '"start_url":"[^"]*"' | cut -d'"' -f4)
if [ "$START_URL" = "/" ]; then
    echo "✓ PWA Manifest start_url is correct: '/'"
else
    echo "✗ PWA Manifest start_url is incorrect: '$START_URL'"
fi
echo ""

# Test 3: Search still works at /search
echo "[Test 3] Checking search route (/search)..."
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/search?q=test")
if [ "$RESPONSE" = "200" ]; then
    echo "✓ Search route returns 200 OK"
else
    echo "✗ Search route returned status: $RESPONSE"
fi
echo ""

# Test 4: Check for old /browser route (should redirect or 404)
echo "[Test 4] Checking old /browser route..."
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/browser")
if [ "$RESPONSE" = "404" ]; then
    echo "✓ Old /browser route properly returns 404"
elif [ "$RESPONSE" = "301" ] || [ "$RESPONSE" = "302" ]; then
    echo "⚠ Old /browser route redirects (Status: $RESPONSE)"
    echo "  Consider if redirect is intentional"
else
    echo "⚠ Old /browser route still accessible (Status: $RESPONSE)"
    echo "  Consider adding a redirect from /browser to /"
fi
echo ""

# Test 5: Service Worker exists
echo "[Test 5] Checking Service Worker..."
SW_CONTENT=$(curl -s "$BASE_URL/sw.js")
if [ $? -eq 0 ]; then
    if echo "$SW_CONTENT" | grep -q "caches.match('/')"; then
        echo "✓ Service Worker exists and references root URL"
    else
        echo "⚠ Service Worker exists but may not reference root URL correctly"
    fi
else
    echo "✗ Failed to fetch service worker"
fi
echo ""

echo "========================================"
echo "Verification Complete!"
echo "========================================"
echo ""
echo "Next Steps:"
echo "1. Open https://ygxone.com/ in your browser"
echo "2. Verify the browser interface loads"
echo "3. Test navigation, tabs, and URL bar updates"
echo "4. Check PWA install prompt appears"
echo "5. Test all browser features (Research, Citations, etc.)"