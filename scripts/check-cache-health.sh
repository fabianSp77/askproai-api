#!/bin/bash

# Quick health check script for cache system

echo "🔍 Checking cache system health..."
echo ""

# Check if monitor is running
if pgrep -f "cache:monitor --continuous" > /dev/null; then
    echo "✅ Cache monitor is running"
else
    echo "❌ Cache monitor is NOT running"
fi

# Check Redis
if redis-cli ping > /dev/null 2>&1; then
    echo "✅ Redis is responsive"
else
    echo "❌ Redis is NOT responsive"
fi

# Check view directory
VIEW_DIR="/var/www/api-gateway/storage/framework/views"
if [ -w "$VIEW_DIR" ]; then
    echo "✅ View directory is writable"
    FILE_COUNT=$(find $VIEW_DIR -type f -name "*.php" | wc -l)
    STALE_COUNT=$(find $VIEW_DIR -type f -name "*.php" -mtime +1 | wc -l)
    echo "   📊 Total view files: $FILE_COUNT"
    echo "   📊 Stale files (>24h): $STALE_COUNT"
else
    echo "❌ View directory is NOT writable"
fi

# Check recent errors
ERROR_COUNT=$(grep -c "ERROR" /var/www/api-gateway/storage/logs/cache-monitor.log 2>/dev/null || echo "0")
echo ""
echo "📋 Recent errors in log: $ERROR_COUNT"

# Run artisan health check
echo ""
echo "🏥 Running artisan health check..."
cd /var/www/api-gateway && php artisan cache:monitor
