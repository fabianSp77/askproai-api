#!/bin/bash

###############################################################################
# Debugging Verification Script
#
# Purpose: Automatically gather information needed for bug investigation
# Usage: bash run_debug_verification.sh
# Output: Generates debug_verification_results.txt with all findings
###############################################################################

set -e

OUTPUT_FILE="/var/www/api-gateway/DEBUG_VERIFICATION_RESULTS_$(date +%Y-%m-%d_%H-%M-%S).txt"
PROJECT_ROOT="/var/www/api-gateway"

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

###############################################################################
# Helper Functions
###############################################################################

log_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

log_section() {
    echo -e "\n${YELLOW}>>> $1${NC}"
}

log_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

log_error() {
    echo -e "${RED}✗ $1${NC}"
}

log_info() {
    echo "   $1"
}

# Redirect to file and stdout
exec > >(tee -a "$OUTPUT_FILE")
exec 2>&1

###############################################################################
# BUG #1: Agent Hallucination
###############################################################################

log_header "BUG #1: AGENT HALLUCINATION - Information Gathering"

log_section "1.1: Check for local conversation flow files"
cd "$PROJECT_ROOT"
if find . -name "*flow*.json" -not -path "./vendor/*" | head -10; then
    log_success "Flow JSON files found"
else
    log_error "No flow JSON files found"
fi

log_section "1.2: Check for Retell flow-related database tables"
if command -v mysql &> /dev/null; then
    mysql -h localhost -u root -p -e "SHOW TABLES LIKE '%flow%';" 2>/dev/null || log_error "MySQL query failed (DB credentials needed)"
else
    log_error "MySQL client not available"
fi

log_section "1.3: Check for deployment scripts"
ls -la "$PROJECT_ROOT/scripts/deployment/" 2>/dev/null || log_info "No deployment scripts found"

log_section "1.4: Check DeployConversationFlow command"
grep -A 10 "class DeployConversationFlow" "$PROJECT_ROOT/app/Console/Commands/DeployConversationFlow.php" 2>/dev/null | head -15 || log_error "File not found"

log_section "1.5: Check RetellPromptTemplateService"
grep -n "function\|public" "$PROJECT_ROOT/app/Services/Retell/RetellPromptTemplateService.php" 2>/dev/null | head -20 || log_error "File not found"

log_section "1.6: Check ServiceSelectionService"
grep -n "class\|public function" "$PROJECT_ROOT/app/Services/Retell/ServiceSelectionService.php" 2>/dev/null | head -15 || log_info "ServiceSelectionService methods"

log_section "1.7: Check for flow testing framework"
find "$PROJECT_ROOT" -name "*Simulator*.php" -o -name "*FlowValidation*.php" -o -name "*MockFunction*.php" | grep -v vendor

log_section "1.8: Check WebhookResponseService for response format"
grep -A 5 "return_value\|function_result\|webhook_response" "$PROJECT_ROOT/app/Services/Retell/WebhookResponseService.php" 2>/dev/null | head -20 || log_error "File not found"

###############################################################################
# BUG #2: Date Parsing
###############################################################################

log_header "BUG #2: DATE PARSING - Information Gathering"

log_section "2.1: Verify DateTimeParser exists and shows methods"
wc -l "$PROJECT_ROOT/app/Services/Retell/DateTimeParser.php" || log_error "File not found"
grep -n "public function" "$PROJECT_ROOT/app/Services/Retell/DateTimeParser.php" | wc -l && log_success "Public methods count:"
grep "public function" "$PROJECT_ROOT/app/Services/Retell/DateTimeParser.php" | sed 's/.*function //' | sed 's/(.*$//'

log_section "2.2: Check for German date mapping"
grep -A 15 "GERMAN_DATE_MAP" "$PROJECT_ROOT/app/Services/Retell/DateTimeParser.php" | head -20

log_section "2.3: List all supported input formats (from code comments)"
grep -B 2 "Format\|formats:\|Handles:" "$PROJECT_ROOT/app/Services/Retell/DateTimeParser.php" | head -30

log_section "2.4: Check timezone handling"
grep -n "Europe/Berlin\|timezone\|GMT\|UTC" "$PROJECT_ROOT/app/Services/Retell/DateTimeParser.php" | head -10

log_section "2.5: Verify test file exists"
if [ -f "$PROJECT_ROOT/tests/Unit/Services/Retell/DateTimeParserShortFormatTest.php" ]; then
    log_success "DateTimeParser test file found"
    wc -l "$PROJECT_ROOT/tests/Unit/Services/Retell/DateTimeParserShortFormatTest.php"
else
    log_error "DateTimeParser test file not found"
fi

log_section "2.6: Check for request-scoped caching"
grep -n "callTimeCache\|getCachedBerlinTime" "$PROJECT_ROOT/app/Services/Retell/DateTimeParser.php" | head -10

###############################################################################
# BUG #3: Email Crash (ICS)
###############################################################################

log_header "BUG #3: EMAIL CRASH (ICS ATTACHMENT) - Information Gathering"

log_section "3.1: Check installed ICS library"
grep "spatie/icalendar" "$PROJECT_ROOT/composer.json"

log_section "3.2: Verify library is installed"
if [ -d "$PROJECT_ROOT/vendor/spatie/icalendar-generator" ]; then
    log_success "ICS library installed"
    composer show spatie/icalendar-generator 2>/dev/null || log_info "Composer show failed"
    ls -la "$PROJECT_ROOT/vendor/spatie/icalendar-generator/src/" | head -10
else
    log_error "ICS library not installed"
fi

log_section "3.3: Find where ICS is generated"
grep -r "icalendar\|\.ics\|ICalendar" "$PROJECT_ROOT/app" --include="*.php" | grep -v "vendor" | cut -d: -f1 | sort | uniq -c

log_section "3.4: Find mailable/notification classes"
find "$PROJECT_ROOT/app" -name "*Mail.php" -o -name "*Mailable.php" -o -name "*Notification.php" | grep -v vendor | head -20

log_section "3.5: Check for email attachment code"
grep -r "->attach\|attachFrom\|attaches" "$PROJECT_ROOT/app" --include="*.php" | cut -d: -f1 | sort | uniq

log_section "3.6: Check mail configuration"
grep -A 20 "mail\|MAIL" "$PROJECT_ROOT/config/mail.php" 2>/dev/null | head -30 || log_error "Mail config not found"

log_section "3.7: Find notification queue jobs"
find "$PROJECT_ROOT/app/Jobs" -name "*Notification*.php" -o -name "*Mail*.php" -o -name "*Email*.php" | head -15

log_section "3.8: Check for recent package updates related to mail/notification"
git log --oneline --all composer.json 2>/dev/null | grep -i "mail\|ical\|notification\|email" | head -10 || log_info "Git history not available"

###############################################################################
# BUG #4: V9 Not Deployed
###############################################################################

log_header "BUG #4: V9 NOT DEPLOYED - Information Gathering"

log_section "4.1: Check current HEAD commit"
cd "$PROJECT_ROOT"
git log --oneline -1 || log_error "Git not available"

log_section "4.2: Show recent commits"
git log --oneline -20 || log_error "Git not available"

log_section "4.3: Check for V9/version references in recent commits"
git log --oneline --all 2>/dev/null | grep -i "v9\|version.*9\|V[0-9]" | head -20 || log_info "No V9 references found"

log_section "4.4: Check current branch"
git branch || log_error "Git not available"

log_section "4.5: Check for uncommitted changes"
git status || log_error "Git not available"

log_section "4.6: Search for version constants in code"
grep -r "VERSION\|v[0-9]\|const.*VERSION" "$PROJECT_ROOT/app" --include="*.php" | head -20 || log_info "No version constants found"

log_section "4.7: Check .env for version info"
grep -i "version\|v[0-9]" "$PROJECT_ROOT/.env" 2>/dev/null || log_info "No version in .env"

log_section "4.8: Check for versioned class names"
find "$PROJECT_ROOT/app" -name "*v[0-9]*.php" -o -name "*V[0-9]*.php" | grep -v vendor | head -15

log_section "4.9: Check OPcache status"
php -r "
\$status = @opcache_get_status();
if (\$status === false) {
    echo 'OPcache not available or disabled';
} else {
    echo 'OPcache Status: ' . (\$status['opcache_enabled'] ? 'ENABLED' : 'DISABLED') . PHP_EOL;
    echo 'Memory used: ' . (\$status['memory_usage']['used_memory'] ?? 'N/A') . PHP_EOL;
    echo 'Cache full: ' . (\$status['cache_full'] ? 'YES' : 'NO') . PHP_EOL;
}
" || log_error "OPcache check failed"

log_section "4.10: Check Laravel cache directories"
ls -lad "$PROJECT_ROOT/storage/framework/cache" 2>/dev/null || log_error "Cache directory not found"
ls -la "$PROJECT_ROOT/storage/framework/cache/" 2>/dev/null | head -10

log_section "4.11: Check key service file modification times"
stat "$PROJECT_ROOT/app/Services/Retell/ServiceSelectionService.php" 2>/dev/null | grep -E "Modify|Access|Change" || log_error "File not found"

log_section "4.12: Check PHP-FPM process status"
systemctl status php-fpm 2>/dev/null | grep -E "Active|Process" || ps aux | grep php-fpm | head -3

###############################################################################
# TESTING & VALIDATION
###############################################################################

log_header "TESTING & VALIDATION - Information Gathering"

log_section "5.1: Check Retell configuration"
grep "RETELL\|retell" "$PROJECT_ROOT/.env" 2>/dev/null | grep -v "token\|secret" | head -10 || log_info "No Retell config visible in .env"

log_section "5.2: Check for test phone numbers"
grep -r "phone.*test\|test.*phone\|+49" "$PROJECT_ROOT/.env*" 2>/dev/null || log_info "No test phone numbers configured"

log_section "5.3: Check Cal.com service configuration"
grep -A 10 "'calcom'" "$PROJECT_ROOT/config/services.php" | head -15

log_section "5.4: Check for test/staging environment setup"
grep -r "APP_ENV\|STAGING\|TEST" "$PROJECT_ROOT/.env" 2>/dev/null || log_info "No staging config found"

log_section "5.5: List available test files"
find "$PROJECT_ROOT/tests" -name "*.php" -type f | wc -l && echo "Test files found"
find "$PROJECT_ROOT/tests" -name "*Retell*.php" -o -name "*DateTime*.php" | head -15

log_section "5.6: Check test runner configuration"
if [ -f "$PROJECT_ROOT/pest.xml" ] || [ -f "$PROJECT_ROOT/phpunit.xml" ]; then
    log_success "Test configuration found"
    ls -la "$PROJECT_ROOT/pest.xml" "$PROJECT_ROOT/phpunit.xml" 2>/dev/null
else
    log_error "No test configuration found"
fi

###############################################################################
# Summary
###############################################################################

log_header "SUMMARY"

log_info "Verification complete!"
log_info "Output saved to: $OUTPUT_FILE"
log_info ""
log_info "Next steps:"
log_info "1. Review the output file for all findings"
log_info "2. Cross-reference with DEBUGGING_CHECKLIST_COMPREHENSIVE.md"
log_info "3. Identify missing information items"
log_info "4. Perform manual tests for items marked [Manual Testing Needed]"
log_info "5. Create focused RCA for each bug"
log_info "6. Implement fixes in priority order"

echo ""
echo "Verification Results Summary:"
echo "  - BUG #1 (Agent Hallucination): See sections 1.1-1.8"
echo "  - BUG #2 (Date Parsing): See sections 2.1-2.6"
echo "  - BUG #3 (Email Crash): See sections 3.1-3.8"
echo "  - BUG #4 (V9 Not Deployed): See sections 4.1-4.12"
echo "  - Testing/Validation: See sections 5.1-5.6"
