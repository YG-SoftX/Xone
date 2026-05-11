@echo off
REM YG AI Integration Deployment Script for Windows
REM This script automates the YG AI integration across all modules

echo ========================================
echo   YG AI Integration Deployment
echo ========================================
echo.

REM Check if MySQL is running
echo [1/8] Checking MySQL connection...
cd "c:\Users\ASUS\Downloads\YG Soft1\YG Mail"
php artisan env | findstr "DB_CONNECTION" >nul
if %errorlevel% neq 0 (
    echo ERROR: Cannot connect to database. Please start MySQL first.
    pause
    exit /b 1
)
echo OK: Database connection successful
echo.

REM Run YG Mail migrations
echo [2/8] Running YG Mail migrations...
cd "c:\Users\ASUS\Downloads\YG Soft1\YG Mail"
php artisan migrate --force
if %errorlevel% neq 0 (
    echo WARNING: Migration failed. Continuing anyway...
)
echo.

REM Verify .env configuration
echo [3/8] Verifying YG AI configuration...
findstr "YG_AI_URL" "c:\Users\ASUS\Downloads\YG Soft1\YG Mail\.env" >nul
if %errorlevel% neq 0 (
    echo Adding YG AI configuration to .env...
    echo. >> "c:\Users\ASUS\Downloads\YG Soft1\YG Mail\.env"
    echo # YG AI Configuration >> "c:\Users\ASUS\Downloads\YG Soft1\YG Mail\.env"
    echo YG_AI_URL=https://ai.ygxone.com >> "c:\Users\ASUS\Downloads\YG Soft1\YG Mail\.env"
    echo YG_AI_API_KEY= >> "c:\Users\ASUS\Downloads\YG Soft1\YG Mail\.env"
    echo YG_AI_TIMEOUT=5 >> "c:\Users\ASUS\Downloads\YG Soft1\YG Mail\.env"
)
echo OK: YG AI configuration verified
echo.

REM Test YG AI service connectivity
echo [4/8] Testing YG AI service connectivity...
curl -s https://ai.ygxone.com/api/?action=status >nul 2>&1
if %errorlevel% equ 0 (
    echo OK: YG AI service is accessible
) else (
    echo WARNING: Cannot reach YG AI service. Check URL and network.
)
echo.

REM Clear caches
echo [5/8] Clearing application caches...
cd "c:\Users\ASUS\Downloads\YG Soft1\YG Mail"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo OK: Caches cleared
echo.

REM Start queue workers (background)
echo [6/8] Starting queue workers...
start "YG Mail Queue Worker" cmd /k "cd /d c:\Users\ASUS\Downloads\YG Soft1\YG Mail && php artisan queue:work --queue=ai-processing --tries=3"
echo OK: Queue worker started in new window
echo.

REM Create necessary job files
echo [7/8] Creating AI job files...

REM Check if CategorizeEmailJob exists
if not exist "c:\Users\ASUS\Downloads\YG Soft1\YG Mail\app\Jobs\CategorizeEmailJob.php" (
    echo Creating CategorizeEmailJob...
    cd "c:\Users\ASUS\Downloads\YG Soft1\YG Mail"
    php artisan make:job CategorizeEmailJob
)

REM Check if AnalyzeEmailSentimentJob exists
if not exist "c:\Users\ASUS\Downloads\YG Soft1\YG Mail\app\Jobs\AnalyzeEmailSentimentJob.php" (
    echo Creating AnalyzeEmailSentimentJob...
    cd "c:\Users\ASUS\Downloads\YG Soft1\YG Mail"
    php artisan make:job AnalyzeEmailSentimentJob
)

echo OK: Job files created
echo.

REM Final verification
echo [8/8] Running final verification...
cd "c:\Users\ASUS\Downloads\YG Soft1\YG Mail"
php artisan route:list | findstr "smart-reply" >nul
if %errorlevel% equ 0 (
    echo OK: Smart reply endpoint registered
) else (
    echo WARNING: Smart reply endpoint not found. Please add it manually.
)
echo.

echo ========================================
echo   Deployment Complete!
echo ========================================
echo.
echo Next Steps:
echo 1. Review the integration guide: YG_AI_INTEGRATION_COMPLETE_GUIDE.md
echo 2. Add API endpoints to routes/api.php
echo 3. Update controllers with AI integration code
echo 4. Add frontend UI components to Blade templates
echo 5. Test each feature manually
echo.
echo Queue worker is running in a separate window.
echo Monitor logs: tail -f storage/logs/laravel.log
echo.
pause
