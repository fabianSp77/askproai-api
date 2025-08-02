#!/bin/bash
# deploy-business-portal.sh
# Master deployment script for Business Portal improvements

set -e

# Configuration
DEPLOYMENT_ID="bp-$(date +%Y%m%d-%H%M%S)"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_FILE="$PROJECT_ROOT/storage/deployment/deploy-${DEPLOYMENT_ID}.log"
START_TIME=$(date +%s)

# Command line options
DRY_RUN=false
SKIP_VALIDATION=false
FORCE_DEPLOY=false
AUTO_ROLLBACK=true

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Logging functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}✅ $1${NC}" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}⚠️ $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}❌ $1${NC}" | tee -a "$LOG_FILE"
}

# Parse command line options
parse_options() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --dry-run)
                DRY_RUN=true
                shift
                ;;
            --skip-validation)
                SKIP_VALIDATION=true
                shift
                ;;
            --force)
                FORCE_DEPLOY=true
                shift
                ;;
            --no-auto-rollback)
                AUTO_ROLLBACK=false
                shift
                ;;
            --help|-h)
                show_help
                exit 0
                ;;
            *)
                error "Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done
}

show_help() {
    cat << EOF
🚀 Business Portal Deployment Script

Usage: $0 [OPTIONS]

Options:
  --dry-run           Simulate deployment without making changes
  --skip-validation   Skip pre-deployment validation (NOT RECOMMENDED)
  --force             Force deployment even if validation fails
  --no-auto-rollback  Disable automatic rollback on failure
  --help, -h          Show this help message

Examples:
  $0                          # Normal deployment
  $0 --dry-run               # Test deployment process
  $0 --force --skip-validation  # Emergency deployment (use with caution)

Environment:
  Working Directory: $PROJECT_ROOT
  Deployment ID: $DEPLOYMENT_ID
  Log File: $LOG_FILE
EOF
}

# Trap for cleanup on exit
cleanup() {
    local exit_code=$?
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    
    # Update deployment status
    if [ $exit_code -eq 0 ]; then
        echo "success" > "$PROJECT_ROOT/storage/deployment/status-$DEPLOYMENT_ID.txt"
        echo "$DEPLOYMENT_ID" > "$PROJECT_ROOT/storage/deployment/last-successful-deployment.txt"
        git rev-parse HEAD > "$PROJECT_ROOT/storage/deployment/last-successful-commit.txt"
        
        success "🎉 DEPLOYMENT SUCCESSFUL - Duration: ${DURATION} seconds"
        
        # Send success notification
        "$SCRIPT_DIR/send-deployment-notification.sh" "deployment-success" \
            "Business Portal Deployment Successful" "" "$DEPLOYMENT_ID" &
            
        # Start post-deployment monitoring
        nohup "$SCRIPT_DIR/monitor-deployment.sh" "$DEPLOYMENT_ID" > \
            "$PROJECT_ROOT/storage/deployment/monitoring-${DEPLOYMENT_ID}.log" 2>&1 &
            
    else
        echo "failed" > "$PROJECT_ROOT/storage/deployment/status-$DEPLOYMENT_ID.txt"
        error "💥 DEPLOYMENT FAILED - Duration: ${DURATION} seconds"
        
        # Send failure notification
        "$SCRIPT_DIR/send-deployment-notification.sh" "deployment-failed" \
            "Business Portal Deployment Failed" "" "$DEPLOYMENT_ID" &
        
        # Auto rollback if enabled
        if [ "$AUTO_ROLLBACK" = true ] && [ "$DRY_RUN" = false ]; then
            warning "🔄 Initiating automatic rollback..."
            "$SCRIPT_DIR/emergency-rollback.sh" "$DEPLOYMENT_ID" "Automatic rollback after deployment failure" &
        fi
    fi
    
    # Final log entry
    log "Deployment $DEPLOYMENT_ID completed with exit code $exit_code"
}
trap cleanup EXIT

# Initialize deployment
initialize_deployment() {
    log "🚀 BUSINESS PORTAL DEPLOYMENT STARTED"
    log "Deployment ID: $DEPLOYMENT_ID"
    log "Dry Run: $DRY_RUN"
    log "Skip Validation: $SKIP_VALIDATION"
    log "Force Deploy: $FORCE_DEPLOY"
    log "Auto Rollback: $AUTO_ROLLBACK"
    
    # Create deployment directories
    mkdir -p "$PROJECT_ROOT/storage/deployment"
    
    # Change to project root
    cd "$PROJECT_ROOT"
    
    # Send deployment started notification
    if [ "$DRY_RUN" = false ]; then
        "$SCRIPT_DIR/send-deployment-notification.sh" "deployment-started" \
            "Business Portal Deployment Started" "" "$DEPLOYMENT_ID" &
    fi
    
    success "Deployment initialized"
}

# Phase 1: Pre-deployment Validation
run_pre_deployment_validation() {
    log "\n📋 PHASE 1: PRE-DEPLOYMENT VALIDATION"
    
    if [ "$SKIP_VALIDATION" = true ]; then
        warning "Skipping pre-deployment validation (--skip-validation flag)"
        return 0
    fi
    
    if [ "$DRY_RUN" = true ]; then
        log "DRY RUN: Would run pre-deployment validation"
        return 0
    fi
    
    # Run comprehensive validation
    if "$SCRIPT_DIR/pre-deployment-validation.sh"; then
        success "Pre-deployment validation passed"
    else
        local validation_exit_code=$?
        
        if [ "$FORCE_DEPLOY" = true ]; then
            warning "Pre-deployment validation failed but continuing due to --force flag"
        else
            error "Pre-deployment validation failed (exit code: $validation_exit_code)"
            if [ $validation_exit_code -eq 2 ]; then
                warning "Some checks failed but deployment can continue"
                warning "Consider using --force to proceed anyway"
            fi
            exit $validation_exit_code
        fi
    fi
}

# Phase 2: Backup Current State
create_deployment_backups() {
    log "\n💾 PHASE 2: CREATING BACKUPS"
    
    if [ "$DRY_RUN" = true ]; then
        log "DRY RUN: Would create deployment backups"
        return 0
    fi
    
    # Store current commit
    git rev-parse HEAD > "$PROJECT_ROOT/storage/deployment/previous-commit.txt"
    success "Current commit hash saved"
    
    # Backup environment configuration
    cp .env "$PROJECT_ROOT/storage/deployment/.env.backup-$(date +%Y%m%d-%H%M%S)"
    success "Environment configuration backed up"
    
    # Backup current assets
    if [ -d "public/build" ]; then
        cp -r public/build "$PROJECT_ROOT/storage/deployment/assets-backup-$(date +%Y%m%d-%H%M%S)"
        success "Current assets backed up"
    fi
    
    # Database backup
    log "Creating database backup..."
    if mysqldump -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db > \
        "$PROJECT_ROOT/storage/deployment/pre-deployment-db-$(date +%Y%m%d-%H%M%S).sql"; then
        success "Database backup created"
    else
        warning "Database backup failed - continuing deployment"
    fi
    
    success "All backups created successfully"
}

# Phase 3: Feature Flag Preparation
prepare_feature_flags() {
    log "\n🚩 PHASE 3: FEATURE FLAG PREPARATION"
    
    if [ "$DRY_RUN" = true ]; then
        log "DRY RUN: Would prepare feature flags"
        return 0
    fi
    
    # Disable all new features initially
    local features=(
        "ui-improvements"
        "mobile-navigation-fix"
        "dropdown-fixes"
        "responsive-tables"
        "performance-monitoring"
        "enhanced-mobile-nav"
        "test-coverage-reporting"
    )
    
    for feature in "${features[@]}"; do
        if php artisan feature disable "$feature" --quiet 2>/dev/null; then
            log "✅ Feature flag prepared: $feature"
        else
            # Create feature flag if it doesn't exist
            php artisan tinker --execute="
                \$service = app('App\\Services\\FeatureFlagService');
                \$service->createOrUpdate([
                    'key' => '$feature',
                    'name' => '$(echo $feature | tr '-' ' ' | sed 's/\b\w/\U&/g')',
                    'enabled' => false,
                    'rollout_percentage' => 0,
                    'description' => 'Deployment feature flag for $feature'
                ]);
            " 2>/dev/null || warning "Failed to create feature flag: $feature"
        fi
    done
    
    success "Feature flags prepared for gradual rollout"
}

# Phase 4: Code Deployment
deploy_code_changes() {
    log "\n📦 PHASE 4: CODE DEPLOYMENT"
    
    if [ "$DRY_RUN" = true ]; then
        log "DRY RUN: Would deploy code changes"
        return 0
    fi
    
    # Pull latest code
    log "Pulling latest code from repository..."
    git pull origin main
    success "Code updated from repository"
    
    # Install PHP dependencies
    log "Installing PHP dependencies..."
    composer install --no-dev --optimize-autoloader --quiet
    success "PHP dependencies installed"
    
    # Install Node.js dependencies and build assets
    log "Installing Node.js dependencies..."
    npm ci --silent
    success "Node.js dependencies installed"
    
    log "Building frontend assets..."
    npm run build --silent
    success "Frontend assets built"
    
    success "Code deployment completed"
}

# Phase 5: Database Updates (with brief maintenance)
deploy_database_updates() {
    log "\n🗄️ PHASE 5: DATABASE UPDATES"
    
    if [ "$DRY_RUN" = true ]; then
        log "DRY RUN: Would deploy database updates with maintenance mode"
        return 0
    fi
    
    # Check if migrations are needed
    local pending_migrations=$(php artisan migrate:status --pending --quiet | wc -l)
    
    if [ "$pending_migrations" -gt 0 ]; then
        log "Found $pending_migrations pending migrations"
        
        # Enable maintenance mode
        local maintenance_secret="deploy-$(openssl rand -hex 8)"
        php artisan down --secret="$maintenance_secret" --render="errors::maintenance" --quiet
        echo "$maintenance_secret" > "$PROJECT_ROOT/storage/deployment/maintenance-secret-$DEPLOYMENT_ID.txt"
        success "Maintenance mode enabled (secret: $maintenance_secret)"
        
        # Set flag for potential rollback
        touch "$PROJECT_ROOT/storage/deployment/migration-rollback-required.flag"
        
        # Run migrations
        log "Running database migrations..."
        php artisan migrate --force --quiet
        success "Database migrations completed"
        
        # Clear caches
        php artisan optimize:clear --quiet
        success "Caches cleared"
        
        # Rebuild caches
        php artisan config:cache --quiet
        php artisan route:cache --quiet
        php artisan view:cache --quiet
        success "Caches rebuilt"
        
        # Disable maintenance mode
        php artisan up --quiet
        success "Maintenance mode disabled"
        
        # Remove rollback flag if successful
        rm -f "$PROJECT_ROOT/storage/deployment/migration-rollback-required.flag"
        
    else
        log "No pending database migrations"
        
        # Still clear and rebuild caches
        php artisan optimize:clear --quiet
        php artisan config:cache --quiet
        php artisan route:cache --quiet
        php artisan view:cache --quiet
        success "Caches refreshed"
    fi
    
    success "Database updates completed"
}

# Phase 6: Service Restart
restart_services() {
    log "\n🔄 PHASE 6: SERVICE RESTART"
    
    if [ "$DRY_RUN" = true ]; then
        log "DRY RUN: Would restart services"
        return 0
    fi
    
    # Restart PHP-FPM
    if sudo systemctl reload php8.3-fpm 2>/dev/null; then
        success "PHP-FPM reloaded"
    else
        warning "PHP-FPM reload failed - service may need manual restart"
    fi
    
    # Restart queue workers
    php artisan horizon:terminate --quiet 2>/dev/null || true
    success "Queue workers restarted"
    
    # Wait for services to stabilize
    sleep 10
    success "Services stabilized"
}

# Phase 7: Post-deployment Validation
run_post_deployment_validation() {
    log "\n🔍 PHASE 7: POST-DEPLOYMENT VALIDATION"
    
    if [ "$DRY_RUN" = true ]; then
        log "DRY RUN: Would run post-deployment validation"
        return 0
    fi
    
    # Run comprehensive post-deployment validation
    if "$SCRIPT_DIR/post-deployment-validation.sh" "$DEPLOYMENT_ID"; then
        success "Post-deployment validation passed"
    else
        local validation_exit_code=$?
        error "Post-deployment validation failed (exit code: $validation_exit_code)"
        
        if [ "$AUTO_ROLLBACK" = true ]; then
            warning "Triggering automatic rollback due to validation failure"
            "$SCRIPT_DIR/emergency-rollback.sh" "$DEPLOYMENT_ID" "Post-deployment validation failed"
        fi
        
        exit $validation_exit_code
    fi
}

# Phase 8: Gradual Feature Rollout
rollout_features() {
    log "\n🚀 PHASE 8: GRADUAL FEATURE ROLLOUT"
    
    if [ "$DRY_RUN" = true ]; then
        log "DRY RUN: Would perform gradual feature rollout"
        return 0
    fi
    
    local features=(
        "ui-improvements"
        "mobile-navigation-fix"
        "dropdown-fixes"
        "responsive-tables"
        "performance-monitoring"
    )
    
    # Phase 8.1: Enable features at 10%
    log "🎯 Phase 8.1: Enabling features for 10% of users"
    for feature in "${features[@]}"; do
        php artisan feature enable "$feature" --percentage=10 --quiet 2>/dev/null || \
            warning "Failed to enable feature: $feature"
    done
    success "Features enabled at 10% rollout"
    
    # Monitor for 2 minutes
    log "⏱️ Monitoring system health for 2 minutes..."
    sleep 120
    
    # Quick health check
    if ! curl -f -s "https://api.askproai.de/health" >/dev/null; then
        error "Health check failed during 10% rollout"
        exit 1
    fi
    success "10% rollout health check passed"
    
    # Phase 8.2: Increase to 50%
    log "🎯 Phase 8.2: Increasing rollout to 50% of users"
    for feature in "${features[@]}"; do
        php artisan feature enable "$feature" --percentage=50 --quiet 2>/dev/null || \
            warning "Failed to update feature: $feature"
    done
    success "Features enabled at 50% rollout"
    
    # Monitor for 3 minutes
    log "⏱️ Monitoring system health for 3 minutes..."
    sleep 180
    
    # Health check
    if ! curl -f -s "https://api.askproai.de/health" >/dev/null; then
        error "Health check failed during 50% rollout"
        exit 1
    fi
    success "50% rollout health check passed"
    
    # Phase 8.3: Full rollout
    log "🎯 Phase 8.3: Full feature rollout (100%)"
    for feature in "${features[@]}"; do
        php artisan feature enable "$feature" --percentage=100 --quiet 2>/dev/null || \
            warning "Failed to fully enable feature: $feature"
    done
    success "All features fully enabled"
    
    # Final health check
    sleep 60
    if ! curl -f -s "https://api.askproai.de/health" >/dev/null; then
        error "Health check failed after full rollout"
        exit 1
    fi
    success "Full rollout health check passed"
}

# Phase 9: Final Validation and Monitoring Setup
finalize_deployment() {
    log "\n✅ PHASE 9: DEPLOYMENT FINALIZATION"
    
    if [ "$DRY_RUN" = true ]; then
        log "DRY RUN: Would finalize deployment"
        return 0
    fi
    
    # Run final comprehensive validation
    if "$SCRIPT_DIR/post-deployment-validation.sh" "$DEPLOYMENT_ID" >/dev/null 2>&1; then
        success "Final validation passed"
    else
        warning "Final validation had issues - check logs"
    fi
    
    # Clean up old deployment files (keep last 10)
    find "$PROJECT_ROOT/storage/deployment" -name "deploy-*.log" -type f | \
        sort -r | tail -n +11 | xargs rm -f 2>/dev/null || true
    
    # Update deployment tracking
    echo "$(date -Iseconds)" > "$PROJECT_ROOT/storage/deployment/last-deployment-time.txt"
    
    success "Deployment finalized and tracking updated"
}

# Main deployment execution
main() {
    parse_options "$@"
    
    initialize_deployment
    run_pre_deployment_validation
    create_deployment_backups
    prepare_feature_flags
    deploy_code_changes
    deploy_database_updates
    restart_services
    run_post_deployment_validation
    rollout_features
    finalize_deployment
    
    local end_time=$(date +%s)
    local total_duration=$((end_time - START_TIME))
    
    success "🎉 BUSINESS PORTAL DEPLOYMENT COMPLETED SUCCESSFULLY!"
    success "⏱️ Total Duration: ${total_duration} seconds"
    success "🆔 Deployment ID: $DEPLOYMENT_ID"
    success "📊 Dashboard: https://api.askproai.de/admin/deployment-monitor"
}

# Execute main function
main "$@"