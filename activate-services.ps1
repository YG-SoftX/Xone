# =============================================================================
# 🚀 YGXONE Service Activation Script (PowerShell)
# =============================================================================
# Purpose: Instantly activates the 5 major modules in your ecosystem
# Modules: account, developer, master, docx, xcel
# =============================================================================

$ErrorActionPreference = "Stop"

# Colors for output formatting
function Write-Header($text) {
    Write-Host "`n========================================================" -ForegroundColor Cyan
    Write-Host " $text" -ForegroundColor White -BackgroundColor Blue
    Write-Host "========================================================" -ForegroundColor Cyan
}

function Write-Success($text) {
    Write-Host "  ✓ $text" -ForegroundColor Green
}

function Write-Info($text) {
    Write-Host "  → $text" -ForegroundColor Yellow
}

Write-Header "YGXONE MASTER SERVICE ACTIVATION ENGINE"

$services = @("account", "developer", "master", "docx", "xcel")
$rootPath = Get-Location

foreach ($service in $services) {
    Write-Header "ACTIVATING MODULE: $service"
    
    $servicePath = Join-Path $rootPath $service
    if (-not (Test-Path $servicePath)) {
        Write-Warning "Module directory '$service' not found. Skipping."
        continue
    }

    # Navigate to module
    Set-Location $servicePath
    Write-Info "Working directory: $servicePath"

    # Step 1: Handle .env File
    $envFile = Join-Path $servicePath ".env"
    $envExample = Join-Path $servicePath ".env.example"
    
    if (-not (Test-Path $envFile)) {
        if (Test-Path $envExample) {
            Write-Info "Copying .env.example to .env..."
            Copy-Item $envExample $envFile
            Write-Success ".env file created."
        } else {
            Write-Host "  ⚠ No .env.example found. Creating empty .env..." -ForegroundColor Yellow
            New-Item $envFile -ItemType File | Out-Null
        }
    } else {
        Write-Success ".env file already exists."
    }

    # Step 2: Install Composer Dependencies
    Write-Info "Installing Composer dependencies..."
    composer install --ignore-platform-reqs --no-interaction --no-plugins --no-scripts --prefer-dist
    Write-Success "Composer packages installed."

    # Step 3: Generate APP_KEY if empty
    $envContent = Get-Content $envFile -Raw
    if ($envContent -notmatch "APP_KEY=base64:") {
        Write-Info "Generating application key..."
        
        # Temporary SQLite switch to bypass MySQL connection errors
        $tempContent = $envContent -replace "DB_CONNECTION=mysql", "DB_CONNECTION=sqlite"
        $tempContent = $tempContent -replace "DB_DATABASE=ygmarket_account", "DB_DATABASE=:memory:"
        Set-Content $envFile -Value $tempContent -NoNewline
        
        # Generate key
        php artisan key:generate --force
        
        # Restore MySQL settings
        $restoredContent = Get-Content $envFile -Raw
        $restoredContent = $restoredContent -replace "DB_CONNECTION=sqlite", "DB_CONNECTION=mysql"
        $restoredContent = $restoredContent -replace "DB_DATABASE=:memory:", "DB_DATABASE=ygmarket_account"
        Set-Content $envFile -Value $restoredContent -NoNewline
        
        Write-Success "App key generated successfully."
    } else {
        Write-Success "App key already configured."
    }

    # Step 4: Clear Laravel Cache
    Write-Info "Clearing application caches..."
    php artisan optimize:clear
    Write-Success "Cache cleared successfully."
}

# Return to root directory
Set-Location $rootPath

Write-Header "🎉 ecosystem SERVICES SUCCESSFULLY ACTIVATED!"
Write-Host "Ready to deploy to cPanel. Push to Git and pull directly!" -ForegroundColor Green
