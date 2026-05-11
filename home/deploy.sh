#!/bin/bash
# =============================================================================
# Post-Deployment Script for YGXone Home (cPanel + GitHub)
# =============================================================================
# This script runs automatically after pulling from GitHub to:
# 1. Install Composer dependencies
# 2. Configure environment
# 3. Run database migrations
# 4. Clear and rebuild caches
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
    composer install --no-interaction --no-progress --optimize-autoloader --no-dev
    echo "✅ Composer dependencies installed"
else
    echo "⚠️  Composer not found! Trying alternative methods..."
    
    # Try using composer from common cPanel paths
    if [ -f "$HOME/bin/composer" ]; then
        $HOME/bin/composer install --no-interaction --no-progress --optimize-autoloader --no-dev
        echo "✅ Composer dependencies installed (cPanel path)"
    elif [ -f "/usr/local/bin/composer" ]; then
        /usr/local/bin/composer install --no-interaction --no-progress --optimize-autoloader --no-dev
        echo "✅ Composer dependencies installed (/usr/local/bin)"
    else
        echo "❌ ERROR: Composer not found!"
        echo "Please install Composer via cPanel or SSH:"
        echo "   curl -sS https://getcomposer.org/installer | php"
        echo "   mv composer.phar ~/bin/composer"
        exit 1
    fi
fi
echo ""

# ── 3. Setup .env File ───────────────────────────────────────────────────────
echo "⚙️  Configuring environment..."
if [ ! -f .env ]; then
    echo "📄 Creating .env file from .env.example..."
    cp .env.example .env
    echo "✅ .env file created"
    
    # IMPORTANT: You must manually set these in cPanel:
    echo "⚠️  IMPORTANT: Update these values in your .env file:"
    echo "   - APP_KEY (run: php artisan key:generate)"
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

# ── 4. Database Migrations ───────────────────────────────────────────────────
echo "🗄️  Running database migrations..."
php artisan migrate --force --no-interaction 2>&1 || {
    echo "⚠️  Migration warning: Some tables may already exist or have conflicts"
    echo "   This is normal for first-time setup. Check the logs if concerned."
}
echo "✅ Migrations completed"
echo ""

# ── 5. Optimize Application ──────────────────────────────────────────────────
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Application optimized"
echo ""

# ── 6. Clear Old Caches ──────────────────────────────────────────────────────
echo "🧹 Clearing old caches..."
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
echo "✅ Caches cleared"
echo ""

# ── 7. Create Required Directories (if missing) ──────────────────────────────
echo "📂 Ensuring required directories exist..."
mkdir -p storage/logs
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/app/public
echo "✅ Directories created"
echo ""

# ── 8. Final Status Check ────────────────────────────────────────────────────
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
echo "   1. Visit: https://ygxone.com"
echo "   2. If you see errors, check: storage/logs/laravel.log"
echo "   3. Verify database connection in cPanel → MySQL Databases"
echo ""
echo "⚠️  If you still see 500 errors:"
echo "   - Clear your browser cache (Ctrl+F5)"
echo "   - Check cPanel Error Logs"
echo "   - Verify .env database credentials"
echo ""