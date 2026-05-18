# =============================================================================
# 🚀 YGXONE Service Activation Script (PowerShell)
# =============================================================================
# Purpose: Instantly activates all modules in your ecosystem
# Modules: home, account, developer, master, docx, xcel
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

$services = @("home", "account", "developer", "master", "docx", "xcel")
$rootPath = Get-Location

# Check if we are running in cPanel environment
$isProduction = $false
if ($rootPath -match "ygmarket" -or $env:COMPUTERNAME -match "access") {
    $isProduction = $true
    Write-Info "Production environment detected! Setting production APP_URLs, sharing sessions, and database."
} else {
    Write-Info "Development environment detected."
}

# Step 1: Bootstrap all .env files and directories first
foreach ($service in $services) {
    $servicePath = Join-Path $rootPath $service
    if (-not (Test-Path $servicePath)) {
        continue
    }

    # Navigate to module
    Set-Location $servicePath

    # Ensure directories exist
    $storageDirs = @(
        "storage/framework/cache/data",
        "storage/framework/sessions",
        "storage/framework/views",
        "storage/logs",
        "bootstrap/cache"
    )
    foreach ($dir in $storageDirs) {
        $fullDir = Join-Path $servicePath $dir
        if (-not (Test-Path $fullDir)) {
            New-Item -ItemType Directory -Path $fullDir -Force | Out-Null
        }
    }

    # Handle .env File
    $envFile = Join-Path $servicePath ".env"
    $envExample = Join-Path $servicePath ".env.example"
    $envProdExample = Join-Path $servicePath ".env.production.example"
    
    if (-not (Test-Path $envFile)) {
        if ($service -eq "docx" -and $isProduction -and (Test-Path $envProdExample)) {
            Copy-Item $envProdExample $envFile
        } elseif (Test-Path $envExample) {
            Copy-Item $envExample $envFile
        } else {
            New-Item $envFile -ItemType File | Out-Null
        }
    }

    # Ensure database variables and APP_KEY placeholder exist
    $envContent = Get-Content $envFile -Raw
    if ($envContent -notmatch "DB_CONNECTION=") {
        $dbGaps = @"

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ygmarket_account
DB_USERNAME=ygmarket_account
DB_PASSWORD='Ygaccount@2.0##2026'

"@
        $envContent = $envContent + $dbGaps
        Set-Content $envFile -Value $envContent -NoNewline
    }

    # Ensure ecosystem SSO URL variables exist (prevents localhost redirections)
    if ($envContent -notmatch "YG_ACCOUNT_URL=") {
        $ssoGaps = @"

YG_ACCOUNT_URL=https://account.ygxone.com
YG_ACCOUNT_API_URL=https://account.ygxone.com/api
YG_MAIL_URL=https://mail.ygxone.com
YG_DRIVE_URL=https://drive.ygxone.com
YG_MASTER_URL=https://master.ygxone.com

"@
        $envContent = $envContent + $ssoGaps
        Set-Content $envFile -Value $envContent -NoNewline
    }

    if ($envContent -notmatch "APP_KEY=") {
        $envContent = $envContent + "`nAPP_KEY=`n"
        Set-Content $envFile -Value $envContent -NoNewline
    }
}

# Step 2: Synchronize APP_KEY across all modules for Single Sign-On (SSO)
Write-Info "Synchronizing APP_KEY across all modules to enable Single Sign-On (SSO)..."
$accountPath = Join-Path $rootPath "account"
Set-Location $accountPath
$accountEnvFile = Join-Path $accountPath ".env"
$accountEnvContent = Get-Content $accountEnvFile -Raw

if ($accountEnvContent -notmatch "APP_KEY=base64:") {
    # Temporarily switch to SQLite
    $tempContent = $accountEnvContent -replace "DB_CONNECTION=mysql", "DB_CONNECTION=sqlite"
    $tempContent = $tempContent -replace "DB_DATABASE=ygmarket_account", "DB_DATABASE=:memory:"
    Set-Content $accountEnvFile -Value $tempContent -NoNewline
    
    php artisan key:generate --force
    
    # Restore
    $restoredContent = Get-Content $accountEnvFile -Raw
    $restoredContent = $restoredContent -replace "DB_CONNECTION=sqlite", "DB_CONNECTION=mysql"
    $restoredContent = $restoredContent -replace "DB_DATABASE=:memory:", "DB_DATABASE=ygmarket_account"
    Set-Content $accountEnvFile -Value $restoredContent -NoNewline
}

# Extract key
$accountEnvContent = Get-Content $accountEnvFile -Raw
$sharedKey = ""
if ($accountEnvContent -match "APP_KEY=(base64:[^\r\n]*)") {
    $sharedKey = $Matches[1]
}

# Apply shared key to all other modules
foreach ($service in $services) {
    if ($service -ne "account") {
        $servicePath = Join-Path $rootPath $service
        if (Test-Path $servicePath) {
            $sEnvFile = Join-Path $servicePath ".env"
            if (Test-Path $sEnvFile) {
                $sEnvContent = Get-Content $sEnvFile -Raw
                $sEnvContent = $sEnvContent -replace "APP_KEY=.*", "APP_KEY=$sharedKey"
                Set-Content $sEnvFile -Value $sEnvContent -NoNewline
            }
        }
    }
}
Write-Success "Ecosystem-wide SSO APP_KEY synchronization complete!"

# Step 3: Run full service configuration, dependency installation, and caching
foreach ($service in $services) {
    Write-Header "ACTIVATING MODULE: $service"
    
    $servicePath = Join-Path $rootPath $service
    if (-not (Test-Path $servicePath)) {
        Write-Warning "Module directory '$service' not found. Skipping."
        continue
    }

    Set-Location $servicePath
    Write-Info "Working directory: $servicePath"

    # Step 4: Configure Environment Settings (APP_URL, DB, Session sharing)
    $envFile = Join-Path $servicePath ".env"
    $envContent = Get-Content $envFile -Raw
    if ($isProduction) {
        Write-Info "Configuring production environment tokens and DB credentials..."
        
        # Set proper subdomains (home service maps to ygxone.com without subdomain)
        if ($service -eq "home") {
            $envContent = $envContent -replace "APP_URL=http://localhost.*", "APP_URL=https://ygxone.com"
            $envContent = $envContent -replace "APP_URL=http://127.0.0.1.*", "APP_URL=https://ygxone.com"
            $envContent = $envContent -replace "APP_URL=https://home.ygxone.com", "APP_URL=https://ygxone.com"
        } else {
            $envContent = $envContent -replace "APP_URL=http://localhost.*", "APP_URL=https://$service.ygxone.com"
            $envContent = $envContent -replace "APP_URL=http://127.0.0.1.*", "APP_URL=https://$service.ygxone.com"
        }
        
        # Configure cross-subdomain SSO sessions
        $envContent = $envContent -replace "SESSION_DRIVER=.*", "SESSION_DRIVER=database"
        $envContent = $envContent -replace "SESSION_DOMAIN=.*", "SESSION_DOMAIN=.ygxone.com"
        $envContent = $envContent -replace "SESSION_SECURE_COOKIE=.*", "SESSION_SECURE_COOKIE=true"
        
        # Configure actual cPanel MySQL credentials
        $envContent = $envContent -replace "DB_CONNECTION=.*", "DB_CONNECTION=mysql"
        $envContent = $envContent -replace "DB_HOST=.*", "DB_HOST=127.0.0.1"
        $envContent = $envContent -replace "DB_PORT=.*", "DB_PORT=3306"
        $envContent = $envContent -replace "DB_DATABASE=.*", "DB_DATABASE=ygmarket_account"
        $envContent = $envContent -replace "DB_USERNAME=.*", "DB_USERNAME=ygmarket_account"
        $envContent = $envContent -replace "DB_PASSWORD=.*", "DB_PASSWORD='Ygaccount@2.0##2026'"

        # Handle service integrations and SSO URLs (Force secure production URLs)
        $envContent = $envContent -replace "YG_ACCOUNT_URL=.*", "YG_ACCOUNT_URL=https://account.ygxone.com"
        $envContent = $envContent -replace "YG_ACCOUNT_API_URL=.*", "YG_ACCOUNT_API_URL=https://account.ygxone.com/api"
        $envContent = $envContent -replace "YG_MAIL_URL=.*", "YG_MAIL_URL=https://mail.ygxone.com"
        $envContent = $envContent -replace "YG_DRIVE_URL=.*", "YG_DRIVE_URL=https://drive.ygxone.com"
        $envContent = $envContent -replace "YG_MASTER_URL=.*", "YG_MASTER_URL=https://master.ygxone.com"
        
        Set-Content $envFile -Value $envContent -NoNewline
        Write-Success "Production URLs, Database credentials, and Session SSO configured successfully."
    } else {
        Write-Info "Applying local development APP_URL configurations..."
        if ($service -eq "home") {
            $envContent = $envContent -replace "APP_URL=.*", "APP_URL=http://localhost:8000"
        } elseif ($service -eq "account") {
            $envContent = $envContent -replace "APP_URL=.*", "APP_URL=http://localhost:8000"
        } elseif ($service -eq "developer") {
            $envContent = $envContent -replace "APP_URL=.*", "APP_URL=http://localhost:8010"
        } else {
            $envContent = $envContent -replace "APP_URL=.*", "APP_URL=http://localhost"
        }
        Set-Content $envFile -Value $envContent -NoNewline
        Write-Success "Local APP_URL configured."
    }

    # Step 5: Install Composer Dependencies
    Write-Info "Installing Composer dependencies..."
    composer install --ignore-platform-reqs --no-interaction --no-plugins --no-scripts --prefer-dist
    Write-Success "Composer packages installed."

    # Step 6: Clear Laravel Cache
    Write-Info "Clearing application caches..."
    php artisan optimize:clear
    Write-Success "Cache cleared successfully."
}

# Return to root directory
Set-Location $rootPath

Write-Header "🎉 ALL ECOSYSTEM SERVICES SUCCESSFULLY ACTIVATED!"
Write-Host "Ready to deploy to cPanel. Push to Git and pull directly!" -ForegroundColor Green
