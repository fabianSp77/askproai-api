#!/bin/bash

# Phase 4 ML & Automation Deployment Script
# This script deploys the complete ML infrastructure

set -e

echo "========================================="
echo "Phase 4: ML & Automation Deployment"
echo "========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running from correct directory
if [ ! -f "docker-compose.ml.yml" ]; then
    echo -e "${RED}Error: docker-compose.ml.yml not found!${NC}"
    echo "Please run this script from /var/www/api-gateway directory"
    exit 1
fi

echo -e "${YELLOW}Step 1: Checking Docker installation...${NC}"
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Docker is not installed!${NC}"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}Docker Compose is not installed!${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Docker and Docker Compose are installed${NC}"

echo -e "${YELLOW}Step 2: Stopping any existing ML services...${NC}"
docker-compose -f docker-compose.ml.yml down 2>/dev/null || true

echo -e "${YELLOW}Step 3: Building ML service image...${NC}"
docker-compose -f docker-compose.ml.yml build ml-service

echo -e "${YELLOW}Step 4: Starting ML infrastructure...${NC}"
docker-compose -f docker-compose.ml.yml up -d

echo -e "${YELLOW}Step 5: Waiting for services to be healthy...${NC}"
sleep 10

# Check PostgreSQL
echo -n "Checking PostgreSQL ML... "
if docker exec askpro-postgres-ml pg_isready -U ml_user -d ml_predictions &>/dev/null; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    echo "PostgreSQL ML is not ready. Check logs: docker logs askpro-postgres-ml"
    exit 1
fi

# Check Redis
echo -n "Checking Redis ML... "
if docker exec askpro-redis-ml redis-cli ping &>/dev/null; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    echo "Redis ML is not ready. Check logs: docker logs askpro-redis-ml"
    exit 1
fi

# Wait for ML service to be ready
echo -n "Checking ML Service... "
MAX_ATTEMPTS=30
ATTEMPT=0

while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    if curl -s http://localhost:8001/health > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC}"
        break
    fi
    sleep 2
    ATTEMPT=$((ATTEMPT + 1))
done

if [ $ATTEMPT -eq $MAX_ATTEMPTS ]; then
    echo -e "${RED}✗${NC}"
    echo "ML Service failed to start. Check logs: docker logs askpro-ml-service"
    exit 1
fi

echo -e "${YELLOW}Step 6: Configuring Laravel environment...${NC}"

# Check if ML configuration exists in .env
if ! grep -q "ML_SERVICE_URL" .env 2>/dev/null; then
    echo "Adding ML configuration to .env..."
    cat >> .env << EOF

# ML Service Configuration
ML_SERVICE_URL=http://localhost:8001
ML_SERVICE_TIMEOUT=5
REDIS_ML_HOST=127.0.0.1
REDIS_ML_PORT=6380
ML_DB_HOST=localhost
ML_DB_PORT=5433
ML_DB_NAME=ml_predictions
ML_DB_USER=ml_user
ML_DB_PASSWORD=ml_secure_pass
EOF
    echo -e "${GREEN}✓ ML configuration added to .env${NC}"
else
    echo -e "${GREEN}✓ ML configuration already exists in .env${NC}"
fi

echo -e "${YELLOW}Step 7: Clearing Laravel caches...${NC}"
php artisan config:clear
php artisan cache:clear
echo -e "${GREEN}✓ Caches cleared${NC}"

echo -e "${YELLOW}Step 8: Testing ML integration...${NC}"

# Test ML service health endpoint
echo -n "Testing ML health endpoint... "
HEALTH_RESPONSE=$(curl -s http://localhost:8001/health)
if echo "$HEALTH_RESPONSE" | grep -q "healthy"; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    echo "ML Service health check failed"
    exit 1
fi

# Test prediction endpoint
echo -n "Testing ML prediction endpoint... "
PREDICTION_RESPONSE=$(curl -s -X POST http://localhost:8001/predict \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": "test-tenant",
    "prediction_type": "usage",
    "features": {
      "hour": 14,
      "day_of_week": 2,
      "usage_mean_30d": 100
    },
    "async_mode": false
  }')

if echo "$PREDICTION_RESPONSE" | grep -q "prediction"; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    echo "ML prediction test failed"
    echo "Response: $PREDICTION_RESPONSE"
fi

echo -e "${YELLOW}Step 9: Checking monitoring services...${NC}"

# Check Prometheus
echo -n "Prometheus: "
if curl -s http://localhost:9090/-/healthy > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Available at http://localhost:9090${NC}"
else
    echo -e "${YELLOW}⚠ Not available (optional)${NC}"
fi

# Check Grafana
echo -n "Grafana: "
if curl -s http://localhost:3000/api/health > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Available at http://localhost:3000 (admin/admin)${NC}"
else
    echo -e "${YELLOW}⚠ Not available (optional)${NC}"
fi

echo ""
echo "========================================="
echo -e "${GREEN}Phase 4 ML Deployment Complete!${NC}"
echo "========================================="
echo ""
echo "Services Running:"
echo "  - ML Service: http://localhost:8001"
echo "  - ML Service Docs: http://localhost:8001/docs"
echo "  - Redis ML: localhost:6380"
echo "  - PostgreSQL ML: localhost:5433"
echo "  - Prometheus: http://localhost:9090"
echo "  - Grafana: http://localhost:3000"
echo ""
echo "Next Steps:"
echo "  1. Test ML predictions from Laravel:"
echo "     php artisan tinker"
echo "     >>> \$client = new \App\Services\MLServiceClient();"
echo "     >>> \$client->getHealthStatus();"
echo ""
echo "  2. View logs:"
echo "     docker-compose -f docker-compose.ml.yml logs -f ml-service"
echo ""
echo "  3. Stop services:"
echo "     docker-compose -f docker-compose.ml.yml down"
echo ""
echo -e "${GREEN}✓ Deployment successful!${NC}"