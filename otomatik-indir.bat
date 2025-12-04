@echo off
chcp 65001 >nul
title E-Arşiv - Otomatik İndirme
color 0E

echo.
echo ═══════════════════════════════════════════════════════════════
echo   E-ARŞIV FATURA SİSTEMİ - OTOMATİK İNDİRME
echo ═══════════════════════════════════════════════════════════════
echo.
echo Bu script PHP ve Node.js'i otomatik indirecek.
echo.
echo UYARI: Bu işlem internet bağlantısı gerektirir!
echo        Toplam indirme: ~80 MB
echo.
pause
echo.

set "APP_DIR=%~dp0"
set "DOWNLOAD_DIR=%APP_DIR%portable\downloads"
set "PHP_DIR=%APP_DIR%portable\php"
set "NODE_DIR=%APP_DIR%portable\node"

REM PowerShell var mı kontrol et
where powershell >nul 2>&1
if %errorlevel% neq 0 (
    echo [✗] PowerShell bulunamadı! Manuel indirme yapmanız gerekiyor.
    echo.
    echo portable-download-links.txt dosyasını açın.
    pause
    exit /b 1
)

if not exist "%DOWNLOAD_DIR%" mkdir "%DOWNLOAD_DIR%"

echo [1/4] PHP indiriliyor...
echo.

set "PHP_URL=https://windows.php.net/downloads/releases/php-8.1.27-Win32-vs16-x64.zip"
set "PHP_ZIP=%DOWNLOAD_DIR%\php.zip"

if not exist "%PHP_ZIP%" (
    powershell -Command "& {[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri '%PHP_URL%' -OutFile '%PHP_ZIP%' -UseBasicParsing}"
    
    if exist "%PHP_ZIP%" (
        echo [✓] PHP indirildi
    ) else (
        echo [✗] PHP indirilemedi!
        pause
        exit /b 1
    )
) else (
    echo [✓] PHP zaten indirilmiş
)

echo.
echo [2/4] PHP çıkarılıyor...
if not exist "%PHP_DIR%" (
    powershell -Command "Expand-Archive -Path '%PHP_ZIP%' -DestinationPath '%PHP_DIR%' -Force"
    echo [✓] PHP çıkarıldı
) else (
    echo [✓] PHP zaten çıkarılmış
)

echo.
echo [3/4] Node.js indiriliyor...
echo.

set "NODE_URL=https://nodejs.org/dist/v18.19.0/node-v18.19.0-win-x64.zip"
set "NODE_ZIP=%DOWNLOAD_DIR%\node.zip"

if not exist "%NODE_ZIP%" (
    powershell -Command "& {[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri '%NODE_URL%' -OutFile '%NODE_ZIP%' -UseBasicParsing}"
    
    if exist "%NODE_ZIP%" (
        echo [✓] Node.js indirildi
    ) else (
        echo [✗] Node.js indirilemedi!
        pause
        exit /b 1
    )
) else (
    echo [✓] Node.js zaten indirilmiş
)

echo.
echo [4/4] Node.js çıkarılıyor...
if not exist "%NODE_DIR%" (
    powershell -Command "Expand-Archive -Path '%NODE_ZIP%' -DestinationPath '%DOWNLOAD_DIR%\node-temp' -Force"
    
    REM Node.js klasör yapısını düzelt
    move "%DOWNLOAD_DIR%\node-temp\node-v18.19.0-win-x64" "%NODE_DIR%" >nul
    rmdir /s /q "%DOWNLOAD_DIR%\node-temp" 2>nul
    
    echo [✓] Node.js çıkarıldı
) else (
    echo [✓] Node.js zaten çıkarılmış
)

echo.
echo ═══════════════════════════════════════════════════════════════
echo   İNDİRME TAMAMLANDI!
echo ═══════════════════════════════════════════════════════════════
echo.
echo Şimdi portable-setup.bat dosyasını çalıştırın.
echo.
pause
