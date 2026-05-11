@echo off
echo ============================================
echo  YG Drive - Full Setup
echo ============================================
echo.

cd /d "%~dp0"

echo [1/8] Installing PHP dependencies...
composer install
if %errorlevel% neq 0 (
    echo ERROR: Composer install failed. Make sure PHP and Composer are installed.
    pause
    exit /b 1
)

echo.
echo [2/8] Copying environment file...
if not exist .env (
    copy .env.example .env
    echo .env created from .env.example
) else (
    echo .env already exists - skipping
)

echo.
echo [3/8] Generating application key...
php artisan key:generate

echo.
echo [4/8] Creating SQLite database...
if not exist database\database.sqlite (
    echo. > database\database.sqlite
    echo SQLite database created
)

echo.
echo [5/8] Running database migrations...
php artisan migrate --force --seed

echo.
echo [6/8] Creating storage symlink...
php artisan storage:link

echo.
echo [7/8] Installing Filament...
php artisan filament:install --panels --no-interaction

echo.
echo [8/8] Caching config for performance...
php artisan config:cache
php artisan route:cache

echo.
echo ============================================
echo  YG Drive Setup Complete!
echo.
echo  To start the server:
echo    php artisan serve --port=8007
echo.
echo  Admin panel: http://localhost:8007/admin
echo  Default admin: admin@ygxone.com / admin123
echo.
echo  IMPORTANT: Change the admin password after login!
echo ============================================
pause
