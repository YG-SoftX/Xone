#!/usr/bin/env bash
# =============================================================================
# YG Account — Automated Backup Script (cPanel compatible)
# =============================================================================
# Creates timestamped backups of:
#   • MySQL database (via mysqldump)
#   • Application files (storage/app, .env)
#
# Recommended cron schedule (daily at 02:00):
#   0 2 * * * bash /home/YOUR_CPANEL_USER/yg-account/scripts/backup.sh >> /home/YOUR_CPANEL_USER/logs/backup.log 2>&1
#
# Usage:
#   bash scripts/backup.sh [--keep-days N]
#
# Options:
#   --keep-days N   Delete backups older than N days (default: 14)
# =============================================================================

set -euo pipefail

# ── Configuration ─────────────────────────────────────────────────────────────
APP_ROOT="${APP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
BACKUP_DIR="${BACKUP_DIR:-$APP_ROOT/storage/backups}"
KEEP_DAYS=14
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Parse arguments
while [[ $# -gt 0 ]]; do
    case "$1" in
        --keep-days) KEEP_DAYS="$2"; shift 2 ;;
        *) echo "Unknown argument: $1" >&2; exit 1 ;;
    esac
done

# Load .env so we can read database credentials
if [[ -f "$APP_ROOT/.env" ]]; then
    # shellcheck disable=SC2046
    export $(grep -v '^#' "$APP_ROOT/.env" | grep -v '^\s*$' | xargs)
fi

DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-}"
DB_USERNAME="${DB_USERNAME:-}"
DB_PASSWORD="${DB_PASSWORD:-}"

# ── Helpers ───────────────────────────────────────────────────────────────────
info()    { echo "[$(date '+%Y-%m-%d %H:%M:%S')] [INFO]  $*"; }
success() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] [OK]    $*"; }
error()   { echo "[$(date '+%Y-%m-%d %H:%M:%S')] [ERROR] $*" >&2; exit 1; }

# ── Ensure backup directory exists ───────────────────────────────────────────
mkdir -p "$BACKUP_DIR"

info "Starting backup — timestamp: $TIMESTAMP"

# ── 1. Database backup ────────────────────────────────────────────────────────
if [[ -n "$DB_DATABASE" && -n "$DB_USERNAME" ]]; then
    DB_BACKUP_FILE="$BACKUP_DIR/db_${DB_DATABASE}_${TIMESTAMP}.sql.gz"

    info "Backing up database '$DB_DATABASE' to $DB_BACKUP_FILE..."

    MYSQL_PWD="$DB_PASSWORD" mysqldump \
        --host="$DB_HOST" \
        --port="$DB_PORT" \
        --user="$DB_USERNAME" \
        --single-transaction \
        --routines \
        --triggers \
        --no-tablespaces \
        "$DB_DATABASE" | gzip -9 > "$DB_BACKUP_FILE"

    chmod 600 "$DB_BACKUP_FILE"
    success "Database backup saved: $(du -sh "$DB_BACKUP_FILE" | cut -f1)"
else
    info "Skipping database backup — DB_DATABASE or DB_USERNAME not set."
fi

# ── 2. Application files backup ───────────────────────────────────────────────
FILES_BACKUP="$BACKUP_DIR/files_${TIMESTAMP}.tar.gz"

info "Backing up application files to $FILES_BACKUP..."

tar -czf "$FILES_BACKUP" \
    --exclude="$APP_ROOT/node_modules" \
    --exclude="$APP_ROOT/vendor" \
    --exclude="$APP_ROOT/.git" \
    --exclude="$APP_ROOT/storage/backups" \
    --exclude="$APP_ROOT/storage/logs" \
    -C "$(dirname "$APP_ROOT")" \
    "$(basename "$APP_ROOT")"

chmod 600 "$FILES_BACKUP"
success "Files backup saved: $(du -sh "$FILES_BACKUP" | cut -f1)"

# ── 3. Prune old backups ──────────────────────────────────────────────────────
info "Removing backups older than $KEEP_DAYS days..."

find "$BACKUP_DIR" -name "*.sql.gz"   -mtime "+$KEEP_DAYS" -delete
find "$BACKUP_DIR" -name "*.tar.gz"   -mtime "+$KEEP_DAYS" -delete

success "Backup complete. Files in $BACKUP_DIR:"
ls -lh "$BACKUP_DIR"
