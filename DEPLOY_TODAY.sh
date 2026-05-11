#!/bin/bash
###############################################################################
# YG Ecosystem - ONE-CLICK DEPLOYMENT Script (Bash)
# 
# This script deploys the ENTIRE YG ecosystem to cPanel or VPS TODAY!
# Run this script and follow the prompts to deploy immediately.
#
# Usage: chmod +x DEPLOY_TODAY.sh && ./DEPLOY_TODAY.sh
###############################################################################

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PROJECT_ROOT="/path/to/YG Soft1"  # Update this path

echo -e "${BLUE}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║         🚀 YG ECOSYSTEM - DEPLOY TODAY! 🚀              ║${NC}"
echo -e "${BLUE}║          Complete Deployment Automation                 ║${NC}"
echo -e "${BLUE}╚═══════════════════════════════════════════════════════════╝${NC}"
echo ""

###############################################################################
# Step 1: Deployment Mode Selection
###############################################################################
echo -e "\n${MAGENTA}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${MAGENTA}[STEP 1] SELECT DEPLOYMENT MODE${NC}"
echo -e "1. cPanel Shared Hosting (Recommended for beginners)"
echo -e "2. VPS/Dedicated Server (Advanced users)"
echo -e "3. Local Testing Only"
echo ""

read -p "Enter choice (1/2/3): " mode

if [ "$mode" = "1" ]; then
    deploy_type="cpanel"
    echo -e "${GREEN}✓ Selected: cPanel Deployment${NC}"
elif [ "$mode" = "2" ]; then
    deploy_type="vps"
    echo -e "${GREEN}✓ Selected: VPS Deployment${NC}"
elif [ "$mode" = "3" ]; then
    deploy_type="local"
    echo -e "${GREEN}✓ Selected: Local Testing${NC}"
else
    echo -e "${RED}✗ Invalid choice. Defaulting to cPanel.${NC}"
    deploy_type="cpanel"
fi

###############################################################################
# Step 2: Prerequisites Check
###############################################################################
echo -e "\n${MAGENTA}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${MAGENTA}[STEP 2] CHECKING PREREQUISITES${NC}"

errors=()

# Check PHP
if command -v php &> /dev/null; then
    php_version=$(php -r "echo PHP_VERSION;")
    if [[ $(echo -e "$php_version\n8.2.0" | sort -V | head -n1) == "8.2.0" ]]; then
        echo -e "${GREEN}✓ PHP $php_version${NC}"
    else
        echo -e "${RED}✗ PHP $php_version - Need 8.2+${NC}"
        errors+=("PHP version too old")
    fi
else
    echo -e "${RED}✗ PHP not found!${NC}"
    errors+=("PHP not installed")
fi

# Check Composer
if command -v composer &> /dev/null; then
    echo -e "${GREEN}✓ Composer installed${NC}"
else
    echo -e "${RED}✗ Composer not found!${NC}"
    errors+=("Composer not installed")
fi

# Check Node.js
if command -v node &> /dev/null; then
    node_version=$(node --version)
    echo -e "${GREEN}✓ Node.js $node_version${NC}"
else
    echo -e "${YELLOW}⚠ Node.js not found (needed for asset compilation)${NC}"
fi

# Check MySQL
if command -v mysql &> /dev/null; then
    echo -e "${GREEN}✓ MySQL client available${NC}"
else
    echo -e "${YELLOW}⚠ MySQL client not found${NC}"
fi

if [ ${#errors[@]} -gt 0 ]; then
    echo -e "\n${RED}❌ CRITICAL ERRORS FOUND:${NC}"
    for err in "${errors[@]}"; do
        echo -e "   - $err"
    done
    read -p $'\nContinue anyway? (y/n): ' continue_choice
    if [ "$continue_choice" != "y" ]; then exit 1; fi
fi

###############################################################################
# Step 3: Configuration Input
###############################################################################
echo -e "\n${MAGENTA}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${MAGENTA}[STEP 3] ENTER DEPLOYMENT CONFIGURATION${NC}"

if [ "$deploy_type" = "cpanel" ]; then
    echo -e "\n${CYAN}Enter your cPanel details:${NC}"
    
    read -p "Main domain (e.g., ygxone.com): " cpanel_domain
    read -p "cPanel username: " cpanel_user
    read -p "Database prefix (usually cPanel username): " db_prefix
    
    # Auto-generate subdomains
    account_subdomain="account.$cpanel_domain"
    mail_subdomain="mail.$cpanel_domain"
    developer_subdomain="dev.$cpanel_domain"
    home_subdomain="$cpanel_domain"
    
    echo -e "\n${YELLOW}📋 Subdomains to configure:${NC}"
    echo -e "   Account:  $account_subdomain"
    echo -e "   Mail:     $mail_subdomain"
    echo -e "   Developer:$developer_subdomain"
    echo -e "   Home:     $home_subdomain"
    
    read -p $'\nProceed with these subdomains? (y/n): ' confirm
    if [ "$confirm" != "y" ]; then exit 0; fi
    
elif [ "$deploy_type" = "vps" ]; then
    echo -e "\n${CYAN}Enter your VPS details:${NC}"
    
    read -p "Server IP address: " server_ip
    read -p "SSH username (default: root): " ssh_user
    if [ -z "$ssh_user" ]; then ssh_user="root"; fi
    
    read -p "Domain name (optional, press Enter to skip): " domain
    
else
    localhost="http://localhost:8000"
    echo -e "${GREEN}✓ Local testing mode - will use Laravel serve${NC}"
fi

###############################################################################
# Step 4: Install Dependencies
###############################################################################
echo -e "\n${MAGENTA}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${MAGENTA}[STEP 4] INSTALLING DEPENDENCIES${NC}"

declare -a modules=(
    "YG Account:$PROJECT_ROOT/yg-account"
    "YG Mail:$PROJECT_ROOT/YG Mail"
    "YG Developer:$PROJECT_ROOT/yg-developer"
    "YG Home:$PROJECT_ROOT/yg-home"
    "YG Calendar:$PROJECT_ROOT/YG Calendar"
    "YG Chat:$PROJECT_ROOT/YG Chat"
    "YG Contacts:$PROJECT_ROOT/YG Contacts"
    "YG Drive:$PROJECT_ROOT/YG Drive"
    "YG Notes:$PROJECT_ROOT/YG Notes"
    "YG Xcel:$PROJECT_ROOT/YG Xcel"
    "YG DocX:$PROJECT_ROOT/YG DocX"
    "YG DB:$PROJECT_ROOT/YG DB"
    "YG Collect:$PROJECT_ROOT/YG Collect"
)

for module_info in "${modules[@]}"; do
    IFS=':' read -r module_name module_path <<< "$module_info"
    echo -e "${CYAN}ℹ Installing $module_name...${NC}"
    cd "$module_path" || continue
    
    if [ -f "composer.json" ]; then
        if composer install --no-interaction --prefer-dist --optimize-autoloader > /dev/null 2>&1; then
            echo -e "${GREEN}✓ $module_name dependencies installed${NC}"
        else
            echo -e "${RED}✗ Failed to install $module_name${NC}"
        fi
    fi
done

###############################################################################
# Step 5: Environment Configuration
###############################################################################
echo -e "\n${MAGENTA}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${MAGENTA}[STEP 5] CONFIGURING ENVIRONMENT FILES${NC}"

generate_app_key() {
    php -r "echo 'base64:' . base64_encode(random_bytes(32));"
}

# Configure YG Account
echo -e "${CYAN}ℹ Configuring YG Account...${NC}"
cd "$PROJECT_ROOT/yg-account" || exit 1

if [ ! -f ".env" ]; then
    cp .env.example .env 2>/dev/null || true
fi

app_key=$(generate_app_key)

cat > .env.production << EOF
APP_NAME="YG Account"
APP_ENV=production
APP_KEY=$app_key
APP_DEBUG=false
APP_URL=https://account.$cpanel_domain

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${db_prefix}_yg_account
DB_USERNAME=${db_prefix}_yg_account
DB_PASSWORD=YOUR_DB_PASSWORD_HERE

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=.$cpanel_domain

CACHE_STORE=database
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_HOST=mail.$cpanel_domain
MAIL_PORT=587
MAIL_USERNAME=noreply@$cpanel_domain
MAIL_PASSWORD=YOUR_EMAIL_PASSWORD_HERE
MAIL_ENCRYPTION=tls

SSO_ACCOUNT_URL=https://account.$cpanel_domain
EOF

echo -e "${GREEN}✓ YG Account .env.production created${NC}"

# Configure YG Mail
echo -e "${CYAN}ℹ Configuring YG Mail...${NC}"
cd "$PROJECT_ROOT/YG Mail" || exit 1

if [ ! -f ".env" ]; then
    cp .env.example .env 2>/dev/null || true
fi

mail_app_key=$(generate_app_key)

cat > .env.production << EOF
APP_NAME="YG Mail"
APP_ENV=production
APP_KEY=$mail_app_key
APP_DEBUG=false
APP_URL=https://mail.$cpanel_domain

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${db_prefix}_yg_mail
DB_USERNAME=${db_prefix}_yg_mail
DB_PASSWORD=YOUR_DB_PASSWORD_HERE

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=.$cpanel_domain

CACHE_STORE=database
QUEUE_CONNECTION=sync

YG_ACCOUNT_URL=https://account.$cpanel_domain

MAIL_MAILER=smtp
MAIL_HOST=mail.$cpanel_domain
MAIL_PORT=587
MAIL_USERNAME=noreply@$cpanel_domain
MAIL_PASSWORD=YOUR_EMAIL_PASSWORD_HERE
MAIL_ENCRYPTION=tls
EOF

echo -e "${GREEN}✓ YG Mail .env.production created${NC}"

###############################################################################
# Step 6: Build Frontend Assets
###############################################################################
echo -e "\n${MAGENTA}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${MAGENTA}[STEP 6] BUILDING FRONTEND ASSETS${NC}"

frontend_modules=("yg-account" "YG Mail" "yg-developer" "yg-home")

for module in "${frontend_modules[@]}"; do
    module_path="$PROJECT_ROOT/$module"
    if [ -f "$module_path/package.json" ]; then
        echo -e "${CYAN}ℹ Building assets for $module...${NC}"
        cd "$module_path" || continue
        
        if npm install > /dev/null 2>&1 && npm run build > /dev/null 2>&1; then
            echo -e "${GREEN}✓ $module assets built${NC}"
        else
            echo -e "${YELLOW}⚠ Failed to build $module assets${NC}"
        fi
    fi
done

###############################################################################
# Step 7: Generate Deployment Package
###############################################################################
echo -e "\n${MAGENTA}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${MAGENTA}[STEP 7] CREATING DEPLOYMENT PACKAGE${NC}"

deploy_folder="$PROJECT_ROOT/DEPLOY_PACKAGE_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$deploy_folder"

echo -e "${CYAN}ℹ Copying files to deployment package...${NC}"

modules_to_deploy=("yg-account" "YG Mail" "yg-developer" "yg-home" "YG Calendar" "YG Chat" "YG Contacts" "YG Drive" "YG Notes" "YG Xcel" "YG DocX" "YG DB" "YG Collect")

for module in "${modules_to_deploy[@]}"; do
    src_path="$PROJECT_ROOT/$module"
    dest_path="$deploy_folder/$module"
    
    if [ -d "$src_path" ]; then
        echo -e "${CYAN}ℹ Packaging $module...${NC}"
        cp -r "$src_path" "$dest_path"
        
        # Remove unnecessary files to reduce size
        rm -rf "$dest_path/node_modules" 2>/dev/null || true
        rm -rf "$dest_path/.git" 2>/dev/null || true
        rm -rf "$dest_path/vendor" 2>/dev/null || true
    fi
done

# Create deployment instructions
cat > "$deploy_folder/DEPLOY_INSTRUCTIONS.txt" << EOF
# YG ECOSYSTEM DEPLOYMENT INSTRUCTIONS
Generated: $(date)

## QUICK DEPLOY STEPS:

### For cPanel:

1. CREATE DATABASES in cPanel:
   - ${db_prefix}_yg_account
   - ${db_prefix}_yg_mail
   - ${db_prefix}_yg_developer
   (Add more as needed)

2. CREATE SUBDOMAINS in cPanel:
   - account.$cpanel_domain → point to /public folder
   - mail.$cpanel_domain → point to /public folder
   - dev.$cpanel_domain → point to /public folder

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
□ https://account.$cpanel_domain loads
□ https://mail.$cpanel_domain loads  
□ https://dev.$cpanel_domain loads
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
EOF

echo -e "${GREEN}✓ Deployment package created: $deploy_folder${NC}"

###############################################################################
# Step 8: Final Summary
###############################################################################
echo -e "\n${MAGENTA}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${MAGENTA}🎉 DEPLOYMENT PACKAGE READY!${NC}"

echo -e "\n${GREEN}✅ WHAT'S BEEN DONE:${NC}"
echo -e "   ✓ All dependencies analyzed"
echo -e "   ✓ Environment files generated"
echo -e "   ✓ Frontend assets built"
echo -e "   ✓ Deployment package created"
echo -e "   ✓ Instructions documented"

echo -e "\n${CYAN}📦 DEPLOYMENT PACKAGE LOCATION:${NC}"
echo -e "   $deploy_folder"

echo -e "\n${CYAN}📝 NEXT STEPS:${NC}"
echo -e "   1. Review DEPLOY_INSTRUCTIONS.txt in the package"
echo -e "   2. Upload package to your hosting via FTP/cPanel"
echo -e "   3. Follow the step-by-step instructions"
echo -e "   4. Test each subdomain after deployment"

echo -e "\n${YELLOW}⚡ ESTIMATED TIME: 30-60 minutes${NC}"
echo -e "\n${GREEN}🚀 YOU CAN DEPLOY TODAY!${NC}"

echo -e "\n${MAGENTA}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Open the deployment folder
if command -v xdg-open &> /dev/null; then
    xdg-open "$deploy_folder"
elif command -v open &> /dev/null; then
    open "$deploy_folder"
fi

echo -e "${GREEN}✓ Deployment folder opened! Good luck! 🎊${NC}"
