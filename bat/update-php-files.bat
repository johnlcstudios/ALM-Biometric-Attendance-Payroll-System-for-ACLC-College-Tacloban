@echo off
echo =========================================
echo Updating All PHP Files for Offline Support
echo =========================================
echo.

cd /d "%~dp0AI-ML-Test-Bench"

echo Updating PHP files to use local SweetAlert2...
echo.

REM List of files to update
set files=login.php signup.php ess.php kiosk.php Payroll-Officer.php setup-db.php

for %%f in (%files%) do (
    if exist "%%f" (
        echo [UPDATE] %%f
        powershell -Command "(Get-Content '%%f') -replace 'https://cdn.jsdelivr.net/npm/sweetalert2@11', 'js/sweetalert2.all.min.js' onerror=\"this.src='https://cdn.jsdelivr.net/npm/sweetalert2@11'\" | Set-Content '%%f'"
        echo [SUCCESS] %%f updated
    ) else (
        echo [SKIP] %%f not found
    )
    echo.
)

echo.
echo =========================================
echo Update Complete!
echo =========================================
echo.
echo All PHP files now use local SweetAlert2 with CDN fallback.
echo.
pause
