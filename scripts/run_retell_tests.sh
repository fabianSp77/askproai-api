#!/bin/bash

#############################################
# Retell Integration Automated Test Runner
# Runs comprehensive tests for the entire
# Retell call and appointment system
#############################################

# Configuration
BASE_DIR="/var/www/api-gateway"
LOG_DIR="$BASE_DIR/storage/logs/tests"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
LOG_FILE="$LOG_DIR/retell_test_${TIMESTAMP}.log"
REPORT_FILE="$LOG_DIR/test_report_${TIMESTAMP}.html"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Create log directory if it doesn't exist
mkdir -p "$LOG_DIR"

# Functions
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

run_test() {
    local test_name=$1
    local command=$2

    echo -e "\n${YELLOW}Running: $test_name${NC}" | tee -a "$LOG_FILE"

    if eval "$command" >> "$LOG_FILE" 2>&1; then
        echo -e "${GREEN}✅ $test_name: PASSED${NC}" | tee -a "$LOG_FILE"
        return 0
    else
        echo -e "${RED}❌ $test_name: FAILED${NC}" | tee -a "$LOG_FILE"
        return 1
    fi
}

# Header
clear
echo "========================================="
echo "  RETELL INTEGRATION TEST SUITE"
echo "  Time: $(date +'%Y-%m-%d %H:%M:%S')"
echo "========================================="
echo ""

log "Starting Retell Integration Test Suite"

# Change to project directory
cd "$BASE_DIR"

# Test Results Tracking
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# 1. Unit Tests
log "Phase 1: Running Unit Tests"
if run_test "Unit Tests" "php artisan test --testsuite=Unit --filter=AppointmentBooking 2>&1"; then
    ((PASSED_TESTS++))
else
    ((FAILED_TESTS++))
fi
((TOTAL_TESTS++))

# 2. Database Connection Test
log "Phase 2: Testing Database Connection"
if run_test "Database Connection" "php artisan tinker --execute=\"DB::connection()->getPdo();\""; then
    ((PASSED_TESTS++))
else
    ((FAILED_TESTS++))
fi
((TOTAL_TESTS++))

# 3. Webhook Endpoint Test
log "Phase 3: Testing Webhook Endpoints"
if run_test "Webhook Test" "php artisan retell:test --scenario=webhook --validate"; then
    ((PASSED_TESTS++))
else
    ((FAILED_TESTS++))
fi
((TOTAL_TESTS++))

# 4. Availability Check Test
log "Phase 4: Testing Availability Check"
if run_test "Availability Check" "php artisan retell:test --scenario=availability"; then
    ((PASSED_TESTS++))
else
    ((FAILED_TESTS++))
fi
((TOTAL_TESTS++))

# 5. Appointment Booking Test
log "Phase 5: Testing Appointment Booking"
if run_test "Appointment Booking" "php artisan retell:test --scenario=appointment"; then
    ((PASSED_TESTS++))
else
    ((FAILED_TESTS++))
fi
((TOTAL_TESTS++))

# 6. Complete Flow Test (with cleanup)
log "Phase 6: Testing Complete Call Flow"
if run_test "Complete Flow" "php artisan retell:test --scenario=complete --validate --cleanup"; then
    ((PASSED_TESTS++))
else
    ((FAILED_TESTS++))
fi
((TOTAL_TESTS++))

# 7. Health Check
log "Phase 7: System Health Check"
HEALTH_CHECK=$(curl -s https://api.askproai.de/api/health | jq -r '.status' 2>/dev/null)
if [ "$HEALTH_CHECK" == "healthy" ] || [ "$HEALTH_CHECK" == "ok" ]; then
    echo -e "${GREEN}✅ Health Check: PASSED${NC}" | tee -a "$LOG_FILE"
    ((PASSED_TESTS++))
else
    echo -e "${RED}❌ Health Check: FAILED${NC}" | tee -a "$LOG_FILE"
    ((FAILED_TESTS++))
fi
((TOTAL_TESTS++))

# 8. Cal.com Connection Test
log "Phase 8: Testing Cal.com Connection"
CAL_STATUS=$(curl -s https://api.askproai.de/api/health/calcom | jq -r '.status' 2>/dev/null)
if [ "$CAL_STATUS" == "healthy" ] || [ "$CAL_STATUS" == "ok" ]; then
    echo -e "${GREEN}✅ Cal.com Connection: PASSED${NC}" | tee -a "$LOG_FILE"
    ((PASSED_TESTS++))
else
    echo -e "${YELLOW}⚠️ Cal.com Connection: WARNING${NC}" | tee -a "$LOG_FILE"
    # Don't count as failed, just warning
fi
((TOTAL_TESTS++))

# 9. Cron Job Check
log "Phase 9: Checking Cron Jobs"
CRON_CHECK=$(crontab -l 2>/dev/null | grep -c "auto_import_calls.sh")
if [ "$CRON_CHECK" -gt 0 ]; then
    echo -e "${GREEN}✅ Cron Jobs: ACTIVE${NC}" | tee -a "$LOG_FILE"
    ((PASSED_TESTS++))
else
    echo -e "${YELLOW}⚠️ Cron Jobs: NOT FOUND${NC}" | tee -a "$LOG_FILE"
    ((FAILED_TESTS++))
fi
((TOTAL_TESTS++))

# 10. Recent Call Import Check
log "Phase 10: Checking Recent Call Imports"
RECENT_CALLS=$(mysql -u root -pMystery2024! -D api_gateway -e "SELECT COUNT(*) as count FROM calls WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);" -s -N 2>/dev/null)
if [ "$RECENT_CALLS" -ge 0 ]; then
    echo -e "${GREEN}✅ Recent Calls: $RECENT_CALLS in last hour${NC}" | tee -a "$LOG_FILE"
    ((PASSED_TESTS++))
else
    echo -e "${RED}❌ Database Query: FAILED${NC}" | tee -a "$LOG_FILE"
    ((FAILED_TESTS++))
fi
((TOTAL_TESTS++))

# Generate Summary
echo ""
echo "=========================================" | tee -a "$LOG_FILE"
echo "           TEST SUMMARY" | tee -a "$LOG_FILE"
echo "=========================================" | tee -a "$LOG_FILE"
echo "Total Tests:  $TOTAL_TESTS" | tee -a "$LOG_FILE"
echo -e "${GREEN}Passed:       $PASSED_TESTS${NC}" | tee -a "$LOG_FILE"
echo -e "${RED}Failed:       $FAILED_TESTS${NC}" | tee -a "$LOG_FILE"

# Calculate pass rate
if [ $TOTAL_TESTS -gt 0 ]; then
    PASS_RATE=$(echo "scale=2; $PASSED_TESTS * 100 / $TOTAL_TESTS" | bc)
    echo "Pass Rate:    ${PASS_RATE}%" | tee -a "$LOG_FILE"
fi

echo "=========================================" | tee -a "$LOG_FILE"

# Overall status
if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "\n${GREEN}✅ ALL TESTS PASSED! System is operational.${NC}" | tee -a "$LOG_FILE"
    EXIT_CODE=0
elif [ $FAILED_TESTS -le 2 ]; then
    echo -e "\n${YELLOW}⚠️ MINOR ISSUES DETECTED. System mostly operational.${NC}" | tee -a "$LOG_FILE"
    EXIT_CODE=1
else
    echo -e "\n${RED}❌ CRITICAL ISSUES DETECTED! Immediate attention required.${NC}" | tee -a "$LOG_FILE"
    EXIT_CODE=2
fi

# Generate HTML Report
cat > "$REPORT_FILE" << EOF
<!DOCTYPE html>
<html>
<head>
    <title>Retell Integration Test Report - $TIMESTAMP</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        .summary { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .passed { color: green; font-weight: bold; }
        .failed { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; }
        .status-pass { background: #d4edda; }
        .status-fail { background: #f8d7da; }
        .timestamp { color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Retell Integration Test Report</h1>
        <p class="timestamp">Generated: $(date +'%Y-%m-%d %H:%M:%S')</p>

        <div class="summary">
            <h2>Summary</h2>
            <p>Total Tests: <strong>$TOTAL_TESTS</strong></p>
            <p>Passed: <span class="passed">$PASSED_TESTS</span></p>
            <p>Failed: <span class="failed">$FAILED_TESTS</span></p>
            <p>Pass Rate: <strong>${PASS_RATE}%</strong></p>
        </div>

        <h2>Test Results</h2>
        <table>
            <tr>
                <th>Test</th>
                <th>Status</th>
                <th>Time</th>
            </tr>
EOF

# Add test results to HTML (would need to track individually)
echo "        </table>" >> "$REPORT_FILE"
echo "        <p>Full log: <a href=\"retell_test_${TIMESTAMP}.log\">View Log</a></p>" >> "$REPORT_FILE"
echo "    </div>" >> "$REPORT_FILE"
echo "</body>" >> "$REPORT_FILE"
echo "</html>" >> "$REPORT_FILE"

log "Test suite completed. Report saved to: $REPORT_FILE"
log "Exit code: $EXIT_CODE"

# Send alert if critical failures
if [ $EXIT_CODE -eq 2 ]; then
    # You could add email/slack notification here
    log "ALERT: Critical test failures detected!"
fi

exit $EXIT_CODE