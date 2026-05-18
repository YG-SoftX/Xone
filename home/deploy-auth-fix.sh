#!/bin/bash
# =============================================================================
# YGXONE Home Module - Authentication & SSO Fix Deployment Script
# =============================================================================
# This script applies all fixes for authentication, admin middleware, and SSO
# Run this from the home module directory on your production server
# =============================================================================

set -e  # Exit on error

echo ""
echo "========================================"
echo "YGXONE Home Module Deployment"
echo "========================================"
echo ""

# Step 1: Navigate to home directory
cd "$(dirname "$0")"
echo "[1/6] Working directory: $(pwd)"
echo ""

# Step 2: Install/update composer dependencies
echo "[2/6] Installing composer dependencies..."
composer install --no-interaction --optimize-autoloader --no-dev
echo "✓ Composer dependencies installed"
echo ""

# Step 3: Run migrations (creates sessions table)
echo "[3/6] Running database migrations..."
php artisan migrate --force
echo "✓ Database migrations completed"
echo ""

# Step 4: Clear all caches
echo "[4/6] Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✓ Caches cleared"
echo ""

# Step 5: Rebuild optimized caches
echo "[5/6] Rebuilding optimized caches..."
php artisan config:cache
php artisan route:cache
php artisan event:cache
echo "✓ Caches rebuilt"
echo ""

# Step 6: Verify session table exists
echo "[6/6] Verifying sessions table..."
php artisan tinker --execute="echo 'Sessions table exists: ' . (Schema::hasTable('sessions') ? 'YES' : 'NO');"
echo ""

echo "========================================"
echo "✓ Deployment Complete!"
echo "========================================"
echo ""
echo "Next steps:"
echo "1. Visit https://ygxone.com/login to test login"
echo "2. Test admin access at https://ygxone.com/admin/dashboard"
echo "3. Verify SSO works across sub-domains (mail.ygxone.com, drive.ygxone.com, etc.)"
echo ""
echo "Session configuration:"
echo "- Driver: database"
echo "- Domain: .ygxone.com (shared across all sub-domains)"
echo ""
