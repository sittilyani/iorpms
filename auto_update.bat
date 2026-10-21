@echo off
rem EasyFlow-L unattended updater. Run by Windows Task Scheduler (see install_auto_update.bat).
rem Checks GitHub and applies an update only when something changed. Log: cache\auto_update.log
cd /d "%~dp0"
set "PHPEXE="
for /f "delims=" %%P in ('dir /b /s /o-n "C:\laragon\bin\php\php.exe" 2^>nul') do if not defined PHPEXE set "PHPEXE=%%P"
if not defined PHPEXE (
    echo PHP not found under C:\laragon\bin\php>> cache\auto_update.log
    exit /b 1
)
"%PHPEXE%" update.php --auto
