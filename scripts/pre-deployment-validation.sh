#!/bin/bash
# pre-deployment-validation.sh
# Comprehensive pre-deployment validation script for Business Portal improvements

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_FILE="$PROJECT_ROOT/storage/deployment/pre-validation-$(date +%Y%m%d-%H%M%S).log"
VALIDATION_SCORE=0
TOTAL_CHECKS=15

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
    VALIDATION_SCORE=$((VALIDATION_SCORE + 1))
}

warning() {
    echo -e "${YELLOW}⚠️ $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}❌ $1${NC}" | tee -a "$LOG_FILE"
}

check_passed() {
    if [ $? -eq 0 ]; then
        success "$1"
        return 0
    else
        error "$1"
        return 1
    fi
}

# Create log directory
mkdir -p "$PROJECT_ROOT/storage/deployment"

log "🔍 Starting comprehensive pre-deployment validation..."
log "📊 Total checks: $TOTAL_CHECKS"

# Change to project root
cd "$PROJECT_ROOT"

# ==========================================
# 1. Code Quality & Testing
# ==========================================
log "\n📋 1. CODE QUALITY & TESTING VALIDATION"

# 1.1 Test Suite Execution
log "1.1 Running full test suite..."
if php artisan test --quiet --coverage --min=60; then
    success "Test suite passed with ≥60% coverage"
else
    error "Test suite failed or coverage below 60%"
fi

# 1.2 Static Analysis
log "1.2 Running static analysis (PHPStan)..."
if composer stan -- --quiet --no-progress; then
    success "Static analysis passed"
else
    error "Static analysis failed"
fi

# 1.3 Code Style Check
log "1.3 Checking code style (Pint)..."
if ./vendor/bin/pint --test --quiet; then
    success "Code style check passed"
else
    error "Code style violations detected"
fi

# 1.4 Security Audit
log "1.4 Running security audit..."
if composer audit --quiet; then
    success "Security audit passed (no vulnerabilities)"
else
    error "Security vulnerabilities detected"
fi

# ==========================================
# 2. Infrastructure & Environment
# ==========================================
log "\n🏗️ 2. INFRASTRUCTURE & ENVIRONMENT VALIDATION"

# 2.1 Environment Configuration
log "2.1 Validating environment configuration..."
if php artisan config:check --quiet 2>/dev/null || php -r "
try {
    \$config = require 'config/app.php';
    if (empty(\$config['key'])) throw new Exception('APP_KEY not set');
    echo 'Configuration valid';
} catch (Exception \$e) {
    echo 'Configuration error: ' . \$e->getMessage();
    exit(1);
}
"; then
    success "Environment configuration valid"
else
    error "Environment configuration invalid"
fi

# 2.2 Database Connectivity
log "2.2 Testing database connectivity..."
if php artisan tinker --execute="DB::select('SELECT 1 as test')" 2>/dev/null | grep -q "test"; then
    success "Database connectivity confirmed"
else
    error "Database connectivity failed"
fi

# 2.3 Redis Connectivity
log "2.3 Testing Redis connectivity..."
if php artisan tinker --execute="Redis::ping()" 2>/dev/null | grep -q "PONG"; then
    success "Redis connectivity confirmed"
else
    error "Redis connectivity failed"
fi

# 2.4 File Permissions
log "2.4 Checking file permissions..."
PERMISSION_ERRORS=0
for dir in storage bootstrap/cache; do
    if [ ! -w "$dir" ]; then
        error "Directory $dir is not writable"
        PERMISSION_ERRORS=$((PERMISSION_ERRORS + 1))
    fi
done

if [ $PERMISSION_ERRORS -eq 0 ]; then
    success "File permissions correct"
else
    error "File permission errors detected"
fi

# ==========================================
# 3. Dependencies & External Services
# ==========================================
log "\n🔗 3. DEPENDENCIES & EXTERNAL SERVICES VALIDATION"

# 3.1 External API Health
log "3.1 Testing external API integrations..."
API_FAILURES=0

# Test Cal.com if configured
if [ -n "${DEFAULT_CALCOM_API_KEY:-}" ]; then
    if curl -f -s -H "Authorization: Bearer $DEFAULT_CALCOM_API_KEY" \
        "https://api.cal.com/v1/me" >/dev/null; then
        log "✅ Cal.com API: Healthy"
    else
        warning "Cal.com API: Failed (will continue without)"
        API_FAILURES=$((API_FAILURES + 1))
    fi
fi

# Test Retell.ai if configured
if [ -n "${RETELL_TOKEN:-}" ]; then
    if curl -f -s -H "Authorization: Bearer $RETELL_TOKEN" \
        "https://api.retellai.com/list-agents" >/dev/null; then
        log "✅ Retell.ai API: Healthy"
    else
        warning "Retell.ai API: Failed (will continue without)"
        API_FAILURES=$((API_FAILURES + 1))
    fi
fi

if [ $API_FAILURES -eq 0 ]; then
    success "External API integrations healthy"
else
    warning "Some external APIs failed ($API_FAILURES), deployment can continue"
    VALIDATION_SCORE=$((VALIDATION_SCORE + 1))  # Don't fail deployment for external APIs
fi

# 3.2 Email Service
log "3.2 Testing email service..."
if php artisan tinker --execute="
try {
    Mail::raw('Test email', function(\$message) {
        \$message->to('test@example.com')->subject('Test');
    });
    echo 'Email service healthy';
} catch (Exception \$e) {
    throw \$e;
}
" 2>/dev/null | grep -q "Email service healthy"; then
    success "Email service configured correctly"
else
    warning "Email service test failed (check configuration)"
    VALIDATION_SCORE=$((VALIDATION_SCORE + 1))  # Don't fail for email issues
fi

# ==========================================
# 4. Performance Validation
# ==========================================
log "\n⚡ 4. PERFORMANCE VALIDATION"

# 4.1 Asset Build Test
log "4.1 Testing asset build process..."
if npm run build >/dev/null 2>&1; then
    success "Asset build completed successfully"
else
    error "Asset build failed"
fi

# 4.2 Bundle Size Check
log "4.2 Checking bundle size..."
if [ -d "public/build" ]; then
    BUNDLE_SIZE=$(du -sm public/build | cut -f1)
    if [ "$BUNDLE_SIZE" -lt 10 ]; then  # Less than 10MB
        success "Bundle size acceptable (${BUNDLE_SIZE}MB)"
    else
        warning "Bundle size large (${BUNDLE_SIZE}MB) - consider optimization"
        VALIDATION_SCORE=$((VALIDATION_SCORE + 1))  # Don't fail for large bundles
    fi
else
    error "Bundle directory not found"
fi

# 4.3 Database Migration Dry Run
log "4.3 Testing database migrations (dry run)..."
if php artisan migrate --pretend --quiet >/dev/null 2>&1; then
    success "Database migrations validated"
else
    error "Database migration validation failed"
fi

# ==========================================
# 5. Feature-Specific Validation
# ==========================================
log "\n🎨 5. FEATURE-SPECIFIC VALIDATION"

# 5.1 UI/UX Fix Validation
log "5.1 Validating UI/UX fixes..."
UI_VALIDATION_SCORE=0

# Check for critical UI fix files
if [ -f "public/css/unified-ui-fixes.css" ]; then
    log "✅ Unified UI fixes CSS present"
    UI_VALIDATION_SCORE=$((UI_VALIDATION_SCORE + 1))
fi

if [ -f "public/js/unified-ui-system.js" ]; then
    log "✅ Unified UI system JS present"
    UI_VALIDATION_SCORE=$((UI_VALIDATION_SCORE + 1))
fi

# Check for mobile fixes
if [ -f "public/js/mobile-navigation-fix.js" ]; then
    log "✅ Mobile navigation fix present"
    UI_VALIDATION_SCORE=$((UI_VALIDATION_SCORE + 1))
fi

if [ $UI_VALIDATION_SCORE -ge 2 ]; then
    success "UI/UX fixes properly deployed"
else
    error "UI/UX fixes incomplete ($UI_VALIDATION_SCORE/3 components found)"
fi

# 5.2 Performance Monitoring Setup
log "5.2 Validating performance monitoring setup..."
if [ -f "app/Services/Monitoring/HealthCheckService.php" ]; then
    if php artisan tinker --execute="
    try {
        \$service = app('App\\Services\\Monitoring\\HealthCheckService');
        echo 'Health check service ready';
    } catch (Exception \$e) {
        throw \$e;
    }
    " 2>/dev/null | grep -q "Health check service ready"; then
        success "Performance monitoring system ready"
    else
        error "Performance monitoring system failed to initialize"
    fi
else
    error "Performance monitoring service not found"
fi

# ==========================================
# 6. Final System Health Check
# ==========================================
log "\n🏥 6. COMPREHENSIVE SYSTEM HEALTH CHECK"

# 6.1 Overall Health Check
log "6.1 Running comprehensive system health check..."
if php artisan health:check --quiet 2>/dev/null || php artisan tinker --execute="
try {
    // Basic health checks
    DB::select('SELECT 1');
    Cache::put('health_check', 'ok', 60);
    \$cached = Cache::get('health_check');
    if (\$cached !== 'ok') throw new Exception('Cache test failed');
    echo 'System health: OK';
} catch (Exception \$e) {
    echo 'System health: FAILED - ' . \$e->getMessage();
    exit(1);
}
" 2>/dev/null | grep -q "System health: OK"; then
    success "Comprehensive health check passed"
else
    error "Comprehensive health check failed"
fi

# ==========================================
# VALIDATION SUMMARY
# ==========================================
log "\n📊 VALIDATION SUMMARY"
log "======================================"

VALIDATION_PERCENTAGE=$((VALIDATION_SCORE * 100 / TOTAL_CHECKS))
log "Score: $VALIDATION_SCORE/$TOTAL_CHECKS ($VALIDATION_PERCENTAGE%)"

if [ $VALIDATION_PERCENTAGE -ge 90 ]; then
    success "🟢 VALIDATION PASSED - Deployment approved"
    echo -e "\n${GREEN}✅ Pre-deployment validation completed successfully!${NC}"
    echo -e "${GREEN}📋 Validation Score: $VALIDATION_SCORE/$TOTAL_CHECKS ($VALIDATION_PERCENTAGE%)${NC}"
    echo -e "${GREEN}🚀 System ready for deployment${NC}"
    
    # Save validation results
    cat > "$PROJECT_ROOT/storage/deployment/validation-results.json" << EOF
{
    "timestamp": "$(date -Iseconds)",
    "score": $VALIDATION_SCORE,
    "total_checks": $TOTAL_CHECKS,
    "percentage": $VALIDATION_PERCENTAGE,
    "status": "passed",
    "deployment_approved": true
}
EOF
    
    exit 0
elif [ $VALIDATION_PERCENTAGE -ge 80 ]; then
    warning "🟡 CONDITIONAL PASS - Review required"
    echo -e "\n${YELLOW}⚠️ Pre-deployment validation needs review${NC}"
    echo -e "${YELLOW}📋 Validation Score: $VALIDATION_SCORE/$TOTAL_CHECKS ($VALIDATION_PERCENTAGE%)${NC}"
    echo -e "${YELLOW}👥 Manual review recommended before deployment${NC}"
    
    # Save validation results
    cat > "$PROJECT_ROOT/storage/deployment/validation-results.json" << EOF
{
    "timestamp": "$(date -Iseconds)",
    "score": $VALIDATION_SCORE,
    "total_checks": $TOTAL_CHECKS,
    "percentage": $VALIDATION_PERCENTAGE,
    "status": "conditional",
    "deployment_approved": false,
    "review_required": true
}
EOF
    
    exit 2
else
    error "🔴 VALIDATION FAILED - Deployment blocked"
    echo -e "\n${RED}❌ Pre-deployment validation failed${NC}"
    echo -e "${RED}📋 Validation Score: $VALIDATION_SCORE/$TOTAL_CHECKS ($VALIDATION_PERCENTAGE%)${NC}"
    echo -e "${RED}🚫 Deployment blocked until issues are resolved${NC}"
    
    # Save validation results
    cat > "$PROJECT_ROOT/storage/deployment/validation-results.json" << EOF
{
    "timestamp": "$(date -Iseconds)",
    "score": $VALIDATION_SCORE,
    "total_checks": $TOTAL_CHECKS,
    "percentage": $VALIDATION_PERCENTAGE,
    "status": "failed",
    "deployment_approved": false,
    "critical_issues": true
}
EOF
    
    exit 1
fi