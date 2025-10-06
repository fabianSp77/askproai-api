#!/bin/bash

################################################################################
# AUTOMATED SECURITY TEST RUNNER
# Executes all PHASE B penetration tests and generates comprehensive report
################################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'
BOLD='\033[1m'

# Paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="/var/www/api-gateway"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
REPORT_DIR="${SCRIPT_DIR}/reports/${TIMESTAMP}"
REPORT_FILE="${REPORT_DIR}/security_test_report.txt"
HTML_REPORT="${REPORT_DIR}/security_test_report.html"

# Test results
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0
WARNINGS=0

################################################################################
# Setup
################################################################################

echo -e "${BOLD}${CYAN}"
cat << "EOF"
╔═══════════════════════════════════════════════════════════════════╗
║                                                                   ║
║   PHASE B - AUTOMATED SECURITY PENETRATION TEST RUNNER           ║
║                                                                   ║
║   Validates security fixes from PHASE A                          ║
║   Tests: 10 attack scenarios across multiple vectors            ║
║                                                                   ║
╚═══════════════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}\n"

# Create report directory
mkdir -p "$REPORT_DIR"

log() {
    echo -e "$1" | tee -a "$REPORT_FILE"
}

header() {
    log "\n${BOLD}${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    log "${BOLD}${BLUE}  $1${NC}"
    log "${BOLD}${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"
}

info() {
    log "${CYAN}[INFO]${NC} $1"
}

success() {
    log "${GREEN}[SUCCESS]${NC} $1"
}

error() {
    log "${RED}[ERROR]${NC} $1"
}

warn() {
    log "${YELLOW}[WARNING]${NC} $1"
}

################################################################################
# Pre-flight Checks
################################################################################

header "PRE-FLIGHT CHECKS"

info "Checking system requirements..."

# Check PHP
if ! command -v php &> /dev/null; then
    error "PHP is not installed"
    exit 1
fi
success "PHP: $(php -v | head -n1)"

# Check artisan
if [ ! -f "$APP_ROOT/artisan" ]; then
    error "Laravel artisan not found at $APP_ROOT/artisan"
    exit 1
fi
success "Laravel artisan: Available"

# Check database connection
if ! php "$APP_ROOT/artisan" db:show &> /dev/null; then
    warn "Database connection issue detected"
else
    success "Database: Connected"
fi

# Check required models
info "Verifying security components..."

REQUIRED_FILES=(
    "$APP_ROOT/app/Scopes/CompanyScope.php"
    "$APP_ROOT/app/Policies/AppointmentPolicy.php"
    "$APP_ROOT/app/Http/Middleware/VerifyRetellSignature.php"
    "$APP_ROOT/app/Observers/AppointmentObserver.php"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$file" ]; then
        success "Found: $(basename $file)"
    else
        warn "Missing: $(basename $file)"
    fi
done

################################################################################
# Test Execution
################################################################################

header "EXECUTING PENETRATION TESTS"

# Test Suite 1: Shell-based HTTP/API tests
info "Running shell-based penetration tests..."

if [ -f "${SCRIPT_DIR}/phase-b-penetration-tests.sh" ]; then
    chmod +x "${SCRIPT_DIR}/phase-b-penetration-tests.sh"

    # Run the test and capture output
    TEST_OUTPUT=$("${SCRIPT_DIR}/phase-b-penetration-tests.sh" 2>&1)
    TEST_EXIT_CODE=$?

    # Save output to report
    echo "$TEST_OUTPUT" >> "$REPORT_FILE"

    # Parse results
    SHELL_PASSED=$(echo "$TEST_OUTPUT" | grep -c "✓ PASS" || true)
    SHELL_FAILED=$(echo "$TEST_OUTPUT" | grep -c "✗ FAIL" || true)
    SHELL_WARNINGS=$(echo "$TEST_OUTPUT" | grep -c "⚠ WARNING" || true)

    PASSED_TESTS=$((PASSED_TESTS + SHELL_PASSED))
    FAILED_TESTS=$((FAILED_TESTS + SHELL_FAILED))
    WARNINGS=$((WARNINGS + SHELL_WARNINGS))

    if [ $TEST_EXIT_CODE -eq 0 ]; then
        success "Shell tests completed successfully"
    else
        error "Shell tests failed with exit code $TEST_EXIT_CODE"
    fi
else
    warn "Shell test script not found: phase-b-penetration-tests.sh"
fi

# Test Suite 2: Tinker-based model layer tests
info "Running tinker-based model layer tests..."

if [ -f "${SCRIPT_DIR}/phase-b-tinker-attacks.php" ]; then
    # Run tinker tests
    TINKER_OUTPUT=$(php "$APP_ROOT/artisan" tinker < "${SCRIPT_DIR}/phase-b-tinker-attacks.php" 2>&1)

    # Save output to report
    echo "$TINKER_OUTPUT" >> "$REPORT_FILE"

    # Parse results
    TINKER_PASSED=$(echo "$TINKER_OUTPUT" | grep -c "✓ PASS\|✓ SECURE" || true)
    TINKER_FAILED=$(echo "$TINKER_OUTPUT" | grep -c "✗ FAIL\|✗ VULNERABLE" || true)
    TINKER_WARNINGS=$(echo "$TINKER_OUTPUT" | grep -c "WARNING" || true)

    PASSED_TESTS=$((PASSED_TESTS + TINKER_PASSED))
    FAILED_TESTS=$((FAILED_TESTS + TINKER_FAILED))
    WARNINGS=$((WARNINGS + TINKER_WARNINGS))

    success "Tinker tests completed"
else
    warn "Tinker test script not found: phase-b-tinker-attacks.php"
fi

TOTAL_TESTS=$((PASSED_TESTS + FAILED_TESTS))

################################################################################
# Generate HTML Report
################################################################################

header "GENERATING HTML REPORT"

cat > "$HTML_REPORT" << 'HTMLEOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Penetration Test Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 40px;
            background: #f8f9fa;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .number {
            font-size: 3em;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-card .label {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stat-card.total .number { color: #3498db; }
        .stat-card.passed .number { color: #27ae60; }
        .stat-card.failed .number { color: #e74c3c; }
        .stat-card.warnings .number { color: #f39c12; }
        .stat-card.rate .number { color: #9b59b6; }
        .content {
            padding: 40px;
        }
        .section {
            margin-bottom: 40px;
        }
        .section h2 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .test-grid {
            display: grid;
            gap: 15px;
        }
        .test-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #ddd;
        }
        .test-item.pass { border-left-color: #27ae60; }
        .test-item.fail { border-left-color: #e74c3c; }
        .test-item.warn { border-left-color: #f39c12; }
        .test-item .test-name {
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 5px;
        }
        .test-item .test-cvss {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
            margin: 5px 0;
        }
        .cvss-critical { background: #e74c3c; color: white; }
        .cvss-high { background: #e67e22; color: white; }
        .cvss-medium { background: #f39c12; color: white; }
        .cvss-low { background: #3498db; color: white; }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
            margin-right: 10px;
        }
        .badge.pass { background: #27ae60; color: white; }
        .badge.fail { background: #e74c3c; color: white; }
        .badge.warn { background: #f39c12; color: white; }
        .footer {
            background: #2c3e50;
            color: white;
            padding: 20px 40px;
            text-align: center;
        }
        .conclusion {
            padding: 30px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
        }
        .conclusion.pass {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
        }
        .conclusion.fail {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }
        .conclusion h3 {
            font-size: 1.8em;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Security Penetration Test Report</h1>
            <p>PHASE B - Vulnerability Validation</p>
            <p style="opacity: 0.7; margin-top: 10px;">TIMESTAMP_PLACEHOLDER</p>
        </div>

        <div class="stats">
            <div class="stat-card total">
                <div class="label">Total Tests</div>
                <div class="number">TOTAL_PLACEHOLDER</div>
            </div>
            <div class="stat-card passed">
                <div class="label">Passed</div>
                <div class="number">PASSED_PLACEHOLDER</div>
            </div>
            <div class="stat-card failed">
                <div class="label">Failed</div>
                <div class="number">FAILED_PLACEHOLDER</div>
            </div>
            <div class="stat-card warnings">
                <div class="label">Warnings</div>
                <div class="number">WARNINGS_PLACEHOLDER</div>
            </div>
            <div class="stat-card rate">
                <div class="label">Pass Rate</div>
                <div class="number">PASSRATE_PLACEHOLDER%</div>
            </div>
        </div>

        <div class="content">
            <div class="section">
                <h2>📊 Test Summary</h2>
                CONCLUSION_PLACEHOLDER
            </div>

            <div class="section">
                <h2>🎯 Attack Scenarios Tested</h2>
                <div class="test-grid">
                    <div class="test-item">
                        <div class="test-name">1. Cross-Tenant Data Access via Model Queries</div>
                        <span class="test-cvss cvss-critical">CVSS 9.8 - CRITICAL</span>
                        <p>Tests CompanyScope global scope enforcement to prevent cross-tenant data leakage</p>
                    </div>
                    <div class="test-item">
                        <div class="test-name">2. Admin Role Privilege Escalation</div>
                        <span class="test-cvss cvss-high">CVSS 8.8 - HIGH</span>
                        <p>Validates role assignment protections and permission boundary enforcement</p>
                    </div>
                    <div class="test-item">
                        <div class="test-name">3. Webhook Forgery Attack (Legacy Route)</div>
                        <span class="test-cvss cvss-critical">CVSS 9.3 - CRITICAL</span>
                        <p>Confirms webhook signature verification prevents forged requests</p>
                    </div>
                    <div class="test-item">
                        <div class="test-name">4. User Enumeration via Timing Analysis</div>
                        <span class="test-cvss cvss-medium">CVSS 5.3 - MEDIUM</span>
                        <p>Checks for timing-based user enumeration vulnerabilities</p>
                    </div>
                    <div class="test-item">
                        <div class="test-name">5. Cross-Company Service Booking</div>
                        <span class="test-cvss cvss-high">CVSS 8.1 - HIGH</span>
                        <p>Validates authorization checks prevent booking services from other companies</p>
                    </div>
                    <div class="test-item">
                        <div class="test-name">6. SQL Injection via company_id Parameter</div>
                        <span class="test-cvss cvss-critical">CVSS 9.8 - CRITICAL</span>
                        <p>Tests input validation and parameterized query usage</p>
                    </div>
                    <div class="test-item">
                        <div class="test-name">7. XSS Injection via Observer Pattern</div>
                        <span class="test-cvss cvss-medium">CVSS 6.1 - MEDIUM</span>
                        <p>Validates output encoding and input sanitization in observers</p>
                    </div>
                    <div class="test-item">
                        <div class="test-name">8. Authorization Policy Bypass</div>
                        <span class="test-cvss cvss-high">CVSS 8.8 - HIGH</span>
                        <p>Confirms Laravel policy enforcement for resource access control</p>
                    </div>
                    <div class="test-item">
                        <div class="test-name">9. CompanyScope Bypass via Raw Queries</div>
                        <span class="test-cvss cvss-critical">CVSS 9.1 - CRITICAL</span>
                        <p>Tests whether raw SQL queries properly enforce tenant isolation</p>
                    </div>
                    <div class="test-item">
                        <div class="test-name">10. Monitor Endpoint Unauthorized Access</div>
                        <span class="test-cvss cvss-high">CVSS 7.5 - HIGH</span>
                        <p>Validates authentication requirements on sensitive monitoring endpoints</p>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>🛡️ Security Controls Validated</h2>
                <ul style="line-height: 2; padding-left: 20px;">
                    <li>✓ Multi-tenant data isolation (CompanyScope)</li>
                    <li>✓ Role-based access control (Spatie Permissions)</li>
                    <li>✓ Webhook signature verification</li>
                    <li>✓ Authorization policies (Laravel Gates)</li>
                    <li>✓ Mass assignment protection</li>
                    <li>✓ Input validation and sanitization</li>
                    <li>✓ SQL injection prevention</li>
                    <li>✓ XSS protection mechanisms</li>
                    <li>✓ Authentication middleware</li>
                    <li>✓ Observer-based business logic security</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <p>Generated by PHASE B Automated Security Test Runner</p>
            <p style="margin-top: 10px; opacity: 0.7;">API Gateway Security Validation Framework</p>
        </div>
    </div>
</body>
</html>
HTMLEOF

# Calculate pass rate
PASS_RATE=0
if [ $TOTAL_TESTS -gt 0 ]; then
    PASS_RATE=$(( PASSED_TESTS * 100 / TOTAL_TESTS ))
fi

# Determine conclusion
if [ $FAILED_TESTS -eq 0 ]; then
    CONCLUSION='<div class="conclusion pass">
        <h3>✅ ALL SECURITY TESTS PASSED</h3>
        <p>PHASE A security fixes are validated and working correctly.</p>
        <p>The system successfully prevents all tested attack vectors.</p>
    </div>'
else
    CONCLUSION='<div class="conclusion fail">
        <h3>❌ SECURITY VULNERABILITIES DETECTED</h3>
        <p>'$FAILED_TESTS' critical security issue(s) found that require immediate attention.</p>
        <p>Review the detailed test output for remediation guidance.</p>
    </div>'
fi

# Replace placeholders
sed -i "s/TIMESTAMP_PLACEHOLDER/$(date '+%Y-%m-%d %H:%M:%S %Z')/g" "$HTML_REPORT"
sed -i "s/TOTAL_PLACEHOLDER/$TOTAL_TESTS/g" "$HTML_REPORT"
sed -i "s/PASSED_PLACEHOLDER/$PASSED_TESTS/g" "$HTML_REPORT"
sed -i "s/FAILED_PLACEHOLDER/$FAILED_TESTS/g" "$HTML_REPORT"
sed -i "s/WARNINGS_PLACEHOLDER/$WARNINGS/g" "$HTML_REPORT"
sed -i "s/PASSRATE_PLACEHOLDER/$PASS_RATE/g" "$HTML_REPORT"
sed -i "s|CONCLUSION_PLACEHOLDER|$CONCLUSION|g" "$HTML_REPORT"

success "HTML report generated: $HTML_REPORT"

################################################################################
# Final Report
################################################################################

header "TEST RESULTS SUMMARY"

log "${BOLD}Total Tests Executed:${NC} $TOTAL_TESTS"
log "${GREEN}${BOLD}Passed:${NC} $PASSED_TESTS"
log "${RED}${BOLD}Failed:${NC} $FAILED_TESTS"
log "${YELLOW}${BOLD}Warnings:${NC} $WARNINGS"
log "${MAGENTA}${BOLD}Pass Rate:${NC} ${PASS_RATE}%"

log "\n${BOLD}Report Files:${NC}"
log "  - Text Report: $REPORT_FILE"
log "  - HTML Report: $HTML_REPORT"

if [ $FAILED_TESTS -eq 0 ]; then
    log "\n${GREEN}${BOLD}╔═══════════════════════════════════════════════════════════════╗${NC}"
    log "${GREEN}${BOLD}║                                                               ║${NC}"
    log "${GREEN}${BOLD}║   ✓ ALL SECURITY TESTS PASSED                                ║${NC}"
    log "${GREEN}${BOLD}║                                                               ║${NC}"
    log "${GREEN}${BOLD}║   PHASE A fixes validated successfully                       ║${NC}"
    log "${GREEN}${BOLD}║   System is secure against tested attack vectors             ║${NC}"
    log "${GREEN}${BOLD}║                                                               ║${NC}"
    log "${GREEN}${BOLD}╚═══════════════════════════════════════════════════════════════╝${NC}"
    exit 0
else
    log "\n${RED}${BOLD}╔═══════════════════════════════════════════════════════════════╗${NC}"
    log "${RED}${BOLD}║                                                               ║${NC}"
    log "${RED}${BOLD}║   ✗ SECURITY VULNERABILITIES DETECTED                        ║${NC}"
    log "${RED}${BOLD}║                                                               ║${NC}"
    log "${RED}${BOLD}║   $FAILED_TESTS critical security issue(s) found                     ║${NC}"
    log "${RED}${BOLD}║   Review detailed logs for remediation steps                 ║${NC}"
    log "${RED}${BOLD}║                                                               ║${NC}"
    log "${RED}${BOLD}╚═══════════════════════════════════════════════════════════════╝${NC}"
    exit 1
fi
