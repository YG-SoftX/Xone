#!/bin/bash
# Diagnostic Script for Browser & Login Issues
# Run this to identify what's causing problems

echo "========================================"
echo "YGXONE Diagnostic Tool"
echo "========================================"
echo ""

BASE_URL="https://ygxone.com"

# Test 1: Check if routes are registered
echo "[Test 1] Checking SSO Routes..."
for route in "/sso/initiate" "/sso/callback" "/sso/logout" "/login"; do
    STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL$route" --max-time 5)
    if [ "$STATUS" = "200" ] || [ "$STATUS" = "302" ] || [ "$STATUS" = "301" ]; then
        echo "  ✓ $route - Status: $STATUS"
    else
        echo "  ✗ $route - Status: $STATUS (error)"
    fi
done
echo ""

# Test 2: Check browser proxy
echo "[Test 2] Testing Browser Proxy..."
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/browse?url=https://www.google.com" --max-time 10)
if [ "$STATUS" = "200" ]; then
    CONTENT_LENGTH=$(curl -s -o /dev/null -w "%{size_download}" "$BASE_URL/browse?url=https://www.google.com" --max-time 10)
    echo "  ✓ Proxy works - Fetched $CONTENT_LENGTH bytes"
else
    echo "  ✗ Proxy failed - Status: $STATUS"
    echo "  → This indicates cURL or network issues"
fi
echo ""

# Test 3: Check account service connectivity
echo "[Test 3] Checking Account Service..."
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "https://account.ygxone.com/up" --max-time 5)
if [ "$STATUS" = "200" ]; then
    echo "  ✓ Account service is healthy"
else
    echo "  ✗ Account service returned: $STATUS"
    echo "  → Check YG_ACCOUNT_URL in .env file"
fi
echo ""

# Test 4: Check PWA manifest
echo "[Test 4] Checking PWA Configuration..."
MANIFEST=$(curl -s "$BASE_URL/site.webmanifest")
START_URL=$(echo "$MANIFEST" | grep -o '"start_url":"[^"]*"' | cut -d'"' -f4)
if [ "$START_URL" = "/" ]; then
    echo "  ✓ PWA start_url is correct: '/'"
else
    echo "  ⚠ PWA start_url is: '$START_URL' (should be '/')"
fi
echo ""

# Test 5: Check health endpoint
echo "[Test 5] Checking Application Health..."
HEALTH=$(curl -s "$BASE_URL/up")
if echo "$HEALTH" | grep -q '"status":"healthy"'; then
    echo "  ✓ Application is healthy"
    DB_STATUS=$(echo "$HEALTH" | grep -o '"database":[a-z]*' | cut -d':' -f2)
    CACHE_STATUS=$(echo "$HEALTH" | grep -o '"cache":[a-z]*' | cut -d':' -f2)
    echo "    Database: $DB_STATUS"
    echo "    Cache: $CACHE_STATUS"
else
    echo "  ⚠ Application may have issues"
fi
echo ""

echo "========================================"
echo "Diagnostic Complete!"
echo "========================================"
echo ""
echo "Next Steps:"
echo "1. If SSO routes show errors → Clear caches: php artisan route:clear"
echo "2. If proxy fails → Check cURL extension and firewall rules"
echo "3. If account service unreachable → Verify YG_ACCOUNT_URL in .env"
echo "4. Review full troubleshooting guide: LOGIN_BROWSER_TROUBLESHOOTING.md"