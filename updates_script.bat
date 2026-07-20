@echo off
:: =====================================================================
:: EasyFlow-L Offline Update Utility (Windows)
:: =====================================================================
:: Designed to apply updates downloaded from Google Drive to localhost.
:: Place SQL update statements in updates\update.sql
:: Place new/replaced files in updates\files\
:: =====================================================================

echo.
echo  ======================================================
echo    EasyFlow-L Offline Update Script (Windows)
echo  ======================================================
echo.

:: 1. Verify working directory
if not exist includes\config.php (
    echo [ERROR] Must run this script from the EasyFlow-L project root folder!
    echo Current dir: %cd%
    echo.
    pause
    exit /b 1
)

:: 2. Check for database updates
if exist updates\update.sql (
    echo [INFO] Found database update: updates\update.sql
    echo [INFO] Importing database changes into local 'methadone' database...
    
    :: Attempt import using standard mysql client on localhost
    mysql -h localhost -u root methadone < updates\update.sql
    
    if %errorlevel% equ 0 (
        echo [SUCCESS] Database updates imported successfully!
        :: Rename or delete sql script to prevent re-running
        del updates\update.sql
    ) else (
        echo [WARNING] Failed to run SQL update via 'mysql' command. 
        echo           If your MySQL root user has a password, edit this script or
        echo           apply the update via the landing page web interface instead.
    )
) else (
    echo [INFO] No database updates (updates\update.sql) found.
)

:: 3. Check for file updates
if exist updates\files (
    echo [INFO] Found updated files in updates\files\
    echo [INFO] Copying files to project root (overwriting)...
    
    xcopy /S /Y /E /Q updates\files\* .
    
    if %errorlevel% equ 0 (
        echo [SUCCESS] File updates applied successfully!
        :: Clean up updates folder
        rd /S /Q updates\files
    ) else (
        echo [ERROR] Failed to copy update files. Check folder permissions.
    )
) else (
    echo [INFO] No file updates (updates\files) found.
)

echo.
echo ======================================================
echo   Update Check and Execution Complete!
echo ======================================================
echo.
pause
