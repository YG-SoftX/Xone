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

# Check if we are running in cPanel environment
$isProduction = $false
if ($rootPath -match "ygmarket" -or $env:COMPUTERNAME -match "access") {
    $isProduction = $true
    Write-Info "Production environment detected! Setting production APP_URLs and session sharing."
} else {
    Write-Info "Development environment detected."
}

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
    $envProdExample = Join-Path $servicePath ".env.production.example"
    
    if (-not (Test-Path $envFile)) {
        if ($service -eq "docx" -and $isProduction -and (Test-Path $envProdExample)) {
            Write-Info "Copying .env.production.example to .env..."
            Copy-Item $envProdExample $envFile
        } elseif (Test-Path $envExample) {
            Write-Info "Copying .env.example to .env..."
            Copy-Item $envExample $envFile
        } else {
            Write-Host "  ⚠ No .env.example found. Creating empty .env..." -ForegroundColor Yellow
            New-Item $envFile -ItemType File | Out-Null
        }
        Write-Success ".env file created."
    } else {
        Write-Success ".env file already exists."
    }

    # Step 2: Configure Environment Settings (APP_URL, DB, Session sharing)
    $envContent = Get-Content $envFile -Raw
    if ($isProduction) {
        Write-Info "Configuring production environment tokens..."
        
        # Set proper subdomains
        $envContent = $envContent -replace "APP_URL=http://localhost.*", "APP_URL=https://$service.ygxone.com"
        $envContent = $envContent -replace "APP_URL=http://127.0.0.1.*", "APP_URL=https://$service.ygxone.com"
        
        # Configure cross-subdomain SSO sessions
        $envContent = $envContent -replace "SESSION_DRIVER=.*", "SESSION_DRIVER=database"
        $envContent = $envContent -replace "SESSION_DOMAIN=.*", "SESSION_DOMAIN=.ygxone.com"
        $envContent = $envContent -replace "SESSION_SECURE_COOKIE=.*", "SESSION_SECURE_COOKIE=true"
        
        # Set database host to localhost
        $envContent = $envContent -replace "DB_HOST=127.0.0.1", "DB_HOST=localhost"

        # Handle service integrations and SSO URLs
        $envContent = $envContent -replace "YG_ACCOUNT_URL=.*", "YG_ACCOUNT_URL=https://account.ygxone.com"
        $envContent = $envContent -replace "YG_ACCOUNT_API_URL=.*", "YG_ACCOUNT_API_URL=https://account.ygxone.com/api"
        
        Set-Content $envFile -Value $envContent -NoNewline
        Write-Success "Production URLs and Session SSO configured successfully."
    } else {
        Write-Info "Applying local development APP_URL configurations..."
        if ($service -eq "account") {
            $envContent = $envContent -replace "APP_URL=.*", "APP_URL=http://localhost:8000"
        } elseif ($service -eq "developer") {
            $envContent = $envContent -replace "APP_URL=.*", "APP_URL=http://localhost:8010"
        } else {
            $envContent = $envContent -replace "APP_URL=.*", "APP_URL=http://localhost"
        }
        Set-Content $envFile -Value $envContent -NoNewline
        Write-Success "Local APP_URL configured."
    }

    # Step 3: Install Composer Dependencies
    Write-Info "Installing Composer dependencies..."
    composer install --ignore-platform-reqs --no-interaction --no-plugins --no-scripts --prefer-dist
    Write-Success "Composer packages installed."

    # Step 4: Generate APP_KEY if empty
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

    # Step 5: Clear Laravel Cache
    Write-Info "Clearing application caches..."
    php artisan optimize:clear
    Write-Success "Cache cleared successfully."
}

# Return to root directory
Set-Location $rootPath

Write-Header "🎉 ecosystem SERVICES SUCCESSFULLY ACTIVATED!"
Write-Host "Ready to deploy to cPanel. Push to Git and pull directly!" -ForegroundColor Green
