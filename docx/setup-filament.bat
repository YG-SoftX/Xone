@echo off
echo ============================================
echo  YG DocX - Filament Setup
echo ============================================
echo.

echo [1/5] Installing Filament via Composer...
composer require filament/filament:"^3.2" -W
if %errorlevel% neq 0 (
    echo ERROR: Composer install failed.
    pause
    exit /b 1
)

echo.
echo [2/5] Adding is_admin column migration...
php artisan make:migration add_is_admin_to_users_table --table=users

echo.
echo [3/5] Running database migrations...
php artisan migrate --force

echo.
echo [4/5] Creating initial admin user...
php artisan make:filament-user

echo.
echo [5/5] Building frontend assets...
npm install
npm run build

echo.
echo ============================================
echo  Setup Complete!
echo  Admin panel: http://localhost:8003/admin
echo ============================================
pause
