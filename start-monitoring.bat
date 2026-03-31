@echo off
REM Account Opening Monitoring Setup Script
REM This script sets up and starts the complete monitoring stack

echo 🚀 Starting Account Opening Monitoring Stack...

REM Colors for output (Windows CMD)
set "RED=[91m"
set "GREEN=[92m"
set "YELLOW=[93m"
set "BLUE=[94m"
set "NC=[0m"

echo 📊 Starting Docker services...
docker-compose up -d

echo.
echo ⏳ Waiting for services to initialize...
timeout /t 10 /nobreak > nul

echo.
echo 🎉 Monitoring stack should be ready!
echo.
echo 📋 Access URLs:
echo   • Grafana:     http://localhost:3000 (admin/admin)
echo   • Prometheus:  http://localhost:9090
echo   • Node Exp:    http://localhost:9100
echo   • MySQL Exp:   http://localhost:9104
echo   • cAdvisor:    http://localhost:8081
echo   • App:         http://localhost:8080
echo.
echo 📊 Available Dashboards:
echo   • Account Opening Overview
echo   • System Metrics
echo   • Security Dashboard
echo.
echo 💡 Useful commands:
echo   • View logs: docker-compose logs -f [service]
echo   • Stop: docker-compose down
echo   • Restart: docker-compose restart
echo.
echo Happy monitoring! 📈
pause