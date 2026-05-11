#!/bin/bash
# YG Console - Complete Setup Script for Linux/Mac
# This script will set up the entire YG Console platform

set -e  # Exit on error

echo "========================================"
echo "  YG Console - Complete Setup"
echo "========================================"
echo ""

# Step 1: Install PHP dependencies
echo "[1/8] Installing PHP dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader
echo ""

# Step 2: Install Node dependencies
echo "[2/8] Installing Node dependencies..."
npm install
echo ""

# Step 3: Setup environment file
echo "[3/8] Setting up environment..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env file from example"
else
    echo ".env file already exists"
fi
echo ""

# Step 4: Generate application key
echo "[4/8] Generating application key..."
php artisan key:generate
echo ""

# Step 5: Run migrations
echo "[5/8] Running database migrations..."
php artisan migrate --force || echo "WARNING: Migration failed. Please check database configuration."
echo ""

# Step 6: Install Filament
echo "[6/8] Installing Filament admin panel..."
composer require filament/filament:"^3.2" -W
echo ""

# Step 7: Install Filament panels
echo "[7/8] Setting up Filament panels..."
php artisan filament:install --panels
echo ""

# Step 8: Build frontend assets
echo "[8/8] Building frontend assets..."
npm run build
echo ""

echo "========================================"
echo "  Setup Complete!"
echo "========================================"
echo ""
echo "Next steps:"
echo "1. Configure your .env file with database credentials"
echo "2. Create a Filament admin user:"
echo "   php artisan make:filament-user"
echo "3. Start the server:"
echo "   php artisan serve --host=0.0.0.0 --port=8000"
echo ""
echo "Visit: http://localhost:8000/admin"
echo ""
