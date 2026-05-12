#!/bin/bash
# =============================================================================
# YG Ecosystem - Unified Database Configuration Script (Linux/Mac)
# =============================================================================
# This script configures ALL modules to use ONE shared database
# Run this BEFORE deploying to cPanel
# Usage: chmod +x setup-unified-database.sh && ./setup-unified-database.sh
# =============================================================================

echo ""
echo "========================================"
echo "  YG Unified Database Configuration"
echo "========================================"
echo ""

# Database Configuration (Update these for your environment)
DB_HOST="127.0.0.1"           # Use 'localhost' for cPanel
DB_PORT="3306"
DB_DATABASE="ygmarket_account"  # Single database for all modules
DB_USERNAME="ygmarket_account"
DB_PASSWORD="Ygaccount@2.0##2026"  # CHANGE THIS IN PRODUCTION!

# List of all Laravel modules
MODULES=(
    "home"
    "account"
    "mail"
    "calendar"
    "chat"
    "contacts"
    "drive"
    "notes"
    "xcel"
    "docx"
    "collect"
    "developer"
)

SUCCESS_COUNT=0
SKIP_COUNT=0
ERROR_COUNT=0

echo "Configuring database for ${#MODULES[@]} modules..."
echo ""

for module in "${MODULES[@]}"; do
    ENV_PATH="$module/.env"
    ENV_EXAMPLE_PATH="$module/.env.example"
    
    echo -n "Processing: $module"
    
    # Check if .env exists, if not copy from .env.example
    if [ ! -f "$ENV_PATH" ]; then
        if [ -f "$ENV_EXAMPLE_PATH" ]; then
            cp "$ENV_EXAMPLE_PATH" "$ENV_PATH"
            echo " (created from .env.example)"
        else
            echo " - SKIPPED (no .env or .env.example)"
            SKIP_COUNT=$((SKIP_COUNT + 1))
            continue
        fi
    fi
    
    # Update database configuration using sed
    sed -i.bak \
        -e "s|^DB_HOST=.*|DB_HOST=$DB_HOST|" \
        -e "s|^DB_PORT=.*|DB_PORT=$DB_PORT|" \
        -e "s|^DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|" \
        -e "s|^DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|" \
        -e "s|^DB_PASSWORD=.*|DB_PASSWORD='$DB_PASSWORD'|" \
        "$ENV_PATH"
    
    # Remove backup file
    rm -f "${ENV_PATH}.bak"
    
    if [ $? -eq 0 ]; then
        echo " - ✓ Configured"
        SUCCESS_COUNT=$((SUCCESS_COUNT + 1))
    else
        echo " - ✗ ERROR"
        ERROR_COUNT=$((ERROR_COUNT + 1))
    fi
done

# Summary
echo ""
echo "========================================"
echo "  Configuration Summary"
echo "========================================"
echo ""

echo "Successfully configured: $SUCCESS_COUNT modules"
echo "Skipped:                 $SKIP_COUNT modules"
echo "Errors:                  $ERROR_COUNT modules"
echo ""

echo "Database Configuration:"
echo "  Host:     $DB_HOST"
echo "  Port:     $DB_PORT"
echo "  Database: $DB_DATABASE"
echo "  Username: $DB_USERNAME"
echo "  Password: ********"
echo ""

if [ $ERROR_COUNT -eq 0 ]; then
    echo "✅ All modules configured successfully!"
    echo ""
    echo "Next steps:"
    echo "1. Review .env files if needed"
    echo "2. For cPanel: Update DB credentials with your cPanel username"
    echo "3. Run migrations: php artisan migrate --force (in each module)"
    echo "4. Clear cache: php artisan config:clear (in each module)"
    echo ""
else
    echo "⚠️  Some modules had errors. Please check and fix manually."
fi

echo "For detailed guide, see: UNIFIED_DATABASE_CONFIG.md"
echo ""
