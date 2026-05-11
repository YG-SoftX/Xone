#!/bin/bash
# Manual deployment script for cPanel shared hosting
# Use this when Git operations fail due to resource limits

echo "Starting manual deployment..."
cd /home4/ygmarket/ygxone.com

# Set proper file permissions
echo "Setting file permissions..."
chmod 644 .env
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Clear all caches
echo "Clearing caches..."
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan cache:clear

# Rebuild caches for production
echo "Rebuilding caches..."
php artisan config:cache
php artisan event:cache

echo "✓ Manual deployment completed successfully!"
