#!/bin/bash
# Real-time monitoring script for new calls
# Usage: ./monitor-new-calls.sh

echo "🔍 Monitoring for new calls in real-time..."
echo "Press Ctrl+C to stop"
echo ""

# Get baseline call count
BASELINE=$(mysql -u askproai_user -paskproai_secure_pass_2024 askproai_db -Nse "SELECT COUNT(*) FROM calls")

while true; do
    # Check for new calls
    CURRENT=$(mysql -u askproai_user -paskproai_secure_pass_2024 askproai_db -Nse "SELECT COUNT(*) FROM calls")

    if [ "$CURRENT" -gt "$BASELINE" ]; then
        echo "🆕 NEW CALL DETECTED!"
        echo ""

        # Get the latest call details
        mysql -u askproai_user -paskproai_secure_pass_2024 askproai_db -e "
        SELECT
            id as call_id,
            retell_call_id,
            company_id,
            customer_id,
            booking_confirmed,
            created_at
        FROM calls
        ORDER BY id DESC
        LIMIT 1;
        "

        # Get the call ID
        CALL_ID=$(mysql -u askproai_user -paskproai_secure_pass_2024 askproai_db -Nse "SELECT id FROM calls ORDER BY id DESC LIMIT 1")

        echo ""
        echo "📊 Running validation..."
        php /var/www/api-gateway/artisan appointments:validate-chain $CALL_ID

        echo ""
        echo "✅ Validation complete. Continuing to monitor..."
        echo ""

        BASELINE=$CURRENT
    fi

    sleep 2
done
