#!/usr/bin/env bash
set -euo pipefail

echo "======================================================"
echo "FEATURE FLAG ROLLBACK"
echo "======================================================"

echo -e "\n== Current Flag Status =="
grep -E '^FEATURE_(CALCOM_V2|COMPOSITE_BOOKINGS)=' .env || echo "No flags found"

echo -e "\n== Rolling back flags to DISABLED =="
sed -i 's/^FEATURE_COMPOSITE_BOOKINGS=.*/FEATURE_COMPOSITE_BOOKINGS=false/' .env
sed -i 's/^FEATURE_CALCOM_V2=.*/FEATURE_CALCOM_V2=false/' .env
sed -i 's/^FEATURE_CALCOM_V2_ENABLED=.*/FEATURE_CALCOM_V2_ENABLED=false/' .env
sed -i 's/^FEATURE_CALCOM_V2_COMPOSITE_BOOKING=.*/FEATURE_CALCOM_V2_COMPOSITE_BOOKING=false/' .env

echo -e "\n== Clearing caches =="
php artisan config:clear
php artisan route:clear
php artisan optimize:clear

echo -e "\n== Terminating queue workers =="
php artisan queue:restart 2>/dev/null || true
php artisan horizon:terminate 2>/dev/null || true

echo -e "\n== New Flag Status =="
grep -E '^FEATURE_(CALCOM_V2|COMPOSITE_BOOKINGS)=' .env

echo -e "\n== Verifying rollback =="
curl -sS http://localhost/api/health | jq '.status' | grep -q "healthy" && \
  echo "✅ API still healthy after rollback" || \
  echo "❌ API health check failed"

echo -e "\n======================================================"
echo "✅ ROLLBACK COMPLETED"
echo "======================================================"
echo ""
echo "Features disabled:"
echo "• FEATURE_CALCOM_V2=false"
echo "• FEATURE_COMPOSITE_BOOKINGS=false"
echo ""
echo "To re-enable, run:"
echo "sed -i 's/^FEATURE_CALCOM_V2=.*/FEATURE_CALCOM_V2=true/' .env"
echo "sed -i 's/^FEATURE_COMPOSITE_BOOKINGS=.*/FEATURE_COMPOSITE_BOOKINGS=true/' .env"
echo "php artisan config:clear && php artisan optimize:clear"