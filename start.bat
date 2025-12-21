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
start "Backend Server" /MIN "%PHP_BIN%" -S 0.0.0.0:8000 -t backend/public backend/public/index.php

echo 🚀 Starting Frontend Server (Port 3000)...
start "Frontend Server" /MIN "%PHP_BIN%" -S 0.0.0.0:3000 -t frontend

echo.
echo ✨ Application Launched!
echo.
echo Opening Browser...
timeout /t 2 >nul
start http://localhost:3000

echo.
echo ⚠️  DO NOT CLOSE THIS WINDOW
echo ⚠️  Close the other two black windows to stop the servers.
echo.
pause
