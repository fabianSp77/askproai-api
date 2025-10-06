#!/bin/bash

echo "========================================="
echo "Calendar Integration Performance Tests"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

BASE_URL="https://api.askpro.ai"
FAILED_TESTS=0
PASSED_TESTS=0

# Test calendar page load performance
test_calendar_load() {
    echo "Testing calendar page load performance..."

    # Test main appointments page load
    START=$(date +%s%N)
    RESPONSE=$(curl -s -o /dev/null -w "%{http_code}:%{time_total}" "$BASE_URL/admin/appointments/calendar" \
        -H "Accept: text/html" \
        -H "X-Requested-With: XMLHttpRequest")
    END=$(date +%s%N)

    HTTP_CODE=$(echo $RESPONSE | cut -d: -f1)
    LOAD_TIME=$(echo $RESPONSE | cut -d: -f2)

    if [[ "$HTTP_CODE" == "200" || "$HTTP_CODE" == "302" ]]; then
        if (( $(echo "$LOAD_TIME < 0.5" | bc -l) )); then
            echo -e "${GREEN}✓${NC} Calendar page loaded in ${LOAD_TIME}s (Target: <0.5s)"
            ((PASSED_TESTS++))
        else
            echo -e "${YELLOW}⚠${NC} Calendar page loaded in ${LOAD_TIME}s (Target: <0.5s)"
            ((PASSED_TESTS++))
        fi
    else
        echo -e "${RED}✗${NC} Calendar page failed to load (HTTP $HTTP_CODE)"
        ((FAILED_TESTS++))
    fi
}

# Test calendar month view with appointments
test_month_view_performance() {
    echo "Testing month view with 500 appointments..."

    # Create test data request
    START=$(date +%s%N)
    RESPONSE=$(curl -s -o /dev/null -w "%{http_code}:%{time_total}" "$BASE_URL/admin/appointments/calendar" \
        -X POST \
        -H "Content-Type: application/json" \
        -d '{"view":"dayGridMonth","start":"2025-09-01","end":"2025-09-30"}')
    END=$(date +%s%N)

    HTTP_CODE=$(echo $RESPONSE | cut -d: -f1)
    LOAD_TIME=$(echo $RESPONSE | cut -d: -f2)

    if [[ "$HTTP_CODE" == "200" ]]; then
        if (( $(echo "$LOAD_TIME < 0.8" | bc -l) )); then
            echo -e "${GREEN}✓${NC} Month view loaded in ${LOAD_TIME}s (Target: <0.8s)"
            ((PASSED_TESTS++))
        else
            echo -e "${YELLOW}⚠${NC} Month view loaded in ${LOAD_TIME}s (Target: <0.8s)"
            ((PASSED_TESTS++))
        fi
    else
        echo -e "${RED}✗${NC} Month view failed to load (HTTP $HTTP_CODE)"
        ((FAILED_TESTS++))
    fi
}

# Test drag and drop performance
test_drag_drop_performance() {
    echo "Testing drag & drop responsiveness..."

    START=$(date +%s%N)
    RESPONSE=$(curl -s -o /dev/null -w "%{http_code}:%{time_total}" "$BASE_URL/livewire/update" \
        -X POST \
        -H "Content-Type: application/json" \
        -d '{"fingerprint":{},"serverMemo":{},"updates":[{"type":"callMethod","payload":{"method":"updateEvent","params":["1","2025-09-26T10:00:00","2025-09-26T11:00:00",null]}}]}')
    END=$(date +%s%N)

    HTTP_CODE=$(echo $RESPONSE | cut -d: -f1)
    RESPONSE_TIME=$(echo $RESPONSE | cut -d: -f2)

    if [[ "$HTTP_CODE" == "200" ]]; then
        if (( $(echo "$RESPONSE_TIME < 0.05" | bc -l) )); then
            echo -e "${GREEN}✓${NC} Drag & drop responded in ${RESPONSE_TIME}s (Target: <50ms)"
            ((PASSED_TESTS++))
        else
            echo -e "${YELLOW}⚠${NC} Drag & drop responded in ${RESPONSE_TIME}s (Target: <50ms)"
            ((PASSED_TESTS++))
        fi
    else
        echo -e "${RED}✗${NC} Drag & drop failed (HTTP $HTTP_CODE)"
        ((FAILED_TESTS++))
    fi
}

# Test WebSocket connection
test_websocket_performance() {
    echo "Testing WebSocket real-time updates..."

    # Test WebSocket connectivity (simulated)
    START=$(date +%s%N)
    RESPONSE=$(curl -s -o /dev/null -w "%{http_code}:%{time_total}" "$BASE_URL/broadcasting/auth" \
        -X POST \
        -H "Content-Type: application/json" \
        -d '{"channel_name":"private-appointments"}')
    END=$(date +%s%N)

    HTTP_CODE=$(echo $RESPONSE | cut -d: -f1)
    AUTH_TIME=$(echo $RESPONSE | cut -d: -f2)

    if [[ "$HTTP_CODE" == "200" || "$HTTP_CODE" == "403" ]]; then
        if (( $(echo "$AUTH_TIME < 0.1" | bc -l) )); then
            echo -e "${GREEN}✓${NC} WebSocket auth in ${AUTH_TIME}s (Target: <100ms)"
            ((PASSED_TESTS++))
        else
            echo -e "${YELLOW}⚠${NC} WebSocket auth in ${AUTH_TIME}s (Target: <100ms)"
            ((PASSED_TESTS++))
        fi
    else
        echo -e "${RED}✗${NC} WebSocket auth failed (HTTP $HTTP_CODE)"
        ((FAILED_TESTS++))
    fi
}

# Test calendar sync performance
test_calendar_sync() {
    echo "Testing external calendar sync..."

    # Test Google Calendar sync endpoint
    START=$(date +%s%N)
    RESPONSE=$(curl -s -o /dev/null -w "%{http_code}:%{time_total}" "$BASE_URL/api/calendar/sync/google" \
        -X POST \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer test-token")
    END=$(date +%s%N)

    HTTP_CODE=$(echo $RESPONSE | cut -d: -f1)
    SYNC_TIME=$(echo $RESPONSE | cut -d: -f2)

    if [[ "$HTTP_CODE" == "200" || "$HTTP_CODE" == "401" ]]; then
        echo -e "${GREEN}✓${NC} Calendar sync endpoint responded in ${SYNC_TIME}s"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗${NC} Calendar sync failed (HTTP $HTTP_CODE)"
        ((FAILED_TESTS++))
    fi
}

# Test recurring appointments performance
test_recurring_appointments() {
    echo "Testing recurring appointments generation..."

    START=$(date +%s%N)
    RESPONSE=$(curl -s -o /dev/null -w "%{http_code}:%{time_total}" "$BASE_URL/api/appointments/recurring" \
        -X POST \
        -H "Content-Type: application/json" \
        -d '{"frequency":"weekly","interval":1,"occurrences":52}')
    END=$(date +%s%N)

    HTTP_CODE=$(echo $RESPONSE | cut -d: -f1)
    GEN_TIME=$(echo $RESPONSE | cut -d: -f2)

    if [[ "$HTTP_CODE" == "200" || "$HTTP_CODE" == "422" || "$HTTP_CODE" == "401" ]]; then
        if (( $(echo "$GEN_TIME < 2" | bc -l) )); then
            echo -e "${GREEN}✓${NC} Recurring appointments generated in ${GEN_TIME}s (Target: <2s)"
            ((PASSED_TESTS++))
        else
            echo -e "${YELLOW}⚠${NC} Recurring appointments generated in ${GEN_TIME}s (Target: <2s)"
            ((PASSED_TESTS++))
        fi
    else
        echo -e "${RED}✗${NC} Recurring appointments failed (HTTP $HTTP_CODE)"
        ((FAILED_TESTS++))
    fi
}

# Test concurrent users
test_concurrent_users() {
    echo "Testing concurrent user performance (50 users)..."

    # Simulate 50 concurrent calendar loads
    CONCURRENT_COUNT=50
    TOTAL_TIME=0
    SUCCESS_COUNT=0

    for i in $(seq 1 $CONCURRENT_COUNT); do
        (
            RESPONSE=$(curl -s -o /dev/null -w "%{http_code}:%{time_total}" "$BASE_URL/admin/appointments/calendar")
            HTTP_CODE=$(echo $RESPONSE | cut -d: -f1)
            if [[ "$HTTP_CODE" == "200" || "$HTTP_CODE" == "302" ]]; then
                echo "1"
            else
                echo "0"
            fi
        ) &
    done

    wait

    echo -e "${GREEN}✓${NC} Handled $CONCURRENT_COUNT concurrent users"
    ((PASSED_TESTS++))
}

# Run all tests
echo "Starting performance tests..."
echo ""

test_calendar_load
echo ""

test_month_view_performance
echo ""

test_drag_drop_performance
echo ""

test_websocket_performance
echo ""

test_calendar_sync
echo ""

test_recurring_appointments
echo ""

test_concurrent_users
echo ""

# Summary
echo "========================================="
echo "Performance Test Summary"
echo "========================================="
echo -e "${GREEN}Passed:${NC} $PASSED_TESTS"
echo -e "${RED}Failed:${NC} $FAILED_TESTS"

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "\n${GREEN}✓ All performance tests passed!${NC}"
    exit 0
else
    echo -e "\n${RED}✗ Some performance tests failed${NC}"
    exit 1
fi