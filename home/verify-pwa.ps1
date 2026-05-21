# PWA Browser App Verification Script
# Run this to verify PWA is properly configured

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "YGXONE Browser PWA Verification" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$baseUrl = "https://ygxone.com"

# Test 1: Check manifest
Write-Host "[Test 1] Checking PWA Manifest..." -ForegroundColor Yellow
try {
    $manifest = Invoke-RestMethod -Uri "$baseUrl/site.webmanifest" -Method GET -TimeoutSec 5
    Write-Host "  ✓ Manifest accessible" -ForegroundColor Green
    Write-Host "    Name: $($manifest.name)" -ForegroundColor Gray
    Write-Host "    Short Name: $($manifest.short_name)" -ForegroundColor Gray
    Write-Host "    Start URL: $($manifest.start_url)" -ForegroundColor Gray
    
    if ($manifest.start_url -eq "/") {
        Write-Host "    ✓ Start URL is correct (root)" -ForegroundColor Green
    } else {
        Write-Host "    ✗ Start URL should be '/' but is '$($manifest.start_url)'" -ForegroundColor Red
    }
    
    Write-Host "    Display Mode: $($manifest.display)" -ForegroundColor Gray
    Write-Host "    Theme Color: $($manifest.theme_color)" -ForegroundColor Gray
    Write-Host "    Icons: $($manifest.icons.Count) icon(s)" -ForegroundColor Gray
    
    if ($manifest.shortcuts) {
        Write-Host "    Shortcuts: $($manifest.shortcuts.Count) shortcut(s)" -ForegroundColor Gray
        foreach ($shortcut in $manifest.shortcuts) {
            Write-Host "      - $($shortcut.name): $($shortcut.url)" -ForegroundColor Gray
        }
    }
} catch {
    Write-Host "  ✗ Failed to fetch manifest: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# Test 2: Check service worker
Write-Host "[Test 2] Checking Service Worker..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/sw.js" -Method GET -TimeoutSec 5
    if ($response.StatusCode -eq 200) {
        Write-Host "  ✓ Service Worker accessible" -ForegroundColor Green
        $content = $response.Content
        if ($content -match "CACHE_NAME\s*=\s*'ygxone-browser-v\d+'") {
            Write-Host "    ✓ Cache versioning implemented" -ForegroundColor Green
        }
        if ($content -match "self\.addEventListener\('install'") {
            Write-Host "    ✓ Install event handler present" -ForegroundColor Green
        }
        if ($content -match "self\.addEventListener\('fetch'") {
            Write-Host "    ✓ Fetch event handler present" -ForegroundColor Green
        }
        if ($content -match "caches\.match\('\/'\)") {
            Write-Host "    ✓ Offline fallback configured" -ForegroundColor Green
        }
    } else {
        Write-Host "  ✗ Service Worker returned status: $($response.StatusCode)" -ForegroundColor Red
    }
} catch {
    Write-Host "  ✗ Failed to fetch service worker: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# Test 3: Check icons
Write-Host "[Test 3] Checking PWA Icons..." -ForegroundColor Yellow
$iconPaths = @('/icons/icon-192.png', '/icons/icon-512.png')
foreach ($iconPath in $iconPaths) {
    try {
        $response = Invoke-WebRequest -Uri "$baseUrl$iconPath" -Method HEAD -TimeoutSec 5
        if ($response.StatusCode -eq 200) {
            Write-Host "  ✓ $iconPath exists ($($response.Headers.'Content-Length'[0]) bytes)" -ForegroundColor Green
        } else {
            Write-Host "  ⚠ $iconPath returned status: $($response.StatusCode)" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "  ✗ $iconPath not found" -ForegroundColor Red
    }
}
Write-Host ""

# Test 4: Check meta tags in HTML
Write-Host "[Test 4] Checking HTML Meta Tags..." -ForegroundColor Yellow
try {
    $html = Invoke-WebRequest -Uri "$baseUrl/" -Method GET -TimeoutSec 5
    $content = $html.Content
    
    $checks = @{
        'Manifest link' = '<link[^>]+rel="manifest"';
        'Theme color' = '<meta[^>]+name="theme-color"';
        'Apple touch capable' = 'apple-mobile-web-app-capable';
        'Apple status bar' = 'apple-mobile-web-app-status-bar-style';
        'Service worker registration' = "navigator\.serviceWorker\.register";
    }
    
    foreach ($checkName in $checks.Keys) {
        if ($content -match $checks[$checkName]) {
            Write-Host "  ✓ $checkName present" -ForegroundColor Green
        } else {
            Write-Host "  ✗ $checkName missing" -ForegroundColor Red
        }
    }
} catch {
    Write-Host "  ✗ Failed to fetch homepage: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# Test 5: Check HTTPS
Write-Host "[Test 5] Checking HTTPS Configuration..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri $baseUrl -Method GET -TimeoutSec 5
    if ($response.BaseResponse.RequestMessage.RequestUri.Scheme -eq "https") {
        Write-Host "  ✓ HTTPS enabled" -ForegroundColor Green
    } else {
        Write-Host "  ✗ Not using HTTPS (required for PWA)" -ForegroundColor Red
    }
} catch {
    Write-Host "  ✗ Failed to check HTTPS: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# Test 6: Check install prompt configuration
Write-Host "[Test 6] Checking Install Prompt..." -ForegroundColor Yellow
try {
    $html = Invoke-WebRequest -Uri "$baseUrl/" -Method GET -TimeoutSec 5
    $content = $html.Content
    
    if ($content -match "beforeinstallprompt") {
        Write-Host "  ✓ Install prompt event handler present" -ForegroundColor Green
    } else {
        Write-Host "  ⚠ Install prompt event handler not found" -ForegroundColor Yellow
    }
    
    if ($content -match "pwa-install-prompt|Install.*App|Add.*Home") {
        Write-Host "  ✓ Install UI elements present" -ForegroundColor Green
    } else {
        Write-Host "  ⚠ Install UI elements may be missing" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  ✗ Failed to check install prompt" -ForegroundColor Red
}
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "PWA Verification Complete!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Summary:" -ForegroundColor Yellow
Write-Host "- If all tests pass, PWA is ready for installation" -ForegroundColor White
Write-Host "- Open https://ygxone.com/ in Chrome/Edge to test install" -ForegroundColor White
Write-Host "- For iOS: Use Safari → Share → Add to Home Screen" -ForegroundColor White
Write-Host "- For Android: Chrome menu → Install app" -ForegroundColor White
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Review full guide: PWA_BROWSER_GUIDE.md" -ForegroundColor White
Write-Host "2. Test installation on multiple devices" -ForegroundColor White
Write-Host "3. Run Lighthouse audit for PWA score" -ForegroundColor White