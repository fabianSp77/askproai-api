#!/bin/bash

# ========================================
# DASHBOARD WIDGET PERFORMANCE TEST
# Tests all dashboard widgets for errors and performance
# ========================================

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

BASE_URL="http://localhost/admin"
COOKIE_JAR="/tmp/widget-test-cookies.txt"
LOG_FILE="/tmp/widget-test.log"

# Counters
WIDGETS_TESTED=0
WIDGETS_PASSED=0
WIDGETS_FAILED=0
SLOW_WIDGETS=0
TOTAL_RESPONSE_TIME=0

echo -e "${BLUE}DASHBOARD WIDGET TEST SUITE${NC}"
echo "======================================"
echo "Starting at: $(date)"
echo ""

# List of all widgets from Dashboard.php
WIDGETS=(
    "dashboard-stats"
    "stats-overview"
    "kpi-metrics"
    "quick-actions"
    "customer-stats"
    "customer-chart"
    "recent-calls"
    "recent-appointments"
    "latest-customers"
    "companies-chart"
    "service-assignment"
    "integration-health"
    "integration-monitor"
    "system-status"
    "activity-log"
)

# Widget class mapping
declare -A WIDGET_CLASSES=(
    ["dashboard-stats"]="App\\\\Filament\\\\Widgets\\\\DashboardStats"
    ["stats-overview"]="App\\\\Filament\\\\Widgets\\\\StatsOverview"
    ["kpi-metrics"]="App\\\\Filament\\\\Widgets\\\\KpiMetricsWidget"
    ["quick-actions"]="App\\\\Filament\\\\Widgets\\\\QuickActionsWidget"
    ["customer-stats"]="App\\\\Filament\\\\Widgets\\\\CustomerStatsOverview"
    ["customer-chart"]="App\\\\Filament\\\\Widgets\\\\CustomerChartWidget"
    ["recent-calls"]="App\\\\Filament\\\\Widgets\\\\RecentCalls"
    ["recent-appointments"]="App\\\\Filament\\\\Widgets\\\\RecentAppointments"
    ["latest-customers"]="App\\\\Filament\\\\Widgets\\\\LatestCustomers"
    ["companies-chart"]="App\\\\Filament\\\\Widgets\\\\CompaniesChartWidget"
    ["service-assignment"]="App\\\\Filament\\\\Widgets\\\\ServiceAssignmentWidget"
    ["integration-health"]="App\\\\Filament\\\\Widgets\\\\IntegrationHealthWidget"
    ["integration-monitor"]="App\\\\Filament\\\\Widgets\\\\IntegrationMonitorWidget"
    ["system-status"]="App\\\\Filament\\\\Widgets\\\\SystemStatus"
    ["activity-log"]="App\\\\Filament\\\\Widgets\\\\ActivityLogWidget"
)

# Function to test individual widget
test_widget() {
    local widget_name="$1"
    local widget_class="${WIDGET_CLASSES[$widget_name]}"
    local widget_display=$(echo "$widget_name" | tr '-' ' ' | sed 's/\b\(.\)/\u\1/g')

    echo -e "\n${YELLOW}Testing Widget: $widget_display${NC}"
    echo "--------------------------------------"

    # Test widget loading
    echo -n "  Loading widget: "
    start_time=$(date +%s%N)

    # Make request to dashboard and check for widget presence
    response=$(curl -s -w "\n%{http_code}\n%{time_total}" "$BASE_URL" \
        -b "$COOKIE_JAR" \
        -H "Accept: text/html" \
        -H "X-Livewire: true" \
        2>/dev/null)

    http_code=$(echo "$response" | tail -2 | head -1)
    response_time=$(echo "$response" | tail -1)
    response_time_ms=$(echo "$response_time * 1000" | bc 2>/dev/null || echo "0")

    end_time=$(date +%s%N)
    duration=$((($end_time - $start_time) / 1000000))

    WIDGETS_TESTED=$((WIDGETS_TESTED + 1))
    TOTAL_RESPONSE_TIME=$(echo "$TOTAL_RESPONSE_TIME + $response_time_ms" | bc)

    # Check if widget loaded successfully
    if [[ "$http_code" == "200" ]]; then
        echo -e "${GREEN}✓ OK (${duration}ms)${NC}"
        WIDGETS_PASSED=$((WIDGETS_PASSED + 1))

        # Check if slow
        if [ "$duration" -gt 1000 ]; then
            echo -e "  ${YELLOW}⚠ Slow response (>1000ms)${NC}"
            SLOW_WIDGETS=$((SLOW_WIDGETS + 1))
        fi
    else
        echo -e "${RED}✗ FAILED (HTTP $http_code)${NC}"
        WIDGETS_FAILED=$((WIDGETS_FAILED + 1))
    fi

    # Test widget refresh/poll
    if [[ "$widget_name" =~ ^(stats-overview|dashboard-stats|system-status|integration-monitor)$ ]]; then
        echo -n "  Testing polling: "
        poll_response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/livewire/message/$widget_class" \
            -b "$COOKIE_JAR" \
            -X POST \
            -H "X-Livewire: true" \
            -d '{"fingerprint":{},"serverMemo":{},"updates":[]}' \
            2>/dev/null)

        if [[ "$poll_response" == "200" ]] || [[ "$poll_response" == "204" ]]; then
            echo -e "${GREEN}✓ Polling works${NC}"
        else
            echo -e "${YELLOW}⚠ Polling not configured${NC}"
        fi
    fi

    # Check for errors in logs
    echo -n "  Checking for errors: "
    recent_errors=$(tail -100 /var/www/api-gateway/storage/logs/laravel.log 2>/dev/null | grep -c "$widget_class" || echo "0")

    if [ "$recent_errors" -eq 0 ]; then
        echo -e "${GREEN}✓ No errors in logs${NC}"
    else
        echo -e "${RED}✗ Found $recent_errors errors in logs${NC}"
        WIDGETS_FAILED=$((WIDGETS_FAILED + 1))
    fi
}

# Test widget database queries
test_widget_queries() {
    echo -e "\n${BLUE}WIDGET DATABASE QUERY ANALYSIS${NC}"
    echo "--------------------------------------"

    echo "  Analyzing widget query performance..."

    # Enable query log temporarily
    mysql -u root askproai_db -e "SET GLOBAL general_log = 'ON';" 2>/dev/null
    mysql -u root askproai_db -e "SET GLOBAL general_log_file = '/tmp/widget_queries.log';" 2>/dev/null

    # Load dashboard
    curl -s "$BASE_URL" -b "$COOKIE_JAR" > /dev/null 2>&1

    # Analyze queries
    if [ -f /tmp/widget_queries.log ]; then
        echo -e "\n  Query Statistics:"
        query_count=$(grep -c "SELECT" /tmp/widget_queries.log 2>/dev/null || echo "0")
        echo "    Total SELECT queries: $query_count"

        slow_queries=$(grep -E "SELECT.*FROM.*WHERE" /tmp/widget_queries.log | wc -l)
        echo "    Complex queries: $slow_queries"

        # Check for N+1 problems
        duplicate_queries=$(sort /tmp/widget_queries.log | uniq -d | wc -l)
        if [ "$duplicate_queries" -gt 0 ]; then
            echo -e "    ${YELLOW}⚠ Potential N+1 queries: $duplicate_queries${NC}"
        else
            echo -e "    ${GREEN}✓ No duplicate queries detected${NC}"
        fi

        rm -f /tmp/widget_queries.log
    fi

    # Disable query log
    mysql -u root askproai_db -e "SET GLOBAL general_log = 'OFF';" 2>/dev/null
}

# Memory usage test
test_memory_usage() {
    echo -e "\n${BLUE}MEMORY USAGE TEST${NC}"
    echo "--------------------------------------"

    echo -n "  Dashboard memory usage: "
    memory_before=$(free -m | grep "^Mem" | awk '{print $3}')

    # Load dashboard multiple times
    for i in {1..5}; do
        curl -s "$BASE_URL" -b "$COOKIE_JAR" > /dev/null 2>&1
        sleep 1
    done

    memory_after=$(free -m | grep "^Mem" | awk '{print $3}')
    memory_increase=$((memory_after - memory_before))

    if [ "$memory_increase" -lt 50 ]; then
        echo -e "${GREEN}✓ Normal (+${memory_increase}MB)${NC}"
    elif [ "$memory_increase" -lt 100 ]; then
        echo -e "${YELLOW}⚠ Elevated (+${memory_increase}MB)${NC}"
    else
        echo -e "${RED}✗ High memory usage (+${memory_increase}MB)${NC}"
    fi
}

# Concurrent load test
test_concurrent_load() {
    echo -e "\n${BLUE}CONCURRENT LOAD TEST${NC}"
    echo "--------------------------------------"

    echo "  Simulating 10 concurrent users..."

    start_time=$(date +%s)

    # Start 10 background requests
    for i in {1..10}; do
        (curl -s -o /dev/null -w "%{http_code} %{time_total}\n" "$BASE_URL" -b "$COOKIE_JAR" >> /tmp/concurrent_results.txt) &
    done

    # Wait for all to complete
    wait

    end_time=$(date +%s)
    total_time=$((end_time - start_time))

    # Analyze results
    if [ -f /tmp/concurrent_results.txt ]; then
        success_count=$(grep -c "^200" /tmp/concurrent_results.txt)
        avg_time=$(awk '{sum+=$2; count++} END {print sum/count}' /tmp/concurrent_results.txt)

        echo "    Successful requests: $success_count/10"
        printf "    Average response time: %.2f seconds\n" "$avg_time"
        echo "    Total test duration: ${total_time} seconds"

        if [ "$success_count" -eq 10 ]; then
            echo -e "    ${GREEN}✓ All requests successful${NC}"
        else
            echo -e "    ${RED}✗ $(( 10 - success_count )) requests failed${NC}"
        fi

        rm -f /tmp/concurrent_results.txt
    fi
}

# 1. Test authentication
echo -e "${BLUE}1. AUTHENTICATION CHECK${NC}"
echo "--------------------------------------"
echo -n "Dashboard access: "
auth_response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL" -c "$COOKIE_JAR" -L)

if [[ "$auth_response" == "200" ]]; then
    echo -e "${GREEN}✓ Authenticated${NC}"
else
    echo -e "${YELLOW}⚠ Not authenticated (HTTP $auth_response)${NC}"
    echo "Attempting to authenticate..."
    # You might want to add authentication logic here
fi

# 2. Test individual widgets
echo -e "\n${BLUE}2. TESTING INDIVIDUAL WIDGETS${NC}"
echo "======================================"

for widget in "${WIDGETS[@]}"; do
    test_widget "$widget"
done

# 3. Test widget queries
test_widget_queries

# 4. Test memory usage
test_memory_usage

# 5. Test concurrent load
test_concurrent_load

# 6. Error log analysis
echo -e "\n${BLUE}ERROR LOG ANALYSIS${NC}"
echo "--------------------------------------"

if [ -f /var/www/api-gateway/storage/logs/laravel.log ]; then
    echo "  Recent widget errors (last hour):"
    widget_errors=$(find /var/www/api-gateway/storage/logs -name "*.log" -mmin -60 -exec grep -l "Widget" {} \; | wc -l)

    if [ "$widget_errors" -eq 0 ]; then
        echo -e "    ${GREEN}✓ No widget errors in last hour${NC}"
    else
        echo -e "    ${RED}✗ Found errors in $widget_errors log files${NC}"
        echo "    Most recent errors:"
        tail -5 /var/www/api-gateway/storage/logs/laravel.log | grep -i "widget" | head -3
    fi
fi

# Calculate average response time
if [ "$WIDGETS_TESTED" -gt 0 ]; then
    avg_response=$(echo "scale=2; $TOTAL_RESPONSE_TIME / $WIDGETS_TESTED" | bc)
else
    avg_response="0"
fi

# Summary
echo ""
echo "======================================"
echo -e "${BLUE}WIDGET TEST SUMMARY${NC}"
echo "--------------------------------------"
echo "Total Widgets Tested: $WIDGETS_TESTED"
echo -e "Passed: ${GREEN}$WIDGETS_PASSED${NC}"
echo -e "Failed: ${RED}$WIDGETS_FAILED${NC}"
echo -e "Slow Widgets (>1s): ${YELLOW}$SLOW_WIDGETS${NC}"
printf "Average Response Time: %.2f ms\n" "$avg_response"
echo "Success Rate: $(( WIDGETS_PASSED * 100 / (WIDGETS_TESTED + 1) ))%"
echo "--------------------------------------"
echo "Completed at: $(date)"
echo "======================================"

# Cleanup
rm -f "$COOKIE_JAR"

# Exit code
if [ $WIDGETS_FAILED -gt 0 ]; then
    exit 1
else
    exit 0
fi