#!/bin/bash

echo "🚀 Deploying Quick Wins Performance Optimizations"
echo "================================================"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# 1. Clear caches
echo -e "\n${GREEN}1. Clearing caches...${NC}"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 2. Run migrations
echo -e "\n${GREEN}2. Running migrations...${NC}"
php artisan migrate --force

# 3. Cache configuration
echo -e "\n${GREEN}3. Caching configuration...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Restart queue workers
echo -e "\n${GREEN}4. Restarting queue workers...${NC}"
php artisan queue:restart

# Check if Horizon is installed
if [ -f "artisan" ] && php artisan list | grep -q "horizon"; then
    echo "Starting Horizon..."
    php artisan horizon:terminate
    nohup php artisan horizon > /dev/null 2>&1 &
    echo "Horizon started in background"
else
    echo "Starting standard queue workers..."
    # Start high priority queue
    nohup php artisan queue:work --queue=webhooks-high-priority --tries=3 --timeout=120 > /dev/null 2>&1 &
    # Start medium priority queue
    nohup php artisan queue:work --queue=webhooks-medium-priority --tries=3 --timeout=120 > /dev/null 2>&1 &
    # Start default queue
    nohup php artisan queue:work --queue=default --tries=3 --timeout=120 > /dev/null 2>&1 &
    echo "Queue workers started"
fi

# 5. Run performance baseline
echo -e "\n${GREEN}5. Running performance baseline...${NC}"
php artisan performance:baseline --save

# 6. Test the optimizations
echo -e "\n${GREEN}6. Testing optimizations...${NC}"
php test-quick-wins.php

# 7. Update .env if needed
echo -e "\n${GREEN}7. Checking environment configuration...${NC}"
if ! grep -q "MONITORING_METRICS_TOKEN" .env; then
    echo "Adding MONITORING_METRICS_TOKEN to .env..."
    echo "" >> .env
    echo "# Performance Monitoring" >> .env
    echo "MONITORING_METRICS_TOKEN=$(openssl rand -base64 32)" >> .env
    echo "MONITORING_ENABLED=true" >> .env
fi

# 8. Final status
echo -e "\n${GREEN}✅ Quick Wins Deployment Complete!${NC}"
echo ""
echo "Next steps:"
echo "1. Update Retell.ai webhook URL to: /retell/optimized-webhook"
echo "2. Monitor performance at: /admin/system-monitoring"
echo "3. View metrics at: /api/metrics (use token from .env)"
echo "4. Check baseline results in: storage/performance-baseline-*.json"
echo ""
echo "To rollback, change webhook URL back to: /retell/webhook"