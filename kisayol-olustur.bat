@echo off
chcp 65001 >nul
title Masaüstü Kısayolu Oluştur
color 0B

echo.
echo ═══════════════════════════════════════════════════════════════
echo   MASAÜSTÜ KISAYOLU OLUŞTUR
echo ═══════════════════════════════════════════════════════════════
echo.

set "APP_DIR=%~dp0"
set "DESKTOP=%USERPROFILE%\Desktop"
set "SHORTCUT=%DESKTOP%\E-Arşiv Fatura.lnk"

REM PowerShell ile kısayol oluştur
powershell -Command "$WS = New-Object -ComObject WScript.Shell; $SC = $WS.CreateShortcut('%SHORTCUT%'); $SC.TargetPath = '%APP_DIR%BASLA.bat'; $SC.WorkingDirectory = '%APP_DIR%'; $SC.IconLocation = '%APP_DIR%logo.ico'; $SC.Description = 'E-Arşiv Fatura Yönetim Sistemi'; $SC.Save()"

if exist "%SHORTCUT%" (
    echo [✓] Kısayol oluşturuldu!
    echo.
    echo Masaüstünüzde "E-Arşiv Fatura" kısayolu oluşturuldu.
    echo Artık bu kısayola tıklayarak uygulamayı başlatabilirsiniz.
) else (
    echo [✗] Kısayol oluşturulamadı!
)

echo.
pause
