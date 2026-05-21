#!/bin/bash
# =============================================================================
# YGXONE Browser - Advanced Features Deployment Script for cPanel
# =============================================================================
# Deploys Deep Research, Citations, Knowledge Graph features
# Compatible with cPanel shared hosting environments
# =============================================================================

set -e  # Exit on error

echo "=========================================="
echo "YGXONE Browser Advanced Features Deploy"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

echo -e "${YELLOW}Step 1/7: Checking environment...${NC}"
# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "PHP Version: $PHP_VERSION"

# Check if artisan exists
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan not found. Are you in the correct directory?${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Environment check passed${NC}"
echo ""

echo -e "${YELLOW}Step 2/7: Setting file permissions...${NC}"
# Set proper permissions for cPanel
chmod 644 .env 2>/dev/null || true
chmod -R 775 storage/ 2>/dev/null || true
chmod -R 775 bootstrap/cache/ 2>/dev/null || true
echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

echo -e "${YELLOW}Step 3/7: Installing/updating dependencies...${NC}"
# Install composer dependencies (production mode)
composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -5
echo -e "${GREEN}✓ Dependencies installed${NC}"
echo ""

echo -e "${YELLOW}Step 4/7: Clearing caches...${NC}"
# Clear all caches (order matters!)
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
echo -e "${GREEN}✓ Caches cleared${NC}"
echo ""

echo -e "${YELLOW}Step 5/7: Running database migrations...${NC}"
# Run migrations for new tables
php artisan migrate --force 2>&1 | tail -10
echo -e "${GREEN}✓ Migrations completed${NC}"
echo ""

echo -e "${YELLOW}Step 6/7: Rebuilding caches...${NC}"
# Rebuild optimized caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Caches rebuilt${NC}"
echo ""

echo -e "${YELLOW}Step 7/7: Verifying deployment...${NC}"
# Verify key services are working
if php artisan route:list | grep -q "api.research.deep"; then
    echo -e "${GREEN}✓ Deep Research routes registered${NC}"
else
    echo -e "${RED}✗ Deep Research routes missing${NC}"
fi

if php artisan route:list | grep -q "api.citations"; then
    echo -e "${GREEN}✓ Citation routes registered${NC}"
else
    echo -e "${RED}✗ Citation routes missing${NC}"
fi

if php artisan route:list | grep -q "api.knowledge-graph"; then
    echo -e "${GREEN}✓ Knowledge Graph routes registered${NC}"
else
    echo -e "${RED}✗ Knowledge Graph routes missing${NC}"
fi

echo ""
echo "=========================================="
echo -e "${GREEN}Deployment Complete!${NC}"
echo "=========================================="
echo ""
echo "New Features Available:"
echo "  • Deep Research Mode - Multi-page synthesis"
echo "  • Smart Citations - Auto-tracking & export"
echo "  • Knowledge Graph - Entity relationship mapping"
echo ""
echo "Access the browser at: https://ygxone.com/browser"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Configure AI API keys in Agent Settings"
echo "2. Test Deep Research with a sample query"
echo "3. Browse websites to populate citations"
echo "4. View Knowledge Graph connections"
echo ""
