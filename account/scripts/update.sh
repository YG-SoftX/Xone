#!/usr/bin/env bash
# YG Account — Smart Update Script
# Usage: bash scripts/update.sh
# Runs from project root. Sources shared update library.

set -euo pipefail
APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

LIB="${ECOSYSTEM_LIB:-$APP_ROOT/../../ecosystem/lib/update-lib.sh}"
[[ -f "$LIB" ]] && source "$LIB" || {
    echo "[ERROR] Update library not found at: $LIB" >&2
    echo "        Run from inside the YGXone project tree, or set ECOSYSTEM_LIB." >&2
    exit 1
}

APP_URL="${APP_URL:-$(grep '^APP_URL=' "$APP_ROOT/.env" 2>/dev/null | cut -d= -f2 | tr -d '"')}"

update_laravel_app "$APP_ROOT" "YG Account" "$APP_URL"
