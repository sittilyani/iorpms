@echo off
echo ========================================
echo ZKTeco Fingerprint Server Setup
echo Using Laragon Node.js v22.12.0
echo ========================================
echo.

cd /d "%~dp0"

echo Checking Laragon Node.js installation...
where node
if errorlevel 1 (
    echo ERROR: Node.js not found in Laragon!
    echo Please make sure Laragon has Node.js installed.
    echo.
    echo In Laragon, go to:
    echo Menu -> Node.js -> Version
    echo Select Node.js 22.12.0 and install
    echo.
    pause
    exit /b 1
)

echo.
echo Step 1: Installing Node.js dependencies...
echo This may take a few minutes...
echo.

call npm install

echo.
echo Step 2: Installation complete!
echo.
echo To start the server:
echo   1. Right-click start_fingerprint_server.bat
echo   2. Select "Run as administrator"
echo   3. Keep the window open
echo.
echo Step 3: Test the server:
echo   Open browser to: http://localhost:3001/health
echo.
pause