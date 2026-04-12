@echo off
echo =========================================
echo Compiling ALM Biometrics Standalone Apps
echo =========================================

set CSC="C:\Windows\Microsoft.NET\Framework64\v4.0.30319\csc.exe"

if not exist %CSC% (
    echo Error: .NET Framework compiler not found!
    pause
    exit /b
)

echo Building ALM-Installer.exe...
%CSC% -out:"ALM-Installer.exe" -target:winexe Installer.cs
if %errorlevel% neq 0 (
    echo [ERROR] Failed to compile Installer.cs
) else (
    echo [SUCCESS] ALM-Installer.exe generated.
)

echo Building ALM-Launcher.exe...
%CSC% -out:"ALM-Launcher.exe" -target:winexe Launcher.cs
if %errorlevel% neq 0 (
    echo [ERROR] Failed to compile Launcher.cs
) else (
    echo [SUCCESS] ALM-Launcher.exe generated.
)

echo.
echo compilation process finished.
