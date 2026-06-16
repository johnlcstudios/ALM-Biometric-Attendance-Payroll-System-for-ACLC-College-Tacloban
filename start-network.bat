@echo off
title ALM System - Network Access
color 0A

set BASE_PATH=C:\xampp
set PROJECT_PATH=/deployment/ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban

echo ============================================
echo   ALM Biometric Attendance Payroll System
echo   Starting server for network access...
echo ============================================
echo.

:: Get local IP address via PowerShell (uses the interface with a default gateway)
echo [1/4] Detecting network IP...
for /f "usebackq delims=" %%a in (`powershell -NoProfile -Command "try { (Get-NetRoute -DestinationPrefix '0.0.0.0/0' | Get-NetIPAddress -AddressFamily IPv4).IPAddress } catch { $null }"`) do set "ip=%%a"

if "%ip%"=="" (
    for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /i "IPv4"') do set "ip=%%a"
)
set "ip=%ip: =%"

:: Generate SSL cert for this IP (so browsers trust the HTTPS connection for camera)
echo [2/4] Generating SSL certificate for %ip%...
"%BASE_PATH%\apache\bin\openssl.exe" req -x509 -newkey rsa:2048 -keyout "%BASE_PATH%\apache\conf\ssl.key\server.key" -out "%BASE_PATH%\apache\conf\ssl.crt\server.crt" -days 365 -nodes -subj "/CN=%ip%" -addext "subjectAltName=IP:%ip%,DNS:localhost" 2>nul
if %errorlevel% equ 0 (
    echo   SSL certificate created for %ip%
) else (
    echo   Warning: Could not generate SSL certificate (using existing)
)

:: Start Apache (run minimized)
echo [3/4] Starting Apache...
start /min "Apache" "%BASE_PATH%\apache_start.bat"

:: Start MySQL (run minimized)
echo [4/4] Starting MySQL...
start /min "MySQL" "%BASE_PATH%\mysql_start.bat"

:: Wait for services to initialize
timeout /t 5 /nobreak >nul

set URL_PATH=%PROJECT_PATH:/=\%
set HTTPS_URL=https://%ip%%PROJECT_PATH%
set HTTP_URL=http://%ip%%PROJECT_PATH%
set LOCAL_URL=https://localhost%PROJECT_PATH%

echo.
echo ============================================
echo   System is now accessible at:
echo.
echo   Local:     %LOCAL_URL%
echo   Network:   %HTTPS_URL%
echo.
echo   IMPORTANT: Camera access requires HTTPS.
echo   When you open the page, click "Advanced"
echo   then "Proceed" to accept the self-signed
echo   certificate - this is safe on your network.
echo ============================================
echo.
echo   Press any key to copy Network URL to clipboard
echo   and open browser...
pause >nul

:: Copy HTTPS network URL to clipboard
echo %HTTPS_URL% | clip

:: Open in default browser using HTTPS
start %LOCAL_URL%

echo.
echo   Network URL copied to clipboard!
echo   Server is running in background.
echo.
pause
