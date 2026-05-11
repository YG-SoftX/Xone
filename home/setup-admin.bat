@echo off
REM ============================================================================
REM YG Home - Admin User Setup Script (Windows)
REM ============================================================================
REM This script helps you configure admin users for the admin dashboard
REM ============================================================================

echo.
echo ========================================
echo  YG Home Admin Configuration Setup
echo ========================================
echo.

REM Check if .env file exists
if not exist ".env" (
    echo [ERROR] .env file not found!
    echo Please copy .env.example to .env first:
    echo   copy .env.example .env
    echo.
    pause
    exit /b 1
)

echo [INFO] Current admin configuration:
echo.
findstr /C:"ADMIN_EMAILS" .env
findstr /C:"ADMIN_USER_IDS" .env
echo.

echo ========================================
echo  Step 1: Configure Admin Email(s)
echo ========================================
echo.
echo Enter admin email address(es):
echo (Multiple emails: comma-separated, no spaces)
echo Example: admin@ygxone.com or admin@ygxone.com,superadmin@ygxone.com
echo.
set /p ADMIN_EMAIL="Your admin email(s): "

if "%ADMIN_EMAIL%"=="" (
    echo [WARNING] No email entered. Keeping existing configuration.
) else (
    echo.
    echo Updating ADMIN_EMAILS in .env...
    
    REM Create temporary file
    type nul > .env.tmp
    
    REM Process each line
    for /f "usebackq delims=" %%a in (".env") do (
        echo %%a | findstr /B /C:"ADMIN_EMAILS=" >nul
        if errorlevel 1 (
            echo %%a >> .env.tmp
        ) else (
            echo ADMIN_EMAILS=%ADMIN_EMAIL% >> .env.tmp
        )
    )
    
    REM Replace original file
    move /Y .env.tmp .env >nul
    echo [SUCCESS] Admin email(s) updated!
)

echo.
echo ========================================
echo  Step 2: Generate Application Key
echo ========================================
echo.
echo Generating APP_KEY (required for sessions)...
call php artisan key:generate

if errorlevel 1 (
    echo [ERROR] Failed to generate APP_KEY
    echo Make sure PHP is installed and in PATH
    pause
    exit /b 1
)

echo.
echo ========================================
echo  Step 3: Clear Configuration Cache
echo ========================================
echo.
echo Clearing caches...
call php artisan config:clear
call php artisan cache:clear

echo.
echo ========================================
echo  Step 4: Verify Database Tables
echo ========================================
echo.
echo Checking if required tables exist...
call php artisan migrate:status

echo.
echo ========================================
echo  Setup Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Start the server: php artisan serve --host=0.0.0.0 --port=8001
echo 2. Visit: http://localhost:8001/login
echo 3. Login with your admin account
echo 4. Access dashboard: http://localhost:8001/admin/dashboard
echo.
echo For detailed instructions, see: ADMIN_CONFIGURATION_GUIDE.md
echo.
pause
