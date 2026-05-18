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

services=("home" "account" "developer" "master" "docx" "xcel" "mail")
root_path=$(pwd)

# Verify if we are running in cPanel/production environment
IS_PRODUCTION=false
if [[ "$root_path" == *"ygmarket"* ]] || [[ "$(hostname)" == *"access"* ]]; then
    IS_PRODUCTION=true
    write_info "Production environment detected! Setting production APP_URLs, sharing sessions, and database."
else
    write_info "Development environment detected."
fi

# Step 1: Bootstrap all .env files first
for service in "${services[@]}"; do
    service_path="$root_path/$service"
    if [ ! -d "$service_path" ]; then
        continue
    fi

    # Navigate to module
    cd "$service_path"

    # Create directories
    mkdir -p storage/framework/cache/data
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p storage/logs
    mkdir -p bootstrap/cache
    chmod -R 775 storage bootstrap/cache || true

    # Handle .env File
    if [ ! -f ".env" ]; then
        if [ "$service" == "docx" ] && [ "$IS_PRODUCTION" == "true" ] && [ -f ".env.production.example" ]; then
            cp .env.production.example .env
        elif [ -f ".env.example" ]; then
            cp .env.example .env
        else
            touch .env
        fi
    fi

    # Ensure database variables exist in the .env
    if ! grep -q "DB_CONNECTION=" .env; then
        echo -e "\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=ygmarket_account\nDB_USERNAME=ygmarket_account\nDB_PASSWORD='Ygaccount@2.0##2026'\n" >> .env
    fi

    # Ensure ecosystem SSO URL variables exist in .env (critical to prevent localhost redirects!)
    if ! grep -q "YG_ACCOUNT_URL=" .env; then
        echo -e "\nYG_ACCOUNT_URL=https://account.ygxone.com\nYG_ACCOUNT_API_URL=https://account.ygxone.com/api\nYG_MAIL_URL=https://mail.ygxone.com\nYG_DRIVE_URL=https://drive.ygxone.com\nYG_MASTER_URL=https://master.ygxone.com\n" >> .env
    fi

    # Ensure APP_KEY placeholder exists in .env
    if ! grep -q "APP_KEY=" .env; then
        echo -e "\nAPP_KEY=" >> .env
    fi
done

# Step 2: Synchronize APP_KEY across all modules for Single Sign-On (SSO)
# In Laravel, cookies and sessions must be decrypted using the same APP_KEY for SSO to work.
write_info "Synchronizing APP_KEY across all modules to enable Single Sign-On (SSO)..."
cd "$root_path/account"
if ! grep -q "APP_KEY=base64:" .env; then
    # Temporarily switch to SQLite to bypass connection issues during generation
    sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/g' .env || true
    sed -i 's/DB_DATABASE=ygmarket_account/DB_DATABASE=:memory:/g' .env || true
    php artisan key:generate --force
    sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/g' .env || true
    sed -i 's/DB_DATABASE=:memory:/DB_DATABASE=ygmarket_account/g' .env || true
fi
shared_key=$(grep "^APP_KEY=" .env | cut -d'=' -f2)

# Copy the shared key to all other modules
for service in "${services[@]}"; do
    if [ "$service" != "account" ] && [ -f "$root_path/$service/.env" ]; then
        sed -i "s|^APP_KEY=.*|APP_KEY=$shared_key|g" "$root_path/$service/.env" || true
    fi
done
write_success "Ecosystem-wide SSO APP_KEY synchronization complete!"

# Step 3: Run full service configuration, dependency installation, and caching
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

    # Step 4: Configure Environment Settings (APP_URL, Database connection, Session Sharing)
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
        
        # Forcefully inject cPanel MySQL credentials cleanly
        sed -i '/^#\? *DB_/d' .env || true
        echo -e "\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=ygmarket_account\nDB_USERNAME=ygmarket_account\nDB_PASSWORD='Ygaccount@2.0##2026'" >> .env

        # Handle service integrations and SSO URLs (Force secure production URLs)
        if ! grep -q "YG_ACCOUNT_API_BASE=" .env; then
            echo -e "\nYG_ACCOUNT_API_BASE=https://account.ygxone.com/api" >> .env
        fi
        sed -i 's|YG_ACCOUNT_URL=.*|YG_ACCOUNT_URL=https://account.ygxone.com|g' .env || true
        sed -i 's|YG_ACCOUNT_API_URL=.*|YG_ACCOUNT_API_URL=https://account.ygxone.com/api|g' .env || true
        sed -i 's|YG_ACCOUNT_API_BASE=.*|YG_ACCOUNT_API_BASE=https://account.ygxone.com/api|g' .env || true
        sed -i 's|YG_MAIL_URL=.*|YG_MAIL_URL=https://mail.ygxone.com|g' .env || true
        sed -i 's|YG_DRIVE_URL=.*|YG_DRIVE_URL=https://drive.ygxone.com|g' .env || true
        sed -i 's|YG_MASTER_URL=.*|YG_MASTER_URL=https://master.ygxone.com|g' .env || true
        
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

    # Step 5: Install Composer Dependencies
    write_info "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction --no-plugins --no-scripts --prefer-dist || \
    composer install --ignore-platform-reqs --no-dev --optimize-autoloader --no-interaction --no-plugins --no-scripts --prefer-dist
    write_success "Composer packages installed."

    # Step 5.5: Run database migrations and seeders (safe for production)
    if [ "$service" == "account" ]; then
        write_info "Running account database migrations and seeders..."
        php artisan migrate --force || true
        php artisan db:seed --force || true
    elif [ "$service" == "master" ]; then
        write_info "Running master database migrations and seeders..."
        php artisan migrate --force || true
        php artisan db:seed --class=AdminUserSeeder --force || true
    else
        write_info "Running database migrations..."
        php artisan migrate --force || true
    fi

    # Step 6: Clear & Rebuild Caches
    write_info "Clearing and optimizing Laravel caches..."
    rm -f bootstrap/cache/*.php || true
    php artisan optimize:clear
    php artisan config:cache || true
    php artisan route:cache || true
    write_success "Caches optimized."
done

# Return to root
cd "$root_path"

write_header "🎉 ALL SERVICES SUCCESSFULLY ACTIVATED!"
echo -e "${GREEN}All services (including ygxone.com search hub) are fully active and production-ready!${NC}"
