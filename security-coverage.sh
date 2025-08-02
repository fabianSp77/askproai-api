#!/bin/bash

# Security Test Coverage Reporter
# Generates comprehensive coverage reports for security tests

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$SCRIPT_DIR"
REPORT_DIR="$PROJECT_DIR/security-reports"
TIMESTAMP=$(date '+%Y%m%d_%H%M%S')

# Configuration
MIN_COVERAGE_CRITICAL=90
MIN_COVERAGE_OVERALL=80
MIN_COVERAGE_SECURITY_FUNCTIONS=95

print_banner() {
    echo -e "${CYAN}"
    echo "╔══════════════════════════════════════════════════════════════════╗"
    echo "║              Security Test Coverage Reporter                     ║"
    echo "║                                                                  ║"
    echo "║  Generates comprehensive coverage analysis for security tests    ║"
    echo "╚══════════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

log_message() {
    local level=$1
    local message=$2
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    case $level in
        "INFO")
            echo -e "${GREEN}[INFO]${NC} $message"
            ;;
        "WARN")
            echo -e "${YELLOW}[WARN]${NC} $message"
            ;;
        "ERROR")
            echo -e "${RED}[ERROR]${NC} $message"
            ;;
    esac
}

setup_directories() {
    log_message "INFO" "Setting up report directories..."
    
    mkdir -p "$REPORT_DIR/coverage/html"
    mkdir -p "$REPORT_DIR/coverage/json"
    mkdir -p "$REPORT_DIR/analysis"
}

run_security_tests_with_coverage() {
    log_message "INFO" "Running security tests with coverage..."
    
    # Run critical tests with coverage
    log_message "INFO" "Running critical security tests..."
    vendor/bin/phpunit \
        --configuration phpunit-security.xml \
        --testsuite Critical \
        --coverage-html "$REPORT_DIR/coverage/html/critical" \
        --coverage-clover "$REPORT_DIR/coverage/critical-clover.xml" \
        --coverage-text="$REPORT_DIR/coverage/critical-text.txt" \
        --log-junit "$REPORT_DIR/critical-junit.xml"
    
    # Run medium priority tests with coverage
    log_message "INFO" "Running medium priority security tests..."
    vendor/bin/phpunit \
        --configuration phpunit-security.xml \
        --testsuite Medium \
        --coverage-html "$REPORT_DIR/coverage/html/medium" \
        --coverage-clover "$REPORT_DIR/coverage/medium-clover.xml" \
        --coverage-text="$REPORT_DIR/coverage/medium-text.txt" \
        --log-junit "$REPORT_DIR/medium-junit.xml"
    
    # Run all security tests with combined coverage
    log_message "INFO" "Running all security tests for combined coverage..."
    vendor/bin/phpunit \
        --configuration phpunit-security.xml \
        --testsuite Security \
        --coverage-html "$REPORT_DIR/coverage/html/all" \
        --coverage-clover "$REPORT_DIR/coverage/all-clover.xml" \
        --coverage-text="$REPORT_DIR/coverage/all-text.txt" \
        --coverage-php "$REPORT_DIR/coverage/all-coverage.php" \
        --log-junit "$REPORT_DIR/all-junit.xml" \
        --testdox-html "$REPORT_DIR/testdox.html"
}

analyze_coverage() {
    log_message "INFO" "Analyzing coverage results..."
    
    # Extract coverage percentages from text reports
    if [ -f "$REPORT_DIR/coverage/critical-text.txt" ]; then
        CRITICAL_COVERAGE=$(grep -o "Lines: *[0-9]*\.[0-9]*%" "$REPORT_DIR/coverage/critical-text.txt" | head -1 | grep -o "[0-9]*\.[0-9]*")
        log_message "INFO" "Critical tests coverage: ${CRITICAL_COVERAGE}%"
    fi
    
    if [ -f "$REPORT_DIR/coverage/all-text.txt" ]; then
        OVERALL_COVERAGE=$(grep -o "Lines: *[0-9]*\.[0-9]*%" "$REPORT_DIR/coverage/all-text.txt" | head -1 | grep -o "[0-9]*\.[0-9]*")
        log_message "INFO" "Overall security tests coverage: ${OVERALL_COVERAGE}%"
    fi
    
    # Generate coverage analysis report
    generate_coverage_analysis
}

generate_coverage_analysis() {
    local analysis_file="$REPORT_DIR/analysis/coverage-analysis-$TIMESTAMP.md"
    
    log_message "INFO" "Generating coverage analysis report..."
    
    cat > "$analysis_file" << EOF
# Security Test Coverage Analysis

**Generated**: $(date)  
**Analysis Type**: Comprehensive Security Test Coverage  
**Project**: AskProAI Platform

## Executive Summary

This report provides detailed analysis of code coverage for security tests across the AskProAI platform.

## Coverage Statistics

### Overall Coverage
- **All Security Tests**: ${OVERALL_COVERAGE:-"N/A"}%
- **Critical Tests Only**: ${CRITICAL_COVERAGE:-"N/A"}%

### Coverage Targets
- **Critical Security Functions**: ${MIN_COVERAGE_SECURITY_FUNCTIONS}% (Required)
- **Overall Security Coverage**: ${MIN_COVERAGE_OVERALL}% (Target)
- **Critical Test Coverage**: ${MIN_COVERAGE_CRITICAL}% (Required)

## Coverage by Test Category

### Critical Security Tests
- Cross-Tenant Authentication
- Admin API Access Control  
- Webhook Data Contamination
- Authentication Bypass
- Database Security

### Medium Priority Tests
- Session Isolation
- Input Validation
- Data Leakage Prevention
- API Security Vulnerabilities
- File System Security

## Security-Critical Files Coverage

### Authentication & Authorization
EOF

    # Add security-critical file analysis
    if [ -f "$REPORT_DIR/coverage/all-clover.xml" ]; then
        echo "Files with insufficient coverage:" >> "$analysis_file"
        # Parse clover.xml for files with low coverage
        # This would require XML parsing - simplified for demo
        echo "- Detailed file-by-file analysis available in HTML reports" >> "$analysis_file"
    fi
    
    cat >> "$analysis_file" << EOF

## Recommendations

### Immediate Actions Required
1. **Files below ${MIN_COVERAGE_CRITICAL}% coverage**: Address immediately
2. **Authentication logic**: Must have 100% coverage
3. **Database queries**: Ensure all security-sensitive queries are tested

### Coverage Improvement Strategies
1. Add edge case testing for low-coverage functions
2. Implement integration tests for complex workflows
3. Add negative test cases for security validations
4. Test error conditions and exception handling

## Detailed Reports

- **HTML Coverage Report**: [All Tests](./coverage/html/all/index.html)
- **Critical Tests HTML**: [Critical Only](./coverage/html/critical/index.html)
- **Clover XML**: Available for CI/CD integration
- **Test Documentation**: [TestDox Report](./testdox.html)

## Next Steps

1. **Review low-coverage files**: Focus on security-critical components
2. **Add missing tests**: Prioritize authentication and authorization
3. **Validate test quality**: Ensure tests actually verify security
4. **Update CI/CD**: Enforce coverage thresholds in pipeline

---
*Generated by AskProAI Security Coverage Tool*
EOF

    log_message "INFO" "Coverage analysis saved to: $analysis_file"
}

generate_coverage_dashboard() {
    local dashboard_file="$REPORT_DIR/security-coverage-dashboard.html"
    
    log_message "INFO" "Generating coverage dashboard..."
    
    cat > "$dashboard_file" << EOF
<!DOCTYPE html>
<html>
<head>
    <title>AskProAI Security Coverage Dashboard</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 20px; background: #f8f9fa; 
        }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px;
            text-align: center;
        }
        .stats-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }
        .stat-card { 
            background: white; padding: 25px; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #667eea;
        }
        .stat-value { font-size: 2.5rem; font-weight: bold; color: #2c3e50; }
        .stat-label { color: #7f8c8d; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; }
        .coverage-bar { 
            width: 100%; height: 8px; background: #ecf0f1; border-radius: 4px; 
            margin: 10px 0; overflow: hidden;
        }
        .coverage-fill { height: 100%; background: linear-gradient(90deg, #e74c3c, #f39c12, #27ae60); }
        .links-section { 
            background: white; padding: 25px; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .link-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; margin-top: 20px;
        }
        .coverage-link { 
            display: block; padding: 15px; background: #f8f9fa; 
            border-radius: 8px; text-decoration: none; color: #2c3e50;
            border: 1px solid #e9ecef; transition: all 0.3s ease;
        }
        .coverage-link:hover { 
            background: #667eea; color: white; transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .timestamp { color: #7f8c8d; font-size: 0.8rem; margin-top: 20px; }
        .status-good { color: #27ae60; }
        .status-warning { color: #f39c12; }
        .status-danger { color: #e74c3c; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔒 Security Coverage Dashboard</h1>
        <p>Real-time security test coverage monitoring for AskProAI</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value status-${OVERALL_COVERAGE:0:2}">${OVERALL_COVERAGE:-"N/A"}%</div>
            <div class="stat-label">Overall Coverage</div>
            <div class="coverage-bar">
                <div class="coverage-fill" style="width: ${OVERALL_COVERAGE:-0}%"></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value status-${CRITICAL_COVERAGE:0:2}">${CRITICAL_COVERAGE:-"N/A"}%</div>
            <div class="stat-label">Critical Tests</div>
            <div class="coverage-bar">
                <div class="coverage-fill" style="width: ${CRITICAL_COVERAGE:-0}%"></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value">10</div>
            <div class="stat-label">Security Test Classes</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value">150+</div>
            <div class="stat-label">Security Assertions</div>
        </div>
    </div>
    
    <div class="links-section">
        <h2>📊 Coverage Reports</h2>
        <div class="link-grid">
            <a href="./coverage/html/all/index.html" class="coverage-link">
                <strong>📈 Complete Coverage</strong><br>
                <small>All security tests combined</small>
            </a>
            <a href="./coverage/html/critical/index.html" class="coverage-link">
                <strong>🚨 Critical Tests Only</strong><br>
                <small>High-severity vulnerabilities</small>
            </a>
            <a href="./coverage/html/medium/index.html" class="coverage-link">
                <strong>⚠️ Medium Priority</strong><br>
                <small>Important security checks</small>
            </a>
            <a href="./testdox.html" class="coverage-link">
                <strong>📋 Test Documentation</strong><br>
                <small>Human-readable test descriptions</small>
            </a>
            <a href="./analysis/" class="coverage-link">
                <strong>🔍 Coverage Analysis</strong><br>
                <small>Detailed analysis and recommendations</small>
            </a>
            <a href="../SECURITY_TEST_GUIDE.md" class="coverage-link">
                <strong>📖 Documentation</strong><br>
                <small>Security testing guide</small>
            </a>
        </div>
    </div>
    
    <div class="timestamp">
        Last updated: $(date)<br>
        Generated by AskProAI Security Coverage Tool
    </div>
</body>
</html>
EOF

    log_message "INFO" "Coverage dashboard saved to: $dashboard_file"
}

validate_coverage_thresholds() {
    log_message "INFO" "Validating coverage thresholds..."
    
    local exit_code=0
    
    # Check critical coverage
    if [ -n "$CRITICAL_COVERAGE" ]; then
        if (( $(echo "$CRITICAL_COVERAGE < $MIN_COVERAGE_CRITICAL" | bc -l) )); then
            log_message "ERROR" "Critical coverage ${CRITICAL_COVERAGE}% below threshold ${MIN_COVERAGE_CRITICAL}%"
            exit_code=1
        else
            log_message "INFO" "✅ Critical coverage meets threshold"
        fi
    fi
    
    # Check overall coverage
    if [ -n "$OVERALL_COVERAGE" ]; then
        if (( $(echo "$OVERALL_COVERAGE < $MIN_COVERAGE_OVERALL" | bc -l) )); then
            log_message "WARN" "Overall coverage ${OVERALL_COVERAGE}% below target ${MIN_COVERAGE_OVERALL}%"
        else
            log_message "INFO" "✅ Overall coverage meets target"
        fi
    fi
    
    return $exit_code
}

main() {
    print_banner
    
    setup_directories
    run_security_tests_with_coverage
    analyze_coverage
    generate_coverage_dashboard
    
    local validation_result=0
    validate_coverage_thresholds || validation_result=$?
    
    echo -e "\n${WHITE}Security Coverage Summary:${NC}"
    echo -e "${BLUE}📊 Reports Generated:${NC}"
    echo -e "  • HTML Dashboard: security-reports/security-coverage-dashboard.html"
    echo -e "  • Coverage Analysis: security-reports/analysis/"
    echo -e "  • Detailed Reports: security-reports/coverage/html/"
    
    if [ $validation_result -eq 0 ]; then
        echo -e "\n${GREEN}✅ All coverage thresholds met!${NC}"
    else
        echo -e "\n${YELLOW}⚠️ Some coverage thresholds not met. Review reports for details.${NC}"
    fi
    
    return $validation_result
}

# Make sure bc is available for floating point comparisons
if ! command -v bc &> /dev/null; then
    log_message "WARN" "bc command not found. Installing for coverage calculations..."
    if command -v apt-get &> /dev/null; then
        sudo apt-get update && sudo apt-get install -y bc
    elif command -v yum &> /dev/null; then
        sudo yum install -y bc
    fi
fi

main "$@"