#!/usr/bin/env bash
# =============================================================================
# YGXone Home — cPanel Deployment Script
# =============================================================================
# Usage: bash scripts/deploy-cpanel.sh
#
# This deploys the static/PHP YGXone home hub to cPanel.
# Document root in cPanel should point directly to this directory.
# =============================================================================
set -euo pipefail

APP_ROOT="${APP_ROOT:-$(pwd)}"
NPM="${NPM:-npm}"

info()    { echo -e "\033[1;34m[INFO]\033[0m  $*"; }
success() { echo -e "\033[1;32m[OK]\033[0m    $*"; }
error()   { echo -e "\033[1;31m[ERROR]\033[0m $*" >&2; exit 1; }

[[ -f "$APP_ROOT/index.html" ]] || error "Run from the yg-home project root."

info "Deploying YGXone Home from $APP_ROOT"

# ── 1. Pull latest code ────────────────────────────────────────────────────────
if git -C "$APP_ROOT" rev-parse --is-inside-work-tree &>/dev/null; then
    info "Pulling latest code..."
    git -C "$APP_ROOT" pull origin "$(git -C "$APP_ROOT" rev-parse --abbrev-ref HEAD)"
fi

# ── 2. Build frontend assets ────────────────────────────────────────────────────
if [[ -f "$APP_ROOT/package.json" ]]; then
    info "Installing npm packages..."
    $NPM ci --prefix "$APP_ROOT" --silent

    info "Building assets..."
    $NPM run build --prefix "$APP_ROOT"
    success "Assets built to dist/"
fi

# ── 3. Set file permissions ─────────────────────────────────────────────────────
info "Setting file permissions..."
find "$APP_ROOT" -type f -not -path "*/.git/*" -exec chmod 644 {} \;
find "$APP_ROOT" -type d -not -path "*/.git/*" -exec chmod 755 {} \;

# Lock down sensitive files
chmod 600 "$APP_ROOT/config.php" 2>/dev/null || true
[[ -f "$APP_ROOT/.htpasswd" ]] && chmod 600 "$APP_ROOT/.htpasswd"

# Make scripts executable
chmod 750 "$APP_ROOT/scripts/"*.sh 2>/dev/null || true

# ── 4. Protect database directory ───────────────────────────────────────────────
if [[ -d "$APP_ROOT/database" ]]; then
    chmod 700 "$APP_ROOT/database"
    find "$APP_ROOT/database" -name "*.sqlite" -exec chmod 600 {} \;
fi

success "YGXone Home deployment complete!"
echo ""
echo "  cPanel setup checklist:"
echo "  ✓ Document root: $(basename "$APP_ROOT")/ (or public_html/ if at domain root)"
echo "  ✓ PHP version: 8.2+ via cPanel PHP Selector"
echo "  ✓ SSL: Enable via cPanel AutoSSL, then uncomment HTTPS redirect in .htaccess"
echo "  ✓ Environment vars: Set in cPanel > PHP INI or .htaccess SetEnv directives"
echo "  ✓ YG_AI_API_URL: Point to https://ai.ygxone.com (or local path)"
