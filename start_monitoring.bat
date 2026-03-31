@echo off
echo ===========================================
echo   Démarrage du système de monitoring
echo   Account Opening Platform
echo ===========================================
echo.

cd /d "%~dp0"

echo Vérification de Docker...
docker --version >nul 2>&1
if errorlevel 1 (
    echo ERREUR: Docker n'est pas installé ou n'est pas accessible.
    echo Veuillez installer Docker Desktop et réessayer.
    pause
    exit /b 1
)

echo Docker détecté. Démarrage des services de monitoring...
echo.

docker-compose down >nul 2>&1
docker-compose up -d

if errorlevel 1 (
    echo ERREUR: Impossible de démarrer les services Docker.
    echo Vérifiez que Docker Desktop est en cours d'exécution.
    pause
    exit /b 1
)

echo.
echo Services démarrés avec succès !
echo.
echo URLs d'accès :
echo - Grafana:     http://localhost:3000 (admin/admin)
echo - Prometheus:  http://localhost:9090
echo - Application: http://localhost:8080
echo.
echo Depuis l'application Account Opening :
echo - Menu Admin -> Monitoring -> Centre de Monitoring
echo.
echo Appuyez sur une touche pour continuer...
pause >nul