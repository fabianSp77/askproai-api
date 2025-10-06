#!/bin/bash

# Test script for all resources
# Generated with Claude Code via Happy

echo "🔍 Testing all Filament Resources"
echo "=================================="
echo ""

# Test CRM Resources
echo "📊 CRM Resources:"
for URL in customers appointments calls; do
    response=$(curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/admin/$URL)
    if [ "$response" = "302" ] || [ "$response" = "200" ]; then
        echo "✅ $URL: OK (HTTP $response)"
    else
        echo "❌ $URL: ERROR (HTTP $response)"
    fi
done

echo ""
echo "📊 Stammdaten Resources:"
# Test Stammdaten Resources
for URL in companies branches services staff working-hours; do
    response=$(curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/admin/$URL)
    if [ "$response" = "302" ] || [ "$response" = "200" ]; then
        echo "✅ $URL: OK (HTTP $response)"
    else
        echo "❌ $URL: ERROR (HTTP $response)"
    fi
done

echo ""
echo "📊 Database Status:"
php artisan tinker --execute="
    echo 'Companies: ' . App\Models\Company::count() . PHP_EOL;
    echo 'Branches: ' . App\Models\Branch::count() . PHP_EOL;
    echo 'Services: ' . App\Models\Service::count() . PHP_EOL;
    echo 'Staff: ' . App\Models\Staff::count() . PHP_EOL;
    echo 'Customers: ' . App\Models\Customer::count() . PHP_EOL;
    echo 'Appointments: ' . App\Models\Appointment::count() . PHP_EOL;
"

echo ""
echo "📊 Recent Errors (last 5 minutes):"
recent_errors=$(find /var/www/api-gateway/storage/logs -name "laravel.log" -mmin -5 -exec grep -c "ERROR" {} \; 2>/dev/null)
if [ -z "$recent_errors" ] || [ "$recent_errors" = "0" ]; then
    echo "✅ No recent errors found"
else
    echo "⚠️ $recent_errors errors found in last 5 minutes"
fi

echo ""
echo "🎯 Test complete!"