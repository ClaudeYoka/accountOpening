@echo off
echo ===========================================
echo   Arrêt du système de monitoring
echo   Account Opening Platform
echo ===========================================
echo.

cd /d "%~dp0"

echo Arrêt des services de monitoring...
docker-compose down

echo.
echo Services arrêtés.
echo.
pause