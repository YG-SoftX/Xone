# YG Ecosystem - Reliable Service Starter
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Starting YG Ecosystem Services" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$basePath = "c:\Users\ASUS\Downloads\YG Soft1"

# Define critical services
$services = @(
    @{Name="yg-account"; Port=8000; Path="yg-account"},
    @{Name="yg-home"; Port=8001; Path="yg-home"},
    @{Name="YG Mail"; Port=8002; Path="YG Mail"},
    @{Name="YG DocX"; Port=8003; Path="YG DocX"},
    @{Name="YG Calendar"; Port=8005; Path="YG Calendar"}
)

foreach ($service in $services) {
    $name = $service.Name
    $port = $service.Port
    $path = Join-Path $basePath $service.Path
    
    Write-Host "`nStarting $name on port $port..." -ForegroundColor Yellow
    
    # Check if already running
    $isRunning = netstat -ano | findstr ":${port}" | findstr "LISTENING"
    if ($isRunning) {
        Write-Host "  ✓ $name already running on port $port" -ForegroundColor Green
        continue
    }
    
    # Create bootstrap/cache if needed
    $cacheDir = Join-Path $path "bootstrap\cache"
    if (-not (Test-Path $cacheDir)) {
        New-Item -ItemType Directory -Path $cacheDir -Force | Out-Null
    }
    
    # Start service
    try {
        Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$path'; php artisan serve --port=$port --host=127.0.0.1" -WindowStyle Minimized
        
        # Wait for it to start
        Start-Sleep -Seconds 3
        
        # Verify it's running
        $checkPort = netstat -ano | findstr ":${port}" | findstr "LISTENING"
        if ($checkPort) {
            Write-Host "  ✓ $name started successfully on http://127.0.0.1:$port" -ForegroundColor Green
        } else {
            Write-Host "  ✗ $name failed to start" -ForegroundColor Red
        }
    } catch {
        Write-Host "  ✗ Error starting $name`: $_" -ForegroundColor Red
    }
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  Service Status Summary" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

foreach ($service in $services) {
    $name = $service.Name
    $port = $service.Port
    $isRunning = netstat -ano | findstr ":${port}" | findstr "LISTENING"
    
    if ($isRunning) {
        Write-Host "✓ $name (Port $port)" -ForegroundColor Green
    } else {
        Write-Host "✗ $name (Port $port) - NOT RUNNING" -ForegroundColor Red
    }
}

Write-Host "`nCentral System: YG Account - http://127.0.0.1:8000" -ForegroundColor Cyan
Write-Host "Document Editor: YG DocX - http://127.0.0.1:8003" -ForegroundColor Cyan
Write-Host ""
