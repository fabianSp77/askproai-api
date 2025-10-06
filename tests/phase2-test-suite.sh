#!/bin/bash

# Phase 2: Booking Engine - Comprehensive Test Suite
# This script runs all tests for the booking system with detailed reporting

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
REPORT_DIR="/var/www/api-gateway/tests/reports"
REPORT_FILE="${REPORT_DIR}/phase2_test_report_${TIMESTAMP}.txt"
PERFORMANCE_LOG="${REPORT_DIR}/performance_${TIMESTAMP}.json"

# Create report directory
mkdir -p "${REPORT_DIR}"

# Function to print colored output
print_status() {
    local status=$1
    local message=$2
    case $status in
        "INFO")
            echo -e "${BLUE}[INFO]${NC} $message"
            ;;
        "SUCCESS")
            echo -e "${GREEN}[SUCCESS]${NC} $message"
            ;;
        "WARNING")
            echo -e "${YELLOW}[WARNING]${NC} $message"
            ;;
        "ERROR")
            echo -e "${RED}[ERROR]${NC} $message"
            ;;
    esac
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $status: $message" >> "${REPORT_FILE}"
}

# Function to run tests and capture results
run_test_suite() {
    local suite_name=$1
    local command=$2

    print_status "INFO" "Running ${suite_name}..."

    local start_time=$(date +%s.%N)

    if eval "${command}" >> "${REPORT_FILE}" 2>&1; then
        local end_time=$(date +%s.%N)
        local duration=$(echo "$end_time - $start_time" | bc)
        print_status "SUCCESS" "${suite_name} completed in ${duration}s"
        echo "SUCCESS" >> "${REPORT_FILE}.status"
        return 0
    else
        local end_time=$(date +%s.%N)
        local duration=$(echo "$end_time - $start_time" | bc)
        print_status "ERROR" "${suite_name} failed after ${duration}s"
        echo "FAILURE" >> "${REPORT_FILE}.status"
        return 1
    fi
}

# Header
echo "======================================================"
echo "PHASE 2: BOOKING ENGINE TEST SUITE"
echo "======================================================"
echo "Timestamp: $(date)"
echo "Report: ${REPORT_FILE}"
echo ""

# Initialize report
cat > "${REPORT_FILE}" << EOF
================================================================================
PHASE 2: BOOKING ENGINE TEST REPORT
================================================================================
Generated: $(date)
Environment: $(php artisan env:current 2>/dev/null || echo "production")
PHP Version: $(php -v | head -n1)
Laravel Version: $(php artisan --version 2>/dev/null || echo "Unknown")
================================================================================

EOF

# 0. Environment Check
print_status "INFO" "=== ENVIRONMENT CHECK ==="

# Check PHP version
if php -v | grep -q "PHP 8"; then
    print_status "SUCCESS" "PHP 8.x detected"
else
    print_status "WARNING" "PHP version may not be optimal"
fi

# Check Redis
if redis-cli ping > /dev/null 2>&1; then
    print_status "SUCCESS" "Redis is running"
else
    print_status "ERROR" "Redis is not running - tests may fail"
fi

# Check MySQL
if php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; then
    print_status "SUCCESS" "Database connection successful"
else
    print_status "ERROR" "Database connection failed"
    exit 1
fi

# Clear caches before testing
print_status "INFO" "Clearing caches..."
php artisan cache:clear > /dev/null 2>&1
php artisan config:clear > /dev/null 2>&1
php artisan route:clear > /dev/null 2>&1

# 1. Unit Tests
print_status "INFO" "=== [1/5] UNIT TESTS ==="

# Check if Booking-related unit tests exist
if ls tests/Unit/*Booking* 2>/dev/null | grep -q .; then
    run_test_suite "Unit Tests - Booking" "php artisan test --testsuite=Unit --filter=Booking" || true
else
    print_status "WARNING" "No Booking unit tests found"
fi

# 2. Feature Tests
print_status "INFO" "=== [2/5] FEATURE TESTS ==="

run_test_suite "Feature Tests - Public Booking" "php artisan test tests/Feature/PublicBookingTest.php" || true

# 3. Performance Tests
print_status "INFO" "=== [3/5] PERFORMANCE TESTS ==="

run_test_suite "Performance Tests - Booking Load" "php artisan test tests/Performance/BookingLoadTest.php" || true

# 4. Integration Tests
print_status "INFO" "=== [4/5] INTEGRATION TESTS ==="

# Test booking API endpoints
print_status "INFO" "Testing API endpoints..."

# Test availability endpoint
curl -sS -X POST "http://localhost/api/v2/availability/check" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{
         "service_id": 1,
         "branch_id": 1,
         "date": "'$(date -d tomorrow +%Y-%m-%d)'",
         "timezone": "Europe/Berlin"
     }' > /tmp/availability_response.json 2>&1 || true

if grep -q '"data"' /tmp/availability_response.json 2>/dev/null; then
    print_status "SUCCESS" "Availability API endpoint working"
else
    print_status "WARNING" "Availability API endpoint may have issues"
fi

# 5. Database Tests
print_status "INFO" "=== [5/5] DATABASE TESTS ==="

# Check indexes
print_status "INFO" "Checking database indexes..."
php artisan tinker --execute="
    \$indexes = DB::select('SHOW INDEXES FROM appointments');
    echo 'Appointment indexes: ' . count(\$indexes) . PHP_EOL;
    \$indexes = DB::select('SHOW INDEXES FROM customers');
    echo 'Customer indexes: ' . count(\$indexes) . PHP_EOL;
    \$indexes = DB::select('SHOW INDEXES FROM calls');
    echo 'Call indexes: ' . count(\$indexes) . PHP_EOL;
" >> "${REPORT_FILE}" 2>&1

# 6. Performance Metrics Collection
print_status "INFO" "=== PERFORMANCE METRICS ==="

# Collect performance metrics
cat > "${PERFORMANCE_LOG}" << EOF
{
    "timestamp": "$(date -Iseconds)",
    "metrics": {
EOF

# Test page load time
START_TIME=$(date +%s%N)
curl -sS -o /dev/null -w "%{http_code}" "http://localhost/admin" > /dev/null 2>&1 || true
END_TIME=$(date +%s%N)
LOAD_TIME=$((($END_TIME - $START_TIME) / 1000000))

cat >> "${PERFORMANCE_LOG}" << EOF
        "admin_page_load_ms": ${LOAD_TIME},
EOF

# Test API response time
START_TIME=$(date +%s%N)
curl -sS -X GET "http://localhost/api/v2/health" > /dev/null 2>&1 || true
END_TIME=$(date +%s%N)
API_TIME=$((($END_TIME - $START_TIME) / 1000000))

cat >> "${PERFORMANCE_LOG}" << EOF
        "api_health_check_ms": ${API_TIME},
EOF

# Memory usage
MEMORY_USAGE=$(php -r "echo memory_get_peak_usage(true) / 1024 / 1024;")

cat >> "${PERFORMANCE_LOG}" << EOF
        "peak_memory_mb": ${MEMORY_USAGE}
    }
}
EOF

print_status "SUCCESS" "Performance metrics collected"

# 7. Security Checks
print_status "INFO" "=== SECURITY CHECKS ==="

# Check for SQL injection vulnerabilities in booking queries
print_status "INFO" "Checking for SQL injection vulnerabilities..."

# Test with malicious input
MALICIOUS_INPUT="1' OR '1'='1"
RESPONSE=$(curl -sS -X POST "http://localhost/api/v2/bookings" \
     -H "Content-Type: application/json" \
     -d "{\"service_id\": \"${MALICIOUS_INPUT}\"}" 2>&1 || true)

if echo "${RESPONSE}" | grep -q "SQL\|syntax\|error in your SQL"; then
    print_status "ERROR" "Potential SQL injection vulnerability detected!"
else
    print_status "SUCCESS" "No SQL injection vulnerabilities detected"
fi

# Check rate limiting
print_status "INFO" "Testing rate limiting..."
RATE_LIMIT_HIT=false
for i in {1..35}; do
    RESPONSE=$(curl -sS -o /dev/null -w "%{http_code}" "http://localhost/api/v2/availability/check" 2>&1 || true)
    if [ "$RESPONSE" = "429" ]; then
        RATE_LIMIT_HIT=true
        break
    fi
done

if [ "$RATE_LIMIT_HIT" = true ]; then
    print_status "SUCCESS" "Rate limiting is working"
else
    print_status "WARNING" "Rate limiting may not be properly configured"
fi

# 8. Test Summary
print_status "INFO" "=== TEST SUMMARY ==="

# Count successes and failures
if [ -f "${REPORT_FILE}.status" ]; then
    SUCCESS_COUNT=$(grep -c "SUCCESS" "${REPORT_FILE}.status" || true)
    FAILURE_COUNT=$(grep -c "FAILURE" "${REPORT_FILE}.status" || true)
    rm "${REPORT_FILE}.status"
else
    SUCCESS_COUNT=0
    FAILURE_COUNT=0
fi

# Generate summary
cat >> "${REPORT_FILE}" << EOF

================================================================================
TEST SUMMARY
================================================================================
Total Test Suites Run: $((SUCCESS_COUNT + FAILURE_COUNT))
Successful: ${SUCCESS_COUNT}
Failed: ${FAILURE_COUNT}
Success Rate: $(echo "scale=2; ${SUCCESS_COUNT} * 100 / ($SUCCESS_COUNT + $FAILURE_COUNT + 0.01)" | bc)%

Performance Metrics:
- Admin Page Load: ${LOAD_TIME}ms
- API Response: ${API_TIME}ms
- Peak Memory: ${MEMORY_USAGE}MB

Report Location: ${REPORT_FILE}
Performance Log: ${PERFORMANCE_LOG}
================================================================================
EOF

# Display summary
echo ""
echo "======================================================"
echo "TEST EXECUTION COMPLETED"
echo "======================================================"
echo "Successful Tests: ${SUCCESS_COUNT}"
echo "Failed Tests: ${FAILURE_COUNT}"
echo ""
echo "Performance:"
echo "  - Page Load: ${LOAD_TIME}ms (Target: <200ms)"
echo "  - API Response: ${API_TIME}ms (Target: <100ms)"
echo "  - Memory Usage: ${MEMORY_USAGE}MB (Target: <100MB)"
echo ""

# Determine overall status
if [ ${FAILURE_COUNT} -eq 0 ] && [ ${LOAD_TIME} -lt 200 ] && [ ${API_TIME} -lt 100 ]; then
    print_status "SUCCESS" "✅ ALL TESTS PASSED - System is ready for production!"
    echo ""
    echo "Detailed report: ${REPORT_FILE}"
    exit 0
else
    print_status "WARNING" "⚠️ Some tests failed or performance targets not met"
    echo ""
    echo "Please review: ${REPORT_FILE}"
    echo ""
    echo "Recommendations:"
    if [ ${FAILURE_COUNT} -gt 0 ]; then
        echo "  - Fix failing tests before deployment"
    fi
    if [ ${LOAD_TIME} -ge 200 ]; then
        echo "  - Optimize page load performance"
    fi
    if [ ${API_TIME} -ge 100 ]; then
        echo "  - Optimize API response times"
    fi
    exit 1
fi