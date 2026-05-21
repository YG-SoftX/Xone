# =============================================================================
# YGXONE Browser - Advanced Features Deployment Script for Windows/cPanel
# =============================================================================
# Deploys Deep Research, Citations, Knowledge Graph features
# Compatible with cPanel shared hosting environments
# =============================================================================

$ErrorActionPreference = "Stop"

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "YGXONE Browser Advanced Features Deploy" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$AppDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $AppDir

Write-Host "Step 1/7: Checking environment..." -ForegroundColor Yellow
# Check PHP version
try {
    $phpVersion = & php -r "echo PHP_VERSION;"
    Write-Host "PHP Version: $phpVersion" -ForegroundColor Gray
} catch {
    Write-Host "Error: PHP not found in PATH" -ForegroundColor Red
    exit 1
}

# Check if artisan exists
if (-not (Test-Path "artisan")) {
    Write-Host "Error: artisan not found. Are you in the correct directory?" -ForegroundColor Red
    exit 1
}

Write-Host "✓ Environment check passed" -ForegroundColor Green
Write-Host ""

Write-Host "Step 2/7: Setting file permissions..." -ForegroundColor Yellow
# Set proper permissions
if (Test-Path ".env") {
    $acl = Get-Acl ".env"
    $acl.SetAccessRuleProtection($false, $true)
    Set-Acl ".env" $acl
}
Write-Host "✓ Permissions set" -ForegroundColor Green
Write-Host ""

Write-Host "Step 3/7: Installing/updating dependencies..." -ForegroundColor Yellow
# Install composer dependencies (production mode)
& composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | Select-Object -Last 5
Write-Host "✓ Dependencies installed" -ForegroundColor Green
Write-Host ""

Write-Host "Step 4/7: Clearing caches..." -ForegroundColor Yellow
# Clear all caches (order matters!)
& php artisan config:clear
& php artisan cache:clear
& php artisan view:clear
& php artisan route:clear
Write-Host "✓ Caches cleared" -ForegroundColor Green
Write-Host ""

Write-Host "Step 5/7: Running database migrations..." -ForegroundColor Yellow
# Run migrations for new tables
& php artisan migrate --force 2>&1 | Select-Object -Last 10
Write-Host "✓ Migrations completed" -ForegroundColor Green
Write-Host ""

Write-Host "Step 6/7: Rebuilding caches..." -ForegroundColor Yellow
# Rebuild optimized caches
& php artisan config:cache
& php artisan route:cache
& php artisan view:cache
Write-Host "✓ Caches rebuilt" -ForegroundColor Green
Write-Host ""

Write-Host "Step 7/7: Verifying deployment..." -ForegroundColor Yellow
# Verify key services are working
$routeList = & php artisan route:list
if ($routeList -match "api.research.deep") {
    Write-Host "✓ Deep Research routes registered" -ForegroundColor Green
} else {
    Write-Host "✗ Deep Research routes missing" -ForegroundColor Red
}

if ($routeList -match "api.citations") {
    Write-Host "✓ Citation routes registered" -ForegroundColor Green
} else {
    Write-Host "✗ Citation routes missing" -ForegroundColor Red
}

if ($routeList -match "api.knowledge-graph") {
    Write-Host "✓ Knowledge Graph routes registered" -ForegroundColor Green
} else {
    Write-Host "✗ Knowledge Graph routes missing" -ForegroundColor Red
}

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "Deployment Complete!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "New Features Available:" -ForegroundColor Cyan
Write-Host "  • Deep Research Mode - Multi-page synthesis" -ForegroundColor White
Write-Host "  • Smart Citations - Auto-tracking & export" -ForegroundColor White
Write-Host "  • Knowledge Graph - Entity relationship mapping" -ForegroundColor White
Write-Host ""
Write-Host "Access the browser at: https://ygxone.com/browser" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Configure AI API keys in Agent Settings" -ForegroundColor White
Write-Host "2. Test Deep Research with a sample query" -ForegroundColor White
Write-Host "3. Browse websites to populate citations" -ForegroundColor White
Write-Host "4. View Knowledge Graph connections" -ForegroundColor White
Write-Host ""
