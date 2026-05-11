@echo off
REM YG Console - Complete Setup Script for Windows
REM This script will set up the entire YG Console platform

echo ========================================
echo   YG Console - Complete Setup
echo ========================================
echo.

REM Step 1: Install PHP dependencies
echo [1/8] Installing PHP dependencies...
call composer install --no-interaction --prefer-dist --optimize-autoloader
if %errorlevel% neq 0 (
    echo ERROR: Composer install failed!
    pause
    exit /b 1
)
echo.

REM Step 2: Install Node dependencies
echo [2/8] Installing Node dependencies...
call npm install
if %errorlevel% neq 0 (
    echo ERROR: NPM install failed!
    pause
    exit /b 1
)
echo.

REM Step 3: Setup environment file
echo [3/8] Setting up environment...
if not exist .env (
    copy .env.example .env
    echo Created .env file from example
) else (
    echo .env file already exists
)
echo.

REM Step 4: Generate application key
echo [4/8] Generating application key...
call php artisan key:generate
if %errorlevel% neq 0 (
    echo ERROR: Key generation failed!
    pause
    exit /b 1
)
echo.

REM Step 5: Run migrations
echo [5/8] Running database migrations...
call php artisan migrate --force
if %errorlevel% neq 0 (
    echo WARNING: Migration failed. Please check database configuration.
    echo You may need to create the database first.
)
echo.

REM Step 6: Install Filament
echo [6/8] Installing Filament admin panel...
call composer require filament/filament:"^3.2" -W
if %errorlevel% neq 0 (
    echo ERROR: Filament installation failed!
    pause
    exit /b 1
)
echo.

REM Step 7: Install Filament panels
echo [7/8] Setting up Filament panels...
call php artisan filament:install --panels
if %errorlevel% neq 0 (
    echo ERROR: Filament panel installation failed!
    pause
    exit /b 1
)
echo.

REM Step 8: Build frontend assets
echo [8/8] Building frontend assets...
call npm run build
if %errorlevel% neq 0 (
    echo ERROR: Asset build failed!
    pause
    exit /b 1
)
echo.

echo ========================================
echo   Setup Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Configure your .env file with database credentials
echo 2. Create a Filament admin user:
echo    php artisan make:filament-user
echo 3. Start the server:
echo    php artisan serve --host=0.0.0.0 --port=8000
echo.
echo Visit: http://localhost:8000/admin
echo.
pause
