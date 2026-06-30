@echo off
:: Auto DB Backup for IORPMS
:: Find the PHP binary from Laragon (update version if needed)
for /d %%d in ("C:\laragon\bin\php\php8*") do set PHP=%%d\php.exe

if not exist "%PHP%" (
    echo PHP not found. Update path in this script.
    pause
    exit /b 1
)

"%PHP%" "C:\laragon\www\iorpms\backup\auto_backup_cli.php" >> "C:\laragon\www\iorpms\backup\backup_log.txt" 2>&1
