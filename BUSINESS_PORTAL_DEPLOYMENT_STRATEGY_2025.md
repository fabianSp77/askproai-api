# 🚀 Business Portal Deployment Strategy 2025

## 📋 Executive Summary

This document outlines the comprehensive production deployment strategy for the Business Portal improvements planned in the 6-day sprint cycle. The deployment includes critical UI/UX fixes, enhanced test coverage, performance monitoring, and documentation updates with zero-downtime deployment capabilities.

### 🎯 Deployment Scope
- **Critical UI/UX fixes**: Mobile navigation, tables, authentication flows
- **Test suite expansion**: 40% → 60% coverage with new comprehensive test cases
- **Performance monitoring**: Real-time system health and performance tracking
- **Documentation updates**: Automated health checks and deployment guides
- **Frontend optimizations**: Build process improvements and asset optimization

---

## 🏗️ Deployment Architecture Overview

### Current Infrastructure Assessment
- **Platform**: Linux ARM64 (Netcup hosting)
- **Web Server**: Nginx with PHP 8.3-FPM
- **Database**: MariaDB with Redis caching
- **Queue**: Laravel Horizon with Redis backend
- **Assets**: Vite-based build with CDN capabilities
- **Monitoring**: Custom health check system with Prometheus metrics

### Deployment Components
```
┌─────────────────────────────────────────────────────────────┐
│                    Production Environment                    │
├─────────────────────────────────────────────────────────────┤
│ Load Balancer → Nginx → PHP-FPM → Laravel Application     │
│                    ↓                ↓                      │
│              Static Assets    Database/Redis               │
│              (Vite Build)     (Persistent Storage)         │
└─────────────────────────────────────────────────────────────┘
```

---

## 📅 6-Day Sprint Deployment Timeline

### Sprint Day 1-2: Preparation & Testing
**Status**: Development & Testing
- Complete UI/UX fix implementation
- Achieve 60% test coverage milestone
- Performance monitoring system setup
- Documentation updates and validation

### Sprint Day 3: Pre-Deployment Validation
**Status**: Ready for Deployment
- Full test suite execution (Unit, Integration, E2E)
- Performance benchmark establishment
- Security audit completion
- Rollback plan verification

### Sprint Day 4: Production Deployment
**Status**: Deployment Window
- **Time**: 02:00-04:00 CET (Low traffic window)
- **Duration**: Maximum 2 hours
- **Downtime**: <30 seconds for database migrations

### Sprint Day 5: Post-Deployment Monitoring
**Status**: Monitoring & Optimization
- 24-hour intensive monitoring
- Performance metrics validation
- User acceptance testing coordination
- Issue triage and hot-fixes if needed

### Sprint Day 6: Optimization & Documentation
**Status**: Finalization
- Performance optimization based on real data
- Documentation completion
- Stakeholder reporting
- Next sprint planning

---

## 🔄 Phased Rollout Strategy

### Phase 1: Infrastructure Updates (0% User Impact)
```bash
# Pre-deployment preparation (No downtime)
- Asset compilation and optimization
- Database backup creation
- Configuration validation
- Service health verification
```

**Duration**: 30 minutes
**Risk Level**: Low
**Rollback**: Not required

### Phase 2: Backend Deployment (Minimal Downtime)
```bash
# Core application deployment
php artisan down --secret="deploy-2025-$(date +%s)"
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
php artisan up
```

**Duration**: 2-3 minutes
**Risk Level**: Medium
**Rollback**: Automated via deployment script

### Phase 3: Frontend Asset Deployment (0% User Impact)
```bash
# Asset deployment and cache invalidation
npm run build --production
php artisan filament:optimize
# CDN cache invalidation if applicable
```

**Duration**: 5 minutes
**Risk Level**: Low
**Rollback**: Asset version rollback

### Phase 4: Feature Flag Activation (Gradual User Impact)
```bash
# Gradual feature rollout via feature flags
php artisan feature:enable ui-improvements --percentage=10
php artisan feature:enable performance-monitoring --percentage=50
php artisan feature:enable enhanced-mobile-nav --percentage=25
```

**Duration**: Ongoing over 24 hours
**Risk Level**: Low
**Rollback**: Instant via feature flag toggle

---

## 🚩 Feature Flag Implementation

### Feature Flag Strategy
We implement feature flags for all major changes to enable gradual rollout and instant rollback capabilities.

#### Feature Flag Configuration
```php
// config/features.php
return [
    'ui-improvements' => [
        'mobile-navigation-fix' => env('FEATURE_MOBILE_NAV', false),
        'dropdown-fixes' => env('FEATURE_DROPDOWN_FIX', false),
        'responsive-tables' => env('FEATURE_RESPONSIVE_TABLES', false),
    ],
    'performance-monitoring' => [
        'real-time-metrics' => env('FEATURE_METRICS', false),
        'enhanced-logging' => env('FEATURE_ENHANCED_LOGGING', false),
    ],
    'test-coverage-reporting' => [
        'coverage-dashboard' => env('FEATURE_COVERAGE_DASHBOARD', false),
    ],
];
```

#### Feature Flag Management Commands
```bash
# Enable features gradually
php artisan feature:enable ui-improvements --percentage=10  # 10% of users
php artisan feature:enable ui-improvements --percentage=50  # Increase to 50%
php artisan feature:enable ui-improvements --percentage=100 # Full rollout

# Instant rollback capability
php artisan feature:disable ui-improvements  # Instant rollback

# Monitor feature usage
php artisan feature:stats ui-improvements
```

#### Feature Flag Implementation in Code
```php
// In Blade templates
@if(feature('ui-improvements.mobile-navigation-fix'))
    <script src="{{ asset('js/mobile-navigation-fix.js') }}"></script>
@endif

// In Controllers
if (feature('performance-monitoring.real-time-metrics')) {
    $this->logPerformanceMetrics();
}

// In JavaScript
if (window.features?.['ui-improvements']?.['dropdown-fixes']) {
    initializeDropdownFixes();
}
```

---

## 🔄 Rollback Procedures

### Automated Rollback Triggers
- **Response Time**: >2 seconds average
- **Error Rate**: >5% in 5-minute window
- **Failed Health Checks**: 3 consecutive failures
- **User Reports**: >10 critical issues in 10 minutes

### 1. Instant Rollback (Feature Flags)
```bash
#!/bin/bash
# instant-rollback.sh
echo "🚨 Executing instant rollback via feature flags..."

# Disable all new features
php artisan feature:disable ui-improvements
php artisan feature:disable performance-monitoring
php artisan feature:disable enhanced-mobile-nav

# Clear caches
php artisan cache:clear
php artisan config:clear

echo "✅ Instant rollback complete - monitoring for 5 minutes..."
sleep 300
php artisan health:check
```

**Duration**: <30 seconds

### 2. Asset Rollback
```bash
#!/bin/bash
# asset-rollback.sh
echo "🔄 Rolling back frontend assets..."

# Restore previous asset version
PREVIOUS_VERSION=$(cat storage/deployment/previous-version.txt)
rm -rf public/build
cp -r storage/deployment/assets-backup-$PREVIOUS_VERSION public/build

# Clear CDN cache
curl -X POST "https://api.cloudflare.com/client/v4/zones/$CF_ZONE/purge_cache" \
  -H "Authorization: Bearer $CF_TOKEN" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'

echo "✅ Asset rollback complete"
```

**Duration**: 2-3 minutes

### 3. Full Application Rollback
```bash
#!/bin/bash
# full-rollback.sh
set -e

echo "🚨 Executing full application rollback..."

# Enable maintenance mode
php artisan down --secret="rollback-$(date +%s)"

# Restore code
PREVIOUS_COMMIT=$(cat storage/deployment/previous-commit.txt)
git reset --hard $PREVIOUS_COMMIT

# Restore database if needed
if [ -f "storage/deployment/pre-deployment-db.sql" ]; then
    echo "Restoring database..."
    mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db < storage/deployment/pre-deployment-db.sql
fi

# Restore configuration
cp storage/deployment/.env.backup .env

# Clear caches and optimize
php artisan optimize:clear
composer install --no-dev --optimize-autoloader
php artisan migrate:status

# Restart services
sudo systemctl restart php8.3-fpm
php artisan horizon:terminate

# Disable maintenance mode
php artisan up

echo "✅ Full rollback complete"
```

**Duration**: 5-10 minutes

### Rollback Decision Matrix
| Issue Severity | Response Time | Action |
|---------------|---------------|---------|
| Critical (Site Down) | Immediate | Full Rollback |
| High (Major Features Broken) | <5 minutes | Feature Flag Rollback |
| Medium (Performance Issues) | <10 minutes | Asset/Config Rollback |
| Low (Minor UI Issues) | <30 minutes | Hotfix Deployment |

---

## 📊 Post-Deployment Monitoring

### Real-Time Monitoring Dashboard
Access: `/admin/deployment-monitor`

#### Key Metrics to Monitor
```json
{
  "response_times": {
    "target": "<200ms",
    "critical_threshold": ">2000ms"
  },
  "error_rates": {
    "target": "<1%",
    "critical_threshold": ">5%"
  },
  "throughput": {
    "target": ">100 requests/minute",
    "critical_threshold": "<50 requests/minute"
  },
  "health_checks": {
    "database": "healthy",
    "redis": "healthy",
    "queue": "healthy",
    "external_apis": "healthy"
  }
}
```

#### Monitoring Commands
```bash
# Real-time system health
watch -n 5 'php artisan health:check --json'

# Performance monitoring
php artisan monitor:performance --duration=60 --interval=5

# Error rate monitoring
tail -f storage/logs/laravel.log | grep -i error | wc -l

# Queue monitoring
php artisan horizon:status
```

### Automated Alerting System
```bash
# Deployment monitoring script
#!/bin/bash
# monitor-deployment.sh

DEPLOYMENT_TIME=$(date +%s)
MONITORING_DURATION=3600  # 1 hour intensive monitoring

echo "🔍 Starting post-deployment monitoring for 1 hour..."

while [ $(($(date +%s) - DEPLOYMENT_TIME)) -lt $MONITORING_DURATION ]; do
    # Health check
    if ! php artisan health:check --quiet; then
        echo "🚨 Health check failed - considering rollback"
        ./rollback-decision.sh
    fi
    
    # Error rate check
    ERROR_COUNT=$(tail -100 storage/logs/laravel.log | grep -c "ERROR" || echo "0")
    if [ $ERROR_COUNT -gt 10 ]; then
        echo "⚠️ High error rate detected: $ERROR_COUNT errors"
        # Trigger alert to team
        ./send-alert.sh "High error rate: $ERROR_COUNT errors in last 100 log entries"
    fi
    
    # Performance check
    RESPONSE_TIME=$(curl -w "%{time_total}" -s -o /dev/null https://api.askproai.de/health)
    if [ "$(echo "$RESPONSE_TIME > 2.0" | bc)" -eq 1 ]; then
        echo "⚠️ Slow response time: ${RESPONSE_TIME}s"
    fi
    
    sleep 60  # Check every minute
done

echo "✅ Deployment monitoring complete - switching to normal monitoring"
```

### Performance Baseline Validation
```bash
# performance-validation.sh
#!/bin/bash
echo "📊 Validating post-deployment performance..."

# Load previous baseline
BASELINE_FILE="storage/performance/baseline-pre-deployment.json"
if [ -f "$BASELINE_FILE" ]; then
    # Run current performance test
    php artisan performance:test --output=json > storage/performance/current-performance.json
    
    # Compare with baseline
    php artisan performance:compare $BASELINE_FILE storage/performance/current-performance.json
else
    echo "⚠️ No baseline found - creating new baseline"
    php artisan performance:test --output=json > $BASELINE_FILE
fi
```

---

## 📢 Stakeholder Communication Plan

### Communication Timeline

#### T-48 Hours: Pre-Deployment Notice
**Audience**: All stakeholders, customers
**Channel**: Email, In-app notification, Slack
**Template**:
```
Subject: Scheduled Business Portal Improvements - [Date]

Dear [Stakeholder],

We're excited to announce significant improvements to the Business Portal scheduled for deployment on [Date] at 02:00 CET.

🚀 What's New:
- Enhanced mobile navigation and touch responsiveness
- Improved table interfaces with better performance
- Expanded test coverage ensuring reliability
- Real-time performance monitoring dashboard
- Updated documentation and user guides

📅 Timeline:
- Deployment Window: 02:00-04:00 CET
- Expected Downtime: <30 seconds
- New Features: Available immediately after deployment

🔄 Rollback Plan:
We have comprehensive rollback procedures to ensure minimal impact if any issues arise.

Questions? Contact our support team at support@askproai.de

Best regards,
AskProAI Deployment Team
```

#### T-24 Hours: Final Deployment Confirmation
**Audience**: Technical stakeholders, support team
**Channel**: Slack, Email
**Content**:
- Final deployment checklist status
- Go/No-go decision confirmation
- Emergency contact information
- Support team briefing

#### T-0 Hours: Deployment Started
**Audience**: Technical team, key stakeholders
**Channel**: Slack deployment channel
**Content**:
```
🚀 DEPLOYMENT STARTED
Time: 02:00 CET
Expected Duration: <2 hours
Status: Phase 1 (Infrastructure) in progress
Monitor: https://status.askproai.de
```

#### T+15 Minutes: Deployment Status Updates
**Audience**: Technical team
**Channel**: Slack (automated via deployment script)
**Content**:
```bash
# Automated status updates
echo "📊 Deployment Phase 2 Complete" | slack-notify
echo "⏱️ Current Phase: Frontend Asset Deployment" | slack-notify  
echo "📈 System Health: $(php artisan health:check --format=emoji)" | slack-notify
```

#### T+2 Hours: Deployment Complete
**Audience**: All stakeholders
**Channel**: Email, Slack, Status page
**Template**:
```
Subject: ✅ Business Portal Improvements Successfully Deployed

The Business Portal improvements have been successfully deployed!

🎉 New Features Now Live:
- Enhanced mobile navigation
- Improved responsive design
- Performance monitoring dashboard
- Expanded test coverage (60%)

📊 Deployment Statistics:
- Total Downtime: [X] seconds
- Performance Impact: [X]% improvement
- Test Coverage: 40% → 60%
- Error Rate: <0.1%

🔍 Monitoring:
We're actively monitoring system performance for the next 24 hours.

Any issues? Report to support@askproai.de or Slack #support

Next Sprint Focus: [Brief preview of next sprint goals]
```

### Communication Channels

#### Primary Channels
1. **Slack**: `#deployments` - Real-time technical updates
2. **Email**: Stakeholder announcements and status updates
3. **Status Page**: Public status updates at `status.askproai.de`
4. **In-App**: Notifications for logged-in users

#### Emergency Communications
1. **SMS**: Critical issues requiring immediate attention
2. **Phone**: Escalation for major incidents
3. **Slack**: `#incidents` - Incident response coordination

### Stakeholder Groups

#### Group 1: Executive Team
- **When**: Major milestones, issues, completion
- **What**: High-level status, business impact
- **How**: Email summary, Slack notification

#### Group 2: Development Team
- **When**: All phases, real-time updates
- **What**: Technical details, metrics, issues
- **How**: Slack, automated notifications

#### Group 3: Support Team
- **When**: Pre-deployment, completion, issues
- **What**: Feature changes, known issues, escalation procedures
- **How**: Email briefing, Slack updates

#### Group 4: End Users
- **When**: New features available, service interruptions
- **What**: Feature announcements, minimal technical details
- **How**: In-app notifications, email announcements

---

## ✅ Pre-Deployment Checklist

### Code Quality & Testing (Dev Team)
- [ ] All tests pass (Unit, Integration, E2E)
- [ ] Test coverage ≥60% achieved
- [ ] Code review completed and approved
- [ ] Security scan passed (0 critical vulnerabilities)
- [ ] Performance benchmarks meet targets
- [ ] Static analysis passed (PHPStan level 8)
- [ ] Frontend build completed successfully
- [ ] Browser compatibility testing completed

### Infrastructure & Environment (DevOps)
- [ ] Production environment health verified
- [ ] Database backup completed and verified
- [ ] Configuration files updated and validated
- [ ] SSL certificates valid (>30 days remaining)
- [ ] CDN configuration updated
- [ ] Load balancer configuration reviewed
- [ ] Monitoring systems operational
- [ ] Log rotation and storage verified

### Dependencies & External Services
- [ ] All external API integrations tested
- [ ] Cal.com integration verified
- [ ] Retell.ai webhook endpoints tested
- [ ] Stripe payment processing verified
- [ ] Email service (SMTP) operational
- [ ] Redis cache cluster healthy
- [ ] Queue workers operational

### Documentation & Communication
- [ ] Deployment runbook reviewed
- [ ] Rollback procedures tested
- [ ] Stakeholder notifications sent
- [ ] Support team briefed on changes
- [ ] Feature flag configuration documented
- [ ] Post-deployment monitoring plan ready

### Business Continuity
- [ ] Feature flags configured and tested
- [ ] Rollback triggers defined and tested
- [ ] Emergency contacts list updated
- [ ] Incident response procedures reviewed
- [ ] Customer support escalation paths verified

### Final Validation Scripts
```bash
#!/bin/bash
# pre-deployment-validation.sh

echo "🔍 Running comprehensive pre-deployment validation..."

# Test Suite
echo "1. Running full test suite..."
php artisan test --coverage --min=60 || exit 1

# Security Scan
echo "2. Security vulnerability scan..."
composer audit || exit 1

# Performance Baseline
echo "3. Establishing performance baseline..."
php artisan performance:baseline --save

# Database Migration Dry Run
echo "4. Testing database migrations..."
php artisan migrate --pretend

# Health Check
echo "5. Comprehensive health check..."
php artisan health:check --detailed || exit 1

# Asset Build Test
echo "6. Frontend asset build test..."
npm run build || exit 1

# Configuration Validation
echo "7. Configuration validation..."
php artisan config:check || exit 1

echo "✅ All pre-deployment validations passed!"
```

---

## 🎯 Go/No-Go Decision Criteria

### GO Criteria (All must be TRUE)
- [ ] **Test Coverage**: ≥60% achieved
- [ ] **Test Results**: 100% pass rate for critical tests
- [ ] **Performance**: Response times <200ms baseline
- [ ] **Security**: Zero critical vulnerabilities
- [ ] **Health Checks**: All systems operational
- [ ] **Dependencies**: All external services responding
- [ ] **Team Readiness**: Deployment team available
- [ ] **Rollback Tested**: Rollback procedures verified
- [ ] **Stakeholder Approval**: Business stakeholders approve
- [ ] **Timing**: Within planned deployment window

### NO-GO Criteria (Any single TRUE triggers delay)
- [ ] **Critical Test Failures**: Any critical functionality broken
- [ ] **Security Issues**: Unresolved critical vulnerabilities
- [ ] **Performance Degradation**: >10% performance regression
- [ ] **External Service Issues**: Critical integrations down
- [ ] **Team Unavailability**: Key personnel not available
- [ ] **Business Impact**: High-impact business events scheduled
- [ ] **Infrastructure Issues**: Production environment problems
- [ ] **Incomplete Features**: Core features not ready

### Decision Making Process
```bash
#!/bin/bash
# go-no-go-decision.sh

echo "📋 Executing Go/No-Go Decision Process..."

SCORE=0
TOTAL_CRITERIA=10

# Test Coverage Check
if php artisan test:coverage --min=60 --quiet; then
    echo "✅ Test coverage ≥60%"
    SCORE=$((SCORE + 1))
else
    echo "❌ Test coverage <60%"
fi

# Performance Check
RESPONSE_TIME=$(curl -w "%{time_total}" -s -o /dev/null https://api.askproai.de/health)
if [ "$(echo "$RESPONSE_TIME < 0.2" | bc)" -eq 1 ]; then
    echo "✅ Performance baseline met"
    SCORE=$((SCORE + 1))
else
    echo "❌ Performance baseline failed: ${RESPONSE_TIME}s"
fi

# Health Check
if php artisan health:check --quiet; then
    echo "✅ Health checks passed"
    SCORE=$((SCORE + 1))
else
    echo "❌ Health checks failed"
fi

# Calculate percentage
PERCENTAGE=$((SCORE * 100 / TOTAL_CRITERIA))

echo "📊 Go/No-Go Score: $SCORE/$TOTAL_CRITERIA ($PERCENTAGE%)"

if [ $PERCENTAGE -ge 90 ]; then
    echo "🟢 GO: Deployment approved"
    exit 0
elif [ $PERCENTAGE -ge 70 ]; then
    echo "🟡 CONDITIONAL GO: Review required"
    exit 2
else
    echo "🔴 NO-GO: Deployment blocked"
    exit 1
fi
```

---

## 📈 Success Metrics & KPIs

### Primary Success Metrics

#### Performance Metrics
- **Page Load Time**: <1 second (Target), <2 seconds (Acceptable)
- **API Response Time**: <200ms (Target), <500ms (Acceptable)  
- **Mobile Navigation Response**: <100ms (Target), <300ms (Acceptable)
- **Database Query Time**: <50ms average (Target), <100ms (Acceptable)

#### Reliability Metrics
- **Uptime**: 99.9% (Target), 99.5% (Minimum)
- **Error Rate**: <0.1% (Target), <1% (Acceptable)
- **Failed Deployments**: 0% (Target), <5% (Acceptable)
- **Rollback Rate**: <5% (Target), <10% (Acceptable)

#### User Experience Metrics
- **Mobile Navigation Success Rate**: >95%
- **Dropdown Interaction Success**: >98%
- **Table Loading Performance**: <2 seconds
- **Form Submission Success**: >99%

#### Business Metrics
- **Customer Complaints**: <5 per day (Target), <10 (Acceptable)
- **Support Ticket Volume**: No increase >20%
- **Feature Adoption Rate**: >70% within 48 hours
- **User Session Duration**: No decrease >15%

### Monitoring Dashboard Metrics
```json
{
  "deployment_health": {
    "status": "healthy",
    "last_updated": "2025-08-01T10:00:00Z",
    "metrics": {
      "response_time_p95": 180,
      "error_rate_5min": 0.02,
      "throughput_rpm": 450,
      "active_users": 234
    }
  },
  "feature_adoption": {
    "mobile_navigation": {
      "enabled_users": 1250,
      "success_rate": 97.3,
      "avg_interaction_time": 0.8
    },
    "responsive_tables": {
      "page_views": 3400,
      "scroll_interactions": 2890,
      "performance_improvement": 34
    }
  },
  "test_coverage": {
    "current": 61.2,
    "target": 60.0,
    "improvement": 21.2
  }
}
```

### Automated Metric Collection
```bash
#!/bin/bash
# collect-deployment-metrics.sh

echo "📊 Collecting deployment success metrics..."

# Performance metrics
RESPONSE_TIME=$(curl -w "%{time_total}" -s -o /dev/null https://api.askproai.de/health)
echo "Response time: ${RESPONSE_TIME}s"

# Error rate
ERROR_COUNT=$(tail -1000 storage/logs/laravel.log | grep -c "ERROR" || echo "0")
TOTAL_REQUESTS=$(tail -1000 storage/logs/laravel.log | grep -c "INFO" || echo "1000")
ERROR_RATE=$(echo "scale=2; $ERROR_COUNT * 100 / $TOTAL_REQUESTS" | bc)
echo "Error rate: ${ERROR_RATE}%"

# Test coverage
COVERAGE=$(php artisan test:coverage --format=json | jq -r '.summary.coverage')
echo "Test coverage: ${COVERAGE}%"

# Feature usage (from analytics)
MOBILE_NAV_USAGE=$(php artisan analytics:feature-usage mobile-navigation --json | jq -r '.success_rate')
echo "Mobile navigation success rate: ${MOBILE_NAV_USAGE}%"

# Generate metrics report
cat > storage/deployment/metrics-$(date +%Y%m%d-%H%M%S).json << EOF
{
  "timestamp": "$(date -Iseconds)",
  "deployment_id": "$DEPLOYMENT_ID",
  "metrics": {
    "performance": {
      "response_time": $RESPONSE_TIME,
      "error_rate": $ERROR_RATE
    },
    "quality": {
      "test_coverage": $COVERAGE
    },
    "adoption": {
      "mobile_navigation_success": $MOBILE_NAV_USAGE
    }
  }
}
EOF

echo "✅ Metrics collected and saved"
```

---

## 🔧 Automation Scripts & Runbooks

### Master Deployment Script
```bash
#!/bin/bash
# deploy-business-portal.sh
set -e

DEPLOYMENT_ID="bp-$(date +%Y%m%d-%H%M%S)"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="storage/deployment/deploy-${DEPLOYMENT_ID}.log"
START_TIME=$(date +%s)

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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
    exit 1
}

# Trap for cleanup on exit
cleanup() {
    local exit_code=$?
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    
    if [ $exit_code -eq 0 ]; then
        success "Deployment completed successfully in ${DURATION} seconds"
        # Send success notification
        ./scripts/notify-deployment-success.sh "$DEPLOYMENT_ID" "$DURATION"
    else
        error "Deployment failed after ${DURATION} seconds"
        # Trigger rollback
        warning "Initiating automatic rollback..."
        ./scripts/emergency-rollback.sh "$DEPLOYMENT_ID"
    fi
}
trap cleanup EXIT

log "🚀 Starting Business Portal Deployment: $DEPLOYMENT_ID"

# Phase 1: Pre-deployment Validation
log "📋 Phase 1: Pre-deployment Validation"
./scripts/pre-deployment-validation.sh || error "Pre-deployment validation failed"
success "Pre-deployment validation passed"

# Phase 2: Backup Current State
log "💾 Phase 2: Creating Backups"
php artisan backup:run --only-db --quiet
git rev-parse HEAD > storage/deployment/previous-commit.txt
cp .env storage/deployment/.env.backup
cp -r public/build storage/deployment/assets-backup-$(date +%Y%m%d-%H%M%S)
success "Backups created successfully"

# Phase 3: Feature Flag Preparation
log "🚩 Phase 3: Feature Flag Setup"
php artisan feature:disable ui-improvements --quiet
php artisan feature:disable performance-monitoring --quiet
success "Feature flags prepared"

# Phase 4: Code Deployment
log "📦 Phase 4: Code Deployment"
git pull origin main
composer install --no-dev --optimize-autoloader --quiet
npm ci --silent
npm run build --silent
success "Code deployment completed"

# Phase 5: Database Migration (Maintenance Mode)
log "🗄️ Phase 5: Database Updates (Maintenance Mode)"
MAINTENANCE_SECRET="deploy-$(openssl rand -hex 8)"
php artisan down --secret="$MAINTENANCE_SECRET" --render="errors::maintenance"

# Run migrations
php artisan migrate --force --quiet

# Clear and rebuild caches
php artisan optimize:clear --quiet
php artisan config:cache --quiet
php artisan route:cache --quiet
php artisan view:cache --quiet

# Exit maintenance mode
php artisan up --quiet
success "Database updates completed"

# Phase 6: Service Restart
log "🔄 Phase 6: Service Restart"
sudo systemctl reload php8.3-fpm
php artisan horizon:terminate --quiet
success "Services restarted"

# Phase 7: Gradual Feature Rollout
log "🚩 Phase 7: Feature Rollout"
sleep 30  # Allow services to stabilize

# Enable features gradually
php artisan feature:enable ui-improvements --percentage=10 --quiet
sleep 60
php artisan feature:enable ui-improvements --percentage=50 --quiet
sleep 60
php artisan feature:enable performance-monitoring --percentage=25 --quiet
success "Features enabled at 10-50% rollout"

# Phase 8: Post-deployment Validation
log "🔍 Phase 8: Post-deployment Validation"
./scripts/post-deployment-validation.sh || error "Post-deployment validation failed"
success "Post-deployment validation passed"

# Phase 9: Full Feature Activation
log "🚀 Phase 9: Full Feature Activation"
php artisan feature:enable ui-improvements --percentage=100 --quiet
php artisan feature:enable performance-monitoring --percentage=100 --quiet
success "All features fully activated"

# Phase 10: Monitoring Setup
log "📊 Phase 10: Monitoring Activation"
nohup ./scripts/monitor-deployment.sh "$DEPLOYMENT_ID" > storage/deployment/monitoring-${DEPLOYMENT_ID}.log 2>&1 &
success "Deployment monitoring started"

success "🎉 Business Portal Deployment $DEPLOYMENT_ID completed successfully!"
```

### Post-deployment Validation Script
```bash
#!/bin/bash
# post-deployment-validation.sh

echo "🔍 Running post-deployment validation..."

VALIDATION_FAILED=0

# Health Check
echo "1. System health check..."
if php artisan health:check --quiet; then
    echo "✅ System health: OK"
else
    echo "❌ System health: FAILED"
    VALIDATION_FAILED=1
fi

# Critical endpoints
echo "2. Testing critical endpoints..."
ENDPOINTS=(
    "https://api.askproai.de/health"
    "https://api.askproai.de/admin"
    "https://api.askproai.de/api/v1/status"
)

for endpoint in "${ENDPOINTS[@]}"; do
    if curl -f -s "$endpoint" > /dev/null; then
        echo "✅ $endpoint: OK"
    else
        echo "❌ $endpoint: FAILED"
        VALIDATION_FAILED=1
    fi
done

# Database connectivity
echo "3. Database connectivity..."
if php artisan tinker --execute="DB::select('SELECT 1')" > /dev/null 2>&1; then
    echo "✅ Database: OK"
else
    echo "❌ Database: FAILED"
    VALIDATION_FAILED=1
fi

# Queue processing
echo "4. Queue processing..."
if php artisan horizon:status | grep -q "active"; then
    echo "✅ Queues: OK"
else
    echo "❌ Queues: FAILED"
    VALIDATION_FAILED=1
fi

# Performance test
echo "5. Performance validation..."
RESPONSE_TIME=$(curl -w "%{time_total}" -s -o /dev/null https://api.askproai.de/health)
if [ "$(echo "$RESPONSE_TIME < 2.0" | bc)" -eq 1 ]; then
    echo "✅ Performance: OK (${RESPONSE_TIME}s)"
else
    echo "❌ Performance: SLOW (${RESPONSE_TIME}s)"
    VALIDATION_FAILED=1
fi

# Feature functionality
echo "6. Feature functionality tests..."
# Test mobile navigation
MOBILE_NAV_TEST=$(curl -s -H "User-Agent: Mobile" https://api.askproai.de/admin | grep -c "mobile-navigation-fix" || echo "0")
if [ "$MOBILE_NAV_TEST" -gt 0 ]; then
    echo "✅ Mobile navigation: OK"
else
    echo "❌ Mobile navigation: FAILED"
    VALIDATION_FAILED=1
fi

# Summary
if [ $VALIDATION_FAILED -eq 0 ]; then
    echo "✅ All post-deployment validations passed!"
    exit 0
else
    echo "❌ Post-deployment validation failed!"
    exit 1
fi
```

### Emergency Rollback Script
```bash
#!/bin/bash
# emergency-rollback.sh

DEPLOYMENT_ID="${1:-unknown}"
ROLLBACK_START=$(date +%s)

echo "🚨 EMERGENCY ROLLBACK INITIATED - Deployment ID: $DEPLOYMENT_ID"

# 1. Immediate feature flag disable
echo "🚩 Disabling all feature flags..."
php artisan feature:disable ui-improvements --quiet
php artisan feature:disable performance-monitoring --quiet
php artisan feature:disable enhanced-mobile-nav --quiet

# 2. Enable maintenance mode
echo "🔒 Enabling maintenance mode..."
php artisan down --secret="rollback-$(date +%s)" --quiet

# 3. Restore previous code version
echo "⏪ Restoring previous code version..."
if [ -f "storage/deployment/previous-commit.txt" ]; then
    PREVIOUS_COMMIT=$(cat storage/deployment/previous-commit.txt)
    git reset --hard "$PREVIOUS_COMMIT"
    echo "✅ Code restored to commit: $PREVIOUS_COMMIT"
else
    echo "⚠️ No previous commit found, using HEAD~1"
    git reset --hard HEAD~1
fi

# 4. Restore configuration
echo "⚙️ Restoring configuration..."
if [ -f "storage/deployment/.env.backup" ]; then
    cp storage/deployment/.env.backup .env
    echo "✅ Configuration restored"
fi

# 5. Database rollback (if needed)
echo "🗄️ Checking database rollback requirement..."
# Only rollback database if we have a recent backup and migrations were run
if [ -f "storage/deployment/migration-rollback-required.flag" ]; then
    echo "⚠️ Database rollback required - restoring backup..."
    # Find most recent backup
    LATEST_BACKUP=$(ls -t storage/app/backups/*.sql 2>/dev/null | head -1)
    if [ -n "$LATEST_BACKUP" ]; then
        mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db < "$LATEST_BACKUP"
        echo "✅ Database restored from backup"
    else
        echo "❌ No database backup found - manual intervention required"
    fi
fi

# 6. Restore previous assets
echo "🎨 Restoring previous assets..."
LATEST_ASSET_BACKUP=$(ls -td storage/deployment/assets-backup-* 2>/dev/null | head -1)
if [ -n "$LATEST_ASSET_BACKUP" ]; then
    rm -rf public/build
    cp -r "$LATEST_ASSET_BACKUP" public/build
    echo "✅ Assets restored"
fi

# 7. Clear all caches
echo "🧹 Clearing caches..."
php artisan optimize:clear --quiet
composer install --no-dev --optimize-autoloader --quiet

# 8. Restart services
echo "🔄 Restarting services..."
sudo systemctl restart php8.3-fpm
php artisan horizon:terminate --quiet

# 9. Disable maintenance mode
echo "🔓 Disabling maintenance mode..."
php artisan up --quiet

# 10. Validate rollback
echo "✅ Validating rollback..."
sleep 10  # Allow services to start

if curl -f -s https://api.askproai.de/health > /dev/null; then
    ROLLBACK_END=$(date +%s)
    ROLLBACK_DURATION=$((ROLLBACK_END - ROLLBACK_START))
    echo "✅ ROLLBACK SUCCESSFUL - Duration: ${ROLLBACK_DURATION} seconds"
    
    # Notify team
    ./scripts/notify-rollback-success.sh "$DEPLOYMENT_ID" "$ROLLBACK_DURATION"
    exit 0
else
    echo "❌ ROLLBACK VALIDATION FAILED - Manual intervention required"
    ./scripts/notify-critical-failure.sh "$DEPLOYMENT_ID"
    exit 1
fi
```

---

## 🎯 Risk Assessment & Mitigation

### Risk Matrix

| Risk Category | Probability | Impact | Risk Level | Mitigation Strategy |
|--------------|-------------|--------|------------|-------------------|
| **Database Migration Failure** | Low (5%) | Critical | High | Automated rollback, Pre-migration validation |
| **UI Breaking Changes** | Medium (15%) | High | Medium | Feature flags, Gradual rollout |
| **Performance Regression** | Low (10%) | High | Medium | Performance benchmarking, Automated rollback |
| **External API Integration Failure** | Medium (20%) | Medium | Medium | Circuit breakers, Health checks |
| **Mobile Browser Compatibility** | High (30%) | Medium | Medium | Comprehensive browser testing |
| **Cache Issues** | Medium (15%) | Low | Low | Automated cache clearing, Monitoring |

### Detailed Risk Analysis

#### Critical Risks (Immediate Response Required)

**1. Database Migration Failure**
- **Scenario**: Migration fails halfway, leaving database in inconsistent state
- **Impact**: Complete application failure, data corruption risk
- **Probability**: 5% (Low due to migration testing)
- **Mitigation**:
  ```bash
  # Pre-migration validation
  php artisan migrate --pretend
  # Atomic migrations with transactions
  php artisan migrate --force
  # Immediate rollback capability
  mysql askproai_db < storage/deployment/pre-migration-backup.sql
  ```

**2. Authentication System Failure** 
- **Scenario**: Auth improvements break login functionality
- **Impact**: Users cannot access system, business disruption
- **Probability**: 10% (Medium due to auth complexity)
- **Mitigation**:
  ```php
  // Feature flag for auth improvements
  if (feature('auth-improvements')) {
      // New auth logic
  } else {
      // Fallback to old auth logic
  }
  ```

#### High Risks (Close Monitoring Required)

**3. Mobile UI Complete Failure**
- **Scenario**: Mobile navigation breaks completely on deployment
- **Impact**: Mobile users cannot use application
- **Probability**: 15% (Medium due to JS/CSS complexity)
- **Mitigation**:
  - Progressive enhancement approach
  - Fallback CSS without JavaScript
  - Feature flag for mobile improvements
  - Real user monitoring

**4. Performance Degradation**
- **Scenario**: New features cause significant slowdown
- **Impact**: Poor user experience, potential user churn
- **Probability**: 20% (Medium due to new monitoring overhead)
- **Mitigation**:
  ```bash
  # Automated performance monitoring
  ./scripts/performance-monitor.sh &
  # Automatic rollback if response time > 2s
  if [ "$(curl -w '%{time_total}' -s -o /dev/null $URL)" -gt 2 ]; then
      ./scripts/emergency-rollback.sh
  fi
  ```

#### Medium Risks (Standard Monitoring)

**5. Browser Compatibility Issues**
- **Scenario**: New UI features don't work in older browsers
- **Impact**: Subset of users experience broken functionality  
- **Probability**: 30% (High due to diverse browser landscape)
- **Mitigation**:
  - Progressive enhancement
  - Polyfills for older browsers
  - Feature detection before activation
  - Browser-specific fallbacks

**6. External Service Dependencies**
- **Scenario**: Cal.com or Retell.ai integration breaks during deployment
- **Impact**: Core business functions disrupted
- **Probability**: 15% (Medium - external dependencies)
- **Mitigation**:
  - Circuit breaker pattern
  - Graceful degradation
  - Health check integration
  - Service-specific rollback procedures

### Risk Mitigation Strategies

#### Proactive Mitigation

**1. Comprehensive Testing Strategy**
```bash
# Multi-level testing approach
php artisan test --testsuite=Unit     # Fast feedback
php artisan test --testsuite=Feature  # Integration testing
php artisan test --testsuite=E2E      # End-to-end validation

# Browser compatibility testing
npm run test:browsers -- --browsers=chrome,firefox,safari,edge

# Performance testing
php artisan performance:test --baseline
```

**2. Staged Deployment Approach**
```bash
# Phase 1: Infrastructure (0% user impact)
./deploy-infrastructure.sh

# Phase 2: Backend (minimal downtime)
./deploy-backend.sh

# Phase 3: Feature flags (gradual rollout)
php artisan feature:enable ui-improvements --percentage=10
# Monitor for 30 minutes
php artisan feature:enable ui-improvements --percentage=50
# Monitor for 30 minutes  
php artisan feature:enable ui-improvements --percentage=100
```

**3. Monitoring & Alerting**
```bash
# Real-time monitoring during deployment
./scripts/deployment-monitor.sh &

# Automated alerting thresholds
RESPONSE_TIME_THRESHOLD=2.0    # seconds
ERROR_RATE_THRESHOLD=5.0       # percentage
THROUGHPUT_THRESHOLD=50        # requests/minute
```

#### Reactive Mitigation

**1. Automated Rollback Triggers**
```bash
# Response time trigger
if [ "$(curl -w '%{time_total}' -s -o /dev/null $URL)" -gt "$RESPONSE_TIME_THRESHOLD" ]; then
    ./scripts/automated-rollback.sh "performance"
fi

# Error rate trigger
ERROR_RATE=$(tail -100 logs/laravel.log | grep -c "ERROR")
if [ $ERROR_RATE -gt 10 ]; then
    ./scripts/automated-rollback.sh "errors"
fi
```

**2. Manual Intervention Procedures**
```bash
# Emergency contact list
ONCALL_ENGINEER="DevOps Lead"
ESCALATION_PATH="CTO → CEO"
RESPONSE_TIME_SLA="15 minutes"

# Emergency access
EMERGENCY_ACCESS_KEY="stored in 1Password"
DIRECT_SERVER_ACCESS="root@production-server"
```

**3. Communication Protocols**
```bash
# Incident classification
SEVERITY_1="Complete system failure"
SEVERITY_2="Major feature broken"  
SEVERITY_3="Minor issues or degradation"

# Communication channels per severity
S1_NOTIFICATION="SMS + Phone + Slack + Email"
S2_NOTIFICATION="Slack + Email"
S3_NOTIFICATION="Slack"
```

### Risk Monitoring Dashboard

Real-time risk monitoring available at `/admin/deployment-risks`

```json
{
  "deployment_risks": {
    "timestamp": "2025-08-01T10:00:00Z",
    "overall_risk_level": "low",
    "active_mitigations": 7,
    "risks": [
      {
        "category": "performance",
        "level": "low",
        "metric": "response_time",
        "current": 0.18,
        "threshold": 2.0,
        "status": "healthy"
      },
      {
        "category": "errors",
        "level": "low", 
        "metric": "error_rate",
        "current": 0.1,
        "threshold": 5.0,
        "status": "healthy"
      },
      {
        "category": "features",
        "level": "medium",
        "metric": "mobile_nav_success",
        "current": 94.2,
        "threshold": 90.0,
        "status": "warning"
      }
    ]
  }
}
```

---

## 📋 Final Summary

This comprehensive Business Portal Deployment Strategy provides:

### ✅ **Zero-Downtime Deployment Capability**
- **Maximum Downtime**: <30 seconds (database migrations only)
- **Feature Flag Rollout**: Instant activation/deactivation
- **Gradual User Exposure**: 10% → 50% → 100% rollout
- **Automated Rollback**: <2 minutes full system recovery

### 🎯 **Comprehensive Risk Management**
- **Risk Assessment**: All identified risks have mitigation strategies
- **Automated Monitoring**: Real-time system health and performance tracking
- **Multi-level Rollback**: Feature flags, assets, and full system rollback
- **Communication Protocols**: Clear stakeholder communication throughout

### 📊 **Quality Assurance**
- **60% Test Coverage**: Significant improvement from 40%
- **Multi-browser Testing**: Chrome, Firefox, Safari, Edge compatibility
- **Performance Benchmarking**: <200ms response time targets
- **Security Validation**: Zero critical vulnerabilities policy

### 🚀 **Sprint Integration**
- **6-Day Timeline**: Fits perfectly within sprint methodology
- **Daily Milestones**: Clear progress tracking and stakeholder updates
- **Continuous Monitoring**: 24/7 oversight during critical periods
- **Documentation**: Automated health checks and updated guides

### 🔧 **Operational Excellence**
- **Automation Scripts**: Comprehensive runbooks and automated procedures
- **Monitoring Dashboard**: Real-time visibility into deployment health
- **Feature Flags**: Enterprise-grade feature management
- **Emergency Procedures**: Tested and documented escalation paths

The strategy balances aggressive improvement goals with operational stability, ensuring the Business Portal enhancements deliver maximum value while maintaining the high availability standards required for production systems.

---

**Next Steps**: 
1. Review and approve this deployment strategy
2. Execute pre-deployment validation checklist  
3. Schedule deployment window with stakeholders
4. Implement monitoring dashboard and alerting
5. Conduct final go/no-go decision meeting

**Contact**: For questions about this deployment strategy, contact the Launch Orchestrator team at deployment@askproai.de