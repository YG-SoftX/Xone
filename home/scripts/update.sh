#!/usr/bin/env bash
# YG Xone Home — Smart Update Script
set -euo pipefail
APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

LIB="${ECOSYSTEM_LIB:-$APP_ROOT/../../ecosystem/lib/update-lib.sh}"
[[ -f "$LIB" ]] && source "$LIB" || { echo "[ERROR] Update library not found: $LIB" >&2; exit 1; }

APP_URL="${APP_URL:-$(grep '^APP_URL' "$APP_ROOT/.env.example" 2>/dev/null | head -1 | cut -d= -f2)}"
update_static_app "$APP_ROOT" "YG Xone" "$APP_URL"
