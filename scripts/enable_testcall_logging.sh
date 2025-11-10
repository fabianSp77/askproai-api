#!/bin/bash

# Enable Real-Time Test Call Logging
# Usage: ./scripts/enable_testcall_logging.sh [call_id]

set -e

CALL_ID=$1
LOG_FILE="storage/logs/laravel.log"

echo "🔧 Enabling Test Call Logging Mode..."

# Enable debug mode
echo "1. Enabling debug mode..."
sed -i.bak 's/APP_DEBUG=false/APP_DEBUG=true/' .env
echo "   ✅ APP_DEBUG=true"

# Clear config cache
echo "2. Clearing config cache..."
php artisan config:clear
echo "   ✅ Config cache cleared"

# Ensure log file exists and is writable
echo "3. Checking log file..."
touch $LOG_FILE
chmod 664 $LOG_FILE
echo "   ✅ Log file ready: $LOG_FILE"

# Create monitoring scripts
echo "4. Creating monitoring aliases..."
cat > /tmp/testcall_monitor_commands.sh <<'EOF'
#!/bin/bash

# Test Call Monitoring Commands
# Source this file: source /tmp/testcall_monitor_commands.sh

# Monitor ALL activity
alias monitor-all='tail -f storage/logs/laravel.log | grep -E "(WEBHOOK|FUNCTION_CALL|CALCOM_API|DYNAMIC_VARS|ERROR)"'

# Monitor specific call (set CALL_ID first)
alias monitor-call='tail -f storage/logs/laravel.log | grep "$CALL_ID"'

# Monitor only errors
alias monitor-errors='tail -f storage/logs/laravel.log | grep "❌ ERROR"'

# Monitor only webhooks
alias monitor-webhooks='tail -f storage/logs/laravel.log | grep "🔔 WEBHOOK"'

# Monitor only function calls
alias monitor-functions='tail -f storage/logs/laravel.log | grep "⚡ FUNCTION_CALL"'

# Monitor only Cal.com API
alias monitor-calcom='tail -f storage/logs/laravel.log | grep "🔗 CALCOM_API"'

# Monitor dynamic variables
alias monitor-vars='tail -f storage/logs/laravel.log | grep "📤 DYNAMIC_VARS"'

echo "✅ Monitoring aliases loaded!"
echo ""
echo "Available commands:"
echo "  monitor-all          - All test call activity"
echo "  monitor-call         - Specific call (set CALL_ID first)"
echo "  monitor-errors       - Only errors"
echo "  monitor-webhooks     - Only webhooks"
echo "  monitor-functions    - Only function calls"
echo "  monitor-calcom       - Only Cal.com API calls"
echo "  monitor-vars         - Only dynamic variables"
echo ""
echo "Example usage:"
echo "  export CALL_ID=\"call_793088ed\""
echo "  monitor-call"
EOF

chmod +x /tmp/testcall_monitor_commands.sh

echo ""
echo "✅ Test Call Logging Enabled!"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  REAL-TIME MONITORING COMMANDS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Load monitoring aliases:"
echo "  source /tmp/testcall_monitor_commands.sh"
echo ""
echo "Quick start (no aliases):"
echo "  # Monitor everything"
echo "  tail -f $LOG_FILE | grep -E '(WEBHOOK|FUNCTION_CALL|CALCOM_API)'"
echo ""

if [ -n "$CALL_ID" ]; then
  echo "  # Monitor your test call: $CALL_ID"
  echo "  tail -f $LOG_FILE | grep '$CALL_ID'"
  echo ""
  echo "Or with aliases:"
  echo "  export CALL_ID=\"$CALL_ID\""
  echo "  monitor-call"
else
  echo "  # Monitor specific call (get call_id from first webhook)"
  echo "  tail -f $LOG_FILE | grep 'call_xxxxx'"
  echo ""
  echo "Or with aliases:"
  echo "  export CALL_ID=\"call_xxxxx\""
  echo "  monitor-call"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📞 Ready for test call! Watch the logs stream in real-time."
echo ""
echo "To disable after test:"
echo "  ./scripts/disable_testcall_logging.sh"
echo ""
