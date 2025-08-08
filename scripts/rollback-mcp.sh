#!/bin/bash

#############################################################################
# MCP Rollback Script
# Emergency rollback for MCP migration issues
#############################################################################

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="/var/backups/mcp-migration"
LOG_FILE="/var/log/mcp-rollback-$(date +%Y%m%d_%H%M%S).log"
ROLLBACK_TIMEOUT=300
AUTO_MODE=false

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
    log_error "Rollback failed at line $line_number with exit code $exit_code"
    log_error "Manual intervention may be required"
    log_error "Check log file: $LOG_FILE"
    exit $exit_code
}

#############################################################################
# Rollback Functions
#############################################################################

confirm_rollback() {
    if [[ $AUTO_MODE == true ]]; then
        log_info "Auto-rollback mode enabled, proceeding without confirmation"
        return 0
    fi
    
    echo
    log_warning "WARNING: This will rollback MCP migration and return to webhook-only mode"
    log_info "This action will:"
    echo "  - Disable MCP endpoints"
    echo "  - Restore webhook-only configuration"
    echo "  - Clear MCP caches"
    echo "  - Revert configuration changes"
    echo
    
    read -p "Are you sure you want to proceed? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        log_info "Rollback cancelled by user"
        exit 0
    fi
}

get_latest_backup() {
    local backup_type=$1
    local backup_file="$BACKUP_DIR/latest_${backup_type}_backup"
    
    if [[ -f "$backup_file" ]]; then
        cat "$backup_file"
    else
        log_error "No $backup_type backup found"
        return 1
    fi
}

restore_configuration() {
    log_info "Restoring configuration from backup..."
    
    local config_backup_timestamp
    config_backup_timestamp=$(get_latest_backup "config")
    
    if [[ -z "$config_backup_timestamp" ]]; then
        log_error "No configuration backup available"
        return 1
    fi
    
    local config_backup_dir="$BACKUP_DIR/config_$config_backup_timestamp"
    
    if [[ ! -d "$config_backup_dir" ]]; then
        log_error "Configuration backup directory not found: $config_backup_dir"
        return 1
    fi
    
    cd "$PROJECT_DIR"
    
    # Restore environment file
    if [[ -f "$config_backup_dir/.env.backup" ]]; then
        cp "$config_backup_dir/.env.backup" .env
        log_success "Environment file restored"
    else
        log_warning "No environment backup found, manual configuration required"
    fi
    
    # Restore configuration files
    if [[ -d "$config_backup_dir/config" ]]; then
        cp -r "$config_backup_dir/config/"* config/
        log_success "Configuration files restored"
    fi
    
    log_success "Configuration restored from: $config_backup_dir"
}

disable_mcp_mode() {
    log_info "Disabling MCP migration mode..."
    
    cd "$PROJECT_DIR"
    
    # Update environment variables
    if [[ -f ".env" ]]; then
        # Disable MCP migration mode
        sed -i 's/^MCP_MIGRATION_MODE=.*/MCP_MIGRATION_MODE=false/' .env
        sed -i 's/^MCP_ROLLOUT_PERCENTAGE=.*/MCP_ROLLOUT_PERCENTAGE=0/' .env
        
        # Ensure webhook fallback is enabled during transition
        sed -i 's/^MCP_FALLBACK_TO_WEBHOOK=.*/MCP_FALLBACK_TO_WEBHOOK=true/' .env
        
        log_success "MCP mode disabled in environment"
    else
        log_error "Environment file not found"
        return 1
    fi
    
    # Clear configuration cache
    php artisan config:clear
    php artisan cache:clear
    
    # Reload configuration
    php artisan config:cache
    
    log_success "MCP migration mode disabled"
}

stop_mcp_services() {
    log_info "Stopping MCP-related services..."
    
    cd "$PROJECT_DIR"
    
    # Stop any MCP background processes
    if pgrep -f "mcp-retell" > /dev/null; then
        pkill -f "mcp-retell" || true
        log_success "MCP processes stopped"
    fi
    
    # Clear MCP-specific caches
    php artisan cache:forget "mcp:*" || true
    redis-cli --scan --pattern "mcp:*" | xargs -r redis-cli del || true
    
    log_success "MCP services stopped"
}

restore_webhook_config() {
    log_info "Restoring webhook-only configuration..."
    
    cd "$PROJECT_DIR"
    
    # Ensure webhook routes are active
    php artisan route:clear
    php artisan route:cache
    
    # Verify webhook endpoint is responding
    local webhook_endpoint="${APP_URL}/api/retell/webhook-simple"
    
    if curl -f -s --max-time 10 -X POST \
        -H "Content-Type: application/json" \
        -d '{"test": true}' \
        "$webhook_endpoint" > /dev/null 2>&1; then
        log_success "Legacy webhook endpoint is responding"
    else
        log_warning "Legacy webhook endpoint check failed (may require signature)"
    fi
    
    log_success "Webhook configuration restored"
}

verify_rollback() {
    log_info "Verifying rollback completion..."
    
    cd "$PROJECT_DIR"
    
    # Check MCP mode is disabled
    if php -r "echo (env('MCP_MIGRATION_MODE') === 'false') ? 'disabled' : 'enabled';" | grep -q "disabled"; then
        log_success "MCP migration mode confirmed disabled"
    else
        log_error "MCP migration mode still enabled"
        return 1
    fi
    
    # Check webhook endpoints
    if php artisan route:list | grep -q "retell.*webhook"; then
        log_success "Webhook routes are active"
    else
        log_warning "Webhook routes may not be properly configured"
    fi
    
    # Test basic application functionality
    if php artisan health:check --quiet; then
        log_success "Basic health check passed"
    else
        log_warning "Health check failed, may require attention"
    fi
    
    log_success "Rollback verification completed"
}

notify_rollback() {
    log_info "Sending rollback notifications..."
    
    # Log rollback event
    php artisan log:info "MCP rollback completed" --context='{"timestamp": "'$(date --iso-8601)'", "reason": "rollback_script"}' || true
    
    # Send webhook notification if configured
    if [[ -n "${SLACK_ALERT_WEBHOOK_URL:-}" ]]; then
        local slack_payload='{
            "text": "🔄 MCP Migration Rollback",
            "attachments": [{
                "color": "warning",
                "fields": [
                    {"title": "Environment", "value": "'"${APP_ENV:-production}"'", "short": true},
                    {"title": "Time", "value": "'$(date)'", "short": true},
                    {"title": "Status", "value": "Rollback completed successfully", "short": false}
                ]
            }]
        }'
        
        curl -X POST -H 'Content-type: application/json' \
            --data "$slack_payload" \
            "$SLACK_ALERT_WEBHOOK_URL" || true
        
        log_success "Slack notification sent"
    fi
    
    log_success "Rollback notifications completed"
}

cleanup_mcp_artifacts() {
    log_info "Cleaning up MCP artifacts..."
    
    cd "$PROJECT_DIR"
    
    # Remove MCP-specific configuration files
    local mcp_configs=("config/mcp-monitoring.php" "config/mcp-retell.php")
    for config in "${mcp_configs[@]}"; do
        if [[ -f "$config" ]]; then
            rm -f "$config"
            log_success "Removed MCP config: $config"
        fi
    done
    
    # Clear MCP logs (keep for debugging but rotate)
    if [[ -f "storage/logs/mcp.log" ]]; then
        mv "storage/logs/mcp.log" "storage/logs/mcp-rollback-$(date +%Y%m%d_%H%M%S).log"
        log_success "MCP logs archived"
    fi
    
    # Remove deployment markers
    rm -f "$BACKUP_DIR/deployment_in_progress"
    
    log_success "MCP artifacts cleaned up"
}

#############################################################################
# Emergency Procedures
#############################################################################

emergency_rollback() {
    log_error "EMERGENCY ROLLBACK TRIGGERED"
    
    # Skip confirmations in emergency mode
    AUTO_MODE=true
    
    # Immediate actions to restore service
    log_info "Performing immediate restoration..."
    
    cd "$PROJECT_DIR"
    
    # Force disable MCP
    echo "MCP_MIGRATION_MODE=false" >> .env.emergency
    echo "MCP_ROLLOUT_PERCENTAGE=0" >> .env.emergency
    echo "MCP_FALLBACK_TO_WEBHOOK=true" >> .env.emergency
    
    if [[ -f ".env.emergency" ]]; then
        cp .env.emergency .env
        log_success "Emergency configuration applied"
    fi
    
    # Clear all caches immediately
    php artisan config:clear || true
    php artisan cache:clear || true
    php artisan route:clear || true
    
    # Restart web server if possible
    if command -v systemctl &> /dev/null; then
        systemctl reload php8.2-fpm || true
        systemctl reload nginx || true
        log_info "Web server reloaded"
    fi
    
    log_success "Emergency rollback completed"
}

#############################################################################
# Main Rollback Logic
#############################################################################

main() {
    local emergency=false
    
    # Parse arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            --auto)
                AUTO_MODE=true
                shift
                ;;
            --emergency)
                emergency=true
                AUTO_MODE=true
                shift
                ;;
            --help)
                echo "Usage: $0 [--auto] [--emergency]"
                echo "  --auto       Skip confirmation prompts"
                echo "  --emergency  Perform immediate emergency rollback"
                exit 0
                ;;
            *)
                echo "Unknown option: $1"
                exit 1
                ;;
        esac
    done
    
    log_info "Starting MCP rollback (auto: $AUTO_MODE, emergency: $emergency)..."
    
    # Emergency mode
    if [[ $emergency == true ]]; then
        emergency_rollback
        return 0
    fi
    
    # Standard rollback process
    confirm_rollback
    
    # Stop MCP services first
    stop_mcp_services
    
    # Disable MCP mode
    disable_mcp_mode
    
    # Restore configurations
    restore_configuration || log_warning "Configuration restore failed, continuing..."
    
    # Restore webhook configuration
    restore_webhook_config
    
    # Clean up artifacts
    cleanup_mcp_artifacts
    
    # Verify rollback
    verify_rollback
    
    # Send notifications
    notify_rollback
    
    log_success "MCP rollback completed successfully!"
    log_info "System has been restored to webhook-only mode"
    log_info "Monitor system stability and check webhook functionality"
    log_info "Log file: $LOG_FILE"
}

# Script execution
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi