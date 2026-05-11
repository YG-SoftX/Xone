#!/usr/bin/env bash
# YGXone Home — Backup Script (cPanel compatible)
# Cron: 0 3 * * * bash /path/to/yg-home/scripts/backup.sh >> /path/to/logs/yghome-backup.log 2>&1
set -euo pipefail

APP_ROOT="${APP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
BACKUP_DIR="${BACKUP_DIR:-$APP_ROOT/storage/backups}"
KEEP_DAYS=14
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting YGXone Home backup..."

# Backup the entire project (exclude node_modules, dist, .git)
FILES_FILE="$BACKUP_DIR/yghome_${TIMESTAMP}.tar.gz"
tar -czf "$FILES_FILE" \
    --exclude="$APP_ROOT/node_modules" \
    --exclude="$APP_ROOT/.git" \
    --exclude="$APP_ROOT/storage/backups" \
    -C "$(dirname "$APP_ROOT")" "$(basename "$APP_ROOT")"
chmod 600 "$FILES_FILE"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Backup: $(du -sh "$FILES_FILE" | cut -f1)"

# Backup SQLite search index separately if it exists
if [[ -f "$APP_ROOT/database/search.sqlite" ]]; then
    DB_FILE="$BACKUP_DIR/search_${TIMESTAMP}.sqlite.gz"
    gzip -c "$APP_ROOT/database/search.sqlite" > "$DB_FILE"
    chmod 600 "$DB_FILE"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] SQLite backup: $(du -sh "$DB_FILE" | cut -f1)"
fi

find "$BACKUP_DIR" \( -name "*.tar.gz" -o -name "*.sqlite.gz" \) -mtime "+$KEEP_DAYS" -delete
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Backup complete."
