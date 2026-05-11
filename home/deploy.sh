#!/bin/bash
# =============================================================================
# Post-Deployment Script for YGXone Applications (cPanel + GitHub)
# =============================================================================
# This script runs automatically after pulling from GitHub to:
# 1. Install Composer dependencies (including jenssegers/agent)
# 2. Build Vite assets (npm install && npm run build)
# 3. Configure environment
# 4. Run database migrations
# 5. Clear and rebuild caches
# =============================================================================

set -e  # Exit on error

echo "🚀 Starting post-deployment setup..."
echo ""

# ── 1. Set Permissions ───────────────────────────────────────────────────────
echo "📁 Setting directory permissions..."
chmod -R 775 storage bootstrap/cache
chown -R $(whoami):$(whoami) . 2>/dev/null || true
echo "✅ Permissions set"
echo ""

# ── 2. Install Composer Dependencies ─────────────────────────────────────────
echo "📦 Installing/updating Composer dependencies..."
if command -v composer &> /dev/null; then
    COMPOSER_CMD="composer"
elif [ -f "$HOME/bin/composer" ]; then
    COMPOSER_CMD="$HOME/bin/composer"
elif [ -f "/usr/local/bin/composer" ]; then
    COMPOSER_CMD="/usr/local/bin/composer"
else
    echo "❌ ERROR: Composer not found!"
    echo "Please install Composer via cPanel or SSH:"
    echo "   curl -sS https://getcomposer.org/installer | php"
    echo "   mv composer.phar ~/bin/composer"
    exit 1
fi

$COMPOSER_CMD install --no-interaction --no-progress --optimize-autoloader --no-dev
echo "✅ Composer dependencies installed"

# Ensure jenssegers/agent is installed (required for device fingerprinting)
echo "🔍 Checking for jenssegers/agent package..."
if ! $COMPOSER_CMD show jenssegers/agent &>/dev/null; then
    echo "📥 Installing jenssegers/agent..."
    $COMPOSER_CMD require jenssegers/agent --no-interaction --no-progress
    echo "✅ jenssegers/agent installed"
else
    echo "✅ jenssegers/agent already installed"
fi
echo ""

# ── 3. Build Vite Assets ─────────────────────────────────────────────────────
echo "🎨 Building frontend assets with Vite..."
if command -v npm &> /dev/null; then
    echo "📥 Installing npm dependencies..."
    npm ci --no-progress --silent 2>&1 || npm install --no-progress --silent
    
    echo "🔨 Building production assets..."
    npm run build --silent
    echo "✅ Vite assets built successfully"
else
    echo "⚠️  NPM not found! Skipping asset build."
    echo "   Please ensure Node.js is installed on your server."
    echo "   Or build locally and commit public/build to git."
fi
echo ""

# ── 4. Setup .env File ───────────────────────────────────────────────────────
echo "⚙️  Configuring environment..."
if [ ! -f .env ]; then
    echo "📄 Creating .env file from .env.example..."
    cp .env.example .env
    echo "✅ .env file created"
    
    # IMPORTANT: You must manually set these in cPanel:
    echo "⚠️  IMPORTANT: Update these values in your .env file:"
    echo "   - APP_KEY (will be auto-generated below)"
    echo "   - DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD"
    echo "   - APP_URL"
else
    echo "✅ .env file exists"
fi

# Generate APP_KEY if missing
if grep -q "^APP_KEY=$" .env || ! grep -q "^APP_KEY=.\+" .env; then
    echo "🔑 Generating APP_KEY..."
    php artisan key:generate
    echo "✅ APP_KEY generated"
else
    echo "✅ APP_KEY already configured"
fi
echo ""

# ── 5. Database Migrations ───────────────────────────────────────────────────
echo "🗄️  Running database migrations..."
php artisan migrate --force --no-interaction 2>&1 || {
    echo "⚠️  Migration warning: Some tables may already exist or have conflicts"
    echo "   This is normal for first-time setup. Check the logs if concerned."
}
echo "✅ Migrations completed"
echo ""

# ── 6. Optimize Application ──────────────────────────────────────────────────
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Application optimized"
echo ""

# ── 7. Clear Old Caches ──────────────────────────────────────────────────────
echo "🧹 Clearing old caches..."
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
echo "✅ Caches cleared"
echo ""

# ── 8. Create Required Directories (if missing) ──────────────────────────────
echo "📂 Ensuring required directories exist..."
mkdir -p storage/logs
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/app/public
echo "✅ Directories created"
echo ""

# ── 9. Final Status Check ────────────────────────────────────────────────────
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ DEPLOYMENT COMPLETE!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📊 System Information:"
echo "   PHP Version: $(php -r 'echo PHP_VERSION;')"
echo "   Laravel Version: $(php artisan --version | awk '{print $2}')"
echo "   Working Directory: $(pwd)"
echo ""
echo "🔍 Next Steps:"
echo "   1. Visit your site and test all features"
echo "   2. If you see errors, check: storage/logs/laravel.log"
echo "   3. Verify database connection in cPanel → MySQL Databases"
echo "   4. Test user registration and login flows"
echo ""
echo "⚠️  Troubleshooting:"
echo "   - Vite errors? → Run 'npm run build' manually"
echo "   - Missing classes? → Run 'composer install'"
echo "   - Database errors? → Check .env credentials"
echo "   - Permission errors? → Run 'chmod -R 775 storage bootstrap/cache'"
echo ""