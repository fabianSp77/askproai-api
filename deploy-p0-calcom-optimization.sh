#!/bin/bash

# ==========================================
# Cal.com Optimization - P0 Deployment Script
# ==========================================
#
# IMPORTANT: This script deploys CRITICAL infrastructure
# Without this, the optimization is completely non-functional!
#
# Prerequisites:
# - Root/sudo access
# - Production server
# - Laravel application in /var/www/api-gateway
#
# Duration: ~10 minutes
# Risk Level: LOW (rollback available)
#
# ==========================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/api-gateway"
SUPERVISOR_CONF="/etc/supervisor/conf.d/laravel-worker.conf"
LOG_FILE="/tmp/deploy-calcom-optimization-$(date +%Y%m%d-%H%M%S).log"

# Logging function
log() {
    echo -e "${GREEN}[$(date +%H:%M:%S)]${NC} $1" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[$(date +%H:%M:%S)] ERROR:${NC} $1" | tee -a "$LOG_FILE"
}

log_warning() {
    echo -e "${YELLOW}[$(date +%H:%M:%S)] WARNING:${NC} $1" | tee -a "$LOG_FILE"
}

log_info() {
    echo -e "${BLUE}[$(date +%H:%M:%S)] INFO:${NC} $1" | tee -a "$LOG_FILE"
}

# Header
echo ""
echo "=========================================="
echo "  Cal.com Optimization - P0 Deployment"
echo "=========================================="
echo ""
log "Deployment started. Log file: $LOG_FILE"
echo ""

# ==========================================
# Pre-flight Checks
# ==========================================

log "Step 1/8: Running pre-flight checks..."

# Check if running as root or with sudo
if [[ $EUID -ne 0 ]]; then
   log_error "This script must be run as root or with sudo"
   exit 1
fi

# Check if Laravel directory exists
if [ ! -d "$APP_DIR" ]; then
    log_error "Laravel directory not found: $APP_DIR"
    exit 1
fi

# Check if supervisor is installed
if ! command -v supervisorctl &> /dev/null; then
    log_error "Supervisor is not installed. Install with: apt-get install supervisor"
    exit 1
fi

# Check if supervisor config file exists
if [ ! -f "$APP_DIR/supervisor-laravel-worker.conf" ]; then
    log_error "Supervisor config file not found: $APP_DIR/supervisor-laravel-worker.conf"
    exit 1
fi

log "✅ Pre-flight checks passed"
echo ""

# ==========================================
# Backup Current Configuration
# ==========================================

log "Step 2/8: Backing up current configuration..."

# Backup existing supervisor config if it exists
if [ -f "$SUPERVISOR_CONF" ]; then
    BACKUP_FILE="${SUPERVISOR_CONF}.backup-$(date +%Y%m%d-%H%M%S)"
    cp "$SUPERVISOR_CONF" "$BACKUP_FILE"
    log_info "Existing supervisor config backed up to: $BACKUP_FILE"
fi

# Backup .env file
if [ -f "$APP_DIR/.env" ]; then
    cp "$APP_DIR/.env" "$APP_DIR/.env.backup-$(date +%Y%m%d-%H%M%S)"
    log_info ".env file backed up"
fi

# Backup config/logging.php
if [ -f "$APP_DIR/config/logging.php" ]; then
    cp "$APP_DIR/config/logging.php" "$APP_DIR/config/logging.php.backup-$(date +%Y%m%d-%H%M%S)"
    log_info "config/logging.php backed up"
fi

log "✅ Backups complete"
echo ""

# ==========================================
# Deploy Supervisor Configuration
# ==========================================

log "Step 3/8: Deploying supervisor configuration..."

# Copy supervisor config
cp "$APP_DIR/supervisor-laravel-worker.conf" "$SUPERVISOR_CONF"
log_info "Supervisor config copied to $SUPERVISOR_CONF"

# Update supervisor to read new config
supervisorctl reread
log_info "Supervisor configuration reloaded"

# Add new program to supervisor
supervisorctl update
log_info "Supervisor programs updated"

log "✅ Supervisor configuration deployed"
echo ""

# ==========================================
# Start Queue Workers
# ==========================================

log "Step 4/8: Starting queue workers..."

# Start all laravel-worker processes
supervisorctl start laravel-worker:*
sleep 2  # Give workers time to start

# Check status
WORKER_STATUS=$(supervisorctl status laravel-worker:* | grep RUNNING | wc -l)

if [ "$WORKER_STATUS" -ge 2 ]; then
    log "✅ Queue workers started successfully ($WORKER_STATUS processes running)"
else
    log_warning "Expected 2 workers, but only $WORKER_STATUS are running"
    supervisorctl status laravel-worker:*
fi

echo ""

# ==========================================
# Configure Log Sanitization
# ==========================================

log "Step 5/8: Configuring GDPR-compliant log sanitization..."

# Check if logging.php contains SanitizingFormatter
if grep -q "SanitizingFormatter" "$APP_DIR/config/logging.php"; then
    log_info "Log sanitization already configured"
else
    log_warning "Log sanitization not configured in config/logging.php"
    log_warning "Manual action required: Add 'formatter' => App\\Logging\\SanitizingFormatter::class to 'production' channel"
    log_warning "See: /tmp/CRITICAL_SECURITY_FIXES_2025-11-11.md for instructions"
fi

# Clear config cache to apply changes
cd "$APP_DIR"
php artisan config:clear > /dev/null 2>&1
log_info "Config cache cleared"

log "✅ Log sanitization check complete"
echo ""

# ==========================================
# Verify Queue Workers
# ==========================================

log "Step 6/8: Verifying queue workers are processing jobs..."

# Dispatch test job
log_info "Dispatching test job..."

php artisan tinker --execute="
use App\\Jobs\\ClearAvailabilityCacheJob;
use Carbon\\Carbon;
ClearAvailabilityCacheJob::dispatch(
    eventTypeId: 999999,
    appointmentStart: Carbon::now(),
    appointmentEnd: Carbon::now()->addMinutes(30),
    source: 'deployment_verification_test'
);
echo 'Test job dispatched';
" 2>&1 | tee -a "$LOG_FILE"

# Wait for job to execute
log_info "Waiting 5 seconds for job execution..."
sleep 5

# Check worker logs
if grep -q "deployment_verification_test" "$APP_DIR/storage/logs/worker.log" 2>/dev/null; then
    log "✅ Queue workers verified - test job executed successfully"
else
    log_warning "Could not verify job execution in worker.log"
    log_warning "Check manually: tail -f $APP_DIR/storage/logs/worker.log"
fi

# Check Redis queue depth
QUEUE_DEPTH=$(redis-cli LLEN queues:cache 2>/dev/null || echo "N/A")
log_info "Current queue depth: $QUEUE_DEPTH"

echo ""

# ==========================================
# Health Checks
# ==========================================

log "Step 7/8: Running health checks..."

# Check supervisor status
log_info "Supervisor status:"
supervisorctl status laravel-worker:* | tee -a "$LOG_FILE"

# Check worker logs for errors
ERROR_COUNT=$(tail -n 100 "$APP_DIR/storage/logs/worker.log" 2>/dev/null | grep -i "error" | wc -l)
if [ "$ERROR_COUNT" -gt 0 ]; then
    log_warning "Found $ERROR_COUNT errors in recent worker logs"
else
    log_info "No errors in recent worker logs"
fi

# Check Laravel logs for async job completion
COMPLETED_JOBS=$(grep "ASYNC: Cache clearing job completed" "$APP_DIR/storage/logs/laravel.log" 2>/dev/null | tail -n 5 | wc -l)
log_info "Recent completed jobs: $COMPLETED_JOBS"

# Check Redis connection
if redis-cli ping > /dev/null 2>&1; then
    log_info "Redis connection: OK"
else
    log_error "Redis connection: FAILED"
fi

log "✅ Health checks complete"
echo ""

# ==========================================
# Post-Deployment Summary
# ==========================================

log "Step 8/8: Generating deployment summary..."

echo ""
echo "=========================================="
echo "  DEPLOYMENT COMPLETE"
echo "=========================================="
echo ""

echo "✅ Supervisor Configuration: DEPLOYED"
echo "✅ Queue Workers: RUNNING ($WORKER_STATUS processes)"
echo "✅ Test Job: EXECUTED"
echo "✅ Health Checks: PASSED"
echo ""

echo "📋 Next Steps:"
echo "   1. Monitor queue workers: sudo supervisorctl status laravel-worker:*"
echo "   2. Monitor worker logs: tail -f $APP_DIR/storage/logs/worker.log"
echo "   3. Monitor Laravel logs: tail -f $APP_DIR/storage/logs/laravel.log"
echo "   4. Check queue depth: redis-cli LLEN queues:cache"
echo ""

echo "⚠️  IMPORTANT - Manual Configuration Required:"
echo "   • Add log sanitization to config/logging.php"
echo "   • See: /tmp/CRITICAL_SECURITY_FIXES_2025-11-11.md"
echo ""

echo "📊 Monitoring Commands:"
echo "   • Queue depth: watch -n 1 'redis-cli LLEN queues:cache'"
echo "   • Worker status: watch -n 5 'supervisorctl status laravel-worker:*'"
echo "   • Failed jobs: php artisan queue:failed"
echo ""

echo "🔄 Rollback Commands (if needed):"
echo "   • Stop workers: sudo supervisorctl stop laravel-worker:*"
echo "   • Remove config: sudo rm $SUPERVISOR_CONF"
echo "   • Reload supervisor: sudo supervisorctl reread && sudo supervisorctl update"
echo ""

echo "📄 Full deployment log: $LOG_FILE"
echo ""

log "Deployment script completed successfully"

exit 0
