# =============================================================================
# YG Ecosystem - Generate APP_KEYs for All Modules
# =============================================================================
# This script installs dependencies (if needed) and generates APP_KEYs
# for all Laravel modules to ensure proper security
# =============================================================================

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  YG APP_KEY Generator" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

$modules = @(
    'home',
    'account',
    'mail',
    'calendar',
    'chat',
    'contacts',
    'drive',
    'notes',
    'xcel',
    'docx',
    'collect',
    'developer'
)

$SUCCESS_COUNT = 0
$SKIP_COUNT = 0
$ERROR_COUNT = 0

Write-Host "Processing $($modules.Count) modules...`n" -ForegroundColor Yellow

foreach ($module in $modules) {
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor DarkGray
    Write-Host "Module: $module" -ForegroundColor Cyan
    
    # Check if module directory exists
    if (-not (Test-Path $module)) {
        Write-Host "  ✗ Directory not found - SKIPPED`n" -ForegroundColor Yellow
        $SKIP_COUNT++
        continue
    }
    
    # Check if .env exists
    if (-not (Test-Path "$module\.env")) {
        Write-Host "  ⚠ No .env file found" -ForegroundColor Yellow
        if (Test-Path "$module\.env.example") {
            Copy-Item "$module\.env.example" "$module\.env"
            Write-Host "  ✓ Created .env from .env.example" -ForegroundColor Green
        } else {
            Write-Host "  ✗ No .env.example either - SKIPPED`n" -ForegroundColor Red
            $SKIP_COUNT++
            continue
        }
    }
    
    # Check if vendor directory exists, install if not
    if (-not (Test-Path "$module\vendor")) {
        Write-Host "  📦 Installing dependencies..." -ForegroundColor Yellow
        Push-Location $module
        
        try {
            composer install --no-dev --optimize-autoloader --quiet --no-interaction
            
            if ($LASTEXITCODE -eq 0) {
                Write-Host "  ✓ Dependencies installed" -ForegroundColor Green
            } else {
                Write-Host "  ✗ Failed to install dependencies" -ForegroundColor Red
                Pop-Location
                $ERROR_COUNT++
                continue
            }
        } catch {
            Write-Host "  ✗ Error installing dependencies: $_" -ForegroundColor Red
            Pop-Location
            $ERROR_COUNT++
            continue
        }
        
        Pop-Location
    } else {
        Write-Host "  ✓ Dependencies already installed" -ForegroundColor Green
    }
    
    # Generate APP_KEY
    Write-Host "  🔑 Generating APP_KEY..." -ForegroundColor Yellow
    Push-Location $module
    
    try {
        $output = php artisan key:generate --force 2>&1
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "  ✓ APP_KEY generated successfully" -ForegroundColor Green
            
            # Verify the key was set
            $envContent = Get-Content ".env" -Raw
            if ($envContent -match '^APP_KEY=base64:.+') {
                Write-Host "  ✓ APP_KEY verified in .env" -ForegroundColor Green
                $SUCCESS_COUNT++
            } else {
                Write-Host "  ⚠ APP_KEY may not be set correctly" -ForegroundColor Yellow
                $SUCCESS_COUNT++
            }
        } else {
            Write-Host "  ✗ Failed to generate APP_KEY" -ForegroundColor Red
            Write-Host "     Error: $output" -ForegroundColor DarkGray
            $ERROR_COUNT++
        }
    } catch {
        Write-Host "  ✗ Error: $_" -ForegroundColor Red
        $ERROR_COUNT++
    } finally {
        Pop-Location
    }
    
    Write-Host ""
}

# Summary
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  Summary" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

Write-Host "Successfully configured: $SUCCESS_COUNT modules" -ForegroundColor Green
Write-Host "Skipped:                 $SKIP_COUNT modules" -ForegroundColor Yellow
Write-Host "Errors:                  $ERROR_COUNT modules`n" -ForegroundColor $(if ($ERROR_COUNT -gt 0) { 'Red' } else { 'Green' })

if ($ERROR_COUNT -eq 0 -and $SKIP_COUNT -eq 0) {
    Write-Host "✅ All modules have APP_KEYs configured!" -ForegroundColor Green
    Write-Host "`nSecurity features enabled:" -ForegroundColor Cyan
    Write-Host "  • Session encryption" -ForegroundColor White
    Write-Host "  • Cookie encryption" -ForegroundColor White
    Write-Host "  • CSRF protection" -ForegroundColor White
    Write-Host "  • Signed URLs" -ForegroundColor White
    Write-Host "`nNext steps:" -ForegroundColor Cyan
    Write-Host "  1. Deploy to cPanel" -ForegroundColor White
    Write-Host "  2. Run migrations: php artisan migrate --force" -ForegroundColor White
    Write-Host "  3. Clear cache: php artisan config:clear" -ForegroundColor White
    Write-Host "  4. Test your application`n" -ForegroundColor White
} elseif ($ERROR_COUNT -gt 0) {
    Write-Host "⚠️  Some modules had errors. Please check above and fix manually." -ForegroundColor Yellow
    Write-Host "`nCommon issues:" -ForegroundColor Cyan
    Write-Host "  • Missing composer.json" -ForegroundColor White
    Write-Host "  • PHP version mismatch" -ForegroundColor White
    Write-Host "  • Network issues during composer install`n" -ForegroundColor White
}

Write-Host "For more information, see: UNIFIED_DATABASE_CONFIG.md`n" -ForegroundColor Cyan
