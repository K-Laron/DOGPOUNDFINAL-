@echo off
echo 🛑 Stopping Catarman Dog Pound Servers...
taskkill /F /IM php.exe
echo.
echo ✅ Servers stopped.
echo.
echo This window will close in 10 seconds...
timeout /t 10 >nul
