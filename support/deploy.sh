#!/bin/bash
# =============================================================================
# YG Support — cPanel Deployment Script
# =============================================================================
# Usage: bash deploy.sh
# Run from the support/ directory on the local machine.
# =============================================================================

set -euo pipefail

DEPLOY_HOST="ygxone.com"
DEPLOY_PATH="/home4/ygmarket/support.ygxone.com"
SSH_USER="ygmarket"

echo "=== Building support module for production ==="

# 1. Install production dependencies
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader

# 2. Cache Laravel config for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Upload to cPanel via rsync
echo "=== Uploading to ${DEPLOY_HOST}:${DEPLOY_PATH} ==="
rsync -avz --delete \
    --exclude '.env' \
    --exclude '.git' \
    --exclude 'node_modules' \
    --exclude 'tests' \
    --exclude '.env.example' \
    . "${SSH_USER}@${DEPLOY_HOST}:${DEPLOY_PATH}"

# 4. Set proper permissions on the server
ssh "${SSH_USER}@${DEPLOY_HOST}" << 'REMOTE'
    chmod -R 775 storage
    chmod -R 775 bootstrap/cache
    chmod 644 .env
    php artisan config:clear
    php artisan view:clear
    php artisan route:clear
    php artisan cache:clear
    php artisan migrate --force
    echo "=== Deployment complete! ==="
REMOTE
