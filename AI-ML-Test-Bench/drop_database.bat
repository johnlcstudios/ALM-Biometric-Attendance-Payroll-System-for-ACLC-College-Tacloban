@echo off
REM Drop Database Executable - DANGEROUS OPERATION!
REM Run only if you want to PERMANENTLY delete alm_biometrics database
REM All data will be LOST. Backup first!

echo.
echo ========================================
echo   DROP DATABASE - ALM BIOMETRICS SYSTEM
echo ========================================
echo.
echo WARNING: This will DELETE the entire 'alm_biometrics' database!
echo - All employees, attendance, payroll records will be LOST
echo - Cannot be undone!
echo.
set /p confirm="Type 'DROP NOW' to continue: "
if /i NOT "%confirm%"=="DROP NOW" (
    echo.
    echo Operation cancelled.
    pause
    exit /b 1
)

echo.
echo BACKUP RECOMMENDED! Press Ctrl+C to cancel or Enter to continue...
pause

echo Dropping database...
mysql -h localhost -u root -e "DROP DATABASE IF EXISTS alm_biometrics;"

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo     Database 'alm_biometrics' DROPPED!
    echo ========================================
    echo.
    echo To recreate:
    echo 1. Run setup-db.php in browser (http://localhost/.../setup-db.php)
    echo 2. Or php AI-ML-Test-Bench\setup-db.php
    echo.
) else (
    echo.
    echo ERROR: Failed to drop database!
    echo Check if MySQL is running (XAMPP).
)

pause

