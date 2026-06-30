@echo off
echo Testing ZKTeco Fingerprint Server...
echo.

cd /d "%~dp0"

echo 1. Testing health endpoint...
curl http://localhost:3001/health
echo.
echo.

echo 2. Testing scanner connection...
curl http://localhost:3001/test
echo.
echo.

echo 3. Done!
pause