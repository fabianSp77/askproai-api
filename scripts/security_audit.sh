#!/bin/bash

###############################################################################
# AskProAI Security Audit Script
# Version: 1.0
# Created: 2025-09-03
###############################################################################

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_FILE="/var/www/api-gateway/storage/logs/security_audit_$(date +%Y%m%d_%H%M%S).log"
HIGH_RISK=0
MEDIUM_RISK=0
LOW_RISK=0

# Function to print security status
print_security() {
    local level="$1"
    local check="$2"
    local details="$3"
    
    case "$level" in
        "SECURE")
            echo -e "${GREEN}✓${NC} $check"
            [ -n "$details" ] && echo "  $details"
            ;;
        "HIGH")
            echo -e "${RED}✗ HIGH RISK${NC} $check"
            [ -n "$details" ] && echo "  $details"
            ((HIGH_RISK++))
            ;;
        "MEDIUM")
            echo -e "${YELLOW}⚠ MEDIUM RISK${NC} $check"
            [ -n "$details" ] && echo "  $details"
            ((MEDIUM_RISK++))
            ;;
        "LOW")
            echo -e "${BLUE}ℹ LOW RISK${NC} $check"
            [ -n "$details" ] && echo "  $details"
            ((LOW_RISK++))
            ;;
    esac
}

# Function to check file permissions
check_file_permissions() {
    local issues=0
    
    # Check .env file permissions
    if [ -f "$PROJECT_ROOT/.env" ]; then
        local env_perms
        env_perms=$(stat -c "%a" "$PROJECT_ROOT/.env")
        if [ "$env_perms" = "600" ] || [ "$env_perms" = "640" ]; then
            print_security "SECURE" "Environment file permissions" "Permissions: $env_perms"
        else
            print_security "HIGH" "Environment file permissions" "Permissions: $env_perms (should be 600 or 640)"
            ((issues++))
        fi
    else
        print_security "HIGH" "Environment file" ".env file not found"
        ((issues++))
    fi
    
    # Check storage directory permissions
    local storage_perms
    storage_perms=$(stat -c "%a" "$PROJECT_ROOT/storage")
    if [ "$storage_perms" = "775" ] || [ "$storage_perms" = "755" ]; then
        print_security "SECURE" "Storage directory permissions" "Permissions: $storage_perms"
    else
        print_security "MEDIUM" "Storage directory permissions" "Permissions: $storage_perms (recommended: 775)"
        ((issues++))
    fi
    
    # Check bootstrap/cache permissions
    if [ -d "$PROJECT_ROOT/bootstrap/cache" ]; then
        local cache_perms
        cache_perms=$(stat -c "%a" "$PROJECT_ROOT/bootstrap/cache")
        if [ "$cache_perms" = "775" ] || [ "$cache_perms" = "755" ]; then
            print_security "SECURE" "Bootstrap cache permissions" "Permissions: $cache_perms"
        else
            print_security "MEDIUM" "Bootstrap cache permissions" "Permissions: $cache_perms (recommended: 775)"
            ((issues++))
        fi
    fi
    
    return $issues
}

# Function to check sensitive file exposure
check_file_exposure() {
    local issues=0
    local base_url="https://api.askproai.de"
    
    # List of sensitive files to check
    local sensitive_files=(
        "/.env"
        "/.git/config"
        "/.git/HEAD"
        "/composer.json"
        "/composer.lock"
        "/.htaccess"
        "/phpinfo.php"
        "/config/database.php"
        "/storage/logs/laravel.log"
    )
    
    for file in "${sensitive_files[@]}"; do
        local response
        response=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 10 "${base_url}${file}" 2>/dev/null)
        
        case "$response" in
            "200")
                print_security "HIGH" "Exposed file: $file" "HTTP Status: $response - File is accessible"
                ((issues++))
                ;;
            "403")
                print_security "SECURE" "Protected file: $file" "HTTP Status: $response - Access denied"
                ;;
            "404")
                print_security "SECURE" "File not found: $file" "HTTP Status: $response"
                ;;
            *)
                print_security "LOW" "Unknown status for: $file" "HTTP Status: $response"
                ;;
        esac
    done
    
    return $issues
}

# Function to check Laravel security configuration
check_laravel_security() {
    local issues=0
    cd "$PROJECT_ROOT"
    
    # Check APP_DEBUG setting
    if grep -q "^APP_DEBUG=false" .env; then
        print_security "SECURE" "Laravel debug mode" "APP_DEBUG is disabled in production"
    elif grep -q "^APP_DEBUG=true" .env; then
        print_security "HIGH" "Laravel debug mode" "APP_DEBUG is enabled (should be false in production)"
        ((issues++))
    else
        print_security "MEDIUM" "Laravel debug mode" "APP_DEBUG setting not found"
        ((issues++))
    fi
    
    # Check APP_ENV setting
    if grep -q "^APP_ENV=production" .env; then
        print_security "SECURE" "Laravel environment" "APP_ENV is set to production"
    else
        local env_setting
        env_setting=$(grep "^APP_ENV=" .env | cut -d= -f2)
        print_security "MEDIUM" "Laravel environment" "APP_ENV is set to: $env_setting (should be 'production')"
        ((issues++))
    fi
    
    # Check APP_KEY presence
    if grep -q "^APP_KEY=base64:" .env && [ "$(grep "^APP_KEY=" .env | cut -d= -f2 | wc -c)" -gt 10 ]; then
        print_security "SECURE" "Laravel application key" "APP_KEY is properly set"
    else
        print_security "HIGH" "Laravel application key" "APP_KEY is missing or improperly configured"
        ((issues++))
    fi
    
    # Check for HTTPS enforcement
    if grep -q "^FORCE_HTTPS=true" .env || grep -q "^APP_URL=https://" .env; then
        print_security "SECURE" "HTTPS enforcement" "Application configured for HTTPS"
    else
        print_security "MEDIUM" "HTTPS enforcement" "HTTPS enforcement not clearly configured"
        ((issues++))
    fi
    
    return $issues
}

# Function to check database security
check_database_security() {
    local issues=0
    cd "$PROJECT_ROOT"
    
    # Check for default database credentials
    local db_password
    db_password=$(grep "^DB_PASSWORD=" .env | cut -d= -f2)
    
    if [ -z "$db_password" ] || [ "$db_password" = "password" ] || [ "$db_password" = "root" ] || [ "$db_password" = "admin" ]; then
        print_security "HIGH" "Database password" "Weak or default database password detected"
        ((issues++))
    elif [ ${#db_password} -lt 12 ]; then
        print_security "MEDIUM" "Database password" "Database password is shorter than 12 characters"
        ((issues++))
    else
        print_security "SECURE" "Database password" "Database password appears strong"
    fi
    
    # Check database host configuration
    local db_host
    db_host=$(grep "^DB_HOST=" .env | cut -d= -f2)
    if [ "$db_host" = "127.0.0.1" ] || [ "$db_host" = "localhost" ]; then
        print_security "SECURE" "Database host" "Database is configured for local access"
    else
        print_security "LOW" "Database host" "Database host: $db_host (ensure it's properly secured)"
    fi
    
    return $issues
}

# Function to check API key security
check_api_keys() {
    local issues=0
    cd "$PROJECT_ROOT"
    
    # Check for placeholder API keys
    local insecure_patterns=(
        "your_api_key_here"
        "your_secret_here"
        "changeme"
        "example"
        "test"
        "demo"
    )
    
    while IFS= read -r line; do
        for pattern in "${insecure_patterns[@]}"; do
            if [[ "$line" == *"$pattern"* ]] && [[ "$line" == *"="* ]]; then
                local key_name
                key_name=$(echo "$line" | cut -d= -f1)
                print_security "HIGH" "Insecure API key: $key_name" "Contains placeholder value"
                ((issues++))
            fi
        done
    done < .env
    
    # Check for API key length (basic heuristic)
    local api_keys=(
        "CALCOM_API_KEY"
        "RETELL_WEBHOOK_SECRET"
        "CALCOM_WEBHOOK_SECRET"
    )
    
    for key in "${api_keys[@]}"; do
        if grep -q "^${key}=" .env; then
            local key_value
            key_value=$(grep "^${key}=" .env | cut -d= -f2)
            if [ ${#key_value} -lt 20 ]; then
                print_security "MEDIUM" "API key length: $key" "Key appears short (${#key_value} characters)"
                ((issues++))
            else
                print_security "SECURE" "API key: $key" "Key length appears adequate"
            fi
        else
            print_security "LOW" "Missing API key: $key" "Key not found in configuration"
        fi
    done
    
    return $issues
}

# Function to check web server security headers
check_security_headers() {
    local issues=0
    local url="https://api.askproai.de"
    
    # Headers to check
    local security_headers=(
        "X-Frame-Options"
        "X-Content-Type-Options"
        "X-XSS-Protection"
        "Strict-Transport-Security"
        "Content-Security-Policy"
    )
    
    # Get headers
    local headers
    headers=$(curl -s -I "$url" 2>/dev/null)
    
    for header in "${security_headers[@]}"; do
        if echo "$headers" | grep -qi "$header"; then
            local header_value
            header_value=$(echo "$headers" | grep -i "$header" | cut -d: -f2- | tr -d '\r\n' | xargs)
            print_security "SECURE" "Security header: $header" "Value: $header_value"
        else
            case "$header" in
                "X-Frame-Options"|"X-Content-Type-Options")
                    print_security "MEDIUM" "Missing security header: $header" "Recommended for security"
                    ((issues++))
                    ;;
                "Strict-Transport-Security")
                    print_security "HIGH" "Missing security header: $header" "Critical for HTTPS security"
                    ((issues++))
                    ;;
                *)
                    print_security "LOW" "Missing security header: $header" "Consider implementing"
                    ;;
            esac
        fi
    done
    
    return $issues
}

# Function to check for common vulnerabilities
check_common_vulnerabilities() {
    local issues=0
    cd "$PROJECT_ROOT"
    
    # Check for debug/test routes in production
    if grep -r "Route::get.*debug\|Route::get.*test" routes/ 2>/dev/null; then
        print_security "MEDIUM" "Debug/test routes" "Debug or test routes found in route files"
        ((issues++))
    else
        print_security "SECURE" "Debug/test routes" "No obvious debug routes found"
    fi
    
    # Check for hardcoded secrets in code
    local secret_patterns=(
        "password.*=.*['\"][^'\"]{8,}['\"]"
        "secret.*=.*['\"][^'\"]{8,}['\"]"
        "api_key.*=.*['\"][^'\"]{8,}['\"]"
    )
    
    local hardcoded_secrets=0
    for pattern in "${secret_patterns[@]}"; do
        if grep -r -E "$pattern" app/ resources/ 2>/dev/null | grep -v "example\|placeholder\|your_" >/dev/null; then
            ((hardcoded_secrets++))
        fi
    done
    
    if [ $hardcoded_secrets -gt 0 ]; then
        print_security "HIGH" "Hardcoded secrets" "$hardcoded_secrets potential hardcoded secrets found in code"
        ((issues++))
    else
        print_security "SECURE" "Hardcoded secrets" "No obvious hardcoded secrets found"
    fi
    
    # Check for SQL injection protection (basic check)
    if grep -r "DB::select.*\$\|DB::raw.*\$" app/ 2>/dev/null | grep -v "?" >/dev/null; then
        print_security "MEDIUM" "SQL injection risk" "Potential raw SQL queries found - verify parameter binding"
        ((issues++))
    else
        print_security "SECURE" "SQL injection protection" "No obvious SQL injection vulnerabilities"
    fi
    
    return $issues
}

# Function to check log file security
check_log_security() {
    local issues=0
    
    # Check if log files contain sensitive information
    local log_dir="$PROJECT_ROOT/storage/logs"
    if [ -d "$log_dir" ]; then
        # Check for sensitive data in logs (basic patterns)
        local sensitive_patterns=(
            "password"
            "api_key"
            "secret"
            "token.*[A-Za-z0-9]{20,}"
        )
        
        local sensitive_logs=0
        for pattern in "${sensitive_patterns[@]}"; do
            if find "$log_dir" -name "*.log" -exec grep -l -i "$pattern" {} \; 2>/dev/null | head -1 >/dev/null; then
                ((sensitive_logs++))
            fi
        done
        
        if [ $sensitive_logs -gt 0 ]; then
            print_security "MEDIUM" "Sensitive data in logs" "Potential sensitive information found in log files"
            ((issues++))
        else
            print_security "SECURE" "Log file content" "No obvious sensitive data in recent logs"
        fi
        
        # Check log file permissions
        local world_readable_logs
        world_readable_logs=$(find "$log_dir" -name "*.log" -perm -o+r | wc -l)
        if [ $world_readable_logs -gt 0 ]; then
            print_security "MEDIUM" "Log file permissions" "$world_readable_logs log files are world-readable"
            ((issues++))
        else
            print_security "SECURE" "Log file permissions" "Log files have proper permissions"
        fi
    fi
    
    return $issues
}

###############################################################################
# MAIN EXECUTION
###############################################################################

echo "=========================================="
echo "  AskProAI Security Audit"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "=========================================="
echo

# File System Security
echo -e "${BLUE}=== File System Security ===${NC}"
check_file_permissions
echo

# Web Exposure Check
echo -e "${BLUE}=== Web Exposure Check ===${NC}"
check_file_exposure
echo

# Laravel Security Configuration
echo -e "${BLUE}=== Laravel Security ===${NC}"
check_laravel_security
echo

# Database Security
echo -e "${BLUE}=== Database Security ===${NC}"
check_database_security
echo

# API Keys & Secrets
echo -e "${BLUE}=== API Keys & Secrets ===${NC}"
check_api_keys
echo

# Web Server Security Headers
echo -e "${BLUE}=== Security Headers ===${NC}"
check_security_headers
echo

# Common Vulnerabilities
echo -e "${BLUE}=== Vulnerability Assessment ===${NC}"
check_common_vulnerabilities
echo

# Log File Security
echo -e "${BLUE}=== Log File Security ===${NC}"
check_log_security
echo

# Summary
echo "=========================================="
echo "Security Audit Summary:"
echo -e "  ${RED}High Risk Issues: $HIGH_RISK${NC}"
echo -e "  ${YELLOW}Medium Risk Issues: $MEDIUM_RISK${NC}"
echo -e "  ${BLUE}Low Risk Issues: $LOW_RISK${NC}"
echo

if [ $HIGH_RISK -eq 0 ] && [ $MEDIUM_RISK -eq 0 ] && [ $LOW_RISK -eq 0 ]; then
    echo -e "${GREEN}✓ Security Status: EXCELLENT${NC}"
    exit 0
elif [ $HIGH_RISK -eq 0 ] && [ $MEDIUM_RISK -le 2 ]; then
    echo -e "${YELLOW}⚠ Security Status: GOOD (Minor improvements needed)${NC}"
    exit 1
elif [ $HIGH_RISK -eq 0 ]; then
    echo -e "${YELLOW}⚠ Security Status: FAIR (Several improvements needed)${NC}"
    exit 2
else
    echo -e "${RED}✗ Security Status: CRITICAL (Immediate attention required)${NC}"
    exit 3
fi