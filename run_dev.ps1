$ErrorActionPreference = "Stop"

function Find-Executable {
    param($Name, $CommonPaths)
    if (Get-Command $Name -ErrorAction SilentlyContinue) { return $Name }
    foreach ($path in $CommonPaths) { if (Test-Path $path) { return $path } }
    return $null
}

Write-Host "🔍 Searching for environment tools..." -ForegroundColor Cyan

# Find PHP
$php = Find-Executable "php" @("C:\xampp\php\php.exe", "C:\php\php.exe", "D:\xampp\php\php.exe")
if (-not $php) {
    Write-Error "PHP not found! Please install XAMPP or PHP."
    exit 1
}
Write-Host "✅ Found PHP: $php" -ForegroundColor Green

# Find MySQL
$mysql = Find-Executable "mysql" @("C:\xampp\mysql\bin\mysql.exe", "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe", "D:\xampp\mysql\bin\mysql.exe")

if ($mysql) {
    Write-Host "✅ Found MySQL: $mysql" -ForegroundColor Green
    
    Write-Host "`n📦 Setting up Database..." -ForegroundColor Cyan
    $dbConfig = @{
        User = 'root'
        Host = '127.0.0.1'
        Port = '3307'
        Name = 'catarman_dog_pound_db'
    }

    try {
        # Create Database
        Write-Host "Creating database if not exists..."
        & $mysql -h $dbConfig.Host -P $dbConfig.Port -u $dbConfig.User -e "CREATE DATABASE IF NOT EXISTS $($dbConfig.Name);"
        
        # Import Schema
        Write-Host "Importing schema..."
        Get-Content "database/schema.sql" | & $mysql -h $dbConfig.Host -P $dbConfig.Port -u $dbConfig.User $dbConfig.Name --force
        
        # Import Seeders
        Write-Host "Importing seeders..."
        Get-Content "database/seeders.sql" | & $mysql -h $dbConfig.Host -P $dbConfig.Port -u $dbConfig.User $dbConfig.Name --force
        
        Write-Host "✅ Database setup complete!" -ForegroundColor Green
    }
    catch {
        Write-Warning "Database setup checking failed (non-critical). Continuing..."
        Write-Warning $_
    }
}
else {
    Write-Warning "MySQL not found. Skipping DB setup."
}

Write-Host "`n🚀 Starting Servers..." -ForegroundColor Cyan

try {
    # Start Backend
    Write-Host "Starting Backend on http://localhost:8000..."
    $backend = Start-Process -FilePath $php -ArgumentList "-S localhost:8000 -t backend/public backend/public/index.php" -PassThru
    Write-Host "Backend PID: $($backend.Id)" -ForegroundColor DarkGray

    # Start Frontend
    Write-Host "Starting Frontend on http://localhost:3000..."
    $frontend = Start-Process -FilePath $php -ArgumentList "-S localhost:3000 -t frontend" -PassThru
    Write-Host "Frontend PID: $($frontend.Id)" -ForegroundColor DarkGray

    # Open Browser
    Start-Sleep -Seconds 2
    Write-Host "Opening Browser..."
    Start-Process "http://localhost:3000"

    Write-Host "`n✨ System is Running!" -ForegroundColor Green
    Write-Host "Backend: http://localhost:8000"
    Write-Host "Frontend: http://localhost:3000"
    Write-Host "Press any key to stop the servers..."

    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

    Stop-Process -Id $backend.Id -ErrorAction SilentlyContinue
    Stop-Process -Id $frontend.Id -ErrorAction SilentlyContinue
    Write-Host "Servers stopped."
}
catch {
    Write-Error "An error occurred starting the servers: $_"
    Read-Host "Press Enter to exit..."
    exit 1
}
