@echo off
TITLE Catarman Dog Pound System
echo 🔍 Searching for PHP and XAMPP Services...

set PHP_BIN=c:\xampp\php\php.exe
if not exist "%PHP_BIN%" (
    set PHP_BIN=php
)

set MYSQL_BIN=c:\xampp\mysql\bin\mysqld.exe
set APACHE_BIN=c:\xampp\apache\bin\httpd.exe

echo ✅ Using PHP: %PHP_BIN%

echo.
echo 🚀 Starting Database (MySQL)...
if exist "%MYSQL_BIN%" (
    powershell -Command "Start-Process '%MYSQL_BIN%' -WindowStyle Hidden"
    echo    - MySQL Started.
) else (
    echo    ⚠️ MySQL not found at %MYSQL_BIN%. Please start manually.
)

echo 🚀 Starting Web Server (Apache)...
if exist "%APACHE_BIN%" (
    powershell -Command "Start-Process '%APACHE_BIN%' -WindowStyle Hidden"
    echo    - Apache Started.
) else (
    echo    ⚠️ Apache not found at %APACHE_BIN%. Please start manually.
)

echo.
echo 🚀 Starting Backend Server (Port 8000)...
powershell -Command "Start-Process '%PHP_BIN%' -ArgumentList '-S 0.0.0.0:8000 -t backend/public backend/public/index.php' -WindowStyle Hidden"

echo 🚀 Starting Frontend Server (Port 3000)...
powershell -Command "Start-Process '%PHP_BIN%' -ArgumentList '-S 0.0.0.0:3000 -t frontend' -WindowStyle Hidden"

echo.
echo ✨ Application & Database Launched!
echo.
echo Opening System and Database Admin...
timeout /t 3 >nul
start http://localhost:3000


echo.
echo ⚠️  Servers are running in the background.
echo ⚠️  Run 'stop.bat' to stop the servers.
echo.
echo This window will close in 10 seconds...
timeout /t 10 >nul
