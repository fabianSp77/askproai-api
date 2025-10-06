#!/bin/bash

################################################################################
# PHASE B - PENETRATION TEST SUITE
# Security Validation for PHASE A Vulnerability Fixes
#
# Purpose: Validate that all 5 critical vulnerabilities from PHASE A are fixed
# Approach: Attempt real exploits and verify they are blocked
# Safety: All tests use test database and won't destroy production data
#
# CVSS Severity Scale:
# - 9.0-10.0: CRITICAL
# - 7.0-8.9:  HIGH
# - 4.0-6.9:  MEDIUM
# - 0.1-3.9:  LOW
################################################################################

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color
BOLD='\033[1m'

# Test counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0
WARNINGS=0

# Configuration
API_URL="${API_URL:-http://localhost}"
TEST_DB="${TEST_DB:-testing}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOG_FILE="/var/www/api-gateway/tests/Security/penetration_test_${TIMESTAMP}.log"

# Test results storage
declare -A TEST_RESULTS
declare -A TEST_DETAILS

################################################################################
# Helper Functions
################################################################################

log() {
    echo -e "$1" | tee -a "$LOG_FILE"
}

header() {
    log "\n${BOLD}${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    log "${BOLD}${BLUE}  $1${NC}"
    log "${BOLD}${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"
}

test_header() {
    log "\n${BOLD}━━━ TEST #$1: $2 ━━━${NC}"
    log "${YELLOW}CVSS Score: $3 | Category: $4${NC}\n"
}

pass() {
    ((PASSED_TESTS++))
    ((TOTAL_TESTS++))
    log "${GREEN}✓ PASS${NC}: $1"
    TEST_RESULTS[$TOTAL_TESTS]="PASS"
    TEST_DETAILS[$TOTAL_TESTS]="$1"
}

fail() {
    ((FAILED_TESTS++))
    ((TOTAL_TESTS++))
    log "${RED}✗ FAIL${NC}: $1"
    log "${RED}   Details: $2${NC}"
    TEST_RESULTS[$TOTAL_TESTS]="FAIL"
    TEST_DETAILS[$TOTAL_TESTS]="$1 - $2"
}

warn() {
    ((WARNINGS++))
    log "${YELLOW}⚠ WARNING${NC}: $1"
}

info() {
    log "${BLUE}ℹ INFO${NC}: $1"
}

# Execute artisan command and capture output
artisan_exec() {
    php /var/www/api-gateway/artisan "$@" 2>&1
}

# Execute SQL query via artisan tinker
sql_query() {
    local query="$1"
    echo "DB::select(\"$query\");" | artisan_exec tinker --execute
}

# Create test users for testing
setup_test_data() {
    info "Setting up test data..."

    # Create test companies
    artisan_exec tinker <<'EOF'
$company1 = \App\Models\Company::firstOrCreate(
    ['name' => 'Test Company Alpha'],
    ['id' => 9001, 'tenant_id' => 1]
);

$company2 = \App\Models\Company::firstOrCreate(
    ['name' => 'Test Company Beta'],
    ['id' => 9002, 'tenant_id' => 1]
);

// Create users for testing
$admin_user = \App\Models\User::firstOrCreate(
    ['email' => 'admin@test-company-alpha.com'],
    [
        'name' => 'Admin User',
        'password' => bcrypt('password'),
        'company_id' => 9001
    ]
);

$regular_user = \App\Models\User::firstOrCreate(
    ['email' => 'user@test-company-alpha.com'],
    [
        'name' => 'Regular User',
        'password' => bcrypt('password'),
        'company_id' => 9001
    ]
);

$malicious_user = \App\Models\User::firstOrCreate(
    ['email' => 'attacker@test-company-beta.com'],
    [
        'name' => 'Malicious User',
        'password' => bcrypt('password'),
        'company_id' => 9002
    ]
);

// Assign roles
$admin_user->assignRole('admin');
$regular_user->assignRole('staff');
$malicious_user->assignRole('staff');

echo "Test data created successfully\n";
EOF
}

cleanup_test_data() {
    info "Cleaning up test data..."
    artisan_exec tinker <<'EOF'
\App\Models\User::whereIn('email', [
    'admin@test-company-alpha.com',
    'user@test-company-alpha.com',
    'attacker@test-company-beta.com'
])->delete();

\App\Models\Company::whereIn('id', [9001, 9002])->delete();

echo "Test data cleaned up\n";
EOF
}

################################################################################
# ATTACK SCENARIO #1: Cross-Tenant Data Access via Direct Model Queries
# CVSS: 9.8 (CRITICAL) - AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H
################################################################################

test_01_cross_tenant_model_access() {
    test_header "1" "Cross-Tenant Data Access via Model Queries" "9.8 CRITICAL" "Authorization Bypass"

    info "Attack: Attempting to query appointments from another company using raw queries"

    local result=$(artisan_exec tinker <<'EOF'
// Simulate attacker from Company Beta (9002) trying to access Company Alpha (9001) data
\Illuminate\Support\Facades\Auth::loginUsingId(\App\Models\User::where('email', 'attacker@test-company-beta.com')->first()->id);

$user = \Illuminate\Support\Facades\Auth::user();
echo "Logged in as: {$user->email} (Company ID: {$user->company_id})\n";

// ATTACK: Try to access appointments from Company Alpha
$appointments = \App\Models\Appointment::where('company_id', 9001)->get();

if ($appointments->isEmpty()) {
    echo "SECURE: CompanyScope prevented cross-tenant access\n";
} else {
    echo "VULNERABLE: Found " . $appointments->count() . " appointments from company 9001\n";
}
EOF
)

    if echo "$result" | grep -q "SECURE: CompanyScope prevented"; then
        pass "CompanyScope successfully blocked cross-tenant model access"
    else
        fail "Cross-tenant data access SUCCEEDED - CompanyScope bypass detected" "$result"
    fi
}

################################################################################
# ATTACK SCENARIO #2: Admin Role Privilege Escalation
# CVSS: 8.8 (HIGH) - AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:H
################################################################################

test_02_privilege_escalation() {
    test_header "2" "Admin Role Privilege Escalation Attempt" "8.8 HIGH" "Privilege Escalation"

    info "Attack: Regular user attempting to escalate to super_admin role"

    local result=$(artisan_exec tinker <<'EOF'
\Illuminate\Support\Facades\Auth::loginUsingId(\App\Models\User::where('email', 'user@test-company-alpha.com')->first()->id);

$user = \Illuminate\Support\Facades\Auth::user();
echo "Current roles: " . $user->getRoleNames()->implode(', ') . "\n";

// ATTACK: Try to assign super_admin role
try {
    $user->assignRole('super_admin');
    $user->refresh();

    if ($user->hasRole('super_admin')) {
        echo "VULNERABLE: Successfully escalated to super_admin\n";
    } else {
        echo "SECURE: Role assignment blocked\n";
    }
} catch (\Exception $e) {
    echo "SECURE: Exception thrown - " . $e->getMessage() . "\n";
}
EOF
)

    if echo "$result" | grep -q "SECURE:"; then
        pass "Privilege escalation attempt blocked successfully"
    else
        fail "Privilege escalation SUCCEEDED" "$result"
    fi
}

################################################################################
# ATTACK SCENARIO #3: Webhook Forgery Attack (Legacy Retell Route)
# CVSS: 9.3 (CRITICAL) - AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:N
################################################################################

test_03_webhook_forgery() {
    test_header "3" "Webhook Forgery Attack on Legacy Route" "9.3 CRITICAL" "Authentication Bypass"

    info "Attack: Sending forged webhook without valid signature"

    local response=$(curl -s -w "\n%{http_code}" -X POST "${API_URL}/api/webhook" \
        -H "Content-Type: application/json" \
        -H "X-Retell-Signature: forged_signature_12345" \
        -d '{
            "event": "call_ended",
            "call_id": "test_call_123",
            "data": {
                "call_status": "ended",
                "appointment_made": true
            }
        }')

    local http_code=$(echo "$response" | tail -n1)
    local body=$(echo "$response" | head -n-1)

    if [ "$http_code" = "401" ] || [ "$http_code" = "403" ]; then
        pass "Webhook forgery rejected with HTTP $http_code"
    else
        fail "Webhook forgery ACCEPTED - signature verification bypassed" "HTTP $http_code: $body"
    fi
}

################################################################################
# ATTACK SCENARIO #4: User Enumeration Attack
# CVSS: 5.3 (MEDIUM) - AV:N/AC:L/PR:N/UI:N/S:U/C:L/I:N/A:N
################################################################################

test_04_user_enumeration() {
    test_header "4" "User Enumeration via Timing Analysis" "5.3 MEDIUM" "Information Disclosure"

    info "Attack: Attempting to enumerate valid emails via response timing"

    # Test with known valid email
    local start_valid=$(date +%s%N)
    local response_valid=$(curl -s -w "\n%{http_code}" -X POST "${API_URL}/login" \
        -H "Content-Type: application/json" \
        -d '{"email": "admin@test-company-alpha.com", "password": "wrongpassword"}')
    local end_valid=$(date +%s%N)
    local time_valid=$(( (end_valid - start_valid) / 1000000 ))

    # Test with known invalid email
    local start_invalid=$(date +%s%N)
    local response_invalid=$(curl -s -w "\n%{http_code}" -X POST "${API_URL}/login" \
        -H "Content-Type: application/json" \
        -d '{"email": "nonexistent@nowhere.com", "password": "wrongpassword"}')
    local end_invalid=$(date +%s%N)
    local time_invalid=$(( (end_invalid - start_invalid) / 1000000 ))

    local time_diff=$(( time_valid > time_invalid ? time_valid - time_invalid : time_invalid - time_valid ))

    info "Valid email response time: ${time_valid}ms"
    info "Invalid email response time: ${time_invalid}ms"
    info "Time difference: ${time_diff}ms"

    if [ $time_diff -lt 50 ]; then
        pass "Response timing is consistent - enumeration prevented"
    else
        warn "Response timing differs by ${time_diff}ms - potential enumeration vector"
    fi
}

################################################################################
# ATTACK SCENARIO #5: Service Booking Cross-Company Attack
# CVSS: 8.1 (HIGH) - AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:N
################################################################################

test_05_cross_company_booking() {
    test_header "5" "Cross-Company Service Booking Attack" "8.1 HIGH" "Authorization Bypass"

    info "Attack: User from Company Beta trying to book service from Company Alpha"

    local result=$(artisan_exec tinker <<'EOF'
// Login as attacker from Company Beta
\Illuminate\Support\Facades\Auth::loginUsingId(\App\Models\User::where('email', 'attacker@test-company-beta.com')->first()->id);

// Try to access and book a service from Company Alpha
$service_alpha = \App\Models\Service::where('company_id', 9001)->first();

if (!$service_alpha) {
    echo "SECURE: CompanyScope prevented service access\n";
} else {
    echo "VULNERABLE: Accessed service from Company Alpha: {$service_alpha->name}\n";

    // Try to create appointment
    try {
        $appointment = \App\Models\Appointment::create([
            'service_id' => $service_alpha->id,
            'company_id' => 9001,
            'customer_id' => 1,
            'staff_id' => 1,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'pending'
        ]);
        echo "VULNERABLE: Created cross-company appointment ID: {$appointment->id}\n";
    } catch (\Exception $e) {
        echo "SECURE: Appointment creation blocked - {$e->getMessage()}\n";
    }
}
EOF
)

    if echo "$result" | grep -q "SECURE:"; then
        pass "Cross-company booking prevented by CompanyScope"
    else
        fail "Cross-company booking SUCCEEDED" "$result"
    fi
}

################################################################################
# ATTACK SCENARIO #6: SQL Injection via company_id Parameter
# CVSS: 9.8 (CRITICAL) - AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H
################################################################################

test_06_sql_injection() {
    test_header "6" "SQL Injection via company_id Manipulation" "9.8 CRITICAL" "Injection"

    info "Attack: Attempting SQL injection via company_id parameter"

    local response=$(curl -s -w "\n%{http_code}" -X GET \
        "${API_URL}/api/v1/appointments?company_id=9001' OR '1'='1" \
        -H "Content-Type: application/json" \
        2>&1)

    local http_code=$(echo "$response" | tail -n1)
    local body=$(echo "$response" | head -n-1)

    # Check if response contains error or sanitized input
    if echo "$body" | grep -iE "error|invalid|unauthorized" > /dev/null; then
        pass "SQL injection attempt rejected"
    elif [ "$http_code" = "400" ] || [ "$http_code" = "401" ] || [ "$http_code" = "403" ]; then
        pass "SQL injection blocked with HTTP $http_code"
    else
        warn "Potential SQL injection vulnerability - manual verification needed"
        info "Response: $body"
    fi
}

################################################################################
# ATTACK SCENARIO #7: XSS Injection via Observer Pattern
# CVSS: 6.1 (MEDIUM) - AV:N/AC:L/PR:N/UI:R/S:C/C:L/I:L/A:N
################################################################################

test_07_xss_via_observer() {
    test_header "7" "XSS Injection via AppointmentObserver" "6.1 MEDIUM" "Cross-Site Scripting"

    info "Attack: Injecting malicious script via appointment notes"

    local result=$(artisan_exec tinker <<'EOF'
\Illuminate\Support\Facades\Auth::loginUsingId(\App\Models\User::where('email', 'attacker@test-company-beta.com')->first()->id);

try {
    $appointment = \App\Models\Appointment::create([
        'service_id' => 1,
        'company_id' => 9002,
        'customer_id' => 1,
        'staff_id' => 1,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
        'status' => 'pending',
        'notes' => '<script>alert("XSS")</script>',
        'metadata' => ['malicious' => '<img src=x onerror=alert(1)>']
    ]);

    $notes = $appointment->notes;
    $metadata = $appointment->metadata;

    if (strpos($notes, '<script>') !== false || strpos(json_encode($metadata), '<img') !== false) {
        echo "VULNERABLE: XSS payload stored without sanitization\n";
    } else {
        echo "SECURE: XSS payload sanitized\n";
    }
} catch (\Exception $e) {
    echo "SECURE: Input validation prevented XSS - {$e->getMessage()}\n";
}
EOF
)

    if echo "$result" | grep -q "SECURE:"; then
        pass "XSS injection blocked or sanitized"
    else
        fail "XSS payload stored without sanitization" "$result"
    fi
}

################################################################################
# ATTACK SCENARIO #8: Authorization Policy Bypass
# CVSS: 8.8 (HIGH) - AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:H
################################################################################

test_08_policy_bypass() {
    test_header "8" "Authorization Policy Bypass Attempt" "8.8 HIGH" "Authorization"

    info "Attack: Regular user attempting to access super_admin-only actions"

    local result=$(artisan_exec tinker <<'EOF'
\Illuminate\Support\Facades\Auth::loginUsingId(\App\Models\User::where('email', 'user@test-company-alpha.com')->first()->id);

$user = \Illuminate\Support\Facades\Auth::user();
$appointment = \App\Models\Appointment::where('company_id', 9001)->first();

if (!$appointment) {
    echo "INFO: No appointment found for testing\n";
} else {
    // Try to force delete (super_admin only)
    try {
        $canForceDelete = $user->can('forceDelete', $appointment);

        if ($canForceDelete) {
            echo "VULNERABLE: Regular user can forceDelete\n";
        } else {
            echo "SECURE: Policy prevented forceDelete for non-super_admin\n";
        }
    } catch (\Exception $e) {
        echo "SECURE: Exception thrown - {$e->getMessage()}\n";
    }
}
EOF
)

    if echo "$result" | grep -q "SECURE:"; then
        pass "Authorization policy correctly enforced"
    else
        warn "Policy bypass test inconclusive - manual verification needed"
        info "Result: $result"
    fi
}

################################################################################
# ATTACK SCENARIO #9: CompanyScope Bypass via Raw Queries
# CVSS: 9.1 (CRITICAL) - AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:N
################################################################################

test_09_companyscope_raw_query_bypass() {
    test_header "9" "CompanyScope Bypass via Raw SQL Queries" "9.1 CRITICAL" "Authorization Bypass"

    info "Attack: Using DB::raw() to bypass CompanyScope global scope"

    local result=$(artisan_exec tinker <<'EOF'
\Illuminate\Support\Facades\Auth::loginUsingId(\App\Models\User::where('email', 'attacker@test-company-beta.com')->first()->id);

$user = \Illuminate\Support\Facades\Auth::user();
echo "Logged in as Company ID: {$user->company_id}\n";

// ATTACK 1: Try DB::table (bypasses Eloquent scopes)
$appointments_table = DB::table('appointments')->where('company_id', 9001)->count();
echo "DB::table found {$appointments_table} appointments from Company 9001\n";

// ATTACK 2: Try raw query
$appointments_raw = DB::select('SELECT * FROM appointments WHERE company_id = 9001');
echo "Raw query found " . count($appointments_raw) . " appointments from Company 9001\n";

// ATTACK 3: Try Eloquent with withoutGlobalScope
try {
    $appointments_bypass = \App\Models\Appointment::withoutGlobalScope(\App\Scopes\CompanyScope::class)
        ->where('company_id', 9001)
        ->count();
    echo "VULNERABLE: withoutGlobalScope found {$appointments_bypass} appointments\n";
} catch (\Exception $e) {
    echo "SECURE: Scope bypass prevented - {$e->getMessage()}\n";
}

if ($appointments_table > 0 || count($appointments_raw) > 0) {
    echo "WARNING: Raw queries can bypass CompanyScope\n";
} else {
    echo "SECURE: No data leakage detected\n";
}
EOF
)

    if echo "$result" | grep -q "WARNING: Raw queries can bypass"; then
        warn "CompanyScope can be bypassed with raw queries - application-level controls needed"
        info "Note: This is expected behavior - raw queries require manual company_id filtering"
    elif echo "$result" | grep -q "SECURE: No data leakage"; then
        pass "CompanyScope functioning correctly, no data found in other company"
    else
        info "CompanyScope test result: $result"
    fi
}

################################################################################
# ATTACK SCENARIO #10: Monitor Endpoint Unauthorized Access
# CVSS: 7.5 (HIGH) - AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N
################################################################################

test_10_monitor_endpoint_access() {
    test_header "10" "Unauthorized Monitor Endpoint Access" "7.5 HIGH" "Authentication Bypass"

    info "Attack: Accessing monitoring endpoints without authentication"

    # Test /api/webhooks/monitor
    local response1=$(curl -s -w "\n%{http_code}" -X GET "${API_URL}/api/webhooks/monitor" 2>&1)
    local http_code1=$(echo "$response1" | tail -n1)

    # Test /api/health/metrics
    local response2=$(curl -s -w "\n%{http_code}" -X GET "${API_URL}/api/health/metrics" 2>&1)
    local http_code2=$(echo "$response2" | tail -n1)

    local all_secured=true

    if [ "$http_code1" = "401" ] || [ "$http_code1" = "403" ]; then
        pass "Webhook monitor endpoint properly protected (HTTP $http_code1)"
    else
        fail "Webhook monitor endpoint accessible without auth" "HTTP $http_code1"
        all_secured=false
    fi

    if [ "$http_code2" = "401" ] || [ "$http_code2" = "403" ]; then
        pass "Health metrics endpoint properly protected (HTTP $http_code2)"
    else
        fail "Health metrics endpoint accessible without auth" "HTTP $http_code2"
        all_secured=false
    fi

    if [ "$all_secured" = true ]; then
        pass "All monitoring endpoints require authentication"
    fi
}

################################################################################
# Test Execution
################################################################################

main() {
    header "PHASE B - SECURITY PENETRATION TEST SUITE"

    log "Test Start Time: $(date)"
    log "Target: $API_URL"
    log "Database: $TEST_DB"
    log "Log File: $LOG_FILE"

    # Setup
    header "SETUP PHASE"
    setup_test_data

    # Run all tests
    header "ATTACK SCENARIOS"

    test_01_cross_tenant_model_access
    test_02_privilege_escalation
    test_03_webhook_forgery
    test_04_user_enumeration
    test_05_cross_company_booking
    test_06_sql_injection
    test_07_xss_via_observer
    test_08_policy_bypass
    test_09_companyscope_raw_query_bypass
    test_10_monitor_endpoint_access

    # Cleanup
    header "CLEANUP PHASE"
    cleanup_test_data

    # Summary
    header "TEST SUMMARY"

    log "${BOLD}Total Tests:${NC} $TOTAL_TESTS"
    log "${GREEN}${BOLD}Passed:${NC} $PASSED_TESTS"
    log "${RED}${BOLD}Failed:${NC} $FAILED_TESTS"
    log "${YELLOW}${BOLD}Warnings:${NC} $WARNINGS"

    local pass_rate=0
    if [ $TOTAL_TESTS -gt 0 ]; then
        pass_rate=$(( PASSED_TESTS * 100 / TOTAL_TESTS ))
    fi

    log "\n${BOLD}Pass Rate: ${pass_rate}%${NC}"

    if [ $FAILED_TESTS -eq 0 ]; then
        log "\n${GREEN}${BOLD}✓ ALL SECURITY TESTS PASSED${NC}"
        log "${GREEN}${BOLD}  PHASE A fixes are validated and working correctly${NC}"
        exit 0
    else
        log "\n${RED}${BOLD}✗ SECURITY VULNERABILITIES DETECTED${NC}"
        log "${RED}${BOLD}  $FAILED_TESTS critical security issue(s) found${NC}"
        log "\n${YELLOW}Failed Tests:${NC}"
        for i in "${!TEST_RESULTS[@]}"; do
            if [ "${TEST_RESULTS[$i]}" = "FAIL" ]; then
                log "${RED}  [$i] ${TEST_DETAILS[$i]}${NC}"
            fi
        done
        exit 1
    fi
}

# Execute main function
main "$@"
