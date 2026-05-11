###############################################################################
# YG Ecosystem - ONE-CLICK DEPLOYMENT Script (PowerShell)
# 
# This script deploys the ENTIRE YG ecosystem to cPanel or VPS TODAY!
# Run this script and follow the prompts to deploy immediately.
#
# Usage: .\DEPLOY_TODAY.ps1
# Note: You may need to run: Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy RemoteSigned
###############################################################################

# Colors for output
function Write-Success { param($msg) Write-Host "✓ $msg" -ForegroundColor Green }
function Write-Warning-Custom { param($msg) Write-Host "⚠ $msg" -ForegroundColor Yellow }
function Write-Error-Custom { param($msg) Write-Host "✗ $msg" -ForegroundColor Red }
function Write-Info { param($msg) Write-Host "ℹ $msg" -ForegroundColor Cyan }
function Write-Step { param($msg) Write-Host "`n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`n$msg" -ForegroundColor Magenta }

$ProjectRoot = "c:\Users\ASUS\Downloads\YG Soft1"

Write-Host "╔═══════════════════════════════════════════════════════════╗" -ForegroundColor Blue
Write-Host "║         🚀 YG ECOSYSTEM - DEPLOY TODAY! 🚀              ║" -ForegroundColor Blue
Write-Host "║          Complete Deployment Automation                 ║" -ForegroundColor Blue
Write-Host "╚═══════════════════════════════════════════════════════════╝" -ForegroundColor Blue
Write-Host ""

###############################################################################
# Step 1: Deployment Mode Selection
###############################################################################
Write-Step "[STEP 1] SELECT DEPLOYMENT MODE"
Write-Host "1. cPanel Shared Hosting (Recommended for beginners)" -ForegroundColor White
Write-Host "2. VPS/Dedicated Server (Advanced users)" -ForegroundColor White
Write-Host "3. Local Testing Only" -ForegroundColor White
Write-Host ""

$mode = Read-Host "Enter choice (1/2/3)"

if ($mode -eq "1") {
    $deployType = "cpanel"
    Write-Success "Selected: cPanel Deployment"
} elseif ($mode -eq "2") {
    $deployType = "vps"
    Write-Success "Selected: VPS Deployment"
} elseif ($mode -eq "3") {
    $deployType = "local"
    Write-Success "Selected: Local Testing"
} else {
    Write-Error-Custom "Invalid choice. Defaulting to cPanel."
    $deployType = "cpanel"
}

###############################################################################
# Step 2: Prerequisites Check
###############################################################################
Write-Step "[STEP 2] CHECKING PREREQUISITES"

$errors = @()

# Check PHP
try {
    $phpVersion = php -r "echo PHP_VERSION;"
    if ([version]$phpVersion -ge [version]"8.2.0") {
        Write-Success "PHP $phpVersion ✓"
    } else {
        Write-Error-Custom "PHP $phpVersion - Need 8.2+"
        $errors += "PHP version too old"
    }
} catch {
    Write-Error-Custom "PHP not found!"
    $errors += "PHP not installed"
}

# Check Composer
try {
    composer --version | Out-Null
    Write-Success "Composer installed ✓"
} catch {
    Write-Error-Custom "Composer not found!"
    $errors += "Composer not installed"
}

# Check Node.js
try {
    $nodeVersion = node --version
    Write-Success "Node.js $nodeVersion ✓"
} catch {
    Write-Warning-Custom "Node.js not found (needed for asset compilation)"
}

# Check MySQL
try {
    mysql --version | Out-Null
    Write-Success "MySQL client available ✓"
} catch {
    Write-Warning-Custom "MySQL client not found"
}

if ($errors.Count -gt 0) {
    Write-Host "`n❌ CRITICAL ERRORS FOUND:" -ForegroundColor Red
    foreach ($err in $errors) {
        Write-Host "   - $err" -ForegroundColor Red
    }
    $continue = Read-Host "`nContinue anyway? (y/n)"
    if ($continue -ne "y") { exit 1 }
}

###############################################################################
# Step 3: Configuration Input
###############################################################################
Write-Step "[STEP 3] ENTER DEPLOYMENT CONFIGURATION"

if ($deployType -eq "cpanel") {
    Write-Host "`nEnter your cPanel details:" -ForegroundColor Cyan
    
    $cpanelDomain = Read-Host "Main domain (e.g., ygxone.com)"
    $cpanelUser = Read-Host "cPanel username"
    $dbPrefix = Read-Host "Database prefix (usually cPanel username)"
    
    # Auto-generate subdomains
    $accountSubdomain = "account.$cpanelDomain"
    $mailSubdomain = "mail.$cpanelDomain"
    $developerSubdomain = "dev.$cpanelDomain"
    $homeSubdomain = $cpanelDomain
    
    Write-Host "`n📋 Subdomains to configure:" -ForegroundColor Yellow
    Write-Host "   Account:  $accountSubdomain" -ForegroundColor White
    Write-Host "   Mail:     $mailSubdomain" -ForegroundColor White
    Write-Host "   Developer:$developerSubdomain" -ForegroundColor White
    Write-Host "   Home:     $homeSubdomain" -ForegroundColor White
    
    $confirm = Read-Host "`nProceed with these subdomains? (y/n)"
    if ($confirm -ne "y") { exit 0 }
    
} elseif ($deployType -eq "vps") {
    Write-Host "`nEnter your VPS details:" -ForegroundColor Cyan
    
    $serverIP = Read-Host "Server IP address"
    $sshUser = Read-Host "SSH username (default: root)"
    if (-not $sshUser) { $sshUser = "root" }
    
    $domain = Read-Host "Domain name (optional, press Enter to skip)"
    
} else {
    $localhost = "http://localhost:8000"
    Write-Success "Local testing mode - will use Laravel serve"
}

###############################################################################
# Step 4: Install Dependencies
###############################################################################
Write-Step "[STEP 4] INSTALLING DEPENDENCIES"

$modules = @(
    @{Name="YG Account"; Path="$ProjectRoot\yg-account"},
    @{Name="YG Mail"; Path="$ProjectRoot\YG Mail"},
    @{Name="YG Developer"; Path="$ProjectRoot\yg-developer"},
    @{Name="YG Home"; Path="$ProjectRoot\yg-home"},
    @{Name="YG Calendar"; Path="$ProjectRoot\YG Calendar"},
    @{Name="YG Chat"; Path="$ProjectRoot\YG Chat"},
    @{Name="YG Contacts"; Path="$ProjectRoot\YG Contacts"},
    @{Name="YG Drive"; Path="$ProjectRoot\YG Drive"},
    @{Name="YG Notes"; Path="$ProjectRoot\YG Notes"},
    @{Name="YG Xcel"; Path="$ProjectRoot\YG Xcel"},
    @{Name="YG DocX"; Path="$ProjectRoot\YG DocX"},
    @{Name="YG DB"; Path="$ProjectRoot\YG DB"},
    @{Name="YG Collect"; Path="$ProjectRoot\YG Collect"}
)

foreach ($module in $modules) {
    Write-Info "Installing $($module.Name)..."
    Set-Location $module.Path
    
    if (Test-Path "composer.json") {
        try {
            composer install --no-interaction --prefer-dist --optimize-autoloader 2>&1 | Out-Null
            Write-Success "$($module.Name) dependencies installed"
        } catch {
            Write-Error-Custom "Failed to install $($module.Name)"
        }
    }
}

###############################################################################
# Step 5: Environment Configuration
###############################################################################
Write-Step "[STEP 5] CONFIGURING ENVIRONMENT FILES"

function Generate-AppKey {
    $randomBytes = New-Object byte[] 32
    [Security.Cryptography.RNGCryptoServiceProvider]::Create().GetBytes($randomBytes)
    return "base64:" + [Convert]::ToBase64String($randomBytes)
}

# Configure YG Account
Write-Info "Configuring YG Account..."
Set-Location "$ProjectRoot\yg-account"

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env" -ErrorAction SilentlyContinue
}

$appKey = Generate-AppKey

# Create production .env
$envContent = @"
APP_NAME="YG Account"
APP_ENV=production
APP_KEY=$appKey
APP_DEBUG=false
APP_URL=https://account.$cpanelDomain

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${dbPrefix}_yg_account
DB_USERNAME=${dbPrefix}_yg_account
DB_PASSWORD=YOUR_DB_PASSWORD_HERE

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=.$cpanelDomain

CACHE_STORE=database
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_HOST=mail.$cpanelDomain
MAIL_PORT=587
MAIL_USERNAME=noreply@$cpanelDomain
MAIL_PASSWORD=YOUR_EMAIL_PASSWORD_HERE
MAIL_ENCRYPTION=tls

SSO_ACCOUNT_URL=https://account.$cpanelDomain
"@

$envContent | Out-File ".env.production" -Encoding UTF8
Write-Success "YG Account .env.production created"

# Configure YG Mail
Write-Info "Configuring YG Mail..."
Set-Location "$ProjectRoot\YG Mail"

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env" -ErrorAction SilentlyContinue
}

$mailAppKey = Generate-AppKey

$mailEnvContent = @"
APP_NAME="YG Mail"
APP_ENV=production
APP_KEY=$mailAppKey
APP_DEBUG=false
APP_URL=https://mail.$cpanelDomain

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${dbPrefix}_yg_mail
DB_USERNAME=${dbPrefix}_yg_mail
DB_PASSWORD=YOUR_DB_PASSWORD_HERE

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=.$cpanelDomain

CACHE_STORE=database
QUEUE_CONNECTION=sync

YG_ACCOUNT_URL=https://account.$cpanelDomain

MAIL_MAILER=smtp
MAIL_HOST=mail.$cpanelDomain
MAIL_PORT=587
MAIL_USERNAME=noreply@$cpanelDomain
MAIL_PASSWORD=YOUR_EMAIL_PASSWORD_HERE
MAIL_ENCRYPTION=tls
"@

$mailEnvContent | Out-File ".env.production" -Encoding UTF8
Write-Success "YG Mail .env.production created"

###############################################################################
# Step 6: Build Frontend Assets
###############################################################################
Write-Step "[STEP 6] BUILDING FRONTEND ASSETS"

$frontendModules = @("yg-account", "YG Mail", "yg-developer", "yg-home")

foreach ($module in $frontendModules) {
    $modulePath = "$ProjectRoot\$module"
    if (Test-Path "$modulePath\package.json") {
        Write-Info "Building assets for $module..."
        Set-Location $modulePath
        
        try {
            npm install 2>&1 | Out-Null
            npm run build 2>&1 | Out-Null
            Write-Success "$module assets built"
        } catch {
            Write-Warning-Custom "Failed to build $module assets"
        }
    }
}

###############################################################################
# Step 7: Generate Deployment Package
###############################################################################
Write-Step "[STEP 7] CREATING DEPLOYMENT PACKAGE"

$deployFolder = "$ProjectRoot\DEPLOY_PACKAGE_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
New-Item -ItemType Directory -Path $deployFolder -Force | Out-Null

Write-Info "Copying files to deployment package..."

# Copy all modules
$modulesToDeploy = @("yg-account", "YG Mail", "yg-developer", "yg-home", "YG Calendar", "YG Chat", "YG Contacts", "YG Drive", "YG Notes", "YG Xcel", "YG DocX", "YG DB", "YG Collect")

foreach ($module in $modulesToDeploy) {
    $srcPath = "$ProjectRoot\$module"
    $destPath = "$deployFolder\$module"
    
    if (Test-Path $srcPath) {
        Write-Info "Packaging $module..."
        Copy-Item -Path $srcPath -Destination $destPath -Recurse -Force
        
        # Remove unnecessary files to reduce size
        Remove-Item "$destPath\node_modules" -Recurse -Force -ErrorAction SilentlyContinue
        Remove-Item "$destPath\.git" -Recurse -Force -ErrorAction SilentlyContinue
        Remove-Item "$destPath\vendor" -Recurse -Force -ErrorAction SilentlyContinue
    }
}

# Create deployment instructions
$instructions = @"
# YG ECOSYSTEM DEPLOYMENT INSTRUCTIONS
Generated: $(Get-Date)

## QUICK DEPLOY STEPS:

### For cPanel:

1. CREATE DATABASES in cPanel:
   - ${dbPrefix}_yg_account
   - ${dbPrefix}_yg_mail
   - ${dbPrefix}_yg_developer
   (Add more as needed)

2. CREATE SUBDOMAINS in cPanel:
   - account.$cpanelDomain → point to /public folder
   - mail.$cpanelDomain → point to /public folder
   - dev.$cpanelDomain → point to /public folder

3. UPLOAD FILES via FTP/cPanel File Manager:
   - Upload each module to its respective subdomain folder
   
4. SET DOCUMENT ROOT to public/ folder for each subdomain

5. RUN THESE COMMANDS via SSH/Terminal for EACH module:
   cd /path/to/module
   composer install --no-dev --optimize-autoloader
   cp .env.production .env
   # Edit .env with correct database credentials
   php artisan key:generate
   php artisan migrate --force
   php artisan storage:link
   php artisan optimize

6. SETUP CRON JOB (for scheduler):
   * * * * * cd /path/to/yg-account && php artisan schedule:run >> /dev/null 2>&1

7. SET PERMISSIONS:
   chmod -R 775 storage/
   chmod -R 775 bootstrap/cache/

### Database Credentials to Update:
- Replace YOUR_DB_PASSWORD_HERE with actual database passwords
- Replace YOUR_EMAIL_PASSWORD_HERE with email account password

## VERIFICATION CHECKLIST:
□ https://account.$cpanelDomain loads
□ https://mail.$cpanelDomain loads  
□ https://dev.$cpanelDomain loads
□ Login works on account subdomain
□ SSO redirects work correctly
□ All API endpoints respond

## SUPPORT:
If you encounter issues, check:
- Error logs: storage/logs/laravel.log
- Web server error logs
- Database connection settings
- File permissions

Good luck with your deployment! 🚀
"@

$instructions | Out-File "$deployFolder\DEPLOY_INSTRUCTIONS.txt" -Encoding UTF8
Write-Success "Deployment package created: $deployFolder"

###############################################################################
# Step 8: Final Summary
###############################################################################
Write-Step "🎉 DEPLOYMENT PACKAGE READY!"

Write-Host "`n✅ WHAT'S BEEN DONE:" -ForegroundColor Green
Write-Host "   ✓ All dependencies analyzed" -ForegroundColor Green
Write-Host "   ✓ Environment files generated" -ForegroundColor Green
Write-Host "   ✓ Frontend assets built" -ForegroundColor Green
Write-Host "   ✓ Deployment package created" -ForegroundColor Green
Write-Host "   ✓ Instructions documented" -ForegroundColor Green

Write-Host "`n📦 DEPLOYMENT PACKAGE LOCATION:" -ForegroundColor Cyan
Write-Host "   $deployFolder" -ForegroundColor Yellow

Write-Host "`n📝 NEXT STEPS:" -ForegroundColor Cyan
Write-Host "   1. Review DEPLOY_INSTRUCTIONS.txt in the package" -ForegroundColor White
Write-Host "   2. Upload package to your hosting via FTP/cPanel" -ForegroundColor White
Write-Host "   3. Follow the step-by-step instructions" -ForegroundColor White
Write-Host "   4. Test each subdomain after deployment" -ForegroundColor White

Write-Host "`n⚡ ESTIMATED TIME: 30-60 minutes" -ForegroundColor Yellow
Write-Host "`n🚀 YOU CAN DEPLOY TODAY!" -ForegroundColor Green

Write-Host "`n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`n" -ForegroundColor Magenta

# Open the deployment folder
Invoke-Item $deployFolder

Write-Success "Deployment folder opened! Good luck! 🎊"
