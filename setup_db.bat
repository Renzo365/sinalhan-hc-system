@echo off
setlocal enabledelayedexpansion

echo ============================================================
echo   Barangay Sinalhan Health Center - Database Setup
echo ============================================================
echo.

REM 1. Look for mysql.exe in common XAMPP installation paths
set "MYSQL_PATH="

if exist "c:\xampp\mysql\bin\mysql.exe" (
    set "MYSQL_PATH=c:\xampp\mysql\bin\mysql.exe"
) else if exist "d:\xampp\mysql\bin\mysql.exe" (
    set "MYSQL_PATH=d:\xampp\mysql\bin\mysql.exe"
) else if exist "e:\xampp\mysql\bin\mysql.exe" (
    set "MYSQL_PATH=e:\xampp\mysql\bin\mysql.exe"
) else (
    where mysql.exe >nul 2>nul
    if !errorlevel! equ 0 (
        set "MYSQL_PATH=mysql.exe"
    )
)

if "%MYSQL_PATH%"=="" (
    echo [ERROR] MySQL client could not be found automatically!
    echo Please make sure XAMPP is installed and MySQL is running.
    echo Default expected path: C:\xampp\mysql\bin\mysql.exe
    echo.
    pause
    exit /b 1
)

echo [1/3] Using MySQL client at: %MYSQL_PATH%
echo [2/3] Checking MySQL connection...
"%MYSQL_PATH%" -u root -e "SELECT 1;" >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Cannot connect to MySQL server.
    echo Please ensure the MySQL module is started in your XAMPP Control Panel!
    echo.
    pause
    exit /b 1
)

echo [3/3] Importing database\complete_setup.sql...
"%MYSQL_PATH%" -u root < "%~dp0database\complete_setup.sql"

if %errorlevel% equ 0 (
    echo.
    echo ============================================================
    echo   Database setup completed successfully!
    echo ============================================================
    echo   Database: sinalhan_hc_system
    echo   Default Accounts:
    echo     - Admin:         admin         / admin1234
    echo     - BHW Staff:     records_staff / staff1234
    echo     - Midwife Staff: midwife_user  / staff1234
    echo ============================================================
) else (
    echo.
    echo [ERROR] Failed to import database\complete_setup.sql.
    echo Please check the error messages above.
)

echo.
pause
