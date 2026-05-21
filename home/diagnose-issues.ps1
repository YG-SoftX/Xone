# Diagnostic Script for Browser & Login Issues
# Run this to identify what's causing problems

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "YGXONE Diagnostic Tool" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$baseUrl = "https://ygxone.com"

# Test 1: Check if routes are registered
Write-Host "[Test 1] Checking SSO Routes..." -ForegroundColor Yellow
try {
    $routes = @('/sso/initiate', '/sso/callback', '/sso/logout', '/login')
    foreach ($route in $routes) {
        try {
            $response = Invoke-WebRequest -Uri "$baseUrl$route" -Method GET -TimeoutSec 5 -MaximumRedirection 0 -ErrorAction SilentlyContinue
            Write-Host "  ✓ $route - Status: $($response.StatusCode)" -ForegroundColor Green
        } catch {
            if ($_.Exception.Response.StatusCode -eq 302 -or $_.Exception.Response.StatusCode -eq 301) {
                Write-Host "  ✓ $route - Redirects (expected)" -ForegroundColor Green
            } else {
                Write-Host "  ✗ $route - Error: $($_.Exception.Message)" -ForegroundColor Red
            }
        }
    }
} catch {
    Write-Host "  ✗ Failed to check routes" -ForegroundColor Red
}
Write-Host ""

# Test 2: Check browser proxy
Write-Host "[Test 2] Testing Browser Proxy..." -ForegroundColor Yellow
try {
    $testUrl = "$baseUrl/browse?url=https://www.google.com"
    $response = Invoke-WebRequest -Uri $testUrl -Method GET -TimeoutSec 10 -ErrorAction SilentlyContinue
    if ($response.StatusCode -eq 200) {
        Write-Host "  ✓ Proxy works - Fetched $($response.Content.Length) bytes" -ForegroundColor Green
    } else {
        Write-Host "  ⚠ Proxy returned status: $($response.StatusCode)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  ✗ Proxy failed: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "  → This indicates cURL or network issues" -ForegroundColor Gray
}
Write-Host ""

# Test 3: Check account service connectivity
Write-Host "[Test 3] Checking Account Service..." -ForegroundColor Yellow
try {
    $accountUrl = "https://account.ygxone.com/up"
    $response = Invoke-WebRequest -Uri $accountUrl -Method GET -TimeoutSec 5 -ErrorAction SilentlyContinue
    if ($response.StatusCode -eq 200) {
        Write-Host "  ✓ Account service is healthy" -ForegroundColor Green
    } else {
        Write-Host "  ✗ Account service returned: $($response.StatusCode)" -ForegroundColor Red
    }
} catch {
    Write-Host "  ✗ Cannot reach account service: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "  → Check YG_ACCOUNT_URL in .env file" -ForegroundColor Gray
}
Write-Host ""

# Test 4: Check PWA manifest
Write-Host "[Test 4] Checking PWA Configuration..." -ForegroundColor Yellow
try {
    $manifest = Invoke-RestMethod -Uri "$baseUrl/site.webmanifest" -Method GET -TimeoutSec 5
    if ($manifest.start_url -eq "/") {
        Write-Host "  ✓ PWA start_url is correct: '/'" -ForegroundColor Green
    } else {
        Write-Host "  ⚠ PWA start_url is: '$($manifest.start_url)' (should be '/')" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  ✗ Failed to fetch manifest" -ForegroundColor Red
}
Write-Host ""

# Test 5: Check health endpoint
Write-Host "[Test 5] Checking Application Health..." -ForegroundColor Yellow
try {
    $health = Invoke-RestMethod -Uri "$baseUrl/up" -Method GET -TimeoutSec 5
    if ($health.status -eq "healthy") {
        Write-Host "  ✓ Application is healthy" -ForegroundColor Green
        Write-Host "    Database: $($health.checks.database)" -ForegroundColor Gray
        Write-Host "    Cache: $($health.checks.cache)" -ForegroundColor Gray
        Write-Host "    Search Index: $($health.checks.search_index)" -ForegroundColor Gray
    } else {
        Write-Host "  ⚠ Application status: $($health.status)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  ✗ Health check failed: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Diagnostic Complete!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. If SSO routes show errors → Clear caches: php artisan route:clear" -ForegroundColor White
Write-Host "2. If proxy fails → Check cURL extension and firewall rules" -ForegroundColor White
Write-Host "3. If account service unreachable → Verify YG_ACCOUNT_URL in .env" -ForegroundColor White
Write-Host "4. Review full troubleshooting guide: LOGIN_BROWSER_TROUBLESHOOTING.md" -ForegroundColor White