@echo off
TITLE Catarman Dog Pound System
echo 🔍 Searching for PHP...

set PHP_BIN=c:\xampp\php\php.exe
if not exist "%PHP_BIN%" (
    set PHP_BIN=php
)

echo ✅ Using PHP: %PHP_BIN%
echo.
echo 🚀 Starting Backend Server (Port 8000)...
powershell -Command "Start-Process '%PHP_BIN%' -ArgumentList '-S 0.0.0.0:8000 -t backend/public backend/public/index.php' -WindowStyle Hidden"

echo 🚀 Starting Frontend Server (Port 3000)...
powershell -Command "Start-Process '%PHP_BIN%' -ArgumentList '-S 0.0.0.0:3000 -t frontend' -WindowStyle Hidden"

echo.
echo ✨ Application Launched in Background!
echo.
echo Opening Browser...
timeout /t 2 >nul
start http://localhost:3000

echo.
echo ⚠️  Servers are running in the background (Hidden).
echo ⚠️  Run 'stop.bat' to stop the servers.
echo.
pause
