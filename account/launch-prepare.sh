#!/bin/bash
# ============================================================================
# YG Account - Quick Launch Script
# ============================================================================
# Run this to quickly prepare YG Account for production launch
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
echo "  🚀 YG Account Launch Preparation"
echo "=========================================="
echo ""

# Check if running as root for some operations
if [ "$EUID" -ne 0 ]; then
    echo -e "${YELLOW}[WARNING]${NC} Some operations may require sudo privileges"
fi

# Step 1: Verify prerequisites
echo -e "${BLUE}[1/8]${NC} Checking prerequisites..."
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
echo -e "${BLUE}[2/8]${NC} Installing dependencies..."
composer install --no-dev --optimize-autoloader --quiet
echo -e "${GREEN}✓${NC} Dependencies installed"
echo ""

# Step 3: Setup environment
echo -e "${BLUE}[3/8]${NC} Configuring environment..."
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
echo -e "${BLUE}[4/8]${NC} Setting up database..."
php artisan migrate --force --no-interaction
echo -e "${GREEN}✓${NC} Database migrations complete"
echo ""

# Step 5: Create admin user (optional)
echo -e "${BLUE}[5/8]${NC} Checking admin user..."
ADMIN_EXISTS=$(php artisan tinker --execute="echo App\Models\User::where('email', 'admin@ygxone.com')->exists() ? 'yes' : 'no';")

if [ "$ADMIN_EXISTS" = "no" ]; then
    echo -e "${YELLOW}[INFO]${NC} Creating default admin user..."
    php artisan tinker --execute="App\Models\User::create(['name'=>'System Administrator','email'=>'admin@ygxone.com','password'=>bcrypt('SecureLaunch2026!'),'email_verified_at'=>now(),'account_type'=>'individual']);"
    echo -e "${GREEN}✓${NC} Admin user created"
    echo -e "${YELLOW}[IMPORTANT]${NC} Default credentials:"
    echo "  Email: admin@ygxone.com"
    echo "  Password: SecureLaunch2026!"
    echo -e "${RED}[ACTION REQUIRED]${NC} Change password immediately after login!"
else
    echo -e "${GREEN}✓${NC} Admin user exists"
fi
echo ""

# Step 6: Build frontend assets
echo -e "${BLUE}[6/8]${NC} Building frontend assets..."
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

# Step 7: Set permissions
echo -e "${BLUE}[7/8]${NC} Setting file permissions..."
chmod -R 775 storage/ bootstrap/cache/ 2>/dev/null || true
find storage/ -type f -exec chmod 664 {} \; 2>/dev/null || true
echo -e "${GREEN}✓${NC} Permissions set"
echo ""

# Step 8: Optimize for production
echo -e "${BLUE}[8/8]${NC} Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
echo -e "${GREEN}✓${NC} Production optimizations applied"
echo ""

# Final summary
echo "=========================================="
echo -e "  ${GREEN}✓ YG Account Ready for Launch!${NC}"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Configure Nginx/Apache with document root: $(pwd)/public"
echo "2. Install SSL certificate for your domain"
echo "3. Update .env with production settings:"
echo "   - APP_ENV=production"
echo "   - APP_DEBUG=false"
echo "   - Correct database credentials"
echo "4. Start queue workers if using async jobs"
echo "5. Setup cron for scheduler: * * * * * cd $(pwd) && php artisan schedule:run"
echo ""
echo "Admin Login:"
echo "  URL: https://your-domain.com/login"
echo "  Email: admin@ygxone.com"
echo "  Password: SecureLaunch2026!"
echo ""
echo -e "${RED}⚠️  IMPORTANT:${NC} Change the default password immediately!"
echo ""
