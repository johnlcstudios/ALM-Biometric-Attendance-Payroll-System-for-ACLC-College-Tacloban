@echo off
echo =========================================
echo Compiling ALM Biometrics Standalone Apps
echo =========================================
echo.

set CSC="C:\Windows\Microsoft.NET\Framework64\v4.0.30319\csc.exe"

if not exist %CSC% (
    echo Error: .NET Framework compiler not found!
    pause
    exit /b
)

REM Generate icon if it doesn't exist
if not exist "ALM-Icon.ico" (
    echo Generating application icon...
    powershell -ExecutionPolicy Bypass -File generate-icon.ps1
    echo.
)

echo Building ALM-Installer.exe...
%CSC% -out:"ALM-Installer.exe" -target:winexe -win32icon:ALM-Icon.ico Installer.cs
if %errorlevel% neq 0 (
    echo [ERROR] Failed to compile Installer.cs
) else (
    echo [SUCCESS] ALM-Installer.exe generated.
)

echo.
echo Building ALM-Launcher.exe...
%CSC% -out:"ALM-Launcher.exe" -target:winexe -win32icon:ALM-Icon.ico Launcher.cs
if %errorlevel% neq 0 (
    echo [ERROR] Failed to compile Launcher.cs
) else (
    echo [SUCCESS] ALM-Launcher.exe generated.
)

echo.
echo =========================================
echo Compilation process finished.
echo =========================================
pause
