#!/usr/bin/env bash
set -euo pipefail
cd /var/www/api-gateway

echo "======================================================"
echo "HARDENING CHECKS"
echo "======================================================"
echo ""

echo "Feature Flags:"
grep -E 'FEATURE_(CALCOM_V2|COMPOSITE_BOOKINGS)=' .env || echo "No flags found"

echo ""
echo "Routes (v2 count):"
V2_COUNT=$(php artisan route:list 2>/dev/null | grep -c '^.*api/v2/' || echo "0")
echo "V2 Routes: $V2_COUNT"

echo ""
echo "Health:"
curl -fsS http://localhost/api/health 2>/dev/null | jq -r '.status' || echo "Health check failed"

echo ""
echo "Queue failed:"
FAILED=$(php artisan queue:failed 2>/dev/null | grep -c "^|" || echo "0")
echo "Failed Jobs: $FAILED"

echo ""
echo "Redis ping:"
redis-cli ping 2>/dev/null || echo "Redis not responding"

echo ""
echo "Supervisor:"
sudo supervisorctl status 2>/dev/null || echo "Supervisor not configured"

echo ""
echo "PHP-FPM:"
sudo /usr/sbin/php-fpm8.3 -tt 2>&1 | head -5 || echo "PHP-FPM check failed"

echo ""
echo "Nginx conf test:"
sudo nginx -t 2>&1 || echo "Nginx config test failed"

echo ""
echo "Config cache refresh:"
php artisan optimize:clear >/dev/null 2>&1 && echo "✅ Optimize cleared" || echo "❌ Optimize clear failed"
php artisan config:clear >/dev/null 2>&1 && echo "✅ Config cleared" || echo "❌ Config clear failed"
php artisan route:clear >/dev/null 2>&1 && echo "✅ Routes cleared" || echo "❌ Route clear failed"

echo ""
echo "======================================================"
echo "✅ HARDENING CHECKS COMPLETE"
echo "======================================================"