#!/bin/bash

echo "🚀 Quick Wins Simple Deployment"
echo "=============================="

# 1. Run remaining migrations
echo -e "\n1. Running migrations..."
php artisan migrate --force

# 2. Test Redis connection
echo -e "\n2. Testing Redis..."
php -r "
try {
    \$redis = new Redis();
    \$redis->connect('127.0.0.1', 6379);
    \$redis->set('test', 'ok');
    echo '✅ Redis is working' . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ Redis error: ' . \$e->getMessage() . PHP_EOL;
}
"

# 3. Test basic functionality
echo -e "\n3. Testing basic Quick Wins features..."

# Test webhook endpoint
echo -n "Testing webhook endpoint... "
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/retell/optimized-webhook -X POST -H "Content-Type: application/json" -d '{"test":true}')
if [ "$RESPONSE" -eq "200" ] || [ "$RESPONSE" -eq "401" ]; then
    echo "✅ OK (Status: $RESPONSE)"
else
    echo "❌ Failed (Status: $RESPONSE)"
fi

# Test health endpoint
echo -n "Testing health endpoint... "
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/health)
if [ "$RESPONSE" -eq "200" ]; then
    echo "✅ OK"
else
    echo "❌ Failed (Status: $RESPONSE)"
fi

# Test metrics endpoint
echo -n "Testing metrics endpoint... "
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/metrics -H "Authorization: Bearer askproai_metrics_token_2025")
if [ "$RESPONSE" -eq "200" ]; then
    echo "✅ OK"
else
    echo "❌ Failed (Status: $RESPONSE)"
fi

echo -e "\n✅ Quick Wins deployment simplified complete!"
echo -e "\nNext steps:"
echo "1. Update Retell.ai webhook URL to: https://api.askproai.de/api/retell/optimized-webhook"
echo "2. Monitor Redis: redis-cli monitor"
echo "3. Check logs: tail -f storage/logs/laravel.log"