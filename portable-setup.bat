@echo off
chcp 65001 >nul
title E-Arşiv Fatura Sistemi - Portable Kurulum
color 0A

echo.
echo ═══════════════════════════════════════════════════════════════
echo   E-ARŞIV FATURA SİSTEMİ - PORTABLE KURULUM
echo ═══════════════════════════════════════════════════════════════
echo.

REM Mevcut dizini al
set "APP_DIR=%~dp0"
set "PHP_DIR=%APP_DIR%portable\php"
set "NODE_DIR=%APP_DIR%portable\node"
set "COMPOSER_DIR=%APP_DIR%portable\composer"

echo [1/5] Dizinler kontrol ediliyor...
if not exist "%APP_DIR%portable" mkdir "%APP_DIR%portable"
if not exist "%APP_DIR%portable\downloads" mkdir "%APP_DIR%portable\downloads"

echo [2/5] PHP kontrol ediliyor...
if not exist "%PHP_DIR%\php.exe" (
    echo PHP bulunamadı! Lütfen portable\php klasörüne PHP ekleyin.
    echo İndirme: https://windows.php.net/download/
    pause
    exit /b 1
)

echo [3/5] Node.js kontrol ediliyor...
if not exist "%NODE_DIR%\node.exe" (
    echo Node.js bulunamadı! Lütfen portable\node klasörüne Node.js ekleyin.
    echo İndirme: https://nodejs.org/
    pause
    exit /b 1
)

echo [4/5] Composer kontrol ediliyor...
if not exist "%COMPOSER_DIR%\composer.phar" (
    echo Composer indiriliyor...
    "%PHP_DIR%\php.exe" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    "%PHP_DIR%\php.exe" composer-setup.php --install-dir="%COMPOSER_DIR%" --filename=composer.phar
    "%PHP_DIR%\php.exe" -r "unlink('composer-setup.php');"
)

echo [5/5] Bağımlılıklar yükleniyor...

REM PHP bağımlılıkları
echo    - PHP paketleri...
cd "%APP_DIR%"
"%PHP_DIR%\php.exe" "%COMPOSER_DIR%\composer.phar" install --no-dev --optimize-autoloader 2>nul

REM Backend bağımlılıkları
if exist "%APP_DIR%backend\composer.json" (
    cd "%APP_DIR%backend"
    "%PHP_DIR%\php.exe" "%COMPOSER_DIR%\composer.phar" install --no-dev --optimize-autoloader 2>nul
)

REM Node.js bağımlılıkları
echo    - Node.js paketleri...
cd "%APP_DIR%html2pdf"
set "PATH=%NODE_DIR%;%PATH%"
call "%NODE_DIR%\npm.cmd" install --production 2>nul
call "%NODE_DIR%\npx.cmd" puppeteer browsers install chrome 2>nul

cd "%APP_DIR%"

echo.
echo ═══════════════════════════════════════════════════════════════
echo   KURULUM TAMAMLANDI!
echo ═══════════════════════════════════════════════════════════════
echo.
echo Uygulamayı başlatmak için "BASLA.bat" dosyasını çalıştırın.
echo.
pause
