# =============================================================================
# YG Ecosystem - Unified Database Configuration Script
# =============================================================================
# This script configures ALL modules to use ONE shared database
# Run this BEFORE deploying to cPanel
# =============================================================================

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  YG Unified Database Configuration" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Database Configuration (Update these for your environment)
$DB_CONFIG = @{
    DB_HOST = '127.0.0.1'           # Use 'localhost' for cPanel
    DB_PORT = '3306'
    DB_DATABASE = 'ygmarket_account'  # Single database for all modules
    DB_USERNAME = 'ygmarket_account'
    DB_PASSWORD = 'Ygaccount@2.0##2026'  # CHANGE THIS IN PRODUCTION!
}

# List of all Laravel modules
$MODULES = @(
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

Write-Host "Configuring database for $($MODULES.Count) modules...`n" -ForegroundColor Yellow

foreach ($module in $MODULES) {
    $envPath = Join-Path $module '.env'
    $envExamplePath = Join-Path $module '.env.example'
    
    Write-Host "Processing: $module" -NoNewline
    
    # Check if .env exists, if not copy from .env.example
    if (-not (Test-Path $envPath)) {
        if (Test-Path $envExamplePath) {
            Copy-Item $envExamplePath $envPath
            Write-Host " (created from .env.example)" -ForegroundColor Gray
        } else {
            Write-Host " - SKIPPED (no .env or .env.example)" -ForegroundColor Yellow
            $SKIP_COUNT++
            continue
        }
    }
    
    try {
        # Read the .env file
        $content = Get-Content $envPath -Raw -Encoding UTF8
        
        # Update database configuration
        $replacements = @{
            '^DB_HOST=.*$' = "DB_HOST=$($DB_CONFIG.DB_HOST)"
            '^DB_PORT=.*$' = "DB_PORT=$($DB_CONFIG.DB_PORT)"
            '^DB_DATABASE=.*$' = "DB_DATABASE=$($DB_CONFIG.DB_DATABASE)"
            '^DB_USERNAME=.*$' = "DB_USERNAME=$($DB_CONFIG.DB_USERNAME)"
            '^DB_PASSWORD=.*$' = "DB_PASSWORD='$($DB_CONFIG.DB_PASSWORD)'"
        }
        
        foreach ($pattern in $replacements.Keys) {
            $content = $content -replace $pattern, $replacements[$pattern]
        }
        
        # Write back to file
        Set-Content $envPath -Value $content -Encoding UTF8 -NoNewline
        
        Write-Host " - ✓ Configured" -ForegroundColor Green
        $SUCCESS_COUNT++
        
    } catch {
        Write-Host " - ✗ ERROR: $($_.Exception.Message)" -ForegroundColor Red
        $ERROR_COUNT++
    }
}

# Summary
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  Configuration Summary" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

Write-Host "Successfully configured: $SUCCESS_COUNT modules" -ForegroundColor Green
Write-Host "Skipped:                 $SKIP_COUNT modules" -ForegroundColor Yellow
Write-Host "Errors:                  $ERROR_COUNT modules`n" -ForegroundColor $(if ($ERROR_COUNT -gt 0) { 'Red' } else { 'Green' })

Write-Host "Database Configuration:" -ForegroundColor Cyan
Write-Host "  Host:     $($DB_CONFIG.DB_HOST)" -ForegroundColor White
Write-Host "  Port:     $($DB_CONFIG.DB_PORT)" -ForegroundColor White
Write-Host "  Database: $($DB_CONFIG.DB_DATABASE)" -ForegroundColor White
Write-Host "  Username: $($DB_CONFIG.DB_USERNAME)" -ForegroundColor White
Write-Host "  Password: ********`n" -ForegroundColor White

if ($ERROR_COUNT -eq 0) {
    Write-Host "✅ All modules configured successfully!" -ForegroundColor Green
    Write-Host "`nNext steps:" -ForegroundColor Cyan
    Write-Host "1. Review .env files if needed" -ForegroundColor White
    Write-Host "2. For cPanel: Update DB credentials with your cPanel username" -ForegroundColor White
    Write-Host "3. Run migrations: php artisan migrate --force (in each module)" -ForegroundColor White
    Write-Host "4. Clear cache: php artisan config:clear (in each module)`n" -ForegroundColor White
} else {
    Write-Host "⚠️  Some modules had errors. Please check and fix manually." -ForegroundColor Yellow
}

Write-Host "For detailed guide, see: UNIFIED_DATABASE_CONFIG.md`n" -ForegroundColor Cyan
