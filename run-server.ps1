

Write-Host "--- CloudDesk Support System Launcher ---" -ForegroundColor Cyan


$phpExe = $null
$wingetPath = "C:\Users\$env:USERNAME\AppData\Local\Microsoft\WinGet\Packages"

if (Test-Path $wingetPath) {
    
    $phpDir = Get-ChildItem -Path $wingetPath -Filter "PHP.PHP*" | Select-Object -ExpandProperty FullName -First 1
    if ($phpDir -and (Test-Path (Join-Path $phpDir "php.exe"))) {
        $phpExe = Join-Path $phpDir "php.exe"
    }
}


if (-not $phpExe -and (Get-Command "php" -ErrorAction SilentlyContinue)) {
    $phpExe = "php"
}


if (-not $phpExe) {
    Write-Host ""
    Write-Host "=========================================" -ForegroundColor Red
    Write-Host " ERROR: PHP is not installed or not found!" -ForegroundColor Red
    Write-Host "=========================================" -ForegroundColor Red
    Write-Host "Please run 'auto-install-clouddesk.ps1' on this laptop first to set up the environment." -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Press Enter to exit..."
    exit
}


$docRoot = if ($PSScriptRoot) { $PSScriptRoot } else { (Get-Location).Path }
Set-Location -Path $docRoot

Write-Host "Checking MySQL Service..." -ForegroundColor Yellow
$mysqlService = Get-Service -Name "MySQL*" -ErrorAction SilentlyContinue | Where-Object { $_.Status -ne 'Running' }
if ($mysqlService) {
    Write-Host "Found MySQL service ($($mysqlService.Name)) but it is stopped. Attempting to start..." -ForegroundColor Yellow
    try {
        Start-Service -Name $mysqlService.Name -ErrorAction Stop
        Write-Host "MySQL service started successfully." -ForegroundColor Green
    } catch {
        Write-Host "Failed to start MySQL service. You may need to run this script as Administrator." -ForegroundColor Red
        Write-Host "Error: $_" -ForegroundColor Gray
    }
} else {
    $mysqlExe = "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqld.exe"
    if (Test-Path $mysqlExe) {
        Write-Host "MySQL service not found, but found mysqld.exe. Attempting to start directly..." -ForegroundColor Yellow
        Start-Process -FilePath $mysqlExe -WindowStyle Hidden
        Start-Sleep -Seconds 5
    } else {
        Write-Host "MySQL service or executable not found." -ForegroundColor Red
    }
}

Write-Host "Checking Database connection..." -ForegroundColor Yellow
$dbCheck = & $phpExe -r "require 'api/config.php'; echo 'OK';" 2>$null

if ($dbCheck -ne "OK") {
    Write-Host "=========================================" -ForegroundColor Red
    Write-Host " WARNING: Database connection failed!" -ForegroundColor Red
    Write-Host "=========================================" -ForegroundColor Red
    Write-Host "Please ensure MySQL is running and configured correctly in api/config.php." -ForegroundColor Yellow
    Write-Host "Error details: $dbCheck" -ForegroundColor Gray
    Write-Host ""
    
    $choice = Read-Host "Do you want to start the server anyway? (Y/N)"
    if ($choice -ne "Y" -and $choice -ne "y") {
        exit
    }
} else {
    Write-Host "Database connection successful!" -ForegroundColor Green
    
    Write-Host "Running Database Updates..." -ForegroundColor Yellow
    $updateOutput = & $phpExe "api/update-db.php"
    Write-Host $updateOutput -ForegroundColor Gray
}

Write-Host "Starting Web Server at http://localhost:8000" -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop the server." -ForegroundColor Gray

& $phpExe -S localhost:8000 -t $docRoot
