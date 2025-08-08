#!/bin/bash

#############################################################################
# MCP Deployment Validation Script
# Validates all deployment artifacts and configurations
#############################################################################

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
CONFIG_DIR="$PROJECT_DIR/config"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Validation results
PASS_COUNT=0
WARN_COUNT=0
FAIL_COUNT=0

# Logging functions
log_pass() {
    echo -e "${GREEN}[PASS]${NC} $1"
    ((PASS_COUNT++))
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
    ((WARN_COUNT++))
}

log_fail() {
    echo -e "${RED}[FAIL]${NC} $1"
    ((FAIL_COUNT++))
}

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

#############################################################################
# Validation Functions
#############################################################################

validate_files() {
    log_info "Validating deployment files..."
    
    # Required scripts
    local required_scripts=(
        "deploy-mcp-migration.sh"
        "mcp-health-check.sh"
        "rollback-mcp.sh"
        "generate-prometheus-config.sh"
        "generate-grafana-dashboard.sh"
    )
    
    for script in "${required_scripts[@]}"; do
        local script_path="$SCRIPT_DIR/$script"
        if [[ -f "$script_path" ]]; then
            if [[ -x "$script_path" ]]; then
                log_pass "Script exists and is executable: $script"
            else
                log_warn "Script not executable: $script"
            fi
        else
            log_fail "Missing required script: $script"
        fi
    done
    
    # Configuration files
    local config_files=(
        ".env.mcp.example"
        "config/prometheus-mcp.yml"
        "config/mcp_alerts.yml"
        "config/grafana-mcp-dashboard.json"
    )
    
    for config in "${config_files[@]}"; do
        local config_path="$PROJECT_DIR/$config"
        if [[ -f "$config_path" ]]; then
            log_pass "Configuration file exists: $config"
        else
            log_fail "Missing configuration file: $config"
        fi
    done
    
    # Documentation
    if [[ -f "$PROJECT_DIR/MCP_DEPLOYMENT_GUIDE.md" ]]; then
        log_pass "Deployment guide exists"
    else
        log_warn "Deployment guide not found"
    fi
}

validate_environment() {
    log_info "Validating environment configuration..."
    
    cd "$PROJECT_DIR"
    
    # Check if .env.mcp.example can be processed
    if [[ -f ".env.mcp.example" ]]; then
        if grep -q "MCP_RETELL_AGENT_TOKEN" ".env.mcp.example"; then
            log_pass "MCP environment template contains required tokens"
        else
            log_fail "MCP environment template missing required tokens"
        fi
        
        if grep -q "MCP_MIGRATION_MODE" ".env.mcp.example"; then
            log_pass "MCP environment template contains migration settings"
        else
            log_fail "MCP environment template missing migration settings"
        fi
    fi
    
    # Check current environment
    if [[ -f ".env" ]]; then
        # Check MCP endpoint configuration
        if php artisan route:list | grep -q "api/mcp/retell/tools"; then
            log_pass "MCP tools endpoint is registered"
        else
            log_fail "MCP tools endpoint not found in routes"
        fi
        
        # Check middleware
        if php artisan route:list | grep "api/mcp/retell/tools" | grep -q "verify.mcp.token"; then
            log_pass "MCP endpoint has authentication middleware"
        else
            log_warn "MCP endpoint authentication middleware not confirmed"
        fi
    else
        log_warn "Main .env file not found, cannot validate current configuration"
    fi
}

validate_monitoring_config() {
    log_info "Validating monitoring configuration..."
    
    # Prometheus configuration
    local prometheus_config="$CONFIG_DIR/prometheus-mcp.yml"
    if [[ -f "$prometheus_config" ]]; then
        # Check for required sections
        if grep -q "askproai-mcp" "$prometheus_config"; then
            log_pass "Prometheus config contains MCP job"
        else
            log_fail "Prometheus config missing MCP scrape job"
        fi
        
        if grep -q "mcp_alerts.yml" "$prometheus_config"; then
            log_pass "Prometheus config references alert rules"
        else
            log_fail "Prometheus config missing alert rules reference"
        fi
    fi
    
    # Alert rules
    local alerts_config="$CONFIG_DIR/mcp_alerts.yml"
    if [[ -f "$alerts_config" ]]; then
        if grep -q "MCPHighResponseTime" "$alerts_config"; then
            log_pass "Alert rules contain response time alerts"
        else
            log_fail "Alert rules missing response time alerts"
        fi
        
        if grep -q "MCPCircuitBreakerOpen" "$alerts_config"; then
            log_pass "Alert rules contain circuit breaker alerts"
        else
            log_fail "Alert rules missing circuit breaker alerts"
        fi
    fi
    
    # Grafana dashboard
    local dashboard_config="$CONFIG_DIR/grafana-mcp-dashboard.json"
    if [[ -f "$dashboard_config" ]]; then
        if command -v jq &> /dev/null; then
            if jq -e '.dashboard.title' "$dashboard_config" | grep -q "MCP"; then
                log_pass "Grafana dashboard JSON is valid and contains MCP title"
            else
                log_fail "Grafana dashboard JSON validation failed"
            fi
        else
            log_warn "jq not available, cannot validate Grafana dashboard JSON"
        fi
    fi
}

validate_dependencies() {
    log_info "Validating system dependencies..."
    
    cd "$PROJECT_DIR"
    
    # PHP and Laravel
    if command -v php &> /dev/null; then
        local php_version=$(php -r "echo PHP_VERSION;")
        if [[ "$(echo "$php_version" | cut -d. -f1)" -ge 8 ]]; then
            log_pass "PHP version is compatible: $php_version"
        else
            log_fail "PHP version too old: $php_version (requires 8.0+)"
        fi
    else
        log_fail "PHP not found"
    fi
    
    if php artisan --version &> /dev/null; then
        local laravel_version=$(php artisan --version | grep -oP 'Laravel Framework \K[0-9]+\.[0-9]+\.[0-9]+')
        log_pass "Laravel is working: $laravel_version"
    else
        log_fail "Laravel artisan not working"
    fi
    
    # Database
    if php artisan db:show &> /dev/null; then
        log_pass "Database connection is working"
    else
        log_fail "Database connection failed"
    fi
    
    # Redis
    if command -v redis-cli &> /dev/null; then
        if redis-cli ping &> /dev/null; then
            log_pass "Redis is accessible"
        else
            log_fail "Redis connection failed"
        fi
    else
        log_warn "Redis CLI not available"
    fi
    
    # Optional monitoring tools
    if command -v curl &> /dev/null; then
        log_pass "curl is available for API testing"
    else
        log_warn "curl not available"
    fi
    
    if command -v jq &> /dev/null; then
        log_pass "jq is available for JSON processing"
    else
        log_warn "jq not available (recommended for monitoring)"
    fi
}

validate_permissions() {
    log_info "Validating file permissions and directories..."
    
    # Check script permissions
    for script in "$SCRIPT_DIR"/*.sh; do
        if [[ -x "$script" ]]; then
            log_pass "Script is executable: $(basename "$script")"
        else
            log_fail "Script not executable: $(basename "$script")"
        fi
    done
    
    # Check backup directory
    local backup_dir="/var/backups/mcp-migration"
    if mkdir -p "$backup_dir" 2>/dev/null; then
        if [[ -w "$backup_dir" ]]; then
            log_pass "Backup directory is writable: $backup_dir"
        else
            log_fail "Backup directory not writable: $backup_dir"
        fi
    else
        log_fail "Cannot create backup directory: $backup_dir"
    fi
    
    # Check log directory
    local log_dir="/var/log"
    if [[ -w "$log_dir" ]]; then
        log_pass "Log directory is writable: $log_dir"
    else
        log_fail "Log directory not writable: $log_dir"
    fi
    
    # Check project directory permissions
    if [[ -w "$PROJECT_DIR" ]]; then
        log_pass "Project directory is writable"
    else
        log_fail "Project directory not writable"
    fi
}

validate_network() {
    log_info "Validating network connectivity..."
    
    # Check external API connectivity
    if curl -s --connect-timeout 10 "https://api.retellai.com" > /dev/null; then
        log_pass "Retell.ai API is reachable"
    else
        log_warn "Retell.ai API connectivity issue"
    fi
    
    if curl -s --connect-timeout 10 "https://api.cal.com" > /dev/null; then
        log_pass "Cal.com API is reachable"
    else
        log_warn "Cal.com API connectivity issue"
    fi
    
    # Check local application
    local app_url="${APP_URL:-https://api.askproai.de}"
    if curl -s --connect-timeout 10 "$app_url/api/health" > /dev/null; then
        log_pass "Local application is reachable: $app_url"
    else
        log_fail "Local application not reachable: $app_url"
    fi
}

#############################################################################
# Report Generation
#############################################################################

generate_report() {
    echo
    log_info "=== MCP Deployment Validation Report ==="
    echo
    
    local total=$((PASS_COUNT + WARN_COUNT + FAIL_COUNT))
    
    echo "Total Checks: $total"
    echo -e "${GREEN}Passed: $PASS_COUNT${NC}"
    echo -e "${YELLOW}Warnings: $WARN_COUNT${NC}"
    echo -e "${RED}Failed: $FAIL_COUNT${NC}"
    echo
    
    # Overall assessment
    if [[ $FAIL_COUNT -eq 0 ]]; then
        if [[ $WARN_COUNT -eq 0 ]]; then
            echo -e "${GREEN}✅ DEPLOYMENT READY${NC}"
            echo "All validation checks passed. Ready for MCP deployment."
        else
            echo -e "${YELLOW}⚠️  DEPLOYMENT READY WITH WARNINGS${NC}"
            echo "Deployment can proceed, but address warnings for optimal performance."
        fi
        return 0
    else
        echo -e "${RED}❌ DEPLOYMENT NOT READY${NC}"
        echo "Critical issues found. Address failed checks before proceeding."
        return 1
    fi
}

#############################################################################
# Main Execution
#############################################################################

main() {
    local quick=false
    
    # Parse arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            --quick)
                quick=true
                shift
                ;;
            --help)
                echo "Usage: $0 [--quick]"
                echo "  --quick    Run essential checks only"
                exit 0
                ;;
            *)
                echo "Unknown option: $1"
                exit 1
                ;;
        esac
    done
    
    echo "MCP Deployment Validation (quick: $quick)"
    echo "==========================================="
    echo
    
    # Essential checks (always run)
    validate_files
    validate_environment
    validate_dependencies
    validate_permissions
    
    # Additional checks (unless quick mode)
    if [[ $quick == false ]]; then
        validate_monitoring_config
        validate_network
    fi
    
    # Generate final report
    generate_report
}

# Script execution
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi