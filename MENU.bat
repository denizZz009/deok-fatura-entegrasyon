@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion
title E-Arşiv Fatura Sistemi - Ana Menü
color 0B

:menu
cls
echo.
echo     ███████╗      █████╗ ██████╗ ███████╗██╗██╗   ██╗
echo     ██╔════╝     ██╔══██╗██╔══██╗██╔════╝██║██║   ██║
echo     █████╗ █████╗███████║██████╔╝███████╗██║██║   ██║
echo     ██╔══╝ ╚════╝██╔══██║██╔══██╗╚════██║██║╚██╗ ██╔╝
echo     ███████╗     ██║  ██║██║  ██║███████║██║ ╚████╔╝ 
echo     ╚══════╝     ╚═╝  ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═══╝  
echo.
echo     FATURA YÖNETİM SİSTEMİ - ANA MENÜ
echo.
echo ═══════════════════════════════════════════════════════════════
echo.
echo   [1] Uygulamayı Başlat
echo   [2] İlk Kurulum (Otomatik İndirme)
echo   [3] Kurulum (Manuel - PHP/Node.js hazır)
echo   [4] Masaüstü Kısayolu Oluştur
echo   [5] Yardım ve Dokümantasyon
echo   [6] Sistem Kontrolü
echo   [0] Çıkış
echo.
echo ═══════════════════════════════════════════════════════════════
echo.
set /p choice="Seçiminiz (0-6): "

if "%choice%"=="1" goto start_app
if "%choice%"=="2" goto auto_setup
if "%choice%"=="3" goto manual_setup
if "%choice%"=="4" goto create_shortcut
if "%choice%"=="5" goto help
if "%choice%"=="6" goto system_check
if "%choice%"=="0" goto exit
goto menu

:start_app
cls
echo.
echo Uygulama başlatılıyor...
echo.
call BASLA.bat
goto menu

:auto_setup
cls
echo.
echo ═══════════════════════════════════════════════════════════════
echo   OTOMATİK KURULUM
echo ═══════════════════════════════════════════════════════════════
echo.
echo Bu işlem:
echo   1. PHP'yi otomatik indirecek (~30 MB)
echo   2. Node.js'i otomatik indirecek (~50 MB)
echo   3. Tüm bağımlılıkları yükleyecek
echo.
echo Toplam süre: 5-10 dakika
echo İnternet bağlantısı gereklidir!
echo.
pause
echo.
call otomatik-indir.bat
echo.
echo Şimdi kurulum yapılacak...
pause
call portable-setup.bat
goto menu

:manual_setup
cls
echo.
echo ═══════════════════════════════════════════════════════════════
echo   MANUEL KURULUM
echo ═══════════════════════════════════════════════════════════════
echo.
echo Bu seçenek için PHP ve Node.js'i manuel indirmiş olmalısınız.
echo.
echo Kontrol ediliyor...
echo.

set "PHP_DIR=%~dp0portable\php"
set "NODE_DIR=%~dp0portable\node"

if not exist "%PHP_DIR%\php.exe" (
    echo [✗] PHP bulunamadı!
    echo.
    echo Lütfen portable-download-links.txt dosyasını açın
    echo ve PHP'yi portable\php\ klasörüne indirin.
    echo.
    pause
    goto menu
)

if not exist "%NODE_DIR%\node.exe" (
    echo [✗] Node.js bulunamadı!
    echo.
    echo Lütfen portable-download-links.txt dosyasını açın
    echo ve Node.js'i portable\node\ klasörüne indirin.
    echo.
    pause
    goto menu
)

echo [✓] PHP bulundu
echo [✓] Node.js bulundu
echo.
echo Kurulum başlatılıyor...
pause
call portable-setup.bat
goto menu

:create_shortcut
cls
echo.
echo Masaüstü kısayolu oluşturuluyor...
echo.
call kisayol-olustur.bat
goto menu

:help
cls
echo.
echo ═══════════════════════════════════════════════════════════════
echo   YARDIM VE DOKÜMANTASYON
echo ═══════════════════════════════════════════════════════════════
echo.
echo   [1] Kullanıcı Kılavuzu (KULLANICI_KILAVUZU.md)
echo   [2] Portable Hazırlama Rehberi (PORTABLE_HAZIRLAMA_REHBERI.md)
echo   [3] İndirme Linkleri (portable-download-links.txt)
echo   [4] Yardım Sayfası (help.html)
echo   [5] Mail Sistemi Kullanımı (MAIL_SISTEMI_KULLANIM.md)
echo   [0] Ana Menüye Dön
echo.
echo ═══════════════════════════════════════════════════════════════
echo.
set /p help_choice="Seçiminiz (0-5): "

if "%help_choice%"=="1" start KULLANICI_KILAVUZU.md
if "%help_choice%"=="2" start PORTABLE_HAZIRLAMA_REHBERI.md
if "%help_choice%"=="3" start portable-download-links.txt
if "%help_choice%"=="4" start help.html
if "%help_choice%"=="5" start MAIL_SISTEMI_KULLANIM.md
if "%help_choice%"=="0" goto menu

pause
goto help

:system_check
cls
echo.
echo ═══════════════════════════════════════════════════════════════
echo   SİSTEM KONTROLÜ
echo ═══════════════════════════════════════════════════════════════
echo.

set "APP_DIR=%~dp0"
set "PHP_DIR=%APP_DIR%portable\php"
set "NODE_DIR=%APP_DIR%portable\node"
set "COMPOSER_DIR=%APP_DIR%portable\composer"

echo Kontrol ediliyor...
echo.

REM PHP
if exist "%PHP_DIR%\php.exe" (
    for /f "tokens=2" %%v in ('"%PHP_DIR%\php.exe" -v ^| findstr /C:"PHP"') do (
        echo [✓] PHP: %%v
        goto :php_ok
    )
) else (
    echo [✗] PHP: Bulunamadı
)
:php_ok

REM Node.js
if exist "%NODE_DIR%\node.exe" (
    for /f "tokens=1" %%v in ('"%NODE_DIR%\node.exe" -v') do (
        echo [✓] Node.js: %%v
    )
) else (
    echo [✗] Node.js: Bulunamadı
)

REM Composer
if exist "%COMPOSER_DIR%\composer.phar" (
    echo [✓] Composer: Yüklü
) else (
    echo [✗] Composer: Bulunamadı
)

REM Vendor
if exist "%APP_DIR%vendor" (
    echo [✓] PHP Paketleri: Yüklü
) else (
    echo [✗] PHP Paketleri: Bulunamadı
)

REM Node modules
if exist "%APP_DIR%html2pdf\node_modules" (
    echo [✓] Node.js Paketleri: Yüklü
) else (
    echo [✗] Node.js Paketleri: Bulunamadı
)

echo.
echo ═══════════════════════════════════════════════════════════════
echo.
pause
goto menu

:exit
cls
echo.
echo Çıkış yapılıyor...
echo.
exit /b 0
