# Browser Migration Verification Script
# Run this after deployment to verify all changes are working correctly

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "YGXONE Browser Migration Verification" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$baseUrl = "https://ygxone.com"

# Test 1: Root URL loads browser
Write-Host "[Test 1] Checking root URL (/)..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/" -Method GET -TimeoutSec 10
    if ($response.StatusCode -eq 200) {
        Write-Host "✓ Root URL returns 200 OK" -ForegroundColor Green
        Write-Host "  Content Length: $($response.Content.Length) bytes" -ForegroundColor Gray
    } else {
        Write-Host "✗ Root URL returned status: $($response.StatusCode)" -ForegroundColor Red
    }
} catch {
    Write-Host "✗ Failed to access root URL: $_" -ForegroundColor Red
}
Write-Host ""

# Test 2: PWA Manifest has correct start_url
Write-Host "[Test 2] Checking PWA Manifest..." -ForegroundColor Yellow
try {
    $manifest = Invoke-RestMethod -Uri "$baseUrl/site.webmanifest" -Method GET -TimeoutSec 10
    if ($manifest.start_url -eq "/") {
        Write-Host "✓ PWA Manifest start_url is correct: '/'" -ForegroundColor Green
    } else {
        Write-Host "✗ PWA Manifest start_url is incorrect: '$($manifest.start_url)'" -ForegroundColor Red
    }
    Write-Host "  Name: $($manifest.name)" -ForegroundColor Gray
    Write-Host "  Short Name: $($manifest.short_name)" -ForegroundColor Gray
} catch {
    Write-Host "✗ Failed to fetch manifest: $_" -ForegroundColor Red
}
Write-Host ""

# Test 3: Search still works at /search
Write-Host "[Test 3] Checking search route (/search)..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/search?q=test" -Method GET -TimeoutSec 10
    if ($response.StatusCode -eq 200) {
        Write-Host "✓ Search route returns 200 OK" -ForegroundColor Green
    } else {
        Write-Host "✗ Search route returned status: $($response.StatusCode)" -ForegroundColor Red
    }
} catch {
    Write-Host "✗ Failed to access search route: $_" -ForegroundColor Red
}
Write-Host ""

# Test 4: Check for old /browser route (should redirect or 404)
Write-Host "[Test 4] Checking old /browser route..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/browser" -Method GET -TimeoutSec 10 -MaximumRedirection 0 -ErrorAction SilentlyContinue
    Write-Host "⚠ Old /browser route still accessible (Status: $($response.StatusCode))" -ForegroundColor Yellow
    Write-Host "  Consider adding a redirect from /browser to /" -ForegroundColor Gray
} catch {
    if ($_.Exception.Response.StatusCode -eq 404) {
        Write-Host "✓ Old /browser route properly returns 404" -ForegroundColor Green
    } else {
        Write-Host "⚠ Old /browser route error: $($_.Exception.Message)" -ForegroundColor Yellow
    }
}
Write-Host ""

# Test 5: Service Worker exists
Write-Host "[Test 5] Checking Service Worker..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/sw.js" -Method GET -TimeoutSec 10
    if ($response.StatusCode -eq 200) {
        $content = $response.Content
        if ($content -match "caches\.match\('\/'\)") {
            Write-Host "✓ Service Worker exists and references root URL" -ForegroundColor Green
        } else {
            Write-Host "⚠ Service Worker exists but may not reference root URL correctly" -ForegroundColor Yellow
        }
    } else {
        Write-Host "✗ Service Worker returned status: $($response.StatusCode)" -ForegroundColor Red
    }
} catch {
    Write-Host "✗ Failed to fetch service worker: $_" -ForegroundColor Red
}
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Verification Complete!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Open https://ygxone.com/ in your browser" -ForegroundColor White
Write-Host "2. Verify the browser interface loads" -ForegroundColor White
Write-Host "3. Test navigation, tabs, and URL bar updates" -ForegroundColor White
Write-Host "4. Check PWA install prompt appears" -ForegroundColor White
Write-Host "5. Test all browser features (Research, Citations, etc.)" -ForegroundColor White