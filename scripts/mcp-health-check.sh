#!/bin/bash

#############################################################################
# MCP Health Check Script
# Comprehensive health verification for MCP integration
#############################################################################

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
HEALTH_CHECK_TIMEOUT=30
MAX_RESPONSE_TIME_MS=500
CRITICAL_ERROR_THRESHOLD=0.1

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Health check results
HEALTH_STATUS="UNKNOWN"
FAILED_CHECKS=()
WARNING_CHECKS=()
CHECK_RESULTS=()

# Logging functions
log() {
    echo -e "[$(date +'%H:%M:%S')] $1"
}

log_info() {
    log "${BLUE}[INFO]${NC} $1"
}

log_success() {
    log "${GREEN}[PASS]${NC} $1"
    CHECK_RESULTS+=("PASS: $1")
}

log_warning() {
    log "${YELLOW}[WARN]${NC} $1"
    WARNING_CHECKS+=("$1")
    CHECK_RESULTS+=("WARN: $1")
}

log_error() {
    log "${RED}[FAIL]${NC} $1"
    FAILED_CHECKS+=("$1")
    CHECK_RESULTS+=("FAIL: $1")
}

#############################################################################
# MCP Service Health Checks
#############################################################################

check_mcp_endpoint() {
    log_info "Checking MCP endpoint availability..."
    
    local endpoint="${APP_URL}/api/mcp/retell/health"
    local start_time=$(date +%s%3N)
    
    if response=$(curl -f -s --max-time $HEALTH_CHECK_TIMEOUT "$endpoint" 2>&1); then
        local end_time=$(date +%s%3N)
        local response_time=$((end_time - start_time))
        
        if [[ $response_time -gt $MAX_RESPONSE_TIME_MS ]]; then
            log_warning "MCP endpoint slow response: ${response_time}ms (limit: ${MAX_RESPONSE_TIME_MS}ms)"
        else
            log_success "MCP endpoint responding in ${response_time}ms"
        fi
        
        # Check response content
        if echo "$response" | jq -e '.status == "healthy"' &> /dev/null; then
            log_success "MCP endpoint reports healthy status"
        else
            log_warning "MCP endpoint response format unexpected"
        fi
    else
        log_error "MCP endpoint not accessible: $response"
    fi
}

check_mcp_tools() {
    log_info "Checking MCP tool availability..."
    
    local tools_endpoint="${APP_URL}/api/mcp/retell/tools"
    
    # Test with a simple tool call
    local test_payload='{
        "tool": "get_available_slots",
        "arguments": {
            "company_id": 1,
            "date": "'$(date +%Y-%m-%d)'"
        },
        "call_id": "health_check_'$(date +%s)'"
    }'
    
    if [[ -n "${MCP_RETELL_AGENT_TOKEN:-}" ]]; then
        local start_time=$(date +%s%3N)
        
        if response=$(curl -f -s --max-time $HEALTH_CHECK_TIMEOUT \
            -H "Authorization: Bearer $MCP_RETELL_AGENT_TOKEN" \
            -H "Content-Type: application/json" \
            -X POST -d "$test_payload" \
            "$tools_endpoint" 2>&1); then
            
            local end_time=$(date +%s%3N)
            local response_time=$((end_time - start_time))
            
            if [[ $response_time -gt $MAX_RESPONSE_TIME_MS ]]; then
                log_warning "MCP tools slow response: ${response_time}ms"
            else
                log_success "MCP tools responding in ${response_time}ms"
            fi
            
            # Check for successful tool execution
            if echo "$response" | jq -e '.success == true' &> /dev/null; then
                log_success "MCP tool execution successful"
            elif echo "$response" | jq -e '.error' &> /dev/null; then
                local error_msg=$(echo "$response" | jq -r '.error.message // .error')
                log_warning "MCP tool execution returned error: $error_msg"
            else
                log_warning "MCP tool response format unexpected"
            fi
        else
            log_error "MCP tools endpoint not responding: $response"
        fi
    else
        log_warning "MCP_RETELL_AGENT_TOKEN not configured, skipping tool test"
    fi
}

check_authentication() {
    log_info "Checking MCP authentication..."
    
    local tools_endpoint="${APP_URL}/api/mcp/retell/tools"
    
    # Test without token (should fail)
    if curl -f -s --max-time 10 \
        -H "Content-Type: application/json" \
        -X POST -d '{"tool":"test"}' \
        "$tools_endpoint" &> /dev/null; then
        log_error "MCP endpoint accepting requests without authentication"
    else
        log_success "MCP endpoint properly requires authentication"
    fi
    
    # Test with invalid token (should fail)
    if curl -f -s --max-time 10 \
        -H "Authorization: Bearer invalid_token" \
        -H "Content-Type: application/json" \
        -X POST -d '{"tool":"test"}' \
        "$tools_endpoint" &> /dev/null; then
        log_error "MCP endpoint accepting invalid tokens"
    else
        log_success "MCP endpoint properly validates tokens"
    fi
}

#############################################################################
# Database Connectivity Checks
#############################################################################

check_database_connectivity() {
    log_info "Checking database connectivity..."
    
    cd "$PROJECT_DIR"
    
    if php artisan db:show --quiet &> /dev/null; then
        log_success "Database connection working"
    else
        log_error "Database connection failed"
    fi
    
    # Check specific tables used by MCP
    local tables=("calls" "companies" "customers" "appointments")
    for table in "${tables[@]}"; do
        if php artisan db:table "$table" --quiet &> /dev/null; then
            log_success "Table '$table' accessible"
        else
            log_warning "Table '$table' not accessible or empty"
        fi
    done
}

#############################################################################
# Cal.com Integration Checks
#############################################################################

check_calcom_integration() {
    log_info "Checking Cal.com integration..."
    
    cd "$PROJECT_DIR"
    
    # Check Cal.com API connectivity
    if [[ -n "${DEFAULT_CALCOM_API_KEY:-}" ]]; then
        local calcom_endpoint="https://api.cal.com/v2/me"
        
        if curl -f -s --max-time 15 \
            -H "Authorization: Bearer $DEFAULT_CALCOM_API_KEY" \
            "$calcom_endpoint" | jq -e '.email' &> /dev/null; then
            log_success "Cal.com API connection working"
        else
            log_warning "Cal.com API connection failed"
        fi
    else
        log_warning "Cal.com API key not configured"
    fi
    
    # Check event types
    if php artisan calcom:test-connection --quiet &> /dev/null; then
        log_success "Cal.com service integration working"
    else
        log_warning "Cal.com service integration issues"
    fi
}

#############################################################################
# Circuit Breaker Status
#############################################################################

check_circuit_breaker() {
    log_info "Checking circuit breaker status..."
    
    cd "$PROJECT_DIR"
    
    # Check if circuit breaker is enabled
    if [[ "${CIRCUIT_BREAKER_ENABLED:-false}" == "true" ]]; then
        log_success "Circuit breaker is enabled"
        
        # Check current state
        if php artisan circuit-breaker:status --service=retell --quiet 2>/dev/null; then
            log_success "Circuit breaker status: CLOSED (healthy)"
        else
            # Check if it's in a failed state
            if php artisan circuit-breaker:status --service=retell 2>&1 | grep -q "OPEN\|HALF_OPEN"; then
                log_warning "Circuit breaker is in protective state"
            else
                log_info "Circuit breaker status unknown"
            fi
        fi
    else
        log_warning "Circuit breaker is disabled"
    fi
}

#############################################################################
# Performance Metrics
#############################################################################

check_performance_metrics() {
    log_info "Checking performance metrics..."
    
    cd "$PROJECT_DIR"
    
    # Check recent MCP response times
    if [[ -f "storage/logs/laravel.log" ]]; then
        local recent_responses=$(grep -i "mcp.*response_time" storage/logs/laravel.log | tail -10 || true)
        
        if [[ -n "$recent_responses" ]]; then
            local avg_time=$(echo "$recent_responses" | grep -oP 'response_time":\K[0-9]+' | awk '{sum+=$1; count++} END {if(count>0) print int(sum/count); else print 0}')
            
            if [[ $avg_time -gt $MAX_RESPONSE_TIME_MS ]]; then
                log_warning "Average MCP response time high: ${avg_time}ms"
            else
                log_success "Average MCP response time good: ${avg_time}ms"
            fi
        else
            log_info "No recent MCP response time data found"
        fi
    fi
    
    # Check error rates
    if [[ -f "storage/logs/laravel.log" ]]; then
        local recent_errors=$(grep -i "mcp.*error" storage/logs/laravel.log | tail -50 | wc -l || echo "0")
        local total_requests=$(grep -i "mcp" storage/logs/laravel.log | tail -100 | wc -l || echo "1")
        
        if [[ $total_requests -gt 0 ]]; then
            local error_rate=$(echo "scale=3; $recent_errors / $total_requests" | bc -l)
            local error_percentage=$(echo "scale=1; $error_rate * 100" | bc -l)
            
            if (( $(echo "$error_rate > $CRITICAL_ERROR_THRESHOLD" | bc -l) )); then
                log_error "High MCP error rate: ${error_percentage}%"
            elif (( $(echo "$error_rate > 0.05" | bc -l) )); then
                log_warning "Elevated MCP error rate: ${error_percentage}%"
            else
                log_success "MCP error rate acceptable: ${error_percentage}%"
            fi
        fi
    fi
}

#############################################################################
# Migration Status Check
#############################################################################

check_migration_status() {
    log_info "Checking MCP migration status..."
    
    cd "$PROJECT_DIR"
    
    # Check migration mode
    if [[ "${MCP_MIGRATION_MODE:-false}" == "true" ]]; then
        log_success "MCP migration mode is enabled"
        
        # Check rollout percentage
        local rollout_pct="${MCP_ROLLOUT_PERCENTAGE:-0}"
        log_info "MCP rollout percentage: $rollout_pct%"
        
        if [[ $rollout_pct -gt 0 ]] && [[ $rollout_pct -lt 100 ]]; then
            log_info "Gradual rollout in progress"
        elif [[ $rollout_pct -eq 100 ]]; then
            log_success "Full MCP rollout completed"
        else
            log_info "MCP rollout not started"
        fi
        
        # Check fallback configuration
        if [[ "${MCP_FALLBACK_TO_WEBHOOK:-false}" == "true" ]]; then
            log_success "Webhook fallback is enabled"
        else
            log_warning "Webhook fallback is disabled"
        fi
    else
        log_warning "MCP migration mode is disabled"
    fi
}

#############################################################################
# Report Generation
#############################################################################

generate_report() {
    echo
    log_info "=== MCP Health Check Report ==="
    echo
    
    # Overall status
    if [[ ${#FAILED_CHECKS[@]} -eq 0 ]]; then
        if [[ ${#WARNING_CHECKS[@]} -eq 0 ]]; then
            HEALTH_STATUS="HEALTHY"
            log_success "Overall Status: HEALTHY"
        else
            HEALTH_STATUS="WARNING"
            log_warning "Overall Status: WARNING"
        fi
    else
        HEALTH_STATUS="CRITICAL"
        log_error "Overall Status: CRITICAL"
    fi
    
    echo
    log_info "Check Summary:"
    for result in "${CHECK_RESULTS[@]}"; do
        echo "  $result"
    done
    
    # Failed checks
    if [[ ${#FAILED_CHECKS[@]} -gt 0 ]]; then
        echo
        log_error "Failed Checks:"
        for check in "${FAILED_CHECKS[@]}"; do
            echo "  - $check"
        done
    fi
    
    # Warning checks
    if [[ ${#WARNING_CHECKS[@]} -gt 0 ]]; then
        echo
        log_warning "Warning Checks:"
        for check in "${WARNING_CHECKS[@]}"; do
            echo "  - $check"
        done
    fi
    
    # Recommendations
    echo
    log_info "Recommendations:"
    
    if [[ ${#FAILED_CHECKS[@]} -gt 0 ]]; then
        echo "  - Address critical issues before proceeding with migration"
        echo "  - Check logs: $PROJECT_DIR/storage/logs/laravel.log"
        echo "  - Consider running rollback: $SCRIPT_DIR/rollback-mcp.sh"
    fi
    
    if [[ ${#WARNING_CHECKS[@]} -gt 0 ]]; then
        echo "  - Monitor warning conditions closely"
        echo "  - Consider adjusting configuration parameters"
    fi
    
    if [[ $HEALTH_STATUS == "HEALTHY" ]]; then
        echo "  - System is ready for MCP migration"
        echo "  - Monitor performance after rollout"
    fi
    
    echo
    log_info "Health check completed at $(date)"
    
    # Return appropriate exit code
    case $HEALTH_STATUS in
        "HEALTHY") return 0 ;;
        "WARNING") return 1 ;;
        "CRITICAL") return 2 ;;
        *) return 3 ;;
    esac
}

#############################################################################
# Main Execution
#############################################################################

main() {
    local comprehensive=false
    
    # Parse arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            --comprehensive)
                comprehensive=true
                shift
                ;;
            --timeout)
                HEALTH_CHECK_TIMEOUT="$2"
                shift 2
                ;;
            --help)
                echo "Usage: $0 [--comprehensive] [--timeout SECONDS]"
                echo "  --comprehensive    Run all health checks"
                echo "  --timeout SECONDS  Set timeout for HTTP requests (default: $HEALTH_CHECK_TIMEOUT)"
                exit 0
                ;;
            *)
                echo "Unknown option: $1"
                exit 1
                ;;
        esac
    done
    
    log_info "Starting MCP health check (comprehensive: $comprehensive)..."
    
    # Core checks (always run)
    check_mcp_endpoint
    check_authentication
    check_database_connectivity
    check_migration_status
    
    # Comprehensive checks
    if [[ $comprehensive == true ]]; then
        check_mcp_tools
        check_calcom_integration
        check_circuit_breaker
        check_performance_metrics
    fi
    
    # Generate and display report
    generate_report
}

# Script execution
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    cd "$PROJECT_DIR"
    main "$@"
fi