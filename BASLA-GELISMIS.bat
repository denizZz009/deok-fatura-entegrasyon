@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion
title E-Arşiv Fatura Sistemi
color 0B

REM ═══════════════════════════════════════════════════════════════
REM   E-ARŞIV FATURA SİSTEMİ - GELİŞMİŞ BAŞLATICI
REM ═══════════════════════════════════════════════════════════════

set "APP_DIR=%~dp0"
set "PHP_DIR=%APP_DIR%portable\php"
set "NODE_DIR=%APP_DIR%portable\node"
set "LOG_FILE=%APP_DIR%app.log"

REM Logo
cls
echo.
echo     ███████╗      █████╗ ██████╗ ███████╗██╗██╗   ██╗
echo     ██╔════╝     ██╔══██╗██╔══██╗██╔════╝██║██║   ██║
echo     █████╗ █████╗███████║██████╔╝███████╗██║██║   ██║
echo     ██╔══╝ ╚════╝██╔══██║██╔══██╗╚════██║██║╚██╗ ██╔╝
echo     ███████╗     ██║  ██║██║  ██║███████║██║ ╚████╔╝ 
echo     ╚══════╝     ╚═╝  ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═══╝  
echo.
echo     FATURA YÖNETİM SİSTEMİ v1.0
echo.
echo ═══════════════════════════════════════════════════════════════
echo.

REM Sistem kontrolü
echo [1/6] Sistem kontrol ediliyor...

REM PHP kontrolü
if not exist "%PHP_DIR%\php.exe" (
    echo.
    echo [✗] HATA: PHP bulunamadı!
    echo.
    echo Çözüm:
    echo 1. portable-setup.bat dosyasını çalıştırın
    echo 2. Veya portable\php klasörüne PHP ekleyin
    echo.
    echo İndirme: https://windows.php.net/download/
    echo.
    pause
    exit /b 1
)
echo    [✓] PHP bulundu

REM Node.js kontrolü
if not exist "%NODE_DIR%\node.exe" (
    echo.
    echo [✗] HATA: Node.js bulunamadı!
    echo.
    echo Çözüm:
    echo 1. portable-setup.bat dosyasını çalıştırın
    echo 2. Veya portable\node klasörüne Node.js ekleyin
    echo.
    echo İndirme: https://nodejs.org/
    echo.
    pause
    exit /b 1
)
echo    [✓] Node.js bulundu

REM Vendor kontrolü
echo [2/6] Bağımlılıklar kontrol ediliyor...
if not exist "%APP_DIR%vendor" (
    echo    [!] PHP paketleri bulunamadı, yükleniyor...
    cd "%APP_DIR%"
    "%PHP_DIR%\php.exe" "%APP_DIR%portable\composer\composer.phar" install --no-dev 2>nul
)
echo    [✓] PHP paketleri hazır

if not exist "%APP_DIR%html2pdf\node_modules" (
    echo    [!] Node.js paketleri bulunamadı, yükleniyor...
    cd "%APP_DIR%html2pdf"
    set "PATH=%NODE_DIR%;%PATH%"
    call "%NODE_DIR%\npm.cmd" install --production 2>nul
)
echo    [✓] Node.js paketleri hazır

REM Port bulma
echo [3/6] Uygun port aranıyor...
set "PORT=8000"
for %%p in (8000 8080 8888 9000 9090) do (
    netstat -ano | findstr ":%%p " >nul 2>&1
    if !errorlevel! neq 0 (
        set "PORT=%%p"
        goto :port_found
    )
)
:port_found
echo    [✓] Port bulundu: %PORT%

REM Geçici klasörler
echo [4/6] Geçici klasörler hazırlanıyor...
if not exist "%APP_DIR%temp_invoices" mkdir "%APP_DIR%temp_invoices"
if not exist "%APP_DIR%backend\downloads" mkdir "%APP_DIR%backend\downloads"
echo    [✓] Klasörler hazır

REM Log başlat
echo [5/6] Log sistemi başlatılıyor...
echo ═══════════════════════════════════════════════════════════════ > "%LOG_FILE%"
echo E-Arşiv Fatura Sistemi - Log >> "%LOG_FILE%"
echo Başlatma Zamanı: %date% %time% >> "%LOG_FILE%"
echo Port: %PORT% >> "%LOG_FILE%"
echo ═══════════════════════════════════════════════════════════════ >> "%LOG_FILE%"
echo. >> "%LOG_FILE%"
echo    [✓] Log hazır

REM Sunucu başlat
echo [6/6] Sunucu başlatılıyor...
echo.
echo ═══════════════════════════════════════════════════════════════
echo.
echo   ✓ Sistem hazır!
echo   ✓ Adres: http://localhost:%PORT%
echo   ✓ Log: %LOG_FILE%
echo.
echo   [!] Bu pencereyi KAPATMAYIN!
echo   [!] Kapatmak için CTRL+C veya pencereyi kapatın
echo.
echo ═══════════════════════════════════════════════════════════════
echo.

REM Tarayıcıda aç
start "" cmd /c "timeout /t 2 /nobreak >nul & start http://localhost:%PORT%"

REM PHP sunucusu
cd "%APP_DIR%"
"%PHP_DIR%\php.exe" -S localhost:%PORT% >> "%LOG_FILE%" 2>&1

REM Kapanış
echo.
echo ═══════════════════════════════════════════════════════════════
echo   Sunucu durduruldu.
echo ═══════════════════════════════════════════════════════════════
echo.
pause
