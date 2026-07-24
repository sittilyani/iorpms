@echo off
echo ========================================
echo Biometric Fingerprint Scanner Server
echo (ZKTeco & SecuGen Support)
echo ========================================
echo.

cd /d "%~dp0"

echo Select Scanner Server to Start:
echo 1. ZKTeco Fingerprint Server (Port 3000)
echo 2. SecuGen Fingerprint Server (Port 8000 / WebAPI 8443)
echo 3. Start Both (ZKTeco & SecuGen)
echo.

set /p choice="Enter option (1, 2, or 3) [Default 3]: "
if "%choice%"=="" set choice=3

if exist "C:\Program Files\SecuGen\SecuGen WebAPI\SecuGenWebAPI.exe" (
    echo Starting native SecuGen WebAPI in background...
    start /B "" "C:\Program Files\SecuGen\SecuGen WebAPI\SecuGenWebAPI.exe"
)

if "%choice%"=="1" (
    echo Starting ZKTeco Server...
    python zkteco_python_server.py
) else if "%choice%"=="2" (
    echo Starting SecuGen Server...
    python secugen_python_server.py
) else (
    echo Starting SecuGen Server in background on Port 8000...
    start /B "" python secugen_python_server.py
    echo Starting ZKTeco Server on Port 3000...
    python zkteco_python_server.py
)

pause