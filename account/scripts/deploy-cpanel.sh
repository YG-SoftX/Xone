#!/usr/bin/env bash
# =============================================================================
# YG Account — cPanel Deployment Script
# =============================================================================
# Usage:
#   bash scripts/deploy-cpanel.sh
#
# Requirements:
#   - SSH access to the cPanel server
#   - PHP CLI available (php or php8.2 depending on your host)
#   - Composer installed globally or as composer.phar
#   - Node.js + npm available (for asset builds)
#   - .env already configured at $APP_ROOT/.env
#
# Run this script from the project root on the SERVER (via SSH), or adapt
# it into a CI/CD pipeline that deploys to the server via rsync/git pull.
# =============================================================================

set -euo pipefail

# ── Configuration — adjust these for your cPanel environment ─────────────────
APP_ROOT="${APP_ROOT:-$(pwd)}"
PHP="${PHP:-php}"                   # e.g. /opt/cpanel/ea-php82/root/usr/bin/php
COMPOSER="${COMPOSER:-composer}"    # e.g. php composer.phar
NPM="${NPM:-npm}"
ARTISAN="$PHP $APP_ROOT/artisan"

# ── Helpers ───────────────────────────────────────────────────────────────────
info()    { echo -e "\033[1;34m[INFO]\033[0m  $*"; }
success() { echo -e "\033[1;32m[OK]\033[0m    $*"; }
error()   { echo -e "\033[1;31m[ERROR]\033[0m $*" >&2; exit 1; }

# ── Pre-flight checks ─────────────────────────────────────────────────────────
[[ -f "$APP_ROOT/artisan" ]]  || error "artisan not found — run from project root."
[[ -f "$APP_ROOT/.env" ]]     || error ".env not found — copy .env.production.example and fill in real values."

info "Starting deployment from $APP_ROOT"

# ── 1. Enable maintenance mode ────────────────────────────────────────────────
info "Enabling maintenance mode..."
$ARTISAN down --retry=60 || true

# ── 2. Pull latest code (if using git deployment) ─────────────────────────────
if git -C "$APP_ROOT" rev-parse --is-inside-work-tree &>/dev/null; then
    info "Pulling latest code from origin..."
    git -C "$APP_ROOT" pull origin "$(git -C "$APP_ROOT" rev-parse --abbrev-ref HEAD)"
fi

# ── 3. Install / update PHP dependencies ──────────────────────────────────────
info "Installing Composer dependencies (no-dev, optimized)..."
$COMPOSER install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --working-dir="$APP_ROOT"

# ── 4. Build frontend assets ──────────────────────────────────────────────────
info "Installing npm packages and building assets..."
$NPM ci --prefix "$APP_ROOT" --silent
$NPM run build --prefix "$APP_ROOT"

# ── 5. Run database migrations ────────────────────────────────────────────────
info "Running database migrations..."
$ARTISAN migrate --force

# ── 6. Clear and re-cache Laravel caches ──────────────────────────────────────
info "Optimizing application caches..."
$ARTISAN optimize:clear
$ARTISAN config:cache
$ARTISAN route:cache
$ARTISAN view:cache
$ARTISAN event:cache

# ── 7. Set file permissions (cPanel typically runs as your user) ───────────────
info "Setting file permissions..."
find "$APP_ROOT" -type f -not -path "*/.git/*" -exec chmod 644 {} \;
find "$APP_ROOT" -type d -not -path "*/.git/*" -exec chmod 755 {} \;
chmod 750 "$APP_ROOT/artisan"
chmod -R 775 "$APP_ROOT/storage"
chmod -R 775 "$APP_ROOT/bootstrap/cache"
chmod 600 "$APP_ROOT/.env"

# ── 8. Restart PHP-FPM / clear OPCache ───────────────────────────────────────
# cPanel does not expose service restart via CLI for shared hosting.
# Touching a PHP file triggers OPCache invalidation for that file.
info "Touching index.php to signal OPCache refresh..."
touch "$APP_ROOT/public/index.php"

# ── 9. Disable maintenance mode ───────────────────────────────────────────────
info "Taking application back online..."
$ARTISAN up

success "Deployment complete!"
echo ""
echo "  Next steps (if first deploy):"
echo "  1. Ensure your cPanel document root points to: $APP_ROOT/public"
echo "  2. Add a cron job for the scheduler:"
echo "     * * * * * $PHP $APP_ROOT/artisan schedule:run >> /dev/null 2>&1"
echo "  3. Add a cron job for the queue worker:"
echo "     * * * * * $PHP $APP_ROOT/artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1"
