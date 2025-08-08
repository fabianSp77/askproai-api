#!/bin/bash

#############################################################################
# MCP Migration Deployment Script
# Zero-downtime deployment with parallel webhook operation
#############################################################################

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="/var/backups/mcp-migration"
LOG_FILE="/var/log/mcp-migration-$(date +%Y%m%d_%H%M%S).log"
ROLLOUT_PERCENTAGE=${MCP_ROLLOUT_PERCENTAGE:-0}
MIGRATION_TIMEOUT=300

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log() {
    echo -e "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log_info() {
    log "${BLUE}[INFO]${NC} $1"
}

log_success() {
    log "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    log "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    log "${RED}[ERROR]${NC} $1"
}

# Error handling
trap 'handle_error $? $LINENO' ERR

handle_error() {
    local exit_code=$1
    local line_number=$2
    log_error "Deployment failed at line $line_number with exit code $exit_code"
    log_error "Check log file: $LOG_FILE"
    
    # Trigger rollback if deployment was in progress
    if [[ -f "$BACKUP_DIR/deployment_in_progress" ]]; then
        log_warning "Triggering automatic rollback..."
        "$SCRIPT_DIR/rollback-mcp.sh" --auto
    fi
    
    exit $exit_code
}

# Cleanup function
cleanup() {
    rm -f "$BACKUP_DIR/deployment_in_progress"
    log_info "Cleanup completed"
}

trap cleanup EXIT

#############################################################################
# Pre-deployment Checks
#############################################################################

check_prerequisites() {
    log_info "Checking prerequisites..."
    
    # Check if running as correct user
    if [[ $EUID -ne 0 ]] && [[ "$(whoami)" != "www-data" ]]; then
        log_error "Script must be run as root or www-data user"
        exit 1
    fi
    
    # Check required directories
    mkdir -p "$BACKUP_DIR" "$(dirname "$LOG_FILE")"
    
    # Check PHP and Laravel
    if ! command -v php &> /dev/null; then
        log_error "PHP is not installed or not in PATH"
        exit 1
    fi
    
    cd "$PROJECT_DIR"
    
    if ! php artisan --version &> /dev/null; then
        log_error "Laravel artisan not working"
        exit 1
    fi
    
    # Check database connectivity
    if ! php artisan db:show &> /dev/null; then
        log_error "Database connection failed"
        exit 1
    fi
    
    # Check Redis connectivity
    if ! php artisan cache:table &> /dev/null 2>&1; then
        if ! redis-cli ping &> /dev/null; then
            log_error "Redis connection failed"
            exit 1
        fi
    fi
    
    log_success "Prerequisites check passed"
}

#############################################################################
# Backup Functions
#############################################################################

backup_configuration() {
    log_info "Backing up current configuration..."
    
    local backup_timestamp=$(date +%Y%m%d_%H%M%S)
    local config_backup_dir="$BACKUP_DIR/config_$backup_timestamp"
    
    mkdir -p "$config_backup_dir"
    
    # Backup environment files
    cp .env "$config_backup_dir/.env.backup" 2>/dev/null || true
    cp .env.example "$config_backup_dir/.env.example.backup" 2>/dev/null || true
    
    # Backup webhook configuration
    if [[ -f "routes/api.php" ]]; then
        cp routes/api.php "$config_backup_dir/api.php.backup"
    fi
    
    # Backup MCP configurations
    if [[ -d "config" ]]; then
        cp -r config "$config_backup_dir/"
    fi
    
    # Backup current webhook endpoints
    php artisan route:list --json | jq '.[] | select(.name | contains("webhook"))' > "$config_backup_dir/webhook_routes.json" 2>/dev/null || true
    
    echo "$backup_timestamp" > "$BACKUP_DIR/latest_config_backup"
    log_success "Configuration backed up to: $config_backup_dir"
}

backup_database_state() {
    log_info "Creating database state snapshot..."
    
    local backup_timestamp=$(date +%Y%m%d_%H%M%S)
    local db_backup_file="$BACKUP_DIR/db_state_$backup_timestamp.sql"
    
    # Only backup relevant tables to minimize size
    php artisan db:table calls --connection=mysql --format=sql > "$db_backup_file" 2>/dev/null || true
    
    if [[ -f "$db_backup_file" ]] && [[ -s "$db_backup_file" ]]; then
        echo "$backup_timestamp" > "$BACKUP_DIR/latest_db_backup"
        log_success "Database state backed up to: $db_backup_file"
    else
        log_warning "Database backup failed or empty"
    fi
}

#############################################################################
# Validation Functions
#############################################################################

validate_mcp_configuration() {
    log_info "Validating MCP configuration..."
    
    # Check required environment variables
    local required_vars=(
        "MCP_RETELL_AGENT_TOKEN"
        "MCP_MIGRATION_MODE"
        "MCP_FALLBACK_TO_WEBHOOK"
        "MCP_RESPONSE_TIME_LIMIT_MS"
    )
    
    local missing_vars=()
    for var in "${required_vars[@]}"; do
        if [[ -z "${!var:-}" ]]; then
            missing_vars+=("$var")
        fi
    done
    
    if [[ ${#missing_vars[@]} -gt 0 ]]; then
        log_error "Missing required environment variables: ${missing_vars[*]}"
        return 1
    fi
    
    # Validate token format
    if [[ ${#MCP_RETELL_AGENT_TOKEN} -lt 32 ]]; then
        log_error "MCP_RETELL_AGENT_TOKEN appears to be invalid (too short)"
        return 1
    fi
    
    # Validate timeout values
    if [[ $MCP_RESPONSE_TIME_LIMIT_MS -gt 5000 ]]; then
        log_warning "MCP response time limit is high: ${MCP_RESPONSE_TIME_LIMIT_MS}ms"
    fi
    
    log_success "MCP configuration validation passed"
}

run_tests() {
    log_info "Running test suite..."
    
    # Run MCP-specific tests
    if ! php artisan test tests/Feature/MCP/RetellMCPEndpointTest.php --stop-on-failure; then
        log_error "MCP endpoint tests failed"
        return 1
    fi
    
    # Run integration tests
    if ! php artisan test tests/Feature/RetellIntegrationTest.php --stop-on-failure; then
        log_error "Retell integration tests failed"
        return 1
    fi
    
    # Run performance tests
    if ! php artisan test tests/Feature/MCP/RetellMCPPerformanceTest.php --stop-on-failure; then
        log_error "MCP performance tests failed"
        return 1
    fi
    
    log_success "All tests passed"
}

#############################################################################
# Health Check Functions
#############################################################################

health_check_services() {
    log_info "Performing health checks..."
    
    local services=("database" "redis" "retell" "calcom")
    local failed_services=()
    
    for service in "${services[@]}"; do
        if ! php artisan health:check --service="$service" --quiet; then
            failed_services+=("$service")
        fi
    done
    
    if [[ ${#failed_services[@]} -gt 0 ]]; then
        log_error "Health check failed for services: ${failed_services[*]}"
        return 1
    fi
    
    log_success "All service health checks passed"
}

health_check_mcp_endpoint() {
    log_info "Testing MCP endpoint health..."
    
    local mcp_health_url="${APP_URL}/api/mcp/retell/health"
    local max_attempts=3
    local attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        if curl -f -s --max-time 10 "$mcp_health_url" > /dev/null; then
            log_success "MCP endpoint health check passed"
            return 0
        fi
        
        log_warning "MCP endpoint health check failed (attempt $attempt/$max_attempts)"
        sleep 2
        ((attempt++))
    done
    
    log_error "MCP endpoint health check failed after $max_attempts attempts"
    return 1
}

#############################################################################
# Migration Functions
#############################################################################

enable_parallel_mode() {
    log_info "Enabling parallel operation mode..."
    
    # Mark deployment as in progress
    touch "$BACKUP_DIR/deployment_in_progress"
    
    # Update configuration for parallel mode
    php artisan config:set MCP_MIGRATION_MODE true
    php artisan config:set MCP_FALLBACK_TO_WEBHOOK true
    php artisan config:set MCP_ROLLOUT_PERCENTAGE "$ROLLOUT_PERCENTAGE"
    
    # Clear configuration cache
    php artisan config:clear
    php artisan cache:clear
    
    # Warm up caches
    php artisan config:cache
    php artisan route:cache
    
    log_success "Parallel operation mode enabled with $ROLLOUT_PERCENTAGE% rollout"
}

setup_monitoring() {
    log_info "Setting up monitoring and alerting..."
    
    # Create monitoring configuration
    cat > config/mcp-monitoring.php << 'EOF'
<?php

return [
    'enabled' => env('MCP_PERFORMANCE_MONITORING_ENABLED', true),
    'sampling_rate' => env('MCP_PERFORMANCE_SAMPLING_RATE', 1.0),
    'thresholds' => [
        'response_time_warning_ms' => env('MCP_RESPONSE_TIME_WARNING_MS', 300),
        'response_time_limit_ms' => env('MCP_RESPONSE_TIME_LIMIT_MS', 500),
        'error_rate_threshold' => env('MCP_ALERT_ERROR_RATE_THRESHOLD', 0.05),
    ],
    'alerts' => [
        'enabled' => true,
        'channels' => ['log', 'webhook'],
    ],
];
EOF
    
    # Generate Prometheus metrics configuration
    "$SCRIPT_DIR/generate-prometheus-config.sh"
    
    # Generate Grafana dashboard
    "$SCRIPT_DIR/generate-grafana-dashboard.sh"
    
    log_success "Monitoring setup completed"
}

#############################################################################
# Main Deployment Logic
#############################################################################

main() {
    log_info "Starting MCP migration deployment..."
    log_info "Rollout percentage: $ROLLOUT_PERCENTAGE%"
    log_info "Log file: $LOG_FILE"
    
    # Pre-deployment phase
    check_prerequisites
    backup_configuration
    backup_database_state
    
    # Validation phase
    validate_mcp_configuration
    health_check_services
    run_tests
    
    # Deployment phase
    enable_parallel_mode
    setup_monitoring
    
    # Post-deployment verification
    health_check_mcp_endpoint
    
    # Gradual rollout
    if [[ $ROLLOUT_PERCENTAGE -gt 0 ]]; then
        log_info "Starting gradual rollout at $ROLLOUT_PERCENTAGE%..."
        "$SCRIPT_DIR/mcp-gradual-rollout.sh" --percentage="$ROLLOUT_PERCENTAGE"
    fi
    
    # Final verification
    "$SCRIPT_DIR/mcp-health-check.sh" --comprehensive
    
    log_success "MCP migration deployment completed successfully!"
    log_info "Monitor the deployment at: ${APP_URL}/admin/system-monitoring"
    log_info "Health check endpoint: ${APP_URL}/api/mcp/retell/health"
    
    # Clean up deployment marker
    rm -f "$BACKUP_DIR/deployment_in_progress"
}

# Script execution
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi