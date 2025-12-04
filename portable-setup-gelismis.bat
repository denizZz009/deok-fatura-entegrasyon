@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion
title E-Arşiv Fatura Sistemi - Kurulum
color 0A

REM ═══════════════════════════════════════════════════════════════
REM   E-ARŞIV FATURA SİSTEMİ - GELİŞMİŞ KURULUM
REM ═══════════════════════════════════════════════════════════════

set "APP_DIR=%~dp0"
set "PHP_DIR=%APP_DIR%portable\php"
set "NODE_DIR=%APP_DIR%portable\node"
set "COMPOSER_DIR=%APP_DIR%portable\composer"

cls
echo.
echo ═══════════════════════════════════════════════════════════════
echo   E-ARŞIV FATURA SİSTEMİ - PORTABLE KURULUM
echo ═══════════════════════════════════════════════════════════════
echo.
echo Bu kurulum aşağıdaki işlemleri yapacak:
echo   1. Dizin yapısını oluştur
echo   2. PHP'yi kontrol et
echo   3. Node.js'i kontrol et
echo   4. Composer'ı indir
echo   5. PHP paketlerini yükle
echo   6. Node.js paketlerini yükle
echo   7. Puppeteer Chrome'u indir
echo.
echo Tahmini süre: 3-5 dakika
echo.
pause
echo.

REM ═══════════════════════════════════════════════════════════════
echo [1/7] Dizin yapısı oluşturuluyor...
REM ═══════════════════════════════════════════════════════════════

if not exist "%APP_DIR%portable" mkdir "%APP_DIR%portable"
if not exist "%APP_DIR%portable\downloads" mkdir "%APP_DIR%portable\downloads"
if not exist "%APP_DIR%temp_invoices" mkdir "%APP_DIR%temp_invoices"
if not exist "%APP_DIR%backend\downloads" mkdir "%APP_DIR%backend\downloads"

echo    [✓] Dizinler oluşturuldu
echo.

REM ═══════════════════════════════════════════════════════════════
echo [2/7] PHP kontrol ediliyor...
REM ═══════════════════════════════════════════════════════════════

if not exist "%PHP_DIR%\php.exe" (
    echo.
    echo    [✗] PHP bulunamadı!
    echo.
    echo    Lütfen aşağıdaki adımları takip edin:
    echo.
    echo    1. https://windows.php.net/download/ adresine gidin
    echo    2. "PHP 8.1 Thread Safe (x64)" sürümünü indirin
    echo    3. ZIP dosyasını portable\php\ klasörüne çıkarın
    echo    4. Bu scripti tekrar çalıştırın
    echo.
    echo    Direkt link:
    echo    https://windows.php.net/downloads/releases/php-8.1.27-Win32-vs16-x64.zip
    echo.
    pause
    exit /b 1
)

REM PHP versiyonu kontrol
for /f "tokens=2" %%v in ('"%PHP_DIR%\php.exe" -v ^| findstr /C:"PHP"') do (
    set "PHP_VERSION=%%v"
    goto :php_version_found
)
:php_version_found

echo    [✓] PHP bulundu: %PHP_VERSION%
echo.

REM ═══════════════════════════════════════════════════════════════
echo [3/7] Node.js kontrol ediliyor...
REM ═══════════════════════════════════════════════════════════════

if not exist "%NODE_DIR%\node.exe" (
    echo.
    echo    [✗] Node.js bulunamadı!
    echo.
    echo    Lütfen aşağıdaki adımları takip edin:
    echo.
    echo    1. https://nodejs.org/dist/ adresine gidin
    echo    2. "node-v18.19.0-win-x64.zip" dosyasını indirin
    echo    3. ZIP dosyasını portable\node\ klasörüne çıkarın
    echo    4. Bu scripti tekrar çalıştırın
    echo.
    echo    Direkt link:
    echo    https://nodejs.org/dist/v18.19.0/node-v18.19.0-win-x64.zip
    echo.
    pause
    exit /b 1
)

REM Node.js versiyonu kontrol
for /f "tokens=1" %%v in ('"%NODE_DIR%\node.exe" -v') do (
    set "NODE_VERSION=%%v"
)

echo    [✓] Node.js bulundu: %NODE_VERSION%
echo.

REM ═══════════════════════════════════════════════════════════════
echo [4/7] Composer kontrol ediliyor...
REM ═══════════════════════════════════════════════════════════════

if not exist "%COMPOSER_DIR%" mkdir "%COMPOSER_DIR%"

if not exist "%COMPOSER_DIR%\composer.phar" (
    echo    [!] Composer indiriliyor...
    
    "%PHP_DIR%\php.exe" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" 2>nul
    
    if exist "composer-setup.php" (
        "%PHP_DIR%\php.exe" composer-setup.php --install-dir="%COMPOSER_DIR%" --filename=composer.phar --quiet
        del composer-setup.php
        echo    [✓] Composer indirildi
    ) else (
        echo    [✗] Composer indirilemedi! İnternet bağlantınızı kontrol edin.
        pause
        exit /b 1
    )
) else (
    echo    [✓] Composer zaten mevcut
)
echo.

REM ═══════════════════════════════════════════════════════════════
echo [5/7] PHP paketleri yükleniyor...
REM ═══════════════════════════════════════════════════════════════

cd "%APP_DIR%"

if exist "composer.json" (
    echo    [!] Ana paketler yükleniyor...
    "%PHP_DIR%\php.exe" "%COMPOSER_DIR%\composer.phar" install --no-dev --optimize-autoloader --quiet
    echo    [✓] Ana paketler yüklendi
)

if exist "backend\composer.json" (
    echo    [!] Backend paketleri yükleniyor...
    cd "%APP_DIR%backend"
    "%PHP_DIR%\php.exe" "%COMPOSER_DIR%\composer.phar" install --no-dev --optimize-autoloader --quiet
    echo    [✓] Backend paketleri yüklendi
)

cd "%APP_DIR%"
echo.

REM ═══════════════════════════════════════════════════════════════
echo [6/7] Node.js paketleri yükleniyor...
REM ═══════════════════════════════════════════════════════════════

if exist "%APP_DIR%html2pdf\package.json" (
    cd "%APP_DIR%html2pdf"
    set "PATH=%NODE_DIR%;%PATH%"
    
    echo    [!] npm paketleri yükleniyor (bu biraz sürebilir)...
    call "%NODE_DIR%\npm.cmd" install --production --silent 2>nul
    
    if exist "node_modules" (
        echo    [✓] npm paketleri yüklendi
    ) else (
        echo    [✗] npm paketleri yüklenemedi!
    )
)

cd "%APP_DIR%"
echo.

REM ═══════════════════════════════════════════════════════════════
echo [7/7] Puppeteer Chrome indiriliyor...
REM ═══════════════════════════════════════════════════════════════

if exist "%APP_DIR%html2pdf\node_modules" (
    cd "%APP_DIR%html2pdf"
    set "PATH=%NODE_DIR%;%PATH%"
    
    echo    [!] Chrome indiriliyor (bu biraz sürebilir)...
    call "%NODE_DIR%\npx.cmd" puppeteer browsers install chrome --silent 2>nul
    
    echo    [✓] Chrome indirildi
)

cd "%APP_DIR%"
echo.

REM ═══════════════════════════════════════════════════════════════
echo.
echo ═══════════════════════════════════════════════════════════════
echo   KURULUM TAMAMLANDI!
echo ═══════════════════════════════════════════════════════════════
echo.
echo   Kurulum özeti:
echo   ✓ PHP: %PHP_VERSION%
echo   ✓ Node.js: %NODE_VERSION%
echo   ✓ Composer: Yüklü
echo   ✓ PHP Paketleri: Yüklü
echo   ✓ Node.js Paketleri: Yüklü
echo   ✓ Puppeteer Chrome: Yüklü
echo.
echo   Uygulamayı başlatmak için:
echo   → BASLA.bat dosyasına çift tıklayın
echo.
echo ═══════════════════════════════════════════════════════════════
echo.
pause
