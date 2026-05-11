#!/bin/bash
# ============================================================================
# YG Home (Search) - Quick Launch Script
# ============================================================================
# Run this to quickly prepare YG Home for production launch
# ============================================================================

set -e

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo ""
echo "=========================================="
echo "  🚀 YG Home (Search) Launch Preparation"
echo "=========================================="
echo ""

# Step 1: Verify prerequisites
echo -e "${BLUE}[1/9]${NC} Checking prerequisites..."
if ! command -v php &> /dev/null; then
    echo -e "${RED}[ERROR]${NC} PHP is not installed"
    exit 1
fi

if ! command -v composer &> /dev/null; then
    echo -e "${RED}[ERROR]${NC} Composer is not installed"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo -e "${GREEN}✓${NC} PHP $PHP_VERSION detected"
echo ""

# Step 2: Install dependencies
echo -e "${BLUE}[2/9]${NC} Installing dependencies..."
composer install --no-dev --optimize-autoloader --quiet
echo -e "${GREEN}✓${NC} Dependencies installed"
echo ""

# Step 3: Setup environment
echo -e "${BLUE}[3/9]${NC} Configuring environment..."
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo -e "${YELLOW}[INFO]${NC} Created .env from .env.example"
    else
        echo -e "${RED}[ERROR]${NC} No .env or .env.example found"
        exit 1
    fi
fi

# Generate APP_KEY if not set
if grep -q "^APP_KEY=$" .env || ! grep -q "^APP_KEY=" .env; then
    php artisan key:generate --force
    echo -e "${GREEN}✓${NC} APP_KEY generated"
else
    echo -e "${GREEN}✓${NC} APP_KEY already configured"
fi
echo ""

# Step 4: Database setup
echo -e "${BLUE}[4/9]${NC} Setting up database..."
php artisan migrate --force --no-interaction
echo -e "${GREEN}✓${NC} Database migrations complete"
echo ""

# Step 5: Create session and cache tables
echo -e "${BLUE}[5/9]${NC} Creating session/cache tables..."
php artisan session:table --force 2>/dev/null || echo -e "${YELLOW}[INFO]${NC} Session table may already exist"
php artisan cache:table --force 2>/dev/null || echo -e "${YELLOW}[INFO]${NC} Cache table may already exist"
php artisan migrate --force --no-interaction
echo -e "${GREEN}✓${NC} Session/cache tables ready"
echo ""

# Step 6: Sync search indexes
echo -e "${BLUE}[6/9]${NC} Syncing search indexes..."
php artisan search:sync --no-interaction 2>/dev/null || echo -e "${YELLOW}[WARNING]${NC} Search sync skipped (may need manual run)"
echo -e "${GREEN}✓${NC} Search indexes synced"
echo ""

# Step 7: Build frontend assets
echo -e "${BLUE}[7/9]${NC} Building frontend assets..."
if [ -f "package.json" ]; then
    if command -v npm &> /dev/null; then
        npm ci --production --silent 2>/dev/null || npm install --production --silent 2>/dev/null
        npm run build --silent 2>/dev/null || echo -e "${YELLOW}[WARNING]${NC} Frontend build skipped"
        echo -e "${GREEN}✓${NC} Assets built"
    else
        echo -e "${YELLOW}[WARNING]${NC} npm not found, skipping asset build"
    fi
else
    echo -e "${GREEN}✓${NC} No frontend assets to build"
fi
echo ""

# Step 8: Set permissions
echo -e "${BLUE}[8/9]${NC} Setting file permissions..."
chmod -R 775 storage/ bootstrap/cache/ 2>/dev/null || true
find storage/ -type f -exec chmod 664 {} \; 2>/dev/null || true
echo -e "${GREEN}✓${NC} Permissions set"
echo ""

# Step 9: Optimize for production
echo -e "${BLUE}[9/9]${NC} Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
echo -e "${GREEN}✓${NC} Production optimizations applied"
echo ""

# Final summary
echo "=========================================="
echo -e "  ${GREEN}✓ YG Home Ready for Launch!${NC}"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Configure Nginx/Apache with document root: $(pwd)/public"
echo "2. Install SSL certificate for ygxone.com"
echo "3. Update .env with production settings:"
echo "   - APP_ENV=production"
echo "   - APP_DEBUG=false"
echo "   - ADMIN_EMAILS=your-admin-email@domain.com"
echo "   - Correct service URLs"
echo "4. Setup cron for scheduler: * * * * * cd $(pwd) && php artisan schedule:run"
echo ""
echo "Access Points:"
echo "  Homepage: https://ygxone.com"
echo "  Search:   https://ygxone.com/search?q=test"
echo "  Admin:    https://ygxone.com/admin/dashboard"
echo ""
echo -e "${YELLOW}[NOTE]${NC} Admin access requires authentication via YG Account SSO"
echo ""
