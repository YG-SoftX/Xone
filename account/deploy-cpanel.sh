#!/bin/bash

# ╔══════════════════════════════════════════════════════╗
# ║  YG Account - cPanel Deployment Script              ║
# ║  Optimized for Shared Hosting Environment           ║
# ╚══════════════════════════════════════════════════════╝

echo "🚀 Starting cPanel-optimized deployment..."

# ── Step 1: Environment Setup ────────────────────────────────────────────────
echo "📋 Step 1: Preparing environment..."

# Install dependencies (production only)
composer install --optimize-autoloader --no-dev --no-interaction

# Generate application key if not exists
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi

# ── Step 2: Database Migration ───────────────────────────────────────────────
echo "🗄️  Step 2: Running database migrations..."
php artisan migrate --force --no-interaction

# Seed essential data
php artisan db:seed --class=ServiceSeeder --no-interaction
php artisan db:seed --class=PlatformFeatureSeeder --no-interaction

# ── Step 3: Cache Optimization ───────────────────────────────────────────────
echo "⚡ Step 3: Optimizing caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# ── Step 4: Storage Permissions ──────────────────────────────────────────────
echo "🔐 Step 4: Setting storage permissions..."
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/

# ── Step 5: Build Frontend Assets ────────────────────────────────────────────
echo "🎨 Step 5: Building frontend assets..."
if command -v npm &> /dev/null; then
    npm ci --production
    npm run build
else
    echo "⚠️  npm not found. Please build assets locally and upload public/build/"
fi

# ── Step 6: Security Hardening ───────────────────────────────────────────────
echo "🛡️  Step 6: Applying security hardening..."
chmod 644 .env
chmod 644 .htaccess

# ── Step 7: Health Check ─────────────────────────────────────────────────────
echo "🏥 Step 7: Running health checks..."
php artisan about --no-interaction

echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║  🎉 Deployment Preparation Complete!                ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""
echo "Next Steps:"
echo "1. Upload files to cPanel File Manager"
echo "2. Set document root to public_html/"
echo "3. Add cron jobs (see documentation)"
echo "4. Install SSL certificate via cPanel"
echo "5. Test login and payment flows"