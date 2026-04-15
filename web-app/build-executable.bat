@echo off
echo =========================================
echo ALM Biometrics System - Build Script
echo Version 2.4.0 Build9
echo =========================================
echo.

set CSC="C:\Windows\Microsoft.NET\Framework64\v4.0.30319\csc.exe"

if not exist %CSC% (
    echo [ERROR] .NET Framework compiler not found!
    echo Please ensure .NET Framework 4.5+ is installed.
    echo Download from: https://dotnet.microsoft.com/download/dotnet-framework
    pause
    exit /b 1
)

echo [INFO] Compiler found at: %CSC%
echo.

REM Check for icon file
echo [1/4] Checking application icon...
if not exist "ALM-Icon.ico" (
    echo [WARN] Icon file not found. Attempting to generate...
    if exist "generate-icon.ps1" (
        powershell -ExecutionPolicy Bypass -File generate-icon.ps1
        if exist "ALM-Icon.ico" (
            echo [SUCCESS] Icon generated successfully.
        ) else (
            echo [WARN] Icon generation failed. Continuing without custom icon.
        )
    ) else (
        echo [WARN] generate-icon.ps1 not found. Using default icon.
    )
) else (
    echo [OK] Icon file found.
)
echo.

REM Build Installer
echo [2/4] Building ALM-Installer.exe (v2.4.0)...
if exist "ALM-Icon.ico" (
    %CSC% -out:"ALM-Installer.exe" -target:winexe -win32icon:ALM-Icon.ico -optimize+ Installer.cs
) else (
    %CSC% -out:"ALM-Installer.exe" -target:winexe -optimize+ Installer.cs
)

if %errorlevel% neq 0 (
    echo [ERROR] Failed to compile Installer.cs
    echo [INFO] Check Installer.cs for syntax errors.
    pause
    exit /b 1
) else (
    echo [SUCCESS] ALM-Installer.exe compiled successfully.
    if exist "ALM-Installer.exe" (
        for %%A in ("ALM-Installer.exe") do echo [INFO] File size: %%~zA bytes
    )
)
echo.

REM Build Launcher
echo [3/4] Building ALM-Launcher.exe (v2.4.0)...
if exist "ALM-Icon.ico" (
    %CSC% -out:"ALM-Launcher.exe" -target:winexe -win32icon:ALM-Icon.ico -optimize+ Launcher.cs
) else (
    %CSC% -out:"ALM-Launcher.exe" -target:winexe -optimize+ Launcher.cs
)

if %errorlevel% neq 0 (
    echo [ERROR] Failed to compile Launcher.cs
    echo [INFO] Check Launcher.cs for syntax errors.
    pause
    exit /b 1
) else (
    echo [SUCCESS] ALM-Launcher.exe compiled successfully.
    if exist "ALM-Launcher.exe" (
        for %%A in ("ALM-Launcher.exe") do echo [INFO] File size: %%~zA bytes
    )
)
echo.

REM Verify output
echo [4/4] Verifying build output...
set BUILD_OK=1
if not exist "ALM-Installer.exe" (
    echo [ERROR] ALM-Installer.exe not found!
    set BUILD_OK=0
)
if not exist "ALM-Launcher.exe" (
    echo [ERROR] ALM-Launcher.exe not found!
    set BUILD_OK=0
)

if %BUILD_OK%==1 (
    echo [OK] All executables built successfully.
) else (
    echo [ERROR] Build verification failed!
    pause
    exit /b 1
)

echo.
echo =========================================
echo BUILD SUMMARY
echo =========================================
echo Version: 2.4.0 Build9
echo Files:   ALM-Installer.exe
echo          ALM-Launcher.exe
echo.
echo Distribution Package Ready!
echo =========================================
echo.
pause
