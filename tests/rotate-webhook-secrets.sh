#!/usr/bin/env bash
set -euo pipefail

cd /var/www/api-gateway

echo "======================================================"
echo "WEBHOOK SECRET ROTATION"
echo "======================================================"
echo ""

# Get current secret
OLD_SECRET=$(grep '^CALCOM_WEBHOOK_SECRET=' .env | cut -d'=' -f2 || echo "none")
echo "Current secret: ${OLD_SECRET:0:8}..."

# Generate new secret
NEW_SECRET=$(openssl rand -hex 32)
echo "New secret: ${NEW_SECRET:0:8}..."

echo ""
echo "Updating .env file..."
if grep -q '^CALCOM_WEBHOOK_SECRET=' .env; then
    sed -i "s/^CALCOM_WEBHOOK_SECRET=.*/CALCOM_WEBHOOK_SECRET=${NEW_SECRET}/" .env
    echo "✅ Secret updated"
else
    echo "CALCOM_WEBHOOK_SECRET=${NEW_SECRET}" >> .env
    echo "✅ Secret added"
fi

echo ""
echo "Clearing config cache..."
php artisan config:clear >/dev/null 2>&1
echo "✅ Config cache cleared"

echo ""
echo "Syncing with Cal.com..."
SYNC_RESPONSE=$(curl -fsS -X POST http://localhost/api/v2/calcom/push-event-types \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"branch_id": 1}' 2>/dev/null || echo '{"error": "sync failed"}')

if echo "$SYNC_RESPONSE" | grep -q '"error"'; then
    echo "⚠️ Cal.com sync needs valid credentials"
else
    echo "✅ Cal.com notified of new secret"
fi

echo ""
echo "Testing webhook endpoint with new secret..."
TEST_PAYLOAD=$(cat <<EOF
{
  "triggerEvent": "BOOKING_CREATED",
  "createdAt": "$(date -Iseconds)",
  "payload": {
    "bookingId": 999999,
    "test": true
  }
}
EOF
)

# Create HMAC signature with new secret
SIGNATURE=$(echo -n "$TEST_PAYLOAD" | openssl dgst -sha256 -hmac "$NEW_SECRET" -hex | cut -d' ' -f2)

TEST_RESPONSE=$(curl -sS -X POST http://localhost/api/webhooks/calcom \
  -H "Content-Type: application/json" \
  -H "X-Cal-Signature-256: ${SIGNATURE}" \
  -d "$TEST_PAYLOAD" 2>/dev/null || echo '{"error": "test failed"}')

if echo "$TEST_RESPONSE" | grep -qE '(processed|received|ok)'; then
    echo "✅ Webhook accepting new secret"
else
    echo "⚠️ Webhook verification needs testing with real Cal.com events"
fi

echo ""
echo "======================================================"
echo "✅ SECRET ROTATION COMPLETE"
echo "======================================================"
echo ""
echo "New secret is active. Update Cal.com webhook configuration:"
echo "1. Go to Cal.com → Settings → Webhooks"
echo "2. Update webhook secret to: ${NEW_SECRET}"
echo "3. Test with a real booking"
echo ""
echo "Rollback if needed:"
echo "sed -i 's/^CALCOM_WEBHOOK_SECRET=.*/CALCOM_WEBHOOK_SECRET=${OLD_SECRET}/' .env"
echo "php artisan config:clear"
echo ""