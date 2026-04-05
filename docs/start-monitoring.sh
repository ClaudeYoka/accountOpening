#!/bin/bash

# Account Opening Monitoring Setup Script
# This script sets up and starts the complete monitoring stack

echo "🚀 Starting Account Opening Monitoring Stack..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to check if a service is healthy
check_service() {
    local service=$1
    local url=$2
    local max_attempts=30
    local attempt=1

    echo -n "⏳ Waiting for $service to be ready..."
    while [ $attempt -le $max_attempts ]; do
        if curl -s --max-time 5 "$url" > /dev/null 2>&1; then
            echo -e "\n${GREEN}✅ $service is ready!${NC}"
            return 0
        fi
        echo -n "."
        sleep 2
        ((attempt++))
    done

    echo -e "\n${RED}❌ $service failed to start${NC}"
    return 1
}

# Start the monitoring stack
echo -e "${BLUE}📊 Starting Docker services...${NC}"
docker-compose up -d

# Wait for services to be ready
echo -e "${YELLOW}⏳ Waiting for services to initialize...${NC}"

# Check Prometheus
check_service "Prometheus" "http://localhost:9090/-/ready"

# Check Grafana
check_service "Grafana" "http://localhost:3000/api/health"

# Check Node Exporter
check_service "Node Exporter" "http://localhost:9100/metrics"

# Check MySQL Exporter
check_service "MySQL Exporter" "http://localhost:9104/metrics"

# Check cAdvisor
check_service "cAdvisor" "http://localhost:8081/healthz"

echo ""
echo -e "${GREEN}🎉 Monitoring stack is ready!${NC}"
echo ""
echo -e "${BLUE}📋 Access URLs:${NC}"
echo "  • Grafana:     http://localhost:3000 (admin/admin)"
echo "  • Prometheus:  http://localhost:9090"
echo "  • Node Exp:    http://localhost:9100"
echo "  • MySQL Exp:   http://localhost:9104"
echo "  • cAdvisor:    http://localhost:8081"
echo "  • App:         http://localhost:8080"
echo ""
echo -e "${YELLOW}📊 Available Dashboards:${NC}"
echo "  • Account Opening Overview"
echo "  • System Metrics"
echo "  • Security Dashboard"
echo ""
echo -e "${BLUE}💡 Useful commands:${NC}"
echo "  • View logs: docker-compose logs -f [service]"
echo "  • Stop: docker-compose down"
echo "  • Restart: docker-compose restart"
echo ""
echo -e "${GREEN}Happy monitoring! 📈${NC}"