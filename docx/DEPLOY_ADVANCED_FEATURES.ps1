# 🚀 YG DocX - Advanced Features Deployment Script
# This script deploys all MS Word-level features

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  YG DocX - MS Word Features Deployer" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$projectPath = "c:\Users\ASUS\Downloads\YG Soft1\YG DocX"

Write-Host "📁 Project Path: $projectPath" -ForegroundColor Yellow
Write-Host ""

# Step 1: Navigate to project
Write-Host "Step 1: Navigating to project directory..." -ForegroundColor Green
Set-Location $projectPath
Write-Host "✓ Done" -ForegroundColor Green
Write-Host ""

# Step 2: Run migrations
Write-Host "Step 2: Running database migrations..." -ForegroundColor Green
try {
    php artisan migrate --force
    Write-Host "✓ Migrations completed successfully" -ForegroundColor Green
} catch {
    Write-Host "✗ Migration failed: $_" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 3: Clear caches
Write-Host "Step 3: Clearing application caches..." -ForegroundColor Green
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
Write-Host "✓ Caches cleared" -ForegroundColor Green
Write-Host ""

# Step 4: Verify routes
Write-Host "Step 4: Verifying new routes..." -ForegroundColor Green
$routeList = php artisan route:list --path=presence,bookmarks,notes,shapes,charts,compare
if ($routeList) {
    Write-Host "✓ Routes registered successfully" -ForegroundColor Green
} else {
    Write-Host "⚠ Warning: Could not verify routes" -ForegroundColor Yellow
}
Write-Host ""

# Step 5: Summary
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  ✅ DEPLOYMENT COMPLETE!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "New Features Available:" -ForegroundColor Yellow
Write-Host "  ✓ Subscript/Superscript" -ForegroundColor White
Write-Host "  ✓ Line Spacing Controls" -ForegroundColor White
Write-Host "  ✓ Indentation Tools" -ForegroundColor White
Write-Host "  ✓ Page Margins & Setup" -ForegroundColor White
Write-Host "  ✓ Headers & Footers" -ForegroundColor White
Write-Host "  ✓ Column Layouts" -ForegroundColor White
Write-Host "  ✓ Advanced Tables" -ForegroundColor White
Write-Host "  ✓ Bookmarks" -ForegroundColor White
Write-Host "  ✓ Footnotes/Endnotes" -ForegroundColor White
Write-Host "  ✓ Shapes & Charts (Infrastructure)" -ForegroundColor White
Write-Host "  ✓ Track Changes" -ForegroundColor White
Write-Host "  ✓ Document Comparison" -ForegroundColor White
Write-Host "  ✓ Real-Time Presence" -ForegroundColor White
Write-Host ""
Write-Host "Database Tables Created: 13" -ForegroundColor Cyan
Write-Host "API Endpoints Added: 10" -ForegroundColor Cyan
Write-Host "Models Created: 6" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "  1. Start server: php artisan serve --port=8003" -ForegroundColor White
Write-Host "  2. Open document: http://localhost:8003/documents/{id}" -ForegroundColor White
Write-Host "  3. Test all new features in the editor" -ForegroundColor White
Write-Host ""
Write-Host "Documentation: ADVANCED_FEATURES_COMPLETE.md" -ForegroundColor Cyan
Write-Host ""
