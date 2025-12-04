@echo off
chcp 65001 >nul
title E-Arşiv Fatura Sistemi
color 0B

REM Mevcut dizini al
set "APP_DIR=%~dp0"
set "PHP_DIR=%APP_DIR%portable\php"
set "NODE_DIR=%APP_DIR%portable\node"

REM Logo göster
cls
echo.
echo     ███████╗      █████╗ ██████╗ ███████╗██╗██╗   ██╗
echo     ██╔════╝     ██╔══██╗██╔══██╗██╔════╝██║██║   ██║
echo     █████╗ █████╗███████║██████╔╝███████╗██║██║   ██║
echo     ██╔══╝ ╚════╝██╔══██║██╔══██╗╚════██║██║╚██╗ ██╔╝
echo     ███████╗     ██║  ██║██║  ██║███████║██║ ╚████╔╝ 
echo     ╚══════╝     ╚═╝  ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═══╝  
echo.
echo     FATURA YÖNETİM SİSTEMİ - PORTABLE SÜRÜM
echo.
echo ═══════════════════════════════════════════════════════════════
echo.

REM Kurulum kontrolü
if not exist "%PHP_DIR%\php.exe" (
    echo [HATA] PHP bulunamadı!
    echo.
    echo Lütfen önce "portable-setup.bat" dosyasını çalıştırın.
    echo.
    pause
    exit /b 1
)

if not exist "%NODE_DIR%\node.exe" (
    echo [HATA] Node.js bulunamadı!
    echo.
    echo Lütfen önce "portable-setup.bat" dosyasını çalıştırın.
    echo.
    pause
    exit /b 1
)

REM Port kontrolü
set "PORT=8000"
netstat -ano | findstr ":%PORT%" >nul 2>&1
if %errorlevel% equ 0 (
    echo [UYARI] Port %PORT% kullanımda! Alternatif port deneniyor...
    set "PORT=8080"
    netstat -ano | findstr ":%PORT%" >nul 2>&1
    if %errorlevel% equ 0 (
        set "PORT=8888"
    )
)

echo [✓] Sistem hazır
echo [✓] PHP: %PHP_DIR%\php.exe
echo [✓] Node.js: %NODE_DIR%\node.exe
echo [✓] Port: %PORT%
echo.
echo ═══════════════════════════════════════════════════════════════
echo.
echo Sunucu başlatılıyor...
echo.
echo Tarayıcınızda otomatik olarak açılacak: http://localhost:%PORT%
echo.
echo [!] Bu pencereyi KAPATMAYIN! Uygulama çalışmaya devam edecek.
echo [!] Uygulamayı kapatmak için bu pencereyi kapatın veya CTRL+C yapın.
echo.
echo ═══════════════════════════════════════════════════════════════
echo.

REM Tarayıcıda aç (3 saniye sonra)
start "" cmd /c "timeout /t 3 /nobreak >nul & start http://localhost:%PORT%"

REM PHP sunucusunu başlat
cd "%APP_DIR%"
"%PHP_DIR%\php.exe" -S localhost:%PORT%

REM Sunucu kapandığında
echo.
echo ═══════════════════════════════════════════════════════════════
echo   Sunucu durduruldu. Pencereyi kapatabilirsiniz.
echo ═══════════════════════════════════════════════════════════════
echo.
pause
