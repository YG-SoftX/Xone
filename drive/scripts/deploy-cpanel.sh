#!/usr/bin/env bash
# YG Drive — cPanel Deployment Script
set -euo pipefail

APP_ROOT="${APP_ROOT:-$(pwd)}"
PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"
ARTISAN="$PHP $APP_ROOT/artisan"

info()    { echo -e "\033[1;34m[INFO]\033[0m  $*"; }
success() { echo -e "\033[1;32m[OK]\033[0m    $*"; }
error()   { echo -e "\033[1;31m[ERROR]\033[0m $*" >&2; exit 1; }

[[ -f "$APP_ROOT/artisan" ]] || error "Run from project root."
[[ -f "$APP_ROOT/.env" ]]    || error ".env missing — copy .env.production.example and configure it."

info "Deploying YG Drive from $APP_ROOT"

$ARTISAN down --retry=60 || true

git -C "$APP_ROOT" pull origin "$(git -C "$APP_ROOT" rev-parse --abbrev-ref HEAD)" 2>/dev/null || true

$COMPOSER install --no-dev --optimize-autoloader --no-interaction --working-dir="$APP_ROOT"

$ARTISAN migrate --force
$ARTISAN storage:link 2>/dev/null || true

$ARTISAN optimize:clear
$ARTISAN config:cache
$ARTISAN route:cache
$ARTISAN view:cache
$ARTISAN event:cache

find "$APP_ROOT" -type f -not -path "*/.git/*" -exec chmod 644 {} \;
find "$APP_ROOT" -type d -not -path "*/.git/*" -exec chmod 755 {} \;
chmod 750 "$APP_ROOT/artisan"
chmod -R 775 "$APP_ROOT/storage"
chmod -R 775 "$APP_ROOT/bootstrap/cache"
chmod 600 "$APP_ROOT/.env"

touch "$APP_ROOT/public/index.php"
$ARTISAN up

success "YG Drive deployment complete!"
echo ""
echo "  Cron jobs to add in cPanel:"
echo "  * * * * * $PHP $APP_ROOT/artisan schedule:run >> /dev/null 2>&1"
echo "  * * * * * $PHP $APP_ROOT/artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1"
