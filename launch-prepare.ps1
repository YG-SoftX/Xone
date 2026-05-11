# ============================================================================
# YG Systems - Quick Launch Script (PowerShell)
# ============================================================================
# Run this in PowerShell to prepare both YG Account and YG Home for launch
# ============================================================================

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  🚀 YG Systems Launch Preparation" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Function to setup a Laravel project
function Setup-LaravelProject {
    param(
        [string]$ProjectName,
        [string]$ProjectPath
    )
    
    Write-Host ""
    Write-Host "[$ProjectName]" -ForegroundColor Yellow
    Write-Host "========================================" -ForegroundColor Yellow
    
    # Check if directory exists
    if (-not (Test-Path $ProjectPath)) {
        Write-Host "[ERROR] Directory not found: $ProjectPath" -ForegroundColor Red
        return $false
    }
    
    Set-Location $ProjectPath
    
    # Step 1: Install dependencies
    Write-Host "[1/6] Installing dependencies..." -ForegroundColor Blue
    try {
        composer install --no-dev --optimize-autoloader --quiet
        Write-Host "✓ Dependencies installed" -ForegroundColor Green
    } catch {
        Write-Host "[ERROR] Failed to install dependencies" -ForegroundColor Red
        return $false
    }
    
    # Step 2: Setup environment
    Write-Host "[2/6] Configuring environment..." -ForegroundColor Blue
    if (-not (Test-Path ".env")) {
        if (Test-Path ".env.example") {
            Copy-Item ".env.example" ".env"
            Write-Host "✓ Created .env from .env.example" -ForegroundColor Green
        } else {
            Write-Host "[ERROR] No .env or .env.example found" -ForegroundColor Red
            return $false
        }
    }
    
    # Generate APP_KEY
    $envContent = Get-Content ".env" -Raw
    if ($envContent -match "^APP_KEY=$" -or $envContent -notmatch "^APP_KEY=") {
        php artisan key:generate --force
        Write-Host "✓ APP_KEY generated" -ForegroundColor Green
    } else {
        Write-Host "✓ APP_KEY already configured" -ForegroundColor Green
    }
    
    # Step 3: Database setup
    Write-Host "[3/6] Setting up database..." -ForegroundColor Blue
    try {
        php artisan migrate --force --no-interaction
        Write-Host "✓ Database migrations complete" -ForegroundColor Green
    } catch {
        Write-Host "[WARNING] Database migration failed (may need manual setup)" -ForegroundColor Yellow
    }
    
    # Step 4: Create session/cache tables (YG Home only)
    if ($ProjectName -eq "YG Home") {
        Write-Host "[4/7] Creating session/cache tables..." -ForegroundColor Blue
        php artisan session:table --force 2>$null
        php artisan cache:table --force 2>$null
        php artisan migrate --force --no-interaction
        Write-Host "✓ Session/cache tables ready" -ForegroundColor Green
        
        # Sync search indexes
        Write-Host "[5/7] Syncing search indexes..." -ForegroundColor Blue
        php artisan search:sync --no-interaction 2>$null
        Write-Host "✓ Search indexes synced" -ForegroundColor Green
    }
    
    # Step 5: Build frontend assets
    Write-Host "[$(if ($ProjectName -eq 'YG Home') {'6'})/7] Building frontend assets..." -ForegroundColor Blue
    if (Test-Path "package.json") {
        if (Get-Command npm -ErrorAction SilentlyContinue) {
            npm ci --production 2>$null | Out-Null
            npm run build 2>$null | Out-Null
            Write-Host "✓ Assets built" -ForegroundColor Green
        } else {
            Write-Host "[WARNING] npm not found, skipping asset build" -ForegroundColor Yellow
        }
    } else {
        Write-Host "✓ No frontend assets to build" -ForegroundColor Green
    }
    
    # Step 6: Set permissions
    Write-Host "[$(if ($ProjectName -eq 'YG Home') {'7'})/7] Setting file permissions..." -ForegroundColor Blue
    try {
        icacls "storage" /grant "Everyone:(OI)(CI)F" /T 2>$null | Out-Null
        icacls "bootstrap\cache" /grant "Everyone:(OI)(CI)F" /T 2>$null | Out-Null
        Write-Host "✓ Permissions set" -ForegroundColor Green
    } catch {
        Write-Host "[WARNING] Permission setting skipped" -ForegroundColor Yellow
    }
    
    # Step 7: Optimize for production
    Write-Host "[$(if ($ProjectName -eq 'YG Home') {'8'})/8] Optimizing for production..." -ForegroundColor Blue
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    Write-Host "✓ Production optimizations applied" -ForegroundColor Green
    
    Write-Host ""
    Write-Host "✓ $ProjectName Ready!" -ForegroundColor Green
    return $true
}

# Main execution
$CurrentDir = Get-Location

# Setup YG Account
$AccountPath = Join-Path $CurrentDir "yg-account"
if (Test-Path $AccountPath) {
    Setup-LaravelProject -ProjectName "YG Account" -ProjectPath $AccountPath
} else {
    Write-Host "[WARNING] YG Account directory not found at: $AccountPath" -ForegroundColor Yellow
}

# Setup YG Home
$HomePath = Join-Path $CurrentDir "yg-home"
if (Test-Path $HomePath) {
    Setup-LaravelProject -ProjectName "YG Home" -ProjectPath $HomePath
} else {
    Write-Host "[WARNING] YG Home directory not found at: $HomePath" -ForegroundColor Yellow
}

# Final summary
Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "  ✓ Launch Preparation Complete!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Cyan
Write-Host "1. Configure web server (Nginx/Apache/IIS)" -ForegroundColor White
Write-Host "2. Install SSL certificates" -ForegroundColor White
Write-Host "3. Update .env files with production settings" -ForegroundColor White
Write-Host "4. Setup scheduled tasks/cron jobs" -ForegroundColor White
Write-Host "5. Test both systems thoroughly" -ForegroundColor White
Write-Host ""
Write-Host "For detailed instructions, see: LAUNCH_TODAY_PLAN.md" -ForegroundColor Yellow
Write-Host ""
