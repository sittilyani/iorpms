@echo off
echo ========================================
echo ZKTeco Fingerprint Server
echo ========================================
echo Using Laragon Node.js v22.12.0
echo.
echo IMPORTANT: Run as Administrator!
echo Right-click -> Run as administrator
echo ========================================
echo.

REM Change to script directory
cd /d "%~dp0"

echo Checking Node.js...
node --version
if errorlevel 1 (
    echo ERROR: Node.js not found!
    echo Please install Node.js via Laragon.
    pause
    exit /b 1
)

echo.
echo Checking dependencies...
if not exist "node_modules" (
    echo Installing dependencies...
    call npm install
)

echo.
echo Starting ZKTeco Fingerprint Server...
echo Server: http://localhost:3001
echo.
echo Keep this window open while using fingerprint features.
echo Press Ctrl+C to stop the server.
echo.
echo ========================================
echo.

node zkteco_server.js

echo.
echo Server stopped.
pause