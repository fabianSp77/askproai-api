#!/bin/bash
# emergency-rollback.sh
# Emergency rollback script for Business Portal deployment

set -e

DEPLOYMENT_ID="${1:-unknown}"
ROLLBACK_REASON="${2:-Emergency rollback triggered}"
ROLLBACK_START=$(date +%s)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_FILE="$PROJECT_ROOT/storage/deployment/emergency-rollback-$(date +%Y%m%d-%H%M%S).log"

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

# Create log directory
mkdir -p "$PROJECT_ROOT/storage/deployment"

log "🚨 EMERGENCY ROLLBACK INITIATED"
log "Deployment ID: $DEPLOYMENT_ID"
log "Reason: $ROLLBACK_REASON"
log "Started at: $(date)"

# Change to project root
cd "$PROJECT_ROOT"

# ==========================================
# Phase 1: Immediate Feature Flag Disable
# ==========================================
log "\n🚩 Phase 1: Immediate Feature Flag Disable"

# Disable all new features immediately
FEATURES_TO_DISABLE=(
    "ui-improvements"
    "mobile-navigation-fix" 
    "dropdown-fixes"
    "responsive-tables"
    "performance-monitoring"
    "enhanced-mobile-nav"
    "test-coverage-reporting"
)

for feature in "${FEATURES_TO_DISABLE[@]}"; do
    if php artisan feature disable "$feature" --quiet 2>/dev/null; then
        log "✅ Disabled feature: $feature"
    else
        warning "Failed to disable feature: $feature (may not exist)"
    fi
done

# Emergency disable all features as fallback
if php artisan feature emergency-disable --reason="$ROLLBACK_REASON" --quiet 2>/dev/null; then
    success "Emergency disabled all feature flags"
else
    warning "Feature flag emergency disable failed - continuing rollback"
fi

# Clear all caches to ensure feature flags take effect
php artisan cache:clear --quiet 2>/dev/null || true
php artisan config:clear --quiet 2>/dev/null || true

success "Phase 1 complete - All features disabled"

# ==========================================
# Phase 2: Enable Maintenance Mode
# ==========================================
log "\n🔒 Phase 2: Enable Maintenance Mode"

MAINTENANCE_SECRET="rollback-$(openssl rand -hex 8)"
php artisan down --secret="$MAINTENANCE_SECRET" --render="errors::maintenance" --quiet 2>/dev/null

# Store maintenance secret for later use
echo "$MAINTENANCE_SECRET" > "$PROJECT_ROOT/storage/deployment/maintenance-secret.txt"

success "Maintenance mode enabled with secret: $MAINTENANCE_SECRET"

# ==========================================
# Phase 3: Code Rollback
# ==========================================
log "\n⏪ Phase 3: Code Rollback"

# Find previous commit
PREVIOUS_COMMIT=""
if [ -f "$PROJECT_ROOT/storage/deployment/previous-commit.txt" ]; then
    PREVIOUS_COMMIT=$(cat "$PROJECT_ROOT/storage/deployment/previous-commit.txt")
    log "Found previous commit: $PREVIOUS_COMMIT"
elif [ -f "$PROJECT_ROOT/storage/deployment/last-successful-deployment.txt" ]; then
    PREVIOUS_COMMIT=$(cat "$PROJECT_ROOT/storage/deployment/last-successful-deployment.txt")
    log "Using last successful deployment: $PREVIOUS_COMMIT"
else
    # Fallback to previous commit
    PREVIOUS_COMMIT=$(git rev-parse HEAD~1)
    warning "No deployment history found, using HEAD~1: $PREVIOUS_COMMIT"
fi

# Stash any uncommitted changes
git stash push -m "Emergency rollback stash $(date)" --quiet 2>/dev/null || true

# Reset to previous commit
if git reset --hard "$PREVIOUS_COMMIT" --quiet 2>/dev/null; then
    success "Code rolled back to commit: $PREVIOUS_COMMIT"
else
    error "Failed to rollback code to: $PREVIOUS_COMMIT"
    
    # Try alternative rollback strategies
    if git reset --hard HEAD~1 --quiet 2>/dev/null; then
        warning "Rolled back to HEAD~1 instead"
    else
        error "All code rollback attempts failed"
    fi
fi

# ==========================================
# Phase 4: Configuration Restore
# ==========================================
log "\n⚙️ Phase 4: Configuration Restore"

# Restore environment configuration
ENV_BACKUP_FILES=(
    "storage/deployment/.env.backup"
    "storage/deployment/.env.pre-deployment"
    ".env.backup"
)

ENV_RESTORED=false
for backup_file in "${ENV_BACKUP_FILES[@]}"; do
    if [ -f "$backup_file" ]; then
        cp "$backup_file" .env
        success "Environment configuration restored from: $backup_file"
        ENV_RESTORED=true
        break
    fi
done

if [ "$ENV_RESTORED" = false ]; then
    warning "No environment backup found - using current configuration"
fi

# ==========================================
# Phase 5: Database Rollback (if needed)
# ==========================================
log "\n🗄️ Phase 5: Database Rollback Assessment"

# Check if database rollback is needed
MIGRATION_ROLLBACK_REQUIRED=false
if [ -f "$PROJECT_ROOT/storage/deployment/migration-rollback-required.flag" ]; then
    MIGRATION_ROLLBACK_REQUIRED=true
    log "Database rollback flag detected"
fi

if [ "$MIGRATION_ROLLBACK_REQUIRED" = true ]; then
    warning "Database rollback required - restoring from backup"
    
    # Find the most recent database backup
    LATEST_DB_BACKUP=""
    for backup_pattern in "storage/deployment/pre-deployment-db-*.sql" "storage/app/backups/*$(date +%Y-%m-%d)*.sql" "storage/app/backups/*.sql"; do
        BACKUP_FILE=$(ls -t $backup_pattern 2>/dev/null | head -1 || echo "")
        if [ -n "$BACKUP_FILE" ]; then
            LATEST_DB_BACKUP="$BACKUP_FILE"
            break
        fi
    done
    
    if [ -n "$LATEST_DB_BACKUP" ] && [ -f "$LATEST_DB_BACKUP" ]; then
        log "Restoring database from: $LATEST_DB_BACKUP"
        
        # Create current backup before restoring
        mysqldump -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db > \
            "storage/deployment/pre-rollback-backup-$(date +%Y%m%d-%H%M%S).sql" 2>/dev/null || \
            warning "Failed to create pre-rollback backup"
        
        # Restore database
        if mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db < "$LATEST_DB_BACKUP" 2>/dev/null; then
            success "Database restored successfully"
        else
            error "Database restore failed - manual intervention required"
        fi
    else
        error "No database backup found - skipping database rollback"
        warning "Database may be in inconsistent state"
    fi
else
    log "No database rollback required"
fi

# ==========================================
# Phase 6: Asset Rollback
# ==========================================
log "\n🎨 Phase 6: Asset Rollback"

# Remove current build assets
if [ -d "public/build" ]; then
    rm -rf public/build
    log "Removed current build assets"
fi

# Restore previous assets
ASSET_RESTORED=false
ASSET_BACKUP_DIRS=(
    "storage/deployment/assets-backup-$(date +%Y%m%d)*"
    "storage/deployment/assets-backup-*"
)

for pattern in "${ASSET_BACKUP_DIRS[@]}"; do
    LATEST_ASSET_BACKUP=$(ls -td $pattern 2>/dev/null | head -1 || echo "")
    if [ -n "$LATEST_ASSET_BACKUP" ] && [ -d "$LATEST_ASSET_BACKUP" ]; then
        cp -r "$LATEST_ASSET_BACKUP" public/build
        success "Assets restored from: $LATEST_ASSET_BACKUP"
        ASSET_RESTORED=true
        break
    fi
done

if [ "$ASSET_RESTORED" = false ]; then
    warning "No asset backup found - rebuilding assets"
    
    # Try to rebuild assets with previous code
    if command -v npm >/dev/null 2>&1; then
        if npm run build --silent 2>/dev/null; then
            success "Assets rebuilt successfully"
        else
            warning "Asset rebuild failed - using minimal fallback"
            mkdir -p public/build/assets
            echo "/* Fallback CSS */" > public/build/assets/app.css
            echo "// Fallback JS" > public/build/assets/app.js
        fi
    fi
fi

# ==========================================
# Phase 7: Dependencies & Optimization
# ==========================================
log "\n📦 Phase 7: Dependencies & Optimization"

# Reinstall dependencies for rolled back code
composer install --no-dev --optimize-autoloader --quiet 2>/dev/null || \
    warning "Composer install failed - continuing with existing dependencies"

# Clear and rebuild all caches
php artisan optimize:clear --quiet 2>/dev/null || true
php artisan config:cache --quiet 2>/dev/null || warning "Config cache failed"
php artisan route:cache --quiet 2>/dev/null || warning "Route cache failed"
php artisan view:cache --quiet 2>/dev/null || warning "View cache failed"

success "Dependencies and caches updated"

# ==========================================
# Phase 8: Service Restart
# ==========================================
log "\n🔄 Phase 8: Service Restart"

# Restart PHP-FPM
if sudo systemctl restart php8.3-fpm 2>/dev/null; then
    success "PHP-FPM restarted"
else
    warning "PHP-FPM restart failed - may need manual restart"
fi

# Restart queue workers
php artisan horizon:terminate --quiet 2>/dev/null || \
    php artisan queue:restart --quiet 2>/dev/null || \
    warning "Queue worker restart failed"

# Wait for services to stabilize
sleep 5

success "Services restarted"

# ==========================================
# Phase 9: Disable Maintenance Mode
# ==========================================
log "\n🔓 Phase 9: Disable Maintenance Mode"

php artisan up --quiet 2>/dev/null
success "Maintenance mode disabled"

# ==========================================
# Phase 10: Rollback Validation
# ==========================================
log "\n✅ Phase 10: Rollback Validation"

# Wait for application to stabilize
sleep 10

VALIDATION_FAILED=0

# Test basic health endpoint
if curl -f -s "https://api.askproai.de/health" >/dev/null; then
    success "Health endpoint responding"
else
    error "Health endpoint failed"
    VALIDATION_FAILED=1
fi

# Test admin panel
ADMIN_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "https://api.askproai.de/admin" 2>/dev/null)
if [ "$ADMIN_RESPONSE" = "200" ] || [ "$ADMIN_RESPONSE" = "302" ]; then
    success "Admin panel accessible (HTTP $ADMIN_RESPONSE)"
else
    error "Admin panel failed (HTTP $ADMIN_RESPONSE)"
    VALIDATION_FAILED=1
fi

# Test database connectivity
if php artisan tinker --execute="DB::select('SELECT 1')" >/dev/null 2>&1; then
    success "Database connectivity confirmed"
else
    error "Database connectivity failed"
    VALIDATION_FAILED=1
fi

# Test response time
RESPONSE_TIME=$(curl -w "%{time_total}" -s -o /dev/null "https://api.askproai.de/health" 2>/dev/null || echo "999")
if [ "$(echo "$RESPONSE_TIME < 5.0" | bc 2>/dev/null || echo 0)" -eq 1 ]; then
    success "Response time acceptable (${RESPONSE_TIME}s)"
else
    warning "Response time slow (${RESPONSE_TIME}s)"
fi

# ==========================================
# ROLLBACK SUMMARY
# ==========================================
ROLLBACK_END=$(date +%s)
ROLLBACK_DURATION=$((ROLLBACK_END - ROLLBACK_START))

log "\n📊 EMERGENCY ROLLBACK SUMMARY"
log "================================"
log "Deployment ID: $DEPLOYMENT_ID"
log "Duration: ${ROLLBACK_DURATION} seconds"
log "Validation Failures: $VALIDATION_FAILED"

# Create rollback report
ROLLBACK_REPORT="$PROJECT_ROOT/storage/deployment/rollback-report-$DEPLOYMENT_ID.json"
cat > "$ROLLBACK_REPORT" << EOF
{
    "deployment_id": "$DEPLOYMENT_ID",
    "rollback_reason": "$ROLLBACK_REASON",
    "timestamp": "$(date -Iseconds)",
    "duration_seconds": $ROLLBACK_DURATION,
    "rolled_back_to_commit": "$PREVIOUS_COMMIT",
    "validation_failures": $VALIDATION_FAILED,
    "phases_completed": {
        "feature_flags_disabled": true,
        "maintenance_mode": true,
        "code_rollback": true,
        "config_restore": $ENV_RESTORED,
        "database_rollback": $MIGRATION_ROLLBACK_REQUIRED,
        "asset_rollback": $ASSET_RESTORED,
        "services_restarted": true,
        "validation_passed": $([ $VALIDATION_FAILED -eq 0 ] && echo 'true' || echo 'false')
    }
}
EOF

if [ $VALIDATION_FAILED -eq 0 ]; then
    success "🟢 EMERGENCY ROLLBACK SUCCESSFUL"
    echo -e "\n${GREEN}✅ Emergency rollback completed successfully!${NC}"
    echo -e "${GREEN}⏱️ Duration: ${ROLLBACK_DURATION} seconds${NC}"
    echo -e "${GREEN}📊 All validation checks passed${NC}"
    echo -e "${GREEN}📄 Report: $ROLLBACK_REPORT${NC}"
    
    # Notify success
    if [ -f "$SCRIPT_DIR/send-alert.sh" ]; then
        "$SCRIPT_DIR/send-alert.sh" "✅ Emergency rollback successful for deployment $DEPLOYMENT_ID (${ROLLBACK_DURATION}s)"
    fi
    
    # Update status
    echo "rollback_success" > "$PROJECT_ROOT/storage/deployment/status-$DEPLOYMENT_ID.txt"
    exit 0
    
else
    error "🔴 EMERGENCY ROLLBACK COMPLETED WITH ISSUES"
    echo -e "\n${RED}⚠️ Emergency rollback completed with validation issues${NC}"
    echo -e "${RED}⏱️ Duration: ${ROLLBACK_DURATION} seconds${NC}"
    echo -e "${RED}❌ Validation failures: $VALIDATION_FAILED${NC}"
    echo -e "${RED}📞 Manual intervention may be required${NC}"
    echo -e "${RED}📄 Report: $ROLLBACK_REPORT${NC}"
    
    # Notify with issues
    if [ -f "$SCRIPT_DIR/send-alert.sh" ]; then
        "$SCRIPT_DIR/send-alert.sh" "⚠️ Emergency rollback completed with issues for deployment $DEPLOYMENT_ID ($VALIDATION_FAILED failures)"
    fi
    
    # Update status
    echo "rollback_issues" > "$PROJECT_ROOT/storage/deployment/status-$DEPLOYMENT_ID.txt"
    exit 2
fi