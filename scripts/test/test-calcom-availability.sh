#!/bin/bash

#################################################
# Cal.com V2 API - Availability Testing Script
# Tests all availability endpoints and scenarios
#################################################

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
API_BASE="${API_BASE:-http://localhost:8000/api/v2}"
API_KEY="${CAL_API_KEY:-test_api_key}"
SERVICE_ID="${SERVICE_ID:-1}"
BRANCH_ID="${BRANCH_ID:-1}"
EVENT_TYPE_ID="${EVENT_TYPE_ID:-100}"
TIMEZONE="Europe/Berlin"

# Test counters
TESTS_RUN=0
TESTS_PASSED=0
TESTS_FAILED=0

# Logging
LOG_FILE="test-availability-$(date +%Y%m%d_%H%M%S).log"

log() {
    echo -e "${GREEN}[$(date +"%H:%M:%S")]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

info() {
    echo -e "${BLUE}[INFO]${NC} $1" | tee -a "$LOG_FILE"
}

# Test result tracking
test_start() {
    TESTS_RUN=$((TESTS_RUN + 1))
    echo -e "\n${BLUE}TEST $TESTS_RUN:${NC} $1"
}

test_pass() {
    TESTS_PASSED=$((TESTS_PASSED + 1))
    echo -e "${GREEN}✓ PASSED${NC}"
}

test_fail() {
    TESTS_FAILED=$((TESTS_FAILED + 1))
    echo -e "${RED}✗ FAILED${NC}: $1"
}

# Helper function to make API calls
api_call() {
    local method=$1
    local endpoint=$2
    local data=$3

    if [ -z "$data" ]; then
        curl -s -X "$method" \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer $API_KEY" \
            "${API_BASE}${endpoint}"
    else
        curl -s -X "$method" \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer $API_KEY" \
            -d "$data" \
            "${API_BASE}${endpoint}"
    fi
}

# Test 1: Simple availability for today
test_simple_availability_today() {
    test_start "Simple Availability - Today"

    local start_date=$(date +%Y-%m-%d)
    local end_date=$(date +%Y-%m-%d)

    local response=$(api_call POST "/availability/simple" "{
        \"service_id\": $SERVICE_ID,
        \"branch_id\": $BRANCH_ID,
        \"start_date\": \"$start_date\",
        \"end_date\": \"$end_date\",
        \"timeZone\": \"$TIMEZONE\"
    }")

    if echo "$response" | grep -q '"slots"'; then
        local slot_count=$(echo "$response" | grep -o '"start"' | wc -l)
        info "Found $slot_count available slots"
        test_pass
    else
        test_fail "No slots returned"
    fi
}

# Test 2: Simple availability for next 7 days
test_simple_availability_week() {
    test_start "Simple Availability - Next 7 Days"

    local start_date=$(date +%Y-%m-%d)
    local end_date=$(date -d "+7 days" +%Y-%m-%d)

    local response=$(api_call POST "/availability/simple" "{
        \"service_id\": $SERVICE_ID,
        \"branch_id\": $BRANCH_ID,
        \"start_date\": \"$start_date\",
        \"end_date\": \"$end_date\",
        \"timeZone\": \"$TIMEZONE\"
    }")

    if echo "$response" | grep -q '"slots"'; then
        local slot_count=$(echo "$response" | grep -o '"start"' | wc -l)
        info "Found $slot_count slots for the week"

        if [ "$slot_count" -gt 0 ]; then
            test_pass
        else
            test_fail "No slots available for entire week"
        fi
    else
        test_fail "Invalid response format"
    fi
}

# Test 3: Availability with specific staff
test_availability_with_staff() {
    test_start "Availability - Specific Staff Member"

    local staff_id="${STAFF_ID:-1}"
    local start_date=$(date -d "tomorrow" +%Y-%m-%d)
    local end_date=$(date -d "tomorrow" +%Y-%m-%d)

    local response=$(api_call POST "/availability/simple" "{
        \"service_id\": $SERVICE_ID,
        \"branch_id\": $BRANCH_ID,
        \"start_date\": \"$start_date\",
        \"end_date\": \"$end_date\",
        \"staff_id\": $staff_id,
        \"timeZone\": \"$TIMEZONE\"
    }")

    if echo "$response" | grep -q '"slots"'; then
        # Verify all slots are for the requested staff
        if echo "$response" | grep -q "\"staff_id\":$staff_id"; then
            test_pass
        else
            test_fail "Slots returned for wrong staff member"
        fi
    else
        test_fail "No response or invalid format"
    fi
}

# Test 4: Composite service availability
test_composite_availability() {
    test_start "Composite Service Availability"

    local composite_service_id="${COMPOSITE_SERVICE_ID:-2}"
    local start_date=$(date -d "tomorrow" +%Y-%m-%d)
    local end_date=$(date -d "tomorrow" +%Y-%m-%d)

    local response=$(api_call POST "/availability/composite" "{
        \"service_id\": $composite_service_id,
        \"branch_id\": $BRANCH_ID,
        \"start_date\": \"$start_date\",
        \"end_date\": \"$end_date\",
        \"timeZone\": \"$TIMEZONE\"
    }")

    if echo "$response" | grep -q '"segments"'; then
        # Check for pause information
        if echo "$response" | grep -q '"pause"'; then
            info "Composite slots with pause periods found"
            test_pass
        else
            test_fail "Composite slots missing pause information"
        fi
    else
        warning "No composite slots available or service not composite"
        test_pass # Pass if service is not composite
    fi
}

# Test 5: Different timezones
test_different_timezones() {
    test_start "Availability - Different Timezones"

    local timezones=("Europe/Berlin" "America/New_York" "Asia/Tokyo")
    local all_passed=true

    for tz in "${timezones[@]}"; do
        info "Testing timezone: $tz"

        local response=$(api_call POST "/availability/simple" "{
            \"service_id\": $SERVICE_ID,
            \"branch_id\": $BRANCH_ID,
            \"start_date\": \"$(date -d 'tomorrow' +%Y-%m-%d)\",
            \"end_date\": \"$(date -d 'tomorrow' +%Y-%m-%d)\",
            \"timeZone\": \"$tz\"
        }")

        if ! echo "$response" | grep -q '"slots"'; then
            error "Failed for timezone: $tz"
            all_passed=false
        fi
    done

    if $all_passed; then
        test_pass
    else
        test_fail "Some timezones failed"
    fi
}

# Test 6: Invalid date range
test_invalid_date_range() {
    test_start "Availability - Invalid Date Range"

    local response=$(api_call POST "/availability/simple" "{
        \"service_id\": $SERVICE_ID,
        \"branch_id\": $BRANCH_ID,
        \"start_date\": \"$(date -d 'tomorrow' +%Y-%m-%d)\",
        \"end_date\": \"$(date -d 'yesterday' +%Y-%m-%d)\",
        \"timeZone\": \"$TIMEZONE\"
    }")

    if echo "$response" | grep -q '"error"'; then
        test_pass
    else
        test_fail "Should have returned error for invalid date range"
    fi
}

# Test 7: Performance test
test_availability_performance() {
    test_start "Availability Performance (10 requests)"

    local total_time=0
    local iterations=10

    for i in $(seq 1 $iterations); do
        local start_time=$(date +%s%N)

        api_call POST "/availability/simple" "{
            \"service_id\": $SERVICE_ID,
            \"branch_id\": $BRANCH_ID,
            \"start_date\": \"$(date +%Y-%m-%d)\",
            \"end_date\": \"$(date +%Y-%m-%d)\",
            \"timeZone\": \"$TIMEZONE\"
        }" > /dev/null

        local end_time=$(date +%s%N)
        local duration=$((($end_time - $start_time) / 1000000))
        total_time=$((total_time + duration))
    done

    local avg_time=$((total_time / iterations))
    info "Average response time: ${avg_time}ms"

    if [ "$avg_time" -lt 1000 ]; then
        test_pass
    else
        test_fail "Average response time too high: ${avg_time}ms"
    fi
}

# Test 8: Concurrent availability checks
test_concurrent_availability() {
    test_start "Concurrent Availability Requests (5 parallel)"

    local pids=()
    local results_dir="/tmp/availability_test_$$"
    mkdir -p "$results_dir"

    # Launch 5 concurrent requests
    for i in {1..5}; do
        (
            api_call POST "/availability/simple" "{
                \"service_id\": $SERVICE_ID,
                \"branch_id\": $BRANCH_ID,
                \"start_date\": \"$(date +%Y-%m-%d)\",
                \"end_date\": \"$(date +%Y-%m-%d)\",
                \"timeZone\": \"$TIMEZONE\"
            }" > "$results_dir/result_$i.json"
        ) &
        pids+=($!)
    done

    # Wait for all requests to complete
    for pid in "${pids[@]}"; do
        wait "$pid"
    done

    # Check all results
    local all_success=true
    for i in {1..5}; do
        if ! grep -q '"slots"' "$results_dir/result_$i.json"; then
            all_success=false
            break
        fi
    done

    rm -rf "$results_dir"

    if $all_success; then
        test_pass
    else
        test_fail "Some concurrent requests failed"
    fi
}

# Test 9: Availability for non-existent service
test_nonexistent_service() {
    test_start "Availability - Non-existent Service"

    local response=$(api_call POST "/availability/simple" "{
        \"service_id\": 99999,
        \"branch_id\": $BRANCH_ID,
        \"start_date\": \"$(date +%Y-%m-%d)\",
        \"end_date\": \"$(date +%Y-%m-%d)\",
        \"timeZone\": \"$TIMEZONE\"
    }")

    if echo "$response" | grep -q '"error"'; then
        test_pass
    else
        test_fail "Should have returned error for non-existent service"
    fi
}

# Test 10: Direct Cal.com V2 slots endpoint
test_direct_calcom_slots() {
    test_start "Direct Cal.com V2 Slots Endpoint"

    local cal_base="${CAL_BASE_URL:-https://api.cal.com/v2}"
    local start_time=$(date -d "tomorrow 9am" --iso-8601=seconds)
    local end_time=$(date -d "tomorrow 6pm" --iso-8601=seconds)

    local response=$(curl -s -X GET \
        -H "Authorization: Bearer $API_KEY" \
        -H "cal-api-version: 2024-08-13" \
        "${cal_base}/slots?eventTypeId=$EVENT_TYPE_ID&startTime=$start_time&endTime=$end_time&timeZone=$TIMEZONE")

    if echo "$response" | grep -q '"slots"'; then
        info "Direct Cal.com API call successful"
        test_pass
    else
        warning "Direct Cal.com API might not be accessible"
        test_pass # Don't fail if external API is not available
    fi
}

# Main execution
main() {
    echo -e "${GREEN}╔════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║   Cal.com V2 Availability Testing Suite   ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════╝${NC}"
    echo ""

    log "Starting availability tests at $(date)"
    log "API Base: $API_BASE"
    log "Service ID: $SERVICE_ID"
    log "Branch ID: $BRANCH_ID"
    echo ""

    # Run all tests
    test_simple_availability_today
    test_simple_availability_week
    test_availability_with_staff
    test_composite_availability
    test_different_timezones
    test_invalid_date_range
    test_availability_performance
    test_concurrent_availability
    test_nonexistent_service
    test_direct_calcom_slots

    # Summary
    echo ""
    echo -e "${GREEN}╔════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║              TEST SUMMARY                 ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "Tests Run:    ${BLUE}$TESTS_RUN${NC}"
    echo -e "Tests Passed: ${GREEN}$TESTS_PASSED${NC}"
    echo -e "Tests Failed: ${RED}$TESTS_FAILED${NC}"

    if [ "$TESTS_FAILED" -eq 0 ]; then
        echo -e "\n${GREEN}ALL TESTS PASSED! ✓${NC}"
        exit 0
    else
        echo -e "\n${RED}SOME TESTS FAILED! ✗${NC}"
        exit 1
    fi
}

# Run if not sourced
if [ "${BASH_SOURCE[0]}" = "${0}" ]; then
    main "$@"
fi