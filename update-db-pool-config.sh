#!/bin/bash

# Update Database Pool Configuration Script
echo "🔧 Updating Database Connection Pool Configuration"
echo "================================================="

# Function to add or update env variable
update_env() {
    local key=$1
    local value=$2
    
    if grep -q "^${key}=" .env; then
        # Update existing
        sed -i "s|^${key}=.*|${key}=${value}|" .env
        echo "✅ Updated ${key}"
    else
        # Add new
        echo "" >> .env
        echo "${key}=${value}" >> .env
        echo "✅ Added ${key}"
    fi
}

# Check if .env file exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    exit 1
fi

echo ""
echo "📝 Updating database pool settings..."

# Update database pool configuration
update_env "DB_PERSISTENT" "false"
update_env "DB_TIMEOUT" "5"
update_env "DB_POOL_ENABLED" "true"
update_env "DB_POOL_MIN" "5"
update_env "DB_POOL_MAX" "80"
update_env "DB_POOL_IDLE_TIMEOUT" "60"
update_env "DB_POOL_WAIT_TIMEOUT" "10"

echo ""
echo "🔍 Current MySQL status:"
mysql -u root -p'V9LGz2tdR5gpDQz' -e "SHOW VARIABLES LIKE 'max_connections'; SHOW STATUS LIKE 'Threads_connected';" 2>/dev/null || echo "Could not check MySQL status"

echo ""
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear

echo ""
echo "⚡ Restarting queue workers to apply changes..."
php artisan horizon:terminate

echo ""
echo "✅ Configuration updated!"
echo ""
echo "📋 Next steps:"
echo "1. Monitor connection usage: watch 'mysql -u root -p\"V9LGz2tdR5gpDQz\" -e \"SHOW STATUS LIKE '\''Threads_connected'\'';\"'"
echo "2. Check logs for connection errors: tail -f storage/logs/laravel.log | grep -i connection"
echo "3. Verify Horizon is running: php artisan horizon:status"