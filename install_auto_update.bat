@echo off
rem Run ONCE on each clinic computer: right-click -> Run as administrator.
rem Creates a Windows scheduled task that checks for updates:
rem   - at start-up (first boot of the day) and then every 4 hours while the computer is on
rem   - every day at 08:00 local time (or as soon as the computer is on, if it was off at 08:00)
rem so the clinic no longer has to open update.php. Remove it with: schtasks /delete /tn "EasyFlow Auto Update" /f
rem Running this again replaces the older hourly task.
net session >nul 2>&1
if errorlevel 1 (
    echo Please right-click this file and choose "Run as administrator".
    pause
    exit /b 1
)
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0install_auto_update.ps1" -Script "%~dp0auto_update.bat"
if errorlevel 1 (
    echo Could not create the scheduled task.
    pause
    exit /b 1
)
echo.
echo Done. This computer will check for updates at start-up, every 4 hours while on, and at 08:00 each day.
echo Results are logged in cache\auto_update.log
pause
