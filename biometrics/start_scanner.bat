@echo off
echo ========================================
echo ZKTeco Fingerprint Scanner Server
echo ========================================
echo.
echo Scanner Installation Path: C:\Program Files (x86)\FPSensor\Biokey
echo.

REM Set the working directory to this batch file's location
cd /d "%~dp0"

echo Current directory: %CD%
echo.

echo Checking Python installation...
python --version
if errorlevel 1 (
    echo ERROR: Python not found!
    echo Please install Python from python.org
    echo.
    pause
    exit /b 1
)

echo Checking Flask installation...
pip show flask >nul 2>&1
if errorlevel 1 (
    echo Installing Flask and dependencies...
    pip install flask flask-cors
    echo Dependencies installed successfully.
    echo.
)

echo Checking scanner DLL...
if exist "C:\Program Files (x86)\FPSensor\Biokey\ZKFPCap.dll" (
    echo ? ZKTeco DLL found: C:\Program Files (x86)\FPSensor\Biokey\ZKFPCap.dll
) else (
    echo ? WARNING: ZKTeco DLL not found!
    echo Please ensure ZKTeco scanner is installed.
)

echo.
echo Starting ZKTeco Fingerprint Server...
echo Server URL: http://localhost:3000
echo.
echo IMPORTANT:
echo 1. Make sure ZKTeco scanner is connected via USB
echo 2. Keep this window open while using fingerprint features
echo 3. Press Ctrl+C to stop the server
echo.
echo ========================================
echo.

python zkteco_python_server.py

echo.
echo Server stopped.
pause