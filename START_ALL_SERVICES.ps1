# YG Ecosystem - Start All Services
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Starting YG Ecosystem Services" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$basePath = "c:\Users\ASUS\Downloads\YG Soft1"

# Define services with their ports
$services = @{
    "yg-account" = 8000
    "yg-home" = 8001
    "YG Mail" = 8002
    "YG DocX" = 8003
    "YG Drive" = 8004
    "YG Calendar" = 8005
    "YG Contacts" = 8006
    "YG Notes" = 8007
    "YG Chat" = 8008
    "yg-developer" = 8009
    "yg-ai" = 8010
}

foreach ($service in $services.Keys) {
    $port = $services[$service]
    $servicePath = Join-Path $basePath $service
    
    Write-Host "Starting $service on port $port..." -ForegroundColor Yellow
    
    if (Test-Path $servicePath) {
        # Create bootstrap/cache directory if missing
        $cacheDir = Join-Path $servicePath "bootstrap\cache"
        if (-not (Test-Path $cacheDir)) {
            New-Item -ItemType Directory -Path $cacheDir -Force | Out-Null
            Write-Host "  Created bootstrap/cache directory" -ForegroundColor Gray
        }
        
        # Check if .env exists
        $envFile = Join-Path $servicePath ".env"
        if (-not (Test-Path $envFile)) {
            Write-Host "  ⚠ Warning: .env file not found" -ForegroundColor Red
            continue
        }
        
        # Start service in background
        Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$servicePath'; php artisan serve --port=$port" -WindowStyle Minimized
        
        Write-Host "  ✓ $service started on http://127.0.0.1:$port" -ForegroundColor Green
        Start-Sleep -Seconds 1
    } else {
        Write-Host "  ✗ $service not found at $servicePath" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  All Services Started!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Service URLs:" -ForegroundColor Yellow
foreach ($service in $services.Keys) {
    $port = $services[$service]
    Write-Host "  $service`: http://127.0.0.1:$port" -ForegroundColor White
}
Write-Host ""
Write-Host "Central System: YG Account (http://127.0.0.1:8000)" -ForegroundColor Cyan
Write-Host ""
