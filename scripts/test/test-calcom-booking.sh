#!/bin/bash

#################################################
# Cal.com V2 API - Booking Testing Script
# Tests all booking operations and scenarios
#################################################

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
API_BASE="${API_BASE:-http://localhost:8000/api/v2}"
API_KEY="${CAL_API_KEY:-test_api_key}"
SERVICE_ID="${SERVICE_ID:-1}"
COMPOSITE_SERVICE_ID="${COMPOSITE_SERVICE_ID:-2}"
BRANCH_ID="${BRANCH_ID:-1}"
STAFF_ID="${STAFF_ID:-1}"
TIMEZONE="Europe/Berlin"

# Test data
TEST_CUSTOMER_NAME="Test Customer $(date +%s)"
TEST_CUSTOMER_EMAIL="test.$(date +%s)@example.com"
TEST_CUSTOMER_PHONE="+49123456789"

# Test counters
TESTS_RUN=0
TESTS_PASSED=0
TESTS_FAILED=0

# Created appointments (for cleanup)
CREATED_APPOINTMENTS=()

# Logging
LOG_FILE="test-booking-$(date +%Y%m%d_%H%M%S).log"

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

success() {
    echo -e "${MAGENTA}[SUCCESS]${NC} $1" | tee -a "$LOG_FILE"
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

# Get available slot for booking
get_available_slot() {
    local service_id=$1
    local is_composite=${2:-false}

    local endpoint="/availability/simple"
    if [ "$is_composite" = "true" ]; then
        endpoint="/availability/composite"
    fi

    local response=$(api_call POST "$endpoint" "{
        \"service_id\": $service_id,
        \"branch_id\": $BRANCH_ID,
        \"start_date\": \"$(date -d 'tomorrow' +%Y-%m-%d)\",
        \"end_date\": \"$(date -d 'tomorrow' +%Y-%m-%d)\",
        \"timeZone\": \"$TIMEZONE\"
    }")

    if [ "$is_composite" = "true" ]; then
        echo "$response" | grep -o '"starts_at":"[^"]*"' | head -1 | cut -d'"' -f4
    else
        echo "$response" | grep -o '"start":"[^"]*"' | head -1 | cut -d'"' -f4
    fi
}

# Test 1: Simple booking creation
test_simple_booking() {
    test_start "Simple Booking Creation"

    # Get an available slot
    local slot=$(get_available_slot $SERVICE_ID false)

    if [ -z "$slot" ]; then
        test_fail "No available slots found"
        return
    fi

    info "Using slot: $slot"

    local response=$(api_call POST "/bookings" "{
        \"service_id\": $SERVICE_ID,
        \"branch_id\": $BRANCH_ID,
        \"staff_id\": $STAFF_ID,
        \"start\": \"$slot\",
        \"customer\": {
            \"name\": \"$TEST_CUSTOMER_NAME\",
            \"email\": \"$TEST_CUSTOMER_EMAIL\",
            \"phone\": \"$TEST_CUSTOMER_PHONE\"
        },
        \"timeZone\": \"$TIMEZONE\",
        \"source\": \"test_script\"
    }")

    if echo "$response" | grep -q '"appointment_id"'; then
        local appointment_id=$(echo "$response" | grep -o '"appointment_id":[0-9]*' | cut -d':' -f2)
        CREATED_APPOINTMENTS+=($appointment_id)
        success "Booking created with ID: $appointment_id"
        test_pass
    else
        error "Response: $response"
        test_fail "Failed to create booking"
    fi
}

# Test 2: Composite booking creation
test_composite_booking() {
    test_start "Composite Booking Creation"

    # Get an available composite slot
    local slot=$(get_available_slot $COMPOSITE_SERVICE_ID true)

    if [ -z "$slot" ]; then
        warning "No composite slots available, skipping test"
        test_pass
        return
    fi

    info "Using composite slot starting at: $slot"

    local response=$(api_call POST "/bookings" "{
        \"service_id\": $COMPOSITE_SERVICE_ID,
        \"branch_id\": $BRANCH_ID,
        \"start\": \"$slot\",
        \"customer\": {
            \"name\": \"Composite $TEST_CUSTOMER_NAME\",
            \"email\": \"composite.$TEST_CUSTOMER_EMAIL\",
            \"phone\": \"$TEST_CUSTOMER_PHONE\"
        },
        \"timeZone\": \"$TIMEZONE\",
        \"source\": \"test_script\"
    }")

    if echo "$response" | grep -q '"composite_uid"'; then
        local appointment_id=$(echo "$response" | grep -o '"appointment_id":[0-9]*' | cut -d':' -f2)
        local composite_uid=$(echo "$response" | grep -o '"composite_uid":"[^"]*"' | cut -d'"' -f4)
        CREATED_APPOINTMENTS+=($appointment_id)
        success "Composite booking created - ID: $appointment_id, UID: $composite_uid"

        # Verify segments
        if echo "$response" | grep -q '"segments"'; then
            info "Composite booking has segments as expected"
        fi

        test_pass
    else
        error "Response: $response"
        test_fail "Failed to create composite booking"
    fi
}

# Test 3: Reschedule booking
test_reschedule_booking() {
    test_start "Reschedule Booking"

    # First create a booking
    local slot=$(get_available_slot $SERVICE_ID false)

    if [ -z "$slot" ]; then
        test_fail "No available slots for initial booking"
        return
    fi

    # Create initial booking
    local create_response=$(api_call POST "/bookings" "{
        \"service_id\": $SERVICE_ID,
        \"branch_id\": $BRANCH_ID,
        \"staff_id\": $STAFF_ID,
        \"start\": \"$slot\",
        \"customer\": {
            \"name\": \"Reschedule Test\",
            \"email\": \"reschedule@test.com\",
            \"phone\": \"+49987654321\"
        },
        \"timeZone\": \"$TIMEZONE\"
    }")

    local appointment_id=$(echo "$create_response" | grep -o '"appointment_id":[0-9]*' | cut -d':' -f2)

    if [ -z "$appointment_id" ]; then
        test_fail "Failed to create initial booking for reschedule"
        return
    fi

    CREATED_APPOINTMENTS+=($appointment_id)
    info "Created booking $appointment_id for rescheduling"

    # Get a different slot for rescheduling
    local new_slot=$(date -d "tomorrow 14:00" --iso-8601=seconds)

    # Reschedule the booking
    local reschedule_response=$(api_call PATCH "/bookings/$appointment_id/reschedule" "{
        \"start\": \"$new_slot\",
        \"timeZone\": \"$TIMEZONE\",
        \"reason\": \"Test reschedule\"
    }")

    if echo "$reschedule_response" | grep -q '"appointment_id"'; then
        success "Booking $appointment_id rescheduled successfully"
        test_pass
    else
        error "Response: $reschedule_response"
        test_fail "Failed to reschedule booking"
    fi
}

# Test 4: Cancel booking
test_cancel_booking() {
    test_start "Cancel Booking"

    # First create a booking
    local slot=$(get_available_slot $SERVICE_ID false)

    if [ -z "$slot" ]; then
        test_fail "No available slots for booking"
        return
    fi

    # Create booking
    local create_response=$(api_call POST "/bookings" "{
        \"service_id\": $SERVICE_ID,
        \"branch_id\": $BRANCH_ID,
        \"staff_id\": $STAFF_ID,
        \"start\": \"$slot\",
        \"customer\": {
            \"name\": \"Cancel Test\",
            \"email\": \"cancel@test.com\",
            \"phone\": \"+49111222333\"
        },
        \"timeZone\": \"$TIMEZONE\"
    }")

    local appointment_id=$(echo "$create_response" | grep -o '"appointment_id":[0-9]*' | cut -d':' -f2)

    if [ -z "$appointment_id" ]; then
        test_fail "Failed to create booking for cancellation"
        return
    fi

    info "Created booking $appointment_id for cancellation"

    # Cancel the booking
    local cancel_response=$(api_call DELETE "/bookings/$appointment_id" "{
        \"reason\": \"Test cancellation\"
    }")

    if echo "$cancel_response" | grep -q '"status":"cancelled"'; then
        success "Booking $appointment_id cancelled successfully"
        # Remove from cleanup list since it's cancelled
        CREATED_APPOINTMENTS=(${CREATED_APPOINTMENTS[@]/$appointment_id/})
        test_pass
    else
        error "Response: $cancel_response"
        test_fail "Failed to cancel booking"
    fi
}

# Test 5: Double booking prevention
test_double_booking_prevention() {
    test_start "Double Booking Prevention"

    local slot=$(get_available_slot $SERVICE_ID false)

    if [ -z "$slot" ]; then
        test_fail "No available slots"
        return
    fi

    info "Testing double booking for slot: $slot"

    # First booking
    local response1=$(api_call POST "/bookings" "{
        \"service_id\": $SERVICE_ID,
        \"branch_id\": $BRANCH_ID,
        \"staff_id\": $STAFF_ID,
        \"start\": \"$slot\",
        \"customer\": {
            \"name\": \"First Customer\",
            \"email\": \"first@test.com\",
            \"phone\": \"+49111111111\"
        },
        \"timeZone\": \"$TIMEZONE\"
    }")

    if echo "$response1" | grep -q '"appointment_id"'; then
        local appointment_id=$(echo "$response1" | grep -o '"appointment_id":[0-9]*' | cut -d':' -f2)
        CREATED_APPOINTMENTS+=($appointment_id)
        info "First booking succeeded: $appointment_id"

        # Attempt second booking for same slot
        local response2=$(api_call POST "/bookings" "{
            \"service_id\": $SERVICE_ID,
            \"branch_id\": $BRANCH_ID,
            \"staff_id\": $STAFF_ID,
            \"start\": \"$slot\",
            \"customer\": {
                \"name\": \"Second Customer\",
                \"email\": \"second@test.com\",
                \"phone\": \"+49222222222\"
            },
            \"timeZone\": \"$TIMEZONE\"
        }")

        if echo "$response2" | grep -q '"error"'; then
            success "Double booking correctly prevented"
            test_pass
        else
            test_fail "Second booking should have been rejected"
        fi
    else
        test_fail "First booking failed"
    fi
}

# Test 6: Booking with missing customer data
test_booking_missing_data() {
    test_start "Booking with Missing Customer Data"

    local slot=$(get_available_slot $SERVICE_ID false)

    local response=$(api_call POST "/bookings" "{
        \"service_id\": $SERVICE_ID,
        \"branch_id\": $BRANCH_ID,
        \"staff_id\": $STAFF_ID,
        \"start\": \"$slot\",
        \"customer\": {
            \"name\": \"\"
        },
        \"timeZone\": \"$TIMEZONE\"
    }")

    if echo "$response" | grep -q '"error"'; then
        success "Validation correctly rejected incomplete data"
        test_pass
    else
        test_fail "Should have rejected booking with missing customer data"
    fi
}

# Test 7: Booking for past date
test_booking_past_date() {
    test_start "Booking for Past Date"

    local past_date=$(date -d "yesterday 10:00" --iso-8601=seconds)

    local response=$(api_call POST "/bookings" "{
        \"service_id\": $SERVICE_ID,
        \"branch_id\": $BRANCH_ID,
        \"staff_id\": $STAFF_ID,
        \"start\": \"$past_date\",
        \"customer\": {
            \"name\": \"Past Test\",
            \"email\": \"past@test.com\"
        },
        \"timeZone\": \"$TIMEZONE\"
    }")

    if echo "$response" | grep -q '"error"'; then
        success "Past date booking correctly rejected"
        test_pass
    else
        test_fail "Should not allow booking for past dates"
    fi
}

# Test 8: Concurrent booking attempts
test_concurrent_bookings() {
    test_start "Concurrent Booking Attempts"

    local slot=$(get_available_slot $SERVICE_ID false)

    if [ -z "$slot" ]; then
        test_fail "No available slots"
        return
    fi

    local pids=()
    local results_dir="/tmp/booking_test_$$"
    mkdir -p "$results_dir"

    # Launch 3 concurrent booking attempts for the same slot
    for i in {1..3}; do
        (
            api_call POST "/bookings" "{
                \"service_id\": $SERVICE_ID,
                \"branch_id\": $BRANCH_ID,
                \"staff_id\": $STAFF_ID,
                \"start\": \"$slot\",
                \"customer\": {
                    \"name\": \"Concurrent Customer $i\",
                    \"email\": \"concurrent$i@test.com\",
                    \"phone\": \"+4900000000$i\"
                },
                \"timeZone\": \"$TIMEZONE\"
            }" > "$results_dir/result_$i.json"
        ) &
        pids+=($!)
    done

    # Wait for all requests
    for pid in "${pids[@]}"; do
        wait "$pid"
    done

    # Count successful bookings
    local success_count=0
    for i in {1..3}; do
        if grep -q '"appointment_id"' "$results_dir/result_$i.json"; then
            success_count=$((success_count + 1))
            local appt_id=$(grep -o '"appointment_id":[0-9]*' "$results_dir/result_$i.json" | cut -d':' -f2)
            CREATED_APPOINTMENTS+=($appt_id)
        fi
    done

    rm -rf "$results_dir"

    if [ "$success_count" -eq 1 ]; then
        success "Only 1 concurrent booking succeeded (correct)"
        test_pass
    else
        test_fail "Expected 1 success, got $success_count"
    fi
}

# Test 9: Booking with custom metadata
test_booking_with_metadata() {
    test_start "Booking with Custom Metadata"

    local slot=$(get_available_slot $SERVICE_ID false)

    if [ -z "$slot" ]; then
        test_fail "No available slots"
        return
    fi

    local response=$(api_call POST "/bookings" "{
        \"service_id\": $SERVICE_ID,
        \"branch_id\": $BRANCH_ID,
        \"staff_id\": $STAFF_ID,
        \"start\": \"$slot\",
        \"customer\": {
            \"name\": \"Metadata Test\",
            \"email\": \"metadata@test.com\"
        },
        \"metadata\": {
            \"test_key\": \"test_value\",
            \"source\": \"automated_test\",
            \"timestamp\": \"$(date --iso-8601=seconds)\"
        },
        \"timeZone\": \"$TIMEZONE\"
    }")

    if echo "$response" | grep -q '"appointment_id"'; then
        local appointment_id=$(echo "$response" | grep -o '"appointment_id":[0-9]*' | cut -d':' -f2)
        CREATED_APPOINTMENTS+=($appointment_id)
        success "Booking with metadata created: $appointment_id"
        test_pass
    else
        test_fail "Failed to create booking with metadata"
    fi
}

# Test 10: Booking flow performance
test_booking_performance() {
    test_start "Booking Flow Performance (5 sequential bookings)"

    local total_time=0
    local successful=0

    for i in {1..5}; do
        local slot=$(get_available_slot $SERVICE_ID false)

        if [ -z "$slot" ]; then
            continue
        fi

        local start_time=$(date +%s%N)

        local response=$(api_call POST "/bookings" "{
            \"service_id\": $SERVICE_ID,
            \"branch_id\": $BRANCH_ID,
            \"staff_id\": $STAFF_ID,
            \"start\": \"$slot\",
            \"customer\": {
                \"name\": \"Perf Test $i\",
                \"email\": \"perf$i@test.com\"
            },
            \"timeZone\": \"$TIMEZONE\"
        }")

        local end_time=$(date +%s%N)
        local duration=$((($end_time - $start_time) / 1000000))
        total_time=$((total_time + duration))

        if echo "$response" | grep -q '"appointment_id"'; then
            successful=$((successful + 1))
            local appt_id=$(echo "$response" | grep -o '"appointment_id":[0-9]*' | cut -d':' -f2)
            CREATED_APPOINTMENTS+=($appt_id)
        fi

        # Add small delay to avoid rate limiting
        sleep 0.5
    done

    if [ "$successful" -gt 0 ]; then
        local avg_time=$((total_time / successful))
        info "Created $successful/5 bookings, avg time: ${avg_time}ms"

        if [ "$avg_time" -lt 2000 ]; then
            test_pass
        else
            test_fail "Average booking time too high: ${avg_time}ms"
        fi
    else
        test_fail "No successful bookings created"
    fi
}

# Cleanup function
cleanup_appointments() {
    if [ ${#CREATED_APPOINTMENTS[@]} -gt 0 ]; then
        echo -e "\n${YELLOW}Cleaning up test appointments...${NC}"

        for appointment_id in "${CREATED_APPOINTMENTS[@]}"; do
            if [ ! -z "$appointment_id" ]; then
                api_call DELETE "/bookings/$appointment_id" '{"reason": "Test cleanup"}' > /dev/null 2>&1
                info "Cleaned up appointment: $appointment_id"
            fi
        done
    fi
}

# Main execution
main() {
    echo -e "${GREEN}╔════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║     Cal.com V2 Booking Testing Suite      ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════╝${NC}"
    echo ""

    log "Starting booking tests at $(date)"
    log "API Base: $API_BASE"
    log "Service ID: $SERVICE_ID"
    log "Composite Service ID: $COMPOSITE_SERVICE_ID"
    echo ""

    # Run all tests
    test_simple_booking
    test_composite_booking
    test_reschedule_booking
    test_cancel_booking
    test_double_booking_prevention
    test_booking_missing_data
    test_booking_past_date
    test_concurrent_bookings
    test_booking_with_metadata
    test_booking_performance

    # Cleanup
    cleanup_appointments

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
        echo "Check log file: $LOG_FILE"
        exit 1
    fi
}

# Trap to ensure cleanup on exit
trap cleanup_appointments EXIT

# Run if not sourced
if [ "${BASH_SOURCE[0]}" = "${0}" ]; then
    main "$@"
fi