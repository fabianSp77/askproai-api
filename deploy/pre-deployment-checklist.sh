#!/bin/bash

# AskProAI Pre-Deployment Checklist
# Version: 2.0.0
# Description: Comprehensive pre-deployment validation with detailed reporting

set -euo pipefail
IFS=$'\n\t'

# Configuration
readonly APP_DIR="${APP_DIR:-/var/www/api-gateway}"
readonly REPORT_FILE="/tmp/askproai-deployment-checklist-$(date +%Y%m%d_%H%M%S).html"
readonly JSON_REPORT="/tmp/askproai-deployment-checklist-$(date +%Y%m%d_%H%M%S).json"

# Parse arguments
ENVIRONMENT="${1:-production}"
VERBOSE=false
OUTPUT_FORMAT="terminal"  # terminal, html, json

while [[ $# -gt 0 ]]; do
    case $1 in
        --verbose|-v)
            VERBOSE=true
            shift
            ;;
        --output=*)
            OUTPUT_FORMAT="${1#*=}"
            shift
            ;;
        *)
            shift
            ;;
    esac
done

# Colors
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly PURPLE='\033[0;35m'
readonly NC='\033[0m'

# Check results storage
declare -A CHECK_RESULTS
declare -A CHECK_DETAILS
TOTAL_CHECKS=0
PASSED_CHECKS=0
FAILED_CHECKS=0
WARNING_CHECKS=0

# Icons
ICON_PASS="✅"
ICON_FAIL="❌"
ICON_WARN="⚠️"
ICON_INFO="ℹ️"
ICON_SKIP="⏭️"

# Start HTML report
init_html_report() {
    cat > "$REPORT_FILE" <<EOF
<!DOCTYPE html>
<html>
<head>
    <title>AskProAI Deployment Checklist - $(date)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #3B82F6; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .summary { display: flex; gap: 20px; margin: 20px 0; }
        .summary-item { padding: 15px; border-radius: 5px; flex: 1; text-align: center; }
        .summary-pass { background: #10B981; color: white; }
        .summary-fail { background: #EF4444; color: white; }
        .summary-warn { background: #F59E0B; color: white; }
        .check { margin: 10px 0; padding: 10px; border-left: 4px solid; }
        .check-pass { border-color: #10B981; background: #F0FDF4; }
        .check-fail { border-color: #EF4444; background: #FEF2F2; }
        .check-warn { border-color: #F59E0B; background: #FFFBEB; }
        .check-title { font-weight: bold; margin-bottom: 5px; }
        .check-details { font-size: 0.9em; color: #666; white-space: pre-wrap; }
        .timestamp { color: #999; font-size: 0.9em; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .badge { padding: 2px 8px; border-radius: 3px; font-size: 0.8em; }
        .badge-success { background: #10B981; color: white; }
        .badge-danger { background: #EF4444; color: white; }
        .badge-warning { background: #F59E0B; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>AskProAI Pre-Deployment Checklist</h1>
        <p class="timestamp">Generated: $(date)</p>
        <p>Environment: <strong>$ENVIRONMENT</strong></p>
EOF
}

# Initialize JSON report
init_json_report() {
    echo "{" > "$JSON_REPORT"
    echo "  \"timestamp\": \"$(date -u +%Y-%m-%dT%H:%M:%SZ)\"," >> "$JSON_REPORT"
    echo "  \"environment\": \"$ENVIRONMENT\"," >> "$JSON_REPORT"
    echo "  \"checks\": [" >> "$JSON_REPORT"
}

# Helper function to run checks
run_check() {
    local category="$1"
    local name="$2"
    local command="$3"
    local severity="${4:-critical}"  # critical, warning, info
    
    ((TOTAL_CHECKS++))
    
    if [ "$VERBOSE" == "true" ]; then
        echo -e "${BLUE}Running:${NC} $name"
    fi
    
    local start_time=$(date +%s.%N)
    local output=""
    local status="fail"
    local icon="$ICON_FAIL"
    local color="$RED"
    
    if output=$(eval "$command" 2>&1); then
        status="pass"
        icon="$ICON_PASS"
        color="$GREEN"
        ((PASSED_CHECKS++))
    else
        if [ "$severity" == "warning" ]; then
            status="warn"
            icon="$ICON_WARN"
            color="$YELLOW"
            ((WARNING_CHECKS++))
        else
            ((FAILED_CHECKS++))
        fi
    fi
    
    local end_time=$(date +%s.%N)
    local duration=$(echo "$end_time - $start_time" | bc)
    
    CHECK_RESULTS["$category:$name"]="$status"
    CHECK_DETAILS["$category:$name"]="$output"
    
    # Terminal output
    if [ "$OUTPUT_FORMAT" == "terminal" ] || [ "$VERBOSE" == "true" ]; then
        printf "%s %-50s %s(%.2fs)%s\n" "$icon" "$name" "$color" "$duration" "$NC"
        if [ "$VERBOSE" == "true" ] && [ -n "$output" ]; then
            echo -e "  ${color}Details:${NC} $output"
        fi
    fi
    
    # Add to reports
    add_to_html_report "$category" "$name" "$status" "$output" "$duration"
    add_to_json_report "$category" "$name" "$status" "$output" "$duration"
}

# Add check result to HTML report
add_to_html_report() {
    local category="$1"
    local name="$2"
    local status="$3"
    local details="$4"
    local duration="$5"
    
    local class="check-$status"
    
    cat >> "$REPORT_FILE" <<EOF
        <div class="check $class">
            <div class="check-title">[$category] $name <span style="float:right">${duration}s</span></div>
            <div class="check-details">$details</div>
        </div>
EOF
}

# Add check result to JSON report
add_to_json_report() {
    local category="$1"
    local name="$2"
    local status="$3"
    local details="$4"
    local duration="$5"
    
    # Escape JSON strings
    details=$(echo "$details" | sed 's/"/\\"/g' | tr '\n' ' ')
    
    if [ $TOTAL_CHECKS -gt 1 ]; then
        echo "," >> "$JSON_REPORT"
    fi
    
    cat >> "$JSON_REPORT" <<EOF
    {
      "category": "$category",
      "name": "$name",
      "status": "$status",
      "details": "$details",
      "duration": $duration
    }
EOF
}

# Section header
print_section() {
    local title="$1"
    
    if [ "$OUTPUT_FORMAT" == "terminal" ]; then
        echo -e "\n${BLUE}=== $title ===${NC}"
    fi
    
    echo "<h2>$title</h2>" >> "$REPORT_FILE"
}

# Run all checks
run_all_checks() {
    cd "$APP_DIR"
    
    # 1. Environment Checks
    print_section "Environment Checks"
    
    run_check "Environment" "PHP Version >= 8.2" \
        "php -r 'exit(version_compare(PHP_VERSION, \"8.2.0\", \">=\") ? 0 : 1);'"
    
    run_check "Environment" "Composer Installed" \
        "composer --version"
    
    run_check "Environment" "Node.js >= 16" \
        "node -v | grep -E 'v(1[6-9]|[2-9][0-9])'"
    
    run_check "Environment" "Required PHP Extensions" \
        "php -m | grep -E '^(pdo|pdo_mysql|mbstring|xml|curl|json|bcmath|redis)$' | wc -l | grep -q '^8$'"
    
    run_check "Environment" "OPcache Enabled" \
        "php -i | grep -q 'opcache.enable => On'" "warning"
    
    # 2. Application Checks
    print_section "Application Configuration"
    
    run_check "Application" "Environment File Exists" \
        "[ -f .env.$ENVIRONMENT ]"
    
    run_check "Application" "APP_KEY Set" \
        "grep -q '^APP_KEY=.\{32,\}' .env.$ENVIRONMENT"
    
    run_check "Application" "Debug Mode Disabled (Production)" \
        "[ \"$ENVIRONMENT\" != \"production\" ] || grep -q '^APP_DEBUG=false' .env.$ENVIRONMENT"
    
    run_check "Application" "HTTPS Forced (Production)" \
        "[ \"$ENVIRONMENT\" != \"production\" ] || grep -q '^FORCE_HTTPS=true' .env.$ENVIRONMENT" "warning"
    
    # 3. Database Checks
    print_section "Database Status"
    
    run_check "Database" "Database Connection" \
        "php artisan db:show --env=$ENVIRONMENT"
    
    run_check "Database" "Migrations Up-to-date" \
        "! php artisan migrate:status --env=$ENVIRONMENT | grep -q 'Pending'"
    
    run_check "Database" "Database Backup Recent" \
        "find /var/backups/askproai -name '*.sql.gz' -mtime -1 | grep -q ." "warning"
    
    # 4. External Services
    print_section "External Service Connectivity"
    
    run_check "Services" "Cal.com API Key Set" \
        "grep -q '^DEFAULT_CALCOM_API_KEY=.\+' .env.$ENVIRONMENT"
    
    run_check "Services" "Retell.ai API Key Set" \
        "grep -q '^DEFAULT_RETELL_API_KEY=.\+' .env.$ENVIRONMENT"
    
    run_check "Services" "Cal.com API Reachable" \
        "curl -s -o /dev/null -w '%{http_code}' https://api.cal.com/v2/health | grep -q '200'" "warning"
    
    run_check "Services" "Retell.ai API Reachable" \
        "curl -s -o /dev/null -w '%{http_code}' https://api.retellai.com | grep -E '200|401'" "warning"
    
    run_check "Services" "Redis Connection" \
        "redis-cli ping | grep -q PONG"
    
    # 5. Dependencies
    print_section "Dependencies"
    
    run_check "Dependencies" "Composer Dependencies Installed" \
        "[ -f vendor/autoload.php ]"
    
    run_check "Dependencies" "No Composer Security Vulnerabilities" \
        "! composer audit 2>&1 | grep -q 'advisories'" "warning"
    
    run_check "Dependencies" "NPM Dependencies Installed" \
        "[ -d node_modules ]"
    
    run_check "Dependencies" "Assets Built" \
        "[ -f public/build/manifest.json ]" "warning"
    
    # 6. File System
    print_section "File System & Permissions"
    
    run_check "FileSystem" "Storage Directory Writable" \
        "[ -w storage ]"
    
    run_check "FileSystem" "Bootstrap Cache Writable" \
        "[ -w bootstrap/cache ]"
    
    run_check "FileSystem" "Log Directory Writable" \
        "[ -w storage/logs ]"
    
    run_check "FileSystem" "Disk Space Available (>5GB)" \
        "[ $(df -BG /var/www | awk 'NR==2 {print int($4)}') -gt 5 ]"
    
    # 7. Queue System
    print_section "Queue System"
    
    run_check "Queue" "Queue Configuration Valid" \
        "grep -q '^QUEUE_CONNECTION=redis' .env.$ENVIRONMENT"
    
    run_check "Queue" "Horizon Installed" \
        "[ -f vendor/bin/horizon ]"
    
    run_check "Queue" "Failed Jobs Table Exists" \
        "php artisan tinker --execute=\"Schema::hasTable('failed_jobs')\" | grep -q true"
    
    # 8. Security
    print_section "Security Configuration"
    
    run_check "Security" "Webhook Secrets Configured" \
        "grep -q '^CALCOM_WEBHOOK_SECRET=.\+' .env.$ENVIRONMENT && grep -q '^RETELL_WEBHOOK_SECRET=.\+' .env.$ENVIRONMENT"
    
    run_check "Security" "BCRYPT Rounds Appropriate" \
        "grep -E '^BCRYPT_ROUNDS=(1[0-9]|[2-9][0-9])' .env.$ENVIRONMENT" "warning"
    
    run_check "Security" "Session Encryption Enabled" \
        "grep -q '^SESSION_ENCRYPT=true' .env.$ENVIRONMENT" "warning"
    
    # 9. Performance
    print_section "Performance Configuration"
    
    run_check "Performance" "Config Cacheable" \
        "php artisan config:cache --env=$ENVIRONMENT && php artisan config:clear"
    
    run_check "Performance" "Routes Cacheable" \
        "php artisan route:cache --env=$ENVIRONMENT && php artisan route:clear"
    
    run_check "Performance" "Redis Available for Cache" \
        "grep -q '^CACHE_DRIVER=redis' .env.$ENVIRONMENT"
    
    # 10. Monitoring
    print_section "Monitoring & Logging"
    
    run_check "Monitoring" "Health Endpoint Accessible" \
        "curl -s -o /dev/null -w '%{http_code}' http://localhost/api/health | grep -q '200'"
    
    run_check "Monitoring" "Log Files Writable" \
        "touch storage/logs/test.log && rm storage/logs/test.log"
    
    run_check "Monitoring" "Error Tracking Configured" \
        "grep -q '^SENTRY_LARAVEL_DSN=.\+' .env.$ENVIRONMENT" "warning"
    
    # 11. Git Status
    print_section "Version Control"
    
    run_check "Git" "Git Repository Clean" \
        "[ -z \"$(git status --porcelain)\" ]" "warning"
    
    run_check "Git" "On Correct Branch" \
        "git branch --show-current | grep -E '^(main|master|production)$'" "warning"
    
    run_check "Git" "No Uncommitted Changes" \
        "git diff-index --quiet HEAD --" "warning"
    
    # 12. Laravel Specific
    print_section "Laravel Framework"
    
    run_check "Laravel" "Maintenance Mode Off" \
        "! [ -f storage/framework/down ]"
    
    run_check "Laravel" "No Scheduled Task Errors" \
        "php artisan schedule:list | grep -v 'No scheduled commands'" "warning"
    
    run_check "Laravel" "Event Listeners Registered" \
        "php artisan event:list | grep -q 'Registered'" "warning"
}

# Generate summary
generate_summary() {
    local pass_rate=$(echo "scale=2; $PASSED_CHECKS * 100 / $TOTAL_CHECKS" | bc)
    
    if [ "$OUTPUT_FORMAT" == "terminal" ]; then
        echo -e "\n${BLUE}=== Deployment Readiness Summary ===${NC}"
        echo -e "Total Checks: $TOTAL_CHECKS"
        echo -e "Passed: ${GREEN}$PASSED_CHECKS${NC}"
        echo -e "Warnings: ${YELLOW}$WARNING_CHECKS${NC}"
        echo -e "Failed: ${RED}$FAILED_CHECKS${NC}"
        echo -e "Pass Rate: ${pass_rate}%"
        
        if [ $FAILED_CHECKS -eq 0 ]; then
            echo -e "\n${GREEN}✅ All critical checks passed! Ready for deployment.${NC}"
            exit_code=0
        else
            echo -e "\n${RED}❌ $FAILED_CHECKS critical checks failed. Fix these before deploying.${NC}"
            exit_code=1
        fi
    fi
    
    # Add summary to HTML report
    cat >> "$REPORT_FILE" <<EOF
        <div class="summary">
            <div class="summary-item summary-pass">
                <h3>$PASSED_CHECKS</h3>
                <p>Passed</p>
            </div>
            <div class="summary-item summary-warn">
                <h3>$WARNING_CHECKS</h3>
                <p>Warnings</p>
            </div>
            <div class="summary-item summary-fail">
                <h3>$FAILED_CHECKS</h3>
                <p>Failed</p>
            </div>
        </div>
        <p><strong>Pass Rate:</strong> ${pass_rate}%</p>
    </div>
</body>
</html>
EOF
    
    # Complete JSON report
    echo -e "\n  ]," >> "$JSON_REPORT"
    echo "  \"summary\": {" >> "$JSON_REPORT"
    echo "    \"total\": $TOTAL_CHECKS," >> "$JSON_REPORT"
    echo "    \"passed\": $PASSED_CHECKS," >> "$JSON_REPORT"
    echo "    \"warnings\": $WARNING_CHECKS," >> "$JSON_REPORT"
    echo "    \"failed\": $FAILED_CHECKS," >> "$JSON_REPORT"
    echo "    \"pass_rate\": $pass_rate" >> "$JSON_REPORT"
    echo "  }" >> "$JSON_REPORT"
    echo "}" >> "$JSON_REPORT"
}

# Main execution
main() {
    echo -e "${PURPLE}AskProAI Pre-Deployment Checklist${NC}"
    echo -e "Environment: ${BLUE}$ENVIRONMENT${NC}"
    echo -e "Time: $(date)\n"
    
    # Initialize reports
    init_html_report
    init_json_report
    
    # Run all checks
    run_all_checks
    
    # Generate summary
    generate_summary
    
    # Show report locations
    echo -e "\nReports generated:"
    echo -e "  HTML: file://$REPORT_FILE"
    echo -e "  JSON: $JSON_REPORT"
    
    # Open HTML report if on desktop
    if [ "$OUTPUT_FORMAT" == "html" ] && command -v xdg-open &> /dev/null; then
        xdg-open "$REPORT_FILE"
    fi
    
    exit ${exit_code:-1}
}

# Run main function
main "$@"