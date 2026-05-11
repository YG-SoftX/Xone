#!/bin/bash
# ============================================================================
# YG Home - Admin User Setup Script (Linux/Mac)
# ============================================================================
# This script helps you configure admin users for the admin dashboard
# ============================================================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo ""
echo "========================================"
echo " YG Home Admin Configuration Setup"
echo "========================================"
echo ""

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo -e "${RED}[ERROR]${NC} .env file not found!"
    echo "Please copy .env.example to .env first:"
    echo "  cp .env.example .env"
    echo ""
    exit 1
fi

echo -e "${BLUE}[INFO]${NC} Current admin configuration:"
echo ""
grep "ADMIN_EMAILS" .env || echo "ADMIN_EMAILS not set"
grep "ADMIN_USER_IDS" .env || echo "ADMIN_USER_IDS not set"
echo ""

echo "========================================"
echo " Step 1: Configure Admin Email(s)"
echo "========================================"
echo ""
echo "Enter admin email address(es):"
echo "(Multiple emails: comma-separated, no spaces)"
echo "Example: admin@ygxone.com or admin@ygxone.com,superadmin@ygxone.com"
echo ""
read -p "Your admin email(s): " ADMIN_EMAIL

if [ -z "$ADMIN_EMAIL" ]; then
    echo -e "${YELLOW}[WARNING]${NC} No email entered. Keeping existing configuration."
else
    echo ""
    echo -e "${BLUE}[INFO]${NC} Updating ADMIN_EMAILS in .env..."
    
    # Backup original file
    cp .env .env.backup
    
    # Update ADMIN_EMAILS line
    if grep -q "^ADMIN_EMAILS=" .env; then
        sed -i.bak "s|^ADMIN_EMAILS=.*|ADMIN_EMAILS=${ADMIN_EMAIL}|" .env
        rm -f .env.bak
    else
        echo "" >> .env
        echo "# Admin Configuration" >> .env
        echo "ADMIN_EMAILS=${ADMIN_EMAIL}" >> .env
    fi
    
    echo -e "${GREEN}[SUCCESS]${NC} Admin email(s) updated!"
fi

echo ""
echo "========================================"
echo " Step 2: Generate Application Key"
echo "========================================"
echo ""
echo "Generating APP_KEY (required for sessions)..."
php artisan key:generate

if [ $? -ne 0 ]; then
    echo -e "${RED}[ERROR]${NC} Failed to generate APP_KEY"
    echo "Make sure PHP and Composer dependencies are installed"
    exit 1
fi

echo ""
echo "========================================"
echo " Step 3: Clear Configuration Cache"
echo "========================================"
echo ""
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear

echo ""
echo "========================================"
echo " Step 4: Verify Database Tables"
echo "========================================"
echo ""
echo "Checking if required tables exist..."
php artisan migrate:status

echo ""
echo "========================================"
echo " Setup Complete!"
echo "========================================"
echo ""
echo -e "${GREEN}Next steps:${NC}"
echo "1. Start the server: php artisan serve --host=0.0.0.0 --port=8001"
echo "2. Visit: http://localhost:8001/login"
echo "3. Login with your admin account"
echo "4. Access dashboard: http://localhost:8001/admin/dashboard"
echo ""
echo "For detailed instructions, see: ADMIN_CONFIGURATION_GUIDE.md"
echo ""
