@echo off
REM setup_rate_limiting.bat
REM Script d'installation rate limiting pour Windows
REM 
REM Usage: setup_rate_limiting.bat

echo.
echo ===================================
echo  Rate Limiting Setup - Windows
echo ===================================
echo.

REM Charger les variables d'environnement depuis .env
for /f "tokens=1,2 delims==" %%a in ('findstr /r "DB_HOST\|DB_USER\|DB_PASS\|DB_NAME" .env') do set %%a=%%b

REM Valeurs par défaut si non trouvées
if not defined DB_HOST set DB_HOST=localhost
if not defined DB_USER set DB_USER=admin
if not defined DB_PASS set DB_PASS=Passw@rd
if not defined DB_NAME set DB_NAME=accountopening_db

REM Chemin vers MySQL Laragon
set MYSQL_PATH=C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe

echo Configuration detectee:
echo   Database Host: %DB_HOST%
echo   Database User: %DB_USER%
echo   Database Name: %DB_NAME%
echo.

REM Step 1: Test connection
echo Verification de la connexion a la BD...
"%MYSQL_PATH%" -h %DB_HOST% -u %DB_USER% -p%DB_PASS% -e "SELECT 1;" >nul 2>&1
if errorlevel 1 (
    echo ERREUR: Impossible de connecter a la BD
    echo Verifier les credentials dans .env
    pause
    exit /b 1
)
echo OK - Connexion etablie
echo.

REM Step 2: Create tables
echo Creation des tables de rate limiting...
"%MYSQL_PATH%" -h %DB_HOST% -u %DB_USER% -p%DB_PASS% %DB_NAME% < db\migrate_rate_limiting.sql
if errorlevel 1 (
    echo ERREUR lors de la creation des tables
    pause
    exit /b 1
)
echo OK - Tables creees
echo.

REM Step 3: Verify
echo.
echo SUCCES! Setup rate limiting termine.
echo.
echo Prochaines etapes:
echo   1. Consulter: RATE_LIMITING_GUIDE.md
echo   2. Integrer RateLimiter dans les fichiers PHP
echo   3. Acceder au dashboard: http://localhost:8080/admin/rate_limiting_dashboard.php
echo.
pause
