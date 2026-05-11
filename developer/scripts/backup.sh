#!/usr/bin/env bash
# YG Developer Portal — Backup Script
# Cron: 0 2 * * * bash /path/to/yg-developer/scripts/backup.sh >> /path/to/logs/backup.log 2>&1
set -euo pipefail

APP_ROOT="${APP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
BACKUP_DIR="${BACKUP_DIR:-$APP_ROOT/storage/backups}"
KEEP_DAYS=14
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

[[ -f "$APP_ROOT/.env" ]] && export $(grep -v '^#' "$APP_ROOT/.env" | grep -v '^\s*$' | xargs) 2>/dev/null || true

mkdir -p "$BACKUP_DIR"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting YG Developer backup..."

if [[ -n "${DB_DATABASE:-}" && -n "${DB_USERNAME:-}" ]]; then
    DB_FILE="$BACKUP_DIR/db_${DB_DATABASE}_${TIMESTAMP}.sql.gz"
    MYSQL_PWD="${DB_PASSWORD:-}" mysqldump --host="${DB_HOST:-localhost}" --user="$DB_USERNAME" \
        --single-transaction --no-tablespaces "$DB_DATABASE" | gzip -9 > "$DB_FILE"
    chmod 600 "$DB_FILE"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] DB backup: $(du -sh "$DB_FILE" | cut -f1)"
fi

FILES_FILE="$BACKUP_DIR/files_${TIMESTAMP}.tar.gz"
tar -czf "$FILES_FILE" \
    --exclude="$APP_ROOT/node_modules" --exclude="$APP_ROOT/vendor" \
    --exclude="$APP_ROOT/.git" --exclude="$APP_ROOT/storage/backups" \
    -C "$(dirname "$APP_ROOT")" "$(basename "$APP_ROOT")"
chmod 600 "$FILES_FILE"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Files backup: $(du -sh "$FILES_FILE" | cut -f1)"

find "$BACKUP_DIR" \( -name "*.sql.gz" -o -name "*.tar.gz" \) -mtime "+$KEEP_DAYS" -delete
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Backup complete."
