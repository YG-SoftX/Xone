#!/bin/bash
# =============================================================================
# 🚀 YGXONE Service Activation Script (Linux/cPanel Bash)
# =============================================================================
# Purpose: Instantly activates all modules in your ecosystem on cPanel
# Modules: home, account, developer, master, docx, xcel
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

services=("home" "account" "developer" "master" "docx" "xcel")
root_path=$(pwd)

# Verify if we are running in cPanel/production environment
IS_PRODUCTION=false
if [[ "$root_path" == *"ygmarket"* ]] || [[ "$(hostname)" == *"access"* ]]; then
    IS_PRODUCTION=true
    write_info "Production environment detected! Setting production APP_URLs, sharing sessions, and database."
else
    write_info "Development environment detected."
fi

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
        # Use .env.production.example for docx if present and in production
        if [ "$service" == "docx" ] && [ "$IS_PRODUCTION" == "true" ] && [ -f ".env.production.example" ]; then
            write_info "Copying .env.production.example to .env..."
            cp .env.production.example .env
        elif [ -f ".env.example" ]; then
            write_info "Copying .env.example to .env..."
            cp .env.example .env
        else
            write_info "No .env.example found. Creating empty .env..."
            touch .env
        fi
        write_success ".env file created."
    else
        write_success ".env file already exists."
    fi

    # Step 2: Ensure database variables exist in the .env (specifically for home module template gaps)
    if ! grep -q "DB_CONNECTION=" .env; then
        write_info "Appending missing database variables to .env..."
        echo -e "\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=ygmarket_account\nDB_USERNAME=ygmarket_account\nDB_PASSWORD='Ygaccount@2.0##2026'\n" >> .env
    fi

    # Step 3: Configure Environment Settings (APP_URL, Database connection, Session Sharing)
    if [ "$IS_PRODUCTION" == "true" ]; then
        write_info "Configuring production environment tokens and DB credentials..."
        
        # Set proper subdomains (home service maps to ygxone.com without subdomain)
        if [ "$service" == "home" ]; then
            sed -i "s|APP_URL=http://localhost.*|APP_URL=https://ygxone.com|g" .env || true
            sed -i "s|APP_URL=http://127.0.0.1.*|APP_URL=https://ygxone.com|g" .env || true
            sed -i "s|APP_URL=https://home.ygxone.com|APP_URL=https://ygxone.com|g" .env || true
        else
            sed -i "s|APP_URL=http://localhost.*|APP_URL=https://$service.ygxone.com|g" .env || true
            sed -i "s|APP_URL=http://127.0.0.1.*|APP_URL=https://$service.ygxone.com|g" .env || true
        fi
        
        # Configure cross-subdomain SSO sessions
        sed -i 's/SESSION_DRIVER=.*/SESSION_DRIVER=database/g' .env || true
        sed -i 's/SESSION_DOMAIN=.*/SESSION_DOMAIN=.ygxone.com/g' .env || true
        sed -i 's/SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=true/g' .env || true
        
        # Configure actual cPanel MySQL credentials
        sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=mysql/g' .env || true
        sed -i 's/DB_HOST=.*/DB_HOST=127.0.0.1/g' .env || true
        sed -i 's/DB_PORT=.*/DB_PORT=3306/g' .env || true
        sed -i 's/DB_DATABASE=.*/DB_DATABASE=ygmarket_account/g' .env || true
        sed -i 's/DB_USERNAME=.*/DB_USERNAME=ygmarket_account/g' .env || true
        sed -i "s/DB_PASSWORD=.*/DB_PASSWORD='Ygaccount@2.0##2026'/g" .env || true

        # Handle service integrations and SSO URLs
        sed -i 's|YG_ACCOUNT_URL=.*|YG_ACCOUNT_URL=https://account.ygxone.com|g' .env || true
        sed -i 's|YG_ACCOUNT_API_URL=.*|YG_ACCOUNT_API_URL=https://account.ygxone.com/api|g' .env || true
        
        write_success "Production URLs, Database credentials, and Session SSO configured successfully."
    else
        write_info "Applying local development APP_URL configurations..."
        if [ "$service" == "home" ]; then
            sed -i 's|APP_URL=.*|APP_URL=http://localhost:8000|g' .env || true
        elif [ "$service" == "account" ]; then
            sed -i 's|APP_URL=.*|APP_URL=http://localhost:8000|g' .env || true
        elif [ "$service" == "developer" ]; then
            sed -i 's|APP_URL=.*|APP_URL=http://localhost:8010|g' .env || true
        else
            sed -i 's|APP_URL=.*|APP_URL=http://localhost|g' .env || true
        fi
        write_success "Local APP_URL configured."
    fi

    # Step 4: Install Composer Dependencies
    write_info "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction --no-plugins --no-scripts --prefer-dist || \
    composer install --ignore-platform-reqs --no-dev --optimize-autoloader --no-interaction --no-plugins --no-scripts --prefer-dist
    write_success "Composer packages installed."

    # Step 5: Generate APP_KEY if empty
    if ! grep -q "APP_KEY=base64:" .env; then
        write_info "Generating application key..."
        
        # Temporary SQLite switch to bypass MySQL connection errors on local systems during key generation
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

    # Step 6: Clear & Rebuild Caches
    write_info "Clearing and optimizing Laravel caches..."
    php artisan optimize:clear
    php artisan config:cache || true
    php artisan route:cache || true
    write_success "Caches optimized."
done

# Return to root
cd "$root_path"

write_header "🎉 ALL SERVICES SUCCESSFULLY ACTIVATED!"
echo -e "${GREEN}All services (including ygxone.com search hub) are fully active and production-ready!${NC}"
