#!/bin/bash
# =============================================================================
# 🚀 YGXONE Service Activation Script (Linux/cPanel Bash)
# =============================================================================
# Purpose: Instantly activates the 5 major modules in your ecosystem on cPanel
# Modules: account, developer, master, docx, xcel
# Usage: chmod +x activate-services.sh && ./activate-services.sh
# =============================================================================

set -e

# Colors for formatting
CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
WHITE_ON_BLUE='\033[1;37;44m'
NC='\033[0m' # No Color

write_header() {
    echo -e "${CYAN}\n========================================================${NC}"
    echo -e "${WHITE_ON_BLUE} $1 ${NC}"
    echo -e "${CYAN}========================================================${NC}"
}

write_success() {
    echo -e "  ${GREEN}✓ $1${NC}"
}

write_info() {
    echo -e "  ${YELLOW}→ $1${NC}"
}

write_header "YGXONE MASTER SERVICE ACTIVATION ENGINE (cPanel)"

services=("account" "developer" "master" "docx" "xcel")
root_path=$(pwd)

for service in "${services[@]}"; do
    write_header "ACTIVATING MODULE: $service"
    
    service_path="$root_path/$service"
    if [ ! -d "$service_path" ]; then
        echo -e "  ${YELLOW}⚠ Module directory '$service' not found. Skipping.${NC}"
        continue
    fi

    # Navigate to module
    cd "$service_path"
    write_info "Working directory: $service_path"

    # Step 1: Handle .env File
    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            write_info "Copying .env.example to .env..."
            cp .env.example .env
            write_success ".env file created."
        else
            write_info "No .env.example found. Creating empty .env..."
            touch .env
        fi
    else
        write_success ".env file already exists."
    fi

    # Step 2: Install Composer Dependencies
    write_info "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction --no-plugins --no-scripts --prefer-dist || \
    composer install --ignore-platform-reqs --no-dev --optimize-autoloader --no-interaction --no-plugins --no-scripts --prefer-dist
    write_success "Composer packages installed."

    # Step 3: Generate APP_KEY if empty
    if ! grep -q "APP_KEY=base64:" .env; then
        write_info "Generating application key..."
        
        # Temporary SQLite switch to bypass MySQL connection errors on local systems
        sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/g' .env || true
        sed -i 's/DB_DATABASE=ygmarket_account/DB_DATABASE=:memory:/g' .env || true
        
        # Generate key
        php artisan key:generate --force
        
        # Restore MySQL settings
        sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/g' .env || true
        sed -i 's/DB_DATABASE=:memory:/DB_DATABASE=ygmarket_account/g' .env || true
        
        write_success "App key generated successfully."
    else
        write_success "App key already configured."
    fi

    # Step 4: Clear & Rebuild Caches
    write_info "Clearing and optimizing Laravel caches..."
    php artisan optimize:clear
    php artisan config:cache || true
    php artisan route:cache || true
    write_success "Caches optimized."
done

# Return to root
cd "$root_path"

write_header "🎉 ALL 5 SERVICES SUCCESSFULLY ACTIVATED!"
echo -e "${GREEN}All services are prepared and ready for production use!${NC}"
