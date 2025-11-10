#!/bin/bash

# Disable Test Call Logging Mode
# Usage: ./scripts/disable_testcall_logging.sh

set -e

echo "🔧 Disabling Test Call Logging Mode..."

# Restore debug mode
echo "1. Disabling debug mode..."
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
echo "   ✅ APP_DEBUG=false"

# Clear config cache
echo "2. Clearing config cache..."
php artisan config:clear
php artisan config:cache
echo "   ✅ Config cache rebuilt"

# Clean up temporary files
echo "3. Cleaning up..."
rm -f /tmp/testcall_monitor_commands.sh
echo "   ✅ Temporary files removed"

echo ""
echo "✅ Test Call Logging Disabled!"
echo ""
echo "Log file preserved at: storage/logs/laravel.log"
echo "To analyze logs: grep 'call_xxxxx' storage/logs/laravel.log"
echo ""
