@echo off
REM This batch file explicitly uses Laragon's Python

set PYTHON_PATH=C:\laragon\bin\python\python-3.10-32\python.exe
if not exist "%PYTHON_PATH%" set PYTHON_PATH=C:\laragon\bin\python\python-3.10\python.exe
if not exist "%PYTHON_PATH%" set PYTHON_PATH=python

echo Starting ZKTeco Fingerprint Server...
echo Using: %PYTHON_PATH%
echo.

if not exist "%PYTHON_PATH%" (
    echo ERROR: Python not found at %PYTHON_PATH%
    echo Please install Python via Laragon:
    echo 1. Open Laragon
    echo 2. Menu > Python > Version
    echo 3. Select Python 3.13 and install
    pause
    exit /b 1
)

REM Change to script directory
cd /d "%~dp0"

REM Check and install dependencies
echo Checking dependencies...
"%PYTHON_PATH%" -c "import flask" 2>nul
if errorlevel 1 (
    echo Installing Flask...
    call "%PYTHON_PATH%" -m pip install flask flask-cors
)

echo.
echo Starting server...
echo Press Ctrl+C to stop
echo.

REM Run the server
"%PYTHON_PATH%" "%SCRIPT_PATH%"

pause