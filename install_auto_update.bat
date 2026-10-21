@echo off
rem Run ONCE on each clinic computer: right-click -> Run as administrator.
rem Creates a Windows scheduled task that checks for updates every hour and at start-up,
rem so the clinic no longer has to open update.php. Remove it with: schtasks /delete /tn "EasyFlow Auto Update" /f
net session >nul 2>&1
if errorlevel 1 (
    echo Please right-click this file and choose "Run as administrator".
    pause
    exit /b 1
)
schtasks /create /tn "EasyFlow Auto Update" /tr "\"%~dp0auto_update.bat\"" /sc hourly /mo 1 /ru SYSTEM /rl HIGHEST /f
if errorlevel 1 (
    echo Could not create the scheduled task.
    pause
    exit /b 1
)
echo.
echo Done. This computer will now check for updates every hour while it is on.
echo Results are logged in cache\auto_update.log
pause
