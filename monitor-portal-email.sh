#!/bin/bash

echo "=== MONITORING Portal Email Clicks ==="
echo ""
echo "I'm now monitoring all logs for portal email activity."
echo "Please go to the Business Portal and try to send an email."
echo ""
echo "Watching for:"
echo "- API calls to send-summary"
echo "- Authentication issues"
echo "- Queue activity"
echo "- Any errors"
echo ""
echo "Press Ctrl+C to stop monitoring"
echo ""
echo "============================================"
echo ""

# Clear the debug log
> storage/logs/portal-email-debug.log

# Monitor multiple logs simultaneously
tail -f storage/logs/laravel.log storage/logs/portal-email-debug.log | grep -E --line-buffered "PORTAL EMAIL|send-summary|CallSummaryEmail|Resend|portal\.auth|VerifyCsrfToken" | while IFS= read -r line; do
    echo "[$(date '+%H:%M:%S')] $line"
done