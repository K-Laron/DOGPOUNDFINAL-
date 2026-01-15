@echo off
REM Database Backup Script for Catarman Dog Pound Management System
REM This script creates a timestamped backup of the MySQL database

REM ============================================
REM CONFIGURATION
REM ============================================
set DB_HOST=127.0.0.1
set DB_PORT=3307
set DB_NAME=catarman_dog_pound_db
set DB_USER=root
set DB_PASS=

REM Backup directory (relative to project root)
set BACKUP_DIR=%~dp0..\backups

REM MySQL path (adjust if using XAMPP in different location)
set MYSQL_PATH=C:\xampp\mysql\bin

REM ============================================
REM CREATE BACKUP
REM ============================================

echo ============================================
echo   Catarman Dog Pound - Database Backup
echo ============================================
echo.

REM Create backup directory if it doesn't exist
if not exist "%BACKUP_DIR%" (
    mkdir "%BACKUP_DIR%"
    echo Created backup directory: %BACKUP_DIR%
)

REM Generate timestamp for filename
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /format:list') do set datetime=%%I
set TIMESTAMP=%datetime:~0,4%-%datetime:~4,2%-%datetime:~6,2%_%datetime:~8,2%%datetime:~10,2%%datetime:~12,2%
set BACKUP_FILE=%BACKUP_DIR%\%DB_NAME%_%TIMESTAMP%.sql

echo Backing up database: %DB_NAME%
echo Backup file: %BACKUP_FILE%
echo.

REM Run mysqldump
"%MYSQL_PATH%\mysqldump.exe" -h %DB_HOST% -P %DB_PORT% -u %DB_USER% %DB_NAME% > "%BACKUP_FILE%" 2>nul

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================
    echo   Backup completed successfully!
    echo ============================================
    echo.
    echo File: %BACKUP_FILE%
    
    REM Show file size
    for %%A in ("%BACKUP_FILE%") do echo Size: %%~zA bytes
    echo.
) else (
    echo.
    echo ============================================
    echo   ERROR: Backup failed!
    echo ============================================
    echo.
    echo Please check:
    echo   1. MySQL is running
    echo   2. Database credentials are correct
    echo   3. MYSQL_PATH is set correctly
    echo.
)

REM ============================================
REM CLEANUP OLD BACKUPS (keep last 10)
REM ============================================

echo Cleaning up old backups (keeping last 10)...
set count=0
for /f "skip=10 delims=" %%F in ('dir /b /o-d "%BACKUP_DIR%\*.sql" 2^>nul') do (
    del "%BACKUP_DIR%\%%F"
    set /a count+=1
)

if %count% GTR 0 (
    echo Deleted %count% old backup(s)
)

echo.
echo Done!
pause
