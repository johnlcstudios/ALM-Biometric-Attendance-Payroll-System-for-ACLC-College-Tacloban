@echo off
echo =========================================
echo Downloading Local Dependencies
echo =========================================
echo.

cd /d "%~dp0AI-ML-Test-Bench"

echo [1/3] Downloading SweetAlert2...
if not exist "js\sweetalert2.min.js" (
    echo Downloading from CDN...
    powershell -Command "& {Invoke-WebRequest -Uri 'https://cdn.jsdelivr.net/npm/sweetalert2@11' -OutFile 'js/sweetalert2.all.min.js'}"
    if exist "js\sweetalert2.all.min.js" (
        echo [SUCCESS] SweetAlert2 downloaded.
    ) else (
        echo [ERROR] Failed to download SweetAlert2.
    )
) else (
    echo [OK] SweetAlert2 already exists.
)
echo.

echo [2/3] Downloading Font Awesome...
if not exist "css\all.min.css" (
    echo Downloading from CDN...
    powershell -Command "& {Invoke-WebRequest -Uri 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' -OutFile 'css/all.min.css'}"
    if exist "css\all.min.css" (
        echo [SUCCESS] Font Awesome CSS downloaded.
    ) else (
        echo [ERROR] Failed to download Font Awesome CSS.
    )
    
    echo Downloading Font Awesome webfonts...
    if not exist "webfonts" mkdir webfonts
    powershell -Command "& {Invoke-WebRequest -Uri 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2' -OutFile 'webfonts/fa-solid-900.woff2'}"
    powershell -Command "& {Invoke-WebRequest -Uri 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.woff2' -OutFile 'webfonts/fa-regular-400.woff2'}"
    powershell -Command "& {Invoke-WebRequest -Uri 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.woff2' -OutFile 'webfonts/fa-brands-400.woff2'}"
    echo [SUCCESS] Font Awesome webfonts downloaded.
) else (
    echo [OK] Font Awesome already exists.
)
echo.

echo [3/3] Downloading Google Fonts (Inter)...
if not exist "css\inter-fonts.css" (
    echo Note: Google Fonts requires internet to download.
    echo Creating fallback font configuration...
    echo /* Using system fonts as fallback */ > css/inter-fonts.css
    echo [INFO] System fonts will be used as fallback.
) else (
    echo [OK] Font configuration already exists.
)
echo.

echo =========================================
echo Download Complete!
echo =========================================
echo.
echo Local files:
echo - js/sweetalert2.all.min.js
echo - css/all.min.css
echo - webfonts/ (Font Awesome fonts)
echo.
pause
