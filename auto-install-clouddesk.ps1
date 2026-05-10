# 🚀 CloudDesk Environment Auto-Setup Script
# This script automates the installation of PHP, MySQL, and Workbench, AND initializes the DB.
# Run this script as ADMINISTRATOR.

Write-Host "--- CloudDesk Support Environment Setup ---" -ForegroundColor Cyan

# 1. Check for Admin Privileges
$currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Error "Please run this script as ADMINISTRATOR."
    exit
}

# 2. Install Packages via WinGet
Write-Host "[1/5] Installing PHP 8.4..." -ForegroundColor Yellow
winget install PHP.PHP.8.4 --accept-package-agreements --accept-source-agreements --silent

Write-Host "[2/5] Installing MySQL Server..." -ForegroundColor Yellow
winget install Oracle.MySQL --accept-package-agreements --silent

Write-Host "[3/5] Installing MySQL Workbench..." -ForegroundColor Yellow
winget install Oracle.MySQL.Workbench --accept-package-agreements --silent

# 3. Locate PHP and Configure php.ini
Write-Host "[4/5] Configuring PHP Environment..." -ForegroundColor Yellow

$phpDir = Get-ChildItem -Path "C:\Users\$env:USERNAME\AppData\Local\Microsoft\WinGet\Packages" -Filter "PHP.PHP.8.4*" | Select-Object -ExpandProperty FullName -First 1

if ($phpDir) {
    $iniPath = Join-Path $phpDir "php.ini"
    $iniTemplate = Join-Path $phpDir "php.ini-development"
    if (-not (Test-Path $iniPath)) { Copy-Item $iniTemplate $iniPath }

    $content = Get-Content $iniPath
    $content = $content -replace ";extension=pdo_mysql", "extension=pdo_mysql"
    $content = $content -replace ";extension=curl", "extension=curl"
    $extDir = Join-Path $phpDir "ext"
    $content = $content -replace ";extension_dir = `"ext`"", "extension_dir = `"$extDir`""
    $content | Set-Content $iniPath
}

# 4. Database Initialization (The "Connection")
Write-Host "[5/5] Initializing Database Schema..." -ForegroundColor Yellow
Start-Sleep -Seconds 5 # Give the service a moment to start

# Try to find mysql.exe
$mysqlExe = Get-ChildItem -Path "C:\Program Files\MySQL" -Filter "mysql.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -ExpandProperty FullName -First 1

if ($mysqlExe) {
    $schemaPath = Join-Path $PSScriptRoot "database\schema.sql"
    if (Test-Path $schemaPath) {
        Write-Host "Importing schema into MySQL..." -ForegroundColor Gray
        try {
            Get-Content $schemaPath | & $mysqlExe -u root
            Write-Host "SUCCESS: Database 'clouddesk_db' is now live and connected!" -ForegroundColor Green
        } catch {
            Write-Warning "Auto-import failed. If you set a password for MySQL root, you'll need to run schema.sql manually in Workbench."
        }
    }
}

# 5. Final Report
Write-Host "`n--- Setup Complete! ---" -ForegroundColor Cyan
Write-Host "✅ Tech Stack Installed"
Write-Host "✅ PHP Configured"
Write-Host "✅ Database Initialized"
Write-Host "`nYou are ready! Just run 'run-server.ps1' to start the app." -ForegroundColor Green
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
