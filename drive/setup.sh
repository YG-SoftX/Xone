#!/bin/bash
set -e
echo "============================================"
echo " YG Drive - Full Setup"
echo "============================================"

echo "[1/8] Installing PHP dependencies..."
composer install

echo "[2/8] Copying environment file..."
[ ! -f .env ] && cp .env.example .env && echo ".env created" || echo ".env exists - skipping"

echo "[3/8] Generating application key..."
php artisan key:generate

echo "[4/8] Creating SQLite database..."
[ ! -f database/database.sqlite ] && touch database/database.sqlite

echo "[5/8] Running database migrations..."
php artisan migrate --force --seed

echo "[6/8] Creating storage symlink..."
php artisan storage:link

echo "[7/8] Installing Filament..."
php artisan filament:install --panels --no-interaction

echo "[8/8] Caching config..."
php artisan config:cache && php artisan route:cache

echo ""
echo "============================================"
echo " YG Drive Setup Complete!"
echo ""
echo " Start: php artisan serve --port=8007"
echo " Admin: http://localhost:8007/admin"
echo " Login: admin@ygxone.com / admin123"
echo " IMPORTANT: Change password after first login!"
echo "============================================"
