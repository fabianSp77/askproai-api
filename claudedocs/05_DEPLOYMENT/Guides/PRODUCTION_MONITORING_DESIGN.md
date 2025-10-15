# PRODUCTION MONITORING & ALERTING INFRASTRUCTURE DESIGN

**Date**: 2025-10-02
**Status**: DESIGN PHASE
**Application**: Multi-Tenant Laravel API Gateway
**Context**: Post-Security Fix Deployment (5 Critical Vulnerabilities Resolved)

---

## Executive Summary

### Monitoring Objectives
- **Security Posture Tracking**: Real-time detection of attacks, breaches, and anomalies
- **Performance Monitoring**: CompanyScope query performance, database health, API latency
- **Business Intelligence**: Multi-tenant usage patterns, webhook delivery rates, tenant health
- **Incident Response**: Automated detection, intelligent alerting, rapid recovery

### Key Metrics
- **Security**: 18 critical metrics tracking auth failures, cross-tenant attempts, webhook forgery
- **Performance**: 12 metrics for query performance, database connections, API response times
- **Business**: 8 metrics for tenant activity, webhook success, API health
- **Alerting**: 4-tier severity system (CRITICAL/HIGH/MEDIUM/LOW) with escalation rules

---

## 1. SECURITY MONITORING

### 1.1 Critical Security Metrics

#### Authentication & Authorization Failures
| Metric | Description | Threshold | Alert Level |
|--------|-------------|-----------|-------------|
| `auth.webhook.signature_failures` | Failed webhook signature validations | >5/min | CRITICAL |
| `auth.api.unauthorized_rate` | HTTP 401 responses per endpoint | >10/min | HIGH |
| `auth.api.forbidden_rate` | HTTP 403 policy denials | >15/min | MEDIUM |
| `auth.session.failed_logins` | Failed Sanctum authentication | >20/5min | HIGH |
| `auth.webhook.missing_signatures` | Unsigned webhook requests | >3/min | CRITICAL |

#### Multi-Tenant Isolation Violations
| Metric | Description | Threshold | Alert Level |
|--------|-------------|-----------|-------------|
| `tenant.cross_access_attempts` | Cross-company query attempts | >0 | CRITICAL |
| `tenant.scope_bypass_attempts` | CompanyScope bypass attempts | >0 | CRITICAL |
| `tenant.policy_violations` | Policy authorization failures | >5/min | HIGH |
| `tenant.admin_scope_bypass` | Admin attempting super_admin actions | >0 | CRITICAL |
| `tenant.service_discovery_violations` | Cross-company service access | >0 | CRITICAL |

#### Webhook Security
| Metric | Description | Threshold | Alert Level |
|--------|-------------|-----------|-------------|
| `webhook.retell.signature_invalid` | Invalid Retell signatures | >2/min | CRITICAL |
| `webhook.calcom.signature_invalid` | Invalid Cal.com signatures | >2/min | CRITICAL |
| `webhook.stripe.signature_invalid` | Invalid Stripe signatures | >1/min | CRITICAL |
| `webhook.rate_limit_exceeded` | Webhook rate limit violations | >10/5min | HIGH |
| `webhook.unconfigured_secret` | Missing webhook secret configs | >0 | CRITICAL |

#### Attack Pattern Detection
| Metric | Description | Threshold | Alert Level |
|--------|-------------|-----------|-------------|
| `security.sql_injection_attempts` | Suspicious SQL patterns in logs | >0 | CRITICAL |
| `security.xss_attempts` | XSS patterns blocked by observer | >5/min | HIGH |
| `security.timing_attack_pattern` | User enumeration timing patterns | >10/5min | MEDIUM |
| `security.brute_force_pattern` | Rapid repeated auth failures | >50/min | CRITICAL |

### 1.2 Log Aggregation Patterns

#### Security Event Log Structure
```json
{
  "timestamp": "2025-10-02T15:30:45.123Z",
  "level": "warning",
  "category": "security",
  "event_type": "auth_failure",
  "subcategory": "webhook_signature",
  "severity": "high",
  "context": {
    "middleware": "VerifyRetellWebhookSignature",
    "endpoint": "/webhook",
    "ip_address": "203.0.113.42",
    "user_agent": "curl/7.68.0",
    "company_id": null,
    "user_id": null,
    "signature_provided": false,
    "error_code": "SIGNATURE_MISSING"
  },
  "trace_id": "req_abc123xyz",
  "span_id": "span_456def"
}
```

#### Multi-Tenant Isolation Event
```json
{
  "timestamp": "2025-10-02T15:31:12.456Z",
  "level": "critical",
  "category": "security",
  "event_type": "tenant_violation",
  "subcategory": "cross_company_access",
  "severity": "critical",
  "context": {
    "user_id": 42,
    "user_company_id": 1,
    "requested_company_id": 2,
    "model": "Service",
    "operation": "view",
    "policy": "ServicePolicy",
    "blocked": true,
    "endpoint": "/api/v2/bookings"
  },
  "trace_id": "req_def789ghi",
  "span_id": "span_123abc"
}
```

#### Performance Degradation Event
```json
{
  "timestamp": "2025-10-02T15:32:05.789Z",
  "level": "warning",
  "category": "performance",
  "event_type": "slow_query",
  "subcategory": "company_scope",
  "severity": "medium",
  "context": {
    "query_time_ms": 1250,
    "threshold_ms": 1000,
    "model": "Appointment",
    "scopes": ["CompanyScope"],
    "company_id": 5,
    "query_type": "select",
    "affected_rows": 1500,
    "sql_fingerprint": "SELECT * FROM appointments WHERE company_id = ? ORDER BY created_at DESC"
  },
  "trace_id": "req_ghi456jkl"
}
```

### 1.3 Security Dashboard Requirements

#### Real-Time Security Overview
**Primary Metrics (30-second refresh)**:
- Authentication failure rate (last 5 minutes)
- Cross-tenant violation attempts (realtime counter)
- Webhook signature failures (by provider)
- Active security incidents (open alerts)
- Top attacking IP addresses

#### Security Heatmap
**Visualization**: Geographic heatmap of failed auth attempts
- Color-coded by severity (green → yellow → red)
- Filterable by time range (1h, 6h, 24h, 7d)
- Drill-down to specific IP/endpoint combinations

#### Webhook Security Panel
**Metrics**:
- Signature validation success rate (%)
- Failed validations by provider (Retell, Cal.com, Stripe)
- Average signature verification time (ms)
- Unconfigured webhook endpoints (alert if >0)

#### Multi-Tenant Isolation Health
**Metrics**:
- CompanyScope query performance (p50, p95, p99)
- Cross-company access attempts (should be 0)
- Policy authorization denial rate by model
- Super admin scope bypass usage (legitimate monitoring)

---

## 2. PERFORMANCE MONITORING

### 2.1 CompanyScope Performance Metrics

| Metric | Description | Threshold | Alert Level |
|--------|-------------|-----------|-------------|
| `scope.company.query_time_p95` | 95th percentile query time | >500ms | MEDIUM |
| `scope.company.query_time_p99` | 99th percentile query time | >1000ms | HIGH |
| `scope.company.queries_per_second` | CompanyScope queries/sec | >1000 | MEDIUM |
| `scope.company.cache_hit_rate` | Company data cache efficiency | <80% | MEDIUM |
| `scope.company.index_usage` | company_id index usage | <95% | HIGH |

### 2.2 Database Performance Metrics

| Metric | Description | Threshold | Alert Level |
|--------|-------------|-----------|-------------|
| `db.connection_pool.active` | Active database connections | >80% | HIGH |
| `db.connection_pool.waiting` | Queued connection requests | >10 | MEDIUM |
| `db.query.slow_log_rate` | Queries exceeding slow log threshold | >5/min | MEDIUM |
| `db.deadlock_rate` | Database deadlocks detected | >1/hour | HIGH |
| `db.replication_lag` | Read replica lag (if applicable) | >5sec | HIGH |

### 2.3 API Performance Metrics

| Metric | Description | Threshold | Alert Level |
|--------|-------------|-----------|-------------|
| `api.response_time_p50` | Median API response time | >200ms | LOW |
| `api.response_time_p95` | 95th percentile response time | >1000ms | MEDIUM |
| `api.response_time_p99` | 99th percentile response time | >2000ms | HIGH |
| `api.throughput` | Requests per second | <10/sec | MEDIUM |
| `api.error_rate_5xx` | Server error rate | >1% | HIGH |

### 2.4 Performance Dashboard Requirements

#### API Performance Overview
**Metrics (1-minute aggregation)**:
- Request throughput (requests/sec) - Line graph
- Response time percentiles (p50, p95, p99) - Multi-line graph
- Error rate by status code (4xx, 5xx) - Stacked bar chart
- Top 10 slowest endpoints - Table with avg response time

#### Database Health Panel
**Metrics**:
- Connection pool utilization (%) - Gauge
- Active/idle connections - Dual gauge
- Slow query log count - Counter with trend
- Query cache hit rate - Percentage with sparkline

#### CompanyScope Performance Panel
**Metrics**:
- Average scope overhead (ms) - Line graph over time
- Queries by company distribution - Bar chart (top 10 tenants)
- Scope cache effectiveness - Hit/miss ratio pie chart
- company_id index usage - Percentage gauge

---

## 3. BUSINESS METRICS

### 3.1 Multi-Tenant Usage Statistics

| Metric | Description | Aggregation | Dashboard |
|--------|-------------|-------------|-----------|
| `tenant.active_companies` | Companies with activity in last 24h | Count | Business Overview |
| `tenant.api_calls_per_company` | API requests by tenant | Count/Company | Tenant Analytics |
| `tenant.storage_per_company` | Data storage by tenant | GB/Company | Resource Usage |
| `tenant.feature_usage` | Feature adoption by tenant | Count/Feature | Product Analytics |
| `tenant.growth_rate` | New tenants per week | Count/Week | Growth Metrics |

### 3.2 API Endpoint Health Checks

| Endpoint | Check Type | Interval | Success Criteria |
|----------|-----------|----------|------------------|
| `GET /health` | Basic health | 30sec | HTTP 200, <100ms |
| `GET /health/detailed` | Detailed health | 2min | HTTP 200, all services UP |
| `GET /health/metrics` | Metrics endpoint | 1min | HTTP 200, valid JSON |
| `GET /health/calcom` | Cal.com integration | 5min | HTTP 200, API reachable |
| `GET /api/zeitinfo` | Time service | 1min | HTTP 200, valid timestamp |

### 3.3 Webhook Delivery Success Rates

| Metric | Description | Threshold | Alert Level |
|--------|-------------|-----------|-------------|
| `webhook.retell.success_rate` | Retell webhook processing success | <95% | MEDIUM |
| `webhook.calcom.success_rate` | Cal.com webhook processing success | <95% | MEDIUM |
| `webhook.stripe.success_rate` | Stripe webhook processing success | <99% | HIGH |
| `webhook.delivery_latency_p95` | 95th percentile processing time | >5sec | MEDIUM |
| `webhook.retry_rate` | Webhook retry attempts | >10% | MEDIUM |

### 3.4 Error Rate Tracking by Tenant

| Metric | Description | Aggregation | Alert Level |
|--------|-------------|-------------|-------------|
| `tenant.error_rate_overall` | Overall error rate per company | %/Company | HIGH if >5% |
| `tenant.api_failures` | API failures by tenant | Count/Company | MEDIUM if >100/h |
| `tenant.webhook_failures` | Webhook failures by tenant | Count/Company | MEDIUM if >10/h |
| `tenant.policy_denials` | Authorization failures by tenant | Count/Company | LOW (monitoring) |

### 3.5 Business Dashboard Requirements

#### Tenant Health Overview
**Metrics**:
- Total active tenants (24h activity) - Counter
- Top 10 tenants by API usage - Horizontal bar chart
- Tenant error rates - Table with color coding
- New tenants this week - Trend line

#### Webhook Analytics Panel
**Metrics**:
- Webhook success rate by provider - Multi-bar chart
- Processing latency distribution - Histogram
- Failed webhooks by reason - Pie chart
- Webhook volume by hour - Line graph

#### Feature Adoption Panel
**Metrics**:
- API v2 adoption rate - Percentage with trend
- Booking endpoints usage - Bar chart by endpoint
- Cal.com sync usage - Count with trend
- Retell AI integration usage - Active tenants counter

---

## 4. ALERTING STRATEGY

### 4.1 Alert Severity Levels

#### CRITICAL (P1) - Immediate Response Required
**Response Time**: <5 minutes
**Escalation**: Immediately to on-call engineer + security team
**Examples**:
- Cross-tenant data access detected
- Webhook signature validation failures >5/min
- Database connection pool exhausted
- API error rate >10%
- Security vulnerability exploit detected

**Notification Channels**:
- PagerDuty (phone call + SMS)
- Slack #alerts-critical (with @channel mention)
- Email to on-call rotation
- SMS to engineering leadership

#### HIGH (P2) - Urgent Attention Needed
**Response Time**: <15 minutes
**Escalation**: To on-call engineer, escalate after 15 min
**Examples**:
- Authentication failure spike (>20/5min)
- Slow query rate increase (>5/min)
- API response time p95 >2000ms
- Database replication lag >5sec
- Webhook success rate <95%

**Notification Channels**:
- PagerDuty (push notification)
- Slack #alerts-high
- Email to on-call engineer

#### MEDIUM (P3) - Investigation Required
**Response Time**: <1 hour
**Escalation**: To engineering team, no immediate escalation
**Examples**:
- API response time p95 >1000ms
- Database connection pool >80%
- XSS attempt rate increase
- Webhook delivery latency >5sec
- Cache hit rate degradation

**Notification Channels**:
- Slack #alerts-medium
- Email digest to engineering team

#### LOW (P4) - Monitoring/Informational
**Response Time**: Next business day
**Escalation**: None, aggregate in daily report
**Examples**:
- API response time p50 >200ms
- Individual policy authorization denials
- Feature usage pattern changes
- Successful super_admin scope bypass (legitimate)

**Notification Channels**:
- Daily email digest
- Weekly dashboard review

### 4.2 Escalation Rules

#### Escalation Paths
```
CRITICAL Alert Fired
    ↓
Initial: On-Call Engineer (Phone + SMS)
    ↓ (5 min no ACK)
Secondary: Backup On-Call Engineer + Engineering Manager
    ↓ (10 min no ACK)
Tertiary: CTO + Security Lead
```

#### Auto-Escalation Triggers
- Alert not acknowledged within 5 minutes (CRITICAL)
- Alert not acknowledged within 15 minutes (HIGH)
- Same alert fired 3 times in 30 minutes
- Multiple CRITICAL alerts fired simultaneously (>2)

#### De-escalation Rules
- CRITICAL → HIGH: Issue acknowledged, mitigation in progress
- HIGH → MEDIUM: Root cause identified, fix deploying
- MEDIUM → LOW: Issue resolved, monitoring for recurrence
- Alert auto-resolves after threshold returns to normal for 10 minutes

### 4.3 Alert Suppression & Grouping

#### Intelligent Grouping
- Group related alerts by trace_id (same request context)
- Suppress duplicate alerts within 5-minute window
- Aggregate multi-tenant alerts (e.g., "5 companies experiencing slow queries")

#### Maintenance Windows
- Scheduled deployment: Suppress non-critical alerts
- Planned database maintenance: Suppress DB performance alerts
- Rate limit testing: Suppress rate limit alerts for test tenants

#### Alert Fatigue Prevention
- Maximum 3 CRITICAL alerts to same person within 1 hour
- Automatic snooze of recurring LOW alerts (>10 occurrences/day)
- Weekly alert quality review: Tune thresholds, retire noisy alerts

### 4.4 On-Call Runbooks

#### Runbook: Cross-Tenant Data Access Detected
**Alert**: `tenant.cross_access_attempts > 0`
**Severity**: CRITICAL

**Immediate Actions**:
1. Acknowledge alert within 2 minutes
2. Check `/var/www/api-gateway/storage/logs/laravel.log` for tenant_violation events
3. Identify affected user_id and company_ids
4. Disable compromised user account: `php artisan user:disable {user_id}`
5. Verify no data exfiltration occurred via audit logs

**Investigation**:
1. Review user authentication history
2. Check for privilege escalation attempts
3. Analyze query patterns for attack signatures
4. Verify CompanyScope is applied to affected models

**Resolution**:
1. Patch any scope bypass vulnerability
2. Notify affected tenants if data access occurred
3. Document incident in security log
4. Schedule security team review within 24 hours

---

#### Runbook: Webhook Signature Validation Failures
**Alert**: `auth.webhook.signature_failures > 5/min`
**Severity**: CRITICAL

**Immediate Actions**:
1. Acknowledge alert within 5 minutes
2. Check webhook signature failure logs: `tail -f storage/logs/laravel.log | grep signature_failures`
3. Identify attacking IP addresses
4. Block IPs via firewall: `sudo ufw deny from {ip_address}`

**Investigation**:
1. Determine if legitimate misconfiguration or attack
2. Verify webhook secrets configured: `php artisan config:show | grep WEBHOOK_SECRET`
3. Check for secret rotation needed (Retell, Cal.com, Stripe dashboards)
4. Review webhook provider status pages

**Resolution**:
1. Update webhook secrets if rotated by provider
2. Contact provider if signatures consistently invalid
3. Monitor for 30 minutes post-resolution
4. Document incident and update runbook

---

#### Runbook: Database Connection Pool Exhausted
**Alert**: `db.connection_pool.active > 80%`
**Severity**: CRITICAL

**Immediate Actions**:
1. Acknowledge alert immediately
2. Check active connections: `mysql -e "SHOW PROCESSLIST;"`
3. Identify long-running queries causing pool starvation
4. Kill non-critical long-running queries: `mysql -e "KILL {process_id};"`

**Investigation**:
1. Check for N+1 query problems in recent deployments
2. Review CompanyScope query performance (may be causing cascading queries)
3. Analyze slow query log: `tail -100 /var/log/mysql/slow.log`
4. Check for connection leak in application code

**Resolution**:
1. Increase connection pool size temporarily (if infrastructure allows)
2. Deploy query optimization fixes
3. Add missing database indexes on company_id columns
4. Schedule capacity planning review

---

#### Runbook: API Error Rate Spike
**Alert**: `api.error_rate_5xx > 1%`
**Severity**: HIGH

**Immediate Actions**:
1. Acknowledge alert within 10 minutes
2. Check error logs: `tail -200 storage/logs/laravel.log | grep ERROR`
3. Identify failing endpoints via metrics dashboard
4. Check application health: `curl https://api.example.com/health/detailed`

**Investigation**:
1. Determine if recent deployment correlation
2. Check external service dependencies (Cal.com, Retell, Stripe)
3. Review error distribution by tenant (single tenant vs global)
4. Analyze stack traces for common error patterns

**Resolution**:
1. Rollback recent deployment if correlation confirmed
2. Implement circuit breaker if external service failure
3. Scale infrastructure if capacity-related
4. Deploy hotfix for application bugs

---

### 4.5 Notification Channel Configuration

#### PagerDuty Integration
**Service**: API Gateway Production
**Escalation Policy**: Engineering → Engineering Manager → CTO
**Routing Keys**:
- `critical-security`: Cross-tenant violations, auth failures
- `critical-availability`: Database failures, API outages
- `high-performance`: Slow queries, connection pool issues

#### Slack Integration
**Channels**:
- `#alerts-critical`: CRITICAL alerts only, @channel enabled
- `#alerts-high`: HIGH alerts, no @channel
- `#alerts-medium`: MEDIUM alerts, digest format
- `#monitoring-daily`: Daily digest + LOW alerts

**Message Format**:
```
🚨 CRITICAL: Cross-Tenant Access Attempt Detected

**Severity**: P1 - Immediate Response Required
**Triggered**: 2025-10-02 15:45:32 UTC
**Alert**: tenant.cross_access_attempts > 0
**Details**:
  - User ID: 42 (Company 1)
  - Attempted Access: Company 2 Service #123
  - Blocked: Yes (ServicePolicy)
  - Endpoint: POST /api/v2/bookings

**Runbook**: https://wiki.example.com/runbooks/cross-tenant-access
**Dashboard**: https://monitoring.example.com/security/tenants
**Logs**: https://logs.example.com/search?trace_id=req_abc123

[Acknowledge] [Escalate] [View Details]
```

#### Email Configuration
**Recipients**:
- CRITICAL: on-call@example.com, security@example.com
- HIGH: on-call@example.com
- MEDIUM: engineering@example.com
- LOW: Daily digest to engineering@example.com

**Format**: HTML emails with alert details, quick actions, runbook links

---

## 5. DASHBOARD DESIGN

### 5.1 Security Dashboard

#### Layout (3-column grid)

**Column 1: Real-Time Security Status**
- Alert counter (CRITICAL/HIGH/MEDIUM/LOW) - Large tiles
- Authentication failure rate - Sparkline (last 1 hour)
- Cross-tenant violation counter - Realtime (should be 0)
- Active incidents - List with status

**Column 2: Threat Detection**
- Failed webhook signatures - Bar chart by provider
- Top attacking IPs - Table with block action
- Policy authorization denials - Line graph by model
- Attack pattern detection - Heatmap

**Column 3: Security Health**
- Webhook authentication success rate - Gauge (target >99%)
- Multi-tenant isolation health - Status indicator
- Security middleware response times - Histogram
- Recent security events - Scrolling log

#### Time Range Controls
- Last 1 hour (default)
- Last 6 hours
- Last 24 hours
- Last 7 days
- Custom range picker

---

### 5.2 Performance Dashboard

#### Layout (2-column grid)

**Top Row: API Performance**
- Request throughput - Line graph (requests/sec)
- Response time percentiles - Multi-line (p50, p95, p99)
- Error rate - Stacked area (4xx, 5xx)
- Endpoint performance - Table (top 10 slowest)

**Middle Row: Database Performance**
- Connection pool utilization - Dual gauge (active/total)
- Slow query rate - Line graph with threshold line
- Query cache hit rate - Percentage with sparkline
- Database replication lag - Gauge (if applicable)

**Bottom Row: CompanyScope Performance**
- Scope query overhead - Line graph (avg ms)
- Queries by tenant - Horizontal bar (top 10)
- Scope cache effectiveness - Pie chart (hit/miss)
- company_id index usage - Percentage gauge

---

### 5.3 Business Metrics Dashboard

#### Layout (Grid + Cards)

**Top Cards: Key Metrics**
- Active Tenants (24h) - Large counter with trend
- Total API Calls (24h) - Counter with comparison to yesterday
- Webhook Success Rate - Percentage with provider breakdown
- System Uptime - Percentage (SLA tracking)

**Middle Row: Tenant Analytics**
- API usage by tenant - Horizontal bar chart (top 20)
- Tenant error rates - Table with color coding (green/yellow/red)
- Tenant growth - Line graph (new tenants/week)
- Feature adoption - Grouped bar chart (v1 vs v2 API)

**Bottom Row: Webhook Analytics**
- Webhook volume by provider - Stacked area chart
- Webhook processing latency - Histogram
- Failed webhooks - Pie chart by failure reason
- Webhook retry rate - Percentage with trend

---

### 5.4 Unified Operations Dashboard

#### Single-Pane-of-Glass View (4-quadrant layout)

**Top-Left: System Health**
- Overall system status - Giant status indicator (Green/Yellow/Red)
- Active alerts count - Tile grid by severity
- Critical services status - Icon grid (API, DB, Cache, Queue)

**Top-Right: Security Overview**
- Authentication failure rate - Gauge
- Cross-tenant violations - Counter (0 = green)
- Webhook signature failures - Mini bar chart

**Bottom-Left: Performance Overview**
- API response time p95 - Line graph (last 4 hours)
- Database connection pool - Gauge
- Request throughput - Sparkline

**Bottom-Right: Business Overview**
- Active tenants - Counter
- API calls/min - Large number
- Webhook success rate - Percentage
- Recent events - Scrolling activity feed

---

## 6. IMPLEMENTATION PLAN

### 6.1 Tools & Services Selection

#### Recommended Stack: Open Source + SaaS Hybrid

**Metrics Collection & Storage**:
- **Prometheus**: Metrics scraping and time-series database
  - Rationale: Industry standard, excellent Laravel integration
  - Cost: Free (self-hosted)
  - Effort: Medium setup (2-3 days)

**Visualization & Dashboards**:
- **Grafana**: Dashboard platform with alerting
  - Rationale: Rich visualization, Prometheus integration
  - Cost: Free (self-hosted) or Grafana Cloud ($49/month for team)
  - Effort: Low setup (1 day)

**Log Aggregation**:
- **Loki** (by Grafana Labs): Log aggregation system
  - Rationale: Integrates with Grafana, label-based indexing
  - Cost: Free (self-hosted)
  - Effort: Medium setup (2 days)
  - Alternative: ELK Stack (Elasticsearch + Logstash + Kibana) if already in use

**Alerting & Incident Management**:
- **PagerDuty**: On-call management and incident response
  - Rationale: Industry standard, reliable escalation
  - Cost: $21/user/month (Professional plan)
  - Effort: Low setup (4 hours)

**APM (Application Performance Monitoring)**:
- **Laravel Telescope** (Development): Built-in debugging
  - Cost: Free
  - Effort: Already installed
- **Sentry** (Production): Error tracking and performance
  - Cost: $26/month (Team plan) or self-hosted free
  - Effort: Low setup (4 hours)

**Infrastructure Monitoring**:
- **Prometheus Node Exporter**: Server metrics
  - Cost: Free
  - Effort: Low setup (2 hours)

**Optional Enhancements**:
- **Grafana OnCall**: Alternative to PagerDuty (open source)
  - Cost: Free (self-hosted)
  - Effort: Medium setup (1 day)

---

### 6.2 Integration with Existing Laravel Logging

#### Phase 1: Structured Logging Enhancement

**Current State Analysis**:
- Logging configured in `/var/www/api-gateway/config/logging.php`
- Multiple log channels: `daily`, `calcom`, `slack`, `papertrail`
- Custom logs: `performance.log`, `security.log`, `health-check.log`

**Required Changes**:

**1. Create Security Logging Channel**
```php
// config/logging.php - Add new channel
'security' => [
    'driver' => 'daily',
    'path' => storage_path('logs/security.log'),
    'level' => 'info',
    'days' => 90, // Retain for compliance
    'tap' => [App\Logging\SecurityLogFormatter::class],
],
```

**2. Create Structured Log Formatter**
```php
// app/Logging/SecurityLogFormatter.php
namespace App\Logging;

use Monolog\Formatter\JsonFormatter;

class SecurityLogFormatter
{
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new JsonFormatter());
        }
    }
}
```

**3. Add Security Logging Middleware**
```php
// app/Http/Middleware/SecurityLogging.php
namespace App\Http\Middleware;

use Illuminate\Support\Facades\Log;

class SecurityLogging
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Log security-relevant events
        if (in_array($response->status(), [401, 403])) {
            Log::channel('security')->warning('Authorization failure', [
                'event_type' => 'auth_failure',
                'status_code' => $response->status(),
                'endpoint' => $request->path(),
                'ip_address' => $request->ip(),
                'user_id' => auth()->id(),
                'company_id' => auth()->user()?->company_id,
                'trace_id' => request()->header('X-Request-ID'),
            ]);
        }

        return $response;
    }
}
```

**4. Register Middleware**
```php
// app/Http/Kernel.php - Add to web/api middleware groups
protected $middlewareGroups = [
    'api' => [
        // ... existing middleware
        \App\Http\Middleware\SecurityLogging::class,
    ],
];
```

---

#### Phase 2: Metrics Export Setup

**1. Install Prometheus Exporter**
```bash
composer require ensi/laravel-prometheus
php artisan vendor:publish --provider="Ensi\LaravelPrometheus\ServiceProvider"
```

**2. Configure Metrics Endpoint**
```php
// routes/api.php - Add metrics endpoint
Route::get('/metrics/prometheus', function () {
    return response(app('prometheus')->render())
        ->header('Content-Type', 'text/plain; version=0.0.4');
})->middleware(['auth:sanctum', 'throttle:60,1']);
```

**3. Create Custom Metrics Collector**
```php
// app/Metrics/SecurityMetrics.php
namespace App\Metrics;

use Prometheus\CollectorRegistry;

class SecurityMetrics
{
    private $registry;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function recordAuthFailure(string $type, string $endpoint)
    {
        $counter = $this->registry->getOrRegisterCounter(
            'app',
            'auth_failures_total',
            'Total authentication failures',
            ['type', 'endpoint']
        );
        $counter->inc([$type, $endpoint]);
    }

    public function recordCrossTenantAttempt(int $userId, int $requestedCompanyId)
    {
        $counter = $this->registry->getOrRegisterCounter(
            'app',
            'tenant_violations_total',
            'Cross-tenant access attempts',
            ['user_id', 'company_id']
        );
        $counter->inc([$userId, $requestedCompanyId]);
    }

    public function recordCompanyScopeQueryTime(float $duration)
    {
        $histogram = $this->registry->getOrRegisterHistogram(
            'app',
            'company_scope_query_duration_seconds',
            'CompanyScope query execution time',
            [],
            [0.001, 0.005, 0.01, 0.05, 0.1, 0.5, 1.0, 2.0, 5.0]
        );
        $histogram->observe($duration);
    }
}
```

---

#### Phase 3: Loki Log Shipping

**1. Install Promtail (Log Shipper)**
```bash
# Install on server
curl -O -L "https://github.com/grafana/loki/releases/download/v2.9.0/promtail-linux-amd64.zip"
unzip promtail-linux-amd64.zip
sudo mv promtail-linux-amd64 /usr/local/bin/promtail
sudo chmod +x /usr/local/bin/promtail
```

**2. Configure Promtail**
```yaml
# /etc/promtail/config.yml
server:
  http_listen_port: 9080
  grpc_listen_port: 0

positions:
  filename: /tmp/positions.yaml

clients:
  - url: http://loki:3100/loki/api/v1/push

scrape_configs:
  - job_name: laravel_security
    static_configs:
      - targets:
          - localhost
        labels:
          job: laravel
          environment: production
          __path__: /var/www/api-gateway/storage/logs/security*.log
    pipeline_stages:
      - json:
          expressions:
            level: level
            event_type: event_type
            category: category
      - labels:
          level:
          event_type:
          category:

  - job_name: laravel_application
    static_configs:
      - targets:
          - localhost
        labels:
          job: laravel
          environment: production
          __path__: /var/www/api-gateway/storage/logs/laravel*.log
```

**3. Create Systemd Service**
```ini
# /etc/systemd/system/promtail.service
[Unit]
Description=Promtail Log Shipper
After=network.target

[Service]
Type=simple
User=www-data
ExecStart=/usr/local/bin/promtail -config.file=/etc/promtail/config.yml
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable promtail
sudo systemctl start promtail
```

---

### 6.3 Deployment Steps

#### Prerequisites
- Docker and Docker Compose installed
- Server with 4GB+ RAM for monitoring stack
- Firewall rules allowing Prometheus/Grafana ports
- Laravel application already deployed

---

#### Step 1: Deploy Monitoring Stack (Docker Compose)

**Create monitoring stack directory**:
```bash
mkdir -p /opt/monitoring
cd /opt/monitoring
```

**Create docker-compose.yml**:
```yaml
# /opt/monitoring/docker-compose.yml
version: '3.8'

services:
  prometheus:
    image: prom/prometheus:v2.47.0
    container_name: prometheus
    volumes:
      - ./prometheus/prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus_data:/prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
      - '--storage.tsdb.retention.time=90d'
    ports:
      - "9090:9090"
    restart: unless-stopped
    networks:
      - monitoring

  grafana:
    image: grafana/grafana:10.1.0
    container_name: grafana
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=change_me_secure_password
      - GF_INSTALL_PLUGINS=grafana-piechart-panel
    volumes:
      - grafana_data:/var/lib/grafana
      - ./grafana/provisioning:/etc/grafana/provisioning
    ports:
      - "3000:3000"
    restart: unless-stopped
    networks:
      - monitoring
    depends_on:
      - prometheus
      - loki

  loki:
    image: grafana/loki:2.9.0
    container_name: loki
    volumes:
      - ./loki/loki-config.yml:/etc/loki/local-config.yaml
      - loki_data:/loki
    ports:
      - "3100:3100"
    command: -config.file=/etc/loki/local-config.yaml
    restart: unless-stopped
    networks:
      - monitoring

  node-exporter:
    image: prom/node-exporter:v1.6.1
    container_name: node-exporter
    command:
      - '--path.rootfs=/host'
    volumes:
      - '/:/host:ro,rslave'
    ports:
      - "9100:9100"
    restart: unless-stopped
    networks:
      - monitoring

volumes:
  prometheus_data:
  grafana_data:
  loki_data:

networks:
  monitoring:
    driver: bridge
```

**Create Prometheus configuration**:
```yaml
# /opt/monitoring/prometheus/prometheus.yml
global:
  scrape_interval: 15s
  evaluation_interval: 15s
  external_labels:
    cluster: 'production'
    environment: 'prod'

scrape_configs:
  - job_name: 'laravel-api'
    static_configs:
      - targets: ['host.docker.internal:8000']
    metrics_path: '/api/metrics/prometheus'
    bearer_token: 'your_sanctum_token_here'

  - job_name: 'node-exporter'
    static_configs:
      - targets: ['node-exporter:9100']

  - job_name: 'prometheus'
    static_configs:
      - targets: ['localhost:9090']
```

**Create Loki configuration**:
```yaml
# /opt/monitoring/loki/loki-config.yml
auth_enabled: false

server:
  http_listen_port: 3100

ingester:
  lifecycler:
    address: 127.0.0.1
    ring:
      kvstore:
        store: inmemory
      replication_factor: 1
  chunk_idle_period: 5m
  chunk_retain_period: 30s

schema_config:
  configs:
    - from: 2023-01-01
      store: boltdb-shipper
      object_store: filesystem
      schema: v11
      index:
        prefix: index_
        period: 24h

storage_config:
  boltdb_shipper:
    active_index_directory: /loki/boltdb-shipper-active
    cache_location: /loki/boltdb-shipper-cache
    shared_store: filesystem
  filesystem:
    directory: /loki/chunks

limits_config:
  enforce_metric_name: false
  reject_old_samples: true
  reject_old_samples_max_age: 168h
  retention_period: 90d
```

**Deploy stack**:
```bash
cd /opt/monitoring
docker-compose up -d
```

---

#### Step 2: Configure Grafana Data Sources

**Access Grafana**: http://your-server:3000 (admin/change_me_secure_password)

**Create Prometheus Data Source**:
1. Navigate to Configuration → Data Sources
2. Add Prometheus data source
3. URL: `http://prometheus:9090`
4. Save & Test

**Create Loki Data Source**:
1. Add Loki data source
2. URL: `http://loki:3100`
3. Save & Test

---

#### Step 3: Import Grafana Dashboards

**Create dashboard provisioning directory**:
```bash
mkdir -p /opt/monitoring/grafana/provisioning/dashboards
mkdir -p /opt/monitoring/grafana/dashboards
```

**Dashboard provisioning config**:
```yaml
# /opt/monitoring/grafana/provisioning/dashboards/default.yml
apiVersion: 1

providers:
  - name: 'Default'
    orgId: 1
    folder: ''
    type: file
    disableDeletion: false
    updateIntervalSeconds: 10
    options:
      path: /etc/grafana/provisioning/dashboards
```

**Security Dashboard JSON** (simplified - full version would be extensive):
```json
{
  "dashboard": {
    "title": "Security Monitoring",
    "panels": [
      {
        "title": "Authentication Failures",
        "type": "graph",
        "targets": [
          {
            "expr": "rate(app_auth_failures_total[5m])"
          }
        ]
      },
      {
        "title": "Cross-Tenant Violations",
        "type": "stat",
        "targets": [
          {
            "expr": "app_tenant_violations_total"
          }
        ],
        "thresholds": {
          "mode": "absolute",
          "steps": [
            { "value": 0, "color": "green" },
            { "value": 1, "color": "red" }
          ]
        }
      }
    ]
  }
}
```

---

#### Step 4: Configure Alerting Rules

**Create Prometheus alert rules**:
```yaml
# /opt/monitoring/prometheus/alerts.yml
groups:
  - name: security_alerts
    interval: 30s
    rules:
      - alert: CrossTenantAccessAttempt
        expr: app_tenant_violations_total > 0
        for: 0s
        labels:
          severity: critical
          category: security
        annotations:
          summary: "Cross-tenant data access attempt detected"
          description: "User attempted to access data from another company ({{ $value }} attempts)"

      - alert: WebhookSignatureFailures
        expr: rate(app_auth_failures_total{type="webhook_signature"}[1m]) > 5
        for: 1m
        labels:
          severity: critical
          category: security
        annotations:
          summary: "High rate of webhook signature failures"
          description: "{{ $value }} webhook signature failures per second"

      - alert: HighAPIErrorRate
        expr: rate(app_http_requests_total{status=~"5.."}[5m]) / rate(app_http_requests_total[5m]) > 0.01
        for: 2m
        labels:
          severity: high
          category: availability
        annotations:
          summary: "API error rate above 1%"
          description: "Current error rate: {{ $value | humanizePercentage }}"

  - name: performance_alerts
    interval: 1m
    rules:
      - alert: SlowCompanyScopeQueries
        expr: histogram_quantile(0.95, app_company_scope_query_duration_seconds_bucket) > 1.0
        for: 5m
        labels:
          severity: medium
          category: performance
        annotations:
          summary: "CompanyScope queries running slow"
          description: "p95 query time: {{ $value }}s (threshold: 1s)"

      - alert: DatabaseConnectionPoolHigh
        expr: mysql_global_status_threads_connected / mysql_global_variables_max_connections > 0.8
        for: 5m
        labels:
          severity: high
          category: performance
        annotations:
          summary: "Database connection pool utilization high"
          description: "{{ $value | humanizePercentage }} of connections in use"
```

**Update prometheus.yml to include alerts**:
```yaml
# Add to /opt/monitoring/prometheus/prometheus.yml
rule_files:
  - 'alerts.yml'

alerting:
  alertmanagers:
    - static_configs:
        - targets: ['alertmanager:9093']
```

---

#### Step 5: Configure PagerDuty Integration

**Install Alertmanager** (add to docker-compose.yml):
```yaml
  alertmanager:
    image: prom/alertmanager:v0.26.0
    container_name: alertmanager
    volumes:
      - ./alertmanager/config.yml:/etc/alertmanager/config.yml
      - alertmanager_data:/alertmanager
    command:
      - '--config.file=/etc/alertmanager/config.yml'
    ports:
      - "9093:9093"
    restart: unless-stopped
    networks:
      - monitoring
```

**Configure Alertmanager**:
```yaml
# /opt/monitoring/alertmanager/config.yml
global:
  resolve_timeout: 5m
  pagerduty_url: 'https://events.pagerduty.com/v2/enqueue'

route:
  receiver: 'default'
  group_by: ['alertname', 'severity']
  group_wait: 10s
  group_interval: 5m
  repeat_interval: 3h

  routes:
    - match:
        severity: critical
      receiver: 'pagerduty-critical'
      continue: true

    - match:
        severity: critical
      receiver: 'slack-critical'

    - match:
        severity: high
      receiver: 'pagerduty-high'
      continue: true

    - match:
        severity: high
      receiver: 'slack-high'

    - match:
        severity: medium
      receiver: 'slack-medium'

receivers:
  - name: 'default'
    webhook_configs:
      - url: 'http://localhost:5001/webhook'

  - name: 'pagerduty-critical'
    pagerduty_configs:
      - service_key: 'YOUR_PAGERDUTY_INTEGRATION_KEY'
        severity: 'critical'
        description: '{{ .CommonAnnotations.summary }}'
        details:
          firing: '{{ .Alerts.Firing | len }}'
          resolved: '{{ .Alerts.Resolved | len }}'

  - name: 'pagerduty-high'
    pagerduty_configs:
      - service_key: 'YOUR_PAGERDUTY_INTEGRATION_KEY'
        severity: 'error'

  - name: 'slack-critical'
    slack_configs:
      - api_url: 'YOUR_SLACK_WEBHOOK_URL'
        channel: '#alerts-critical'
        title: '🚨 CRITICAL: {{ .CommonAnnotations.summary }}'
        text: '{{ .CommonAnnotations.description }}'
        send_resolved: true

  - name: 'slack-high'
    slack_configs:
      - api_url: 'YOUR_SLACK_WEBHOOK_URL'
        channel: '#alerts-high'
        title: '⚠️ HIGH: {{ .CommonAnnotations.summary }}'
        send_resolved: true

  - name: 'slack-medium'
    slack_configs:
      - api_url: 'YOUR_SLACK_WEBHOOK_URL'
        channel: '#alerts-medium'
        title: 'ℹ️ MEDIUM: {{ .CommonAnnotations.summary }}'
        send_resolved: true
```

---

#### Step 6: Configure Sentry for Error Tracking

**Install Sentry SDK**:
```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=YOUR_SENTRY_DSN
```

**Configure Sentry**:
```php
// config/sentry.php - Update performance monitoring
'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.2), // 20% of requests
'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE', 0.2),

// Add context for multi-tenant debugging
'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
    if (auth()->check()) {
        $event->setUser([
            'id' => auth()->id(),
            'company_id' => auth()->user()->company_id,
        ]);
    }
    return $event;
},
```

**Add to .env**:
```env
SENTRY_LARAVEL_DSN=YOUR_SENTRY_DSN
SENTRY_TRACES_SAMPLE_RATE=0.2
SENTRY_ENVIRONMENT=production
```

---

### 6.4 Validation & Testing Approach

#### Monitoring Stack Validation (15 minutes)

**1. Verify Services Running**:
```bash
docker-compose ps
# Expected: All services "Up"

curl http://localhost:9090/-/healthy
# Expected: Prometheus is Healthy.

curl http://localhost:3000/api/health
# Expected: {"database":"ok"}
```

**2. Verify Metrics Collection**:
```bash
curl http://localhost:9090/api/v1/query?query=up
# Expected: JSON response with targets status
```

**3. Test Alert Rules**:
```bash
curl http://localhost:9090/api/v1/rules
# Expected: List of configured alert rules
```

**4. Verify Log Ingestion**:
- Navigate to Grafana → Explore → Loki
- Query: `{job="laravel"} |= "ERROR"`
- Expected: Recent Laravel error logs displayed

---

#### Security Monitoring Validation (30 minutes)

**1. Trigger Authentication Failure Alert**:
```bash
# Send unsigned webhook request (should fail)
for i in {1..10}; do
  curl -X POST http://your-app.com/webhook \
    -H "Content-Type: application/json" \
    -d '{"test": "data"}'
  sleep 1
done
```

**Expected**:
- Prometheus metric `app_auth_failures_total` increases
- Alert fires after 5 failures within 1 minute
- PagerDuty incident created
- Slack message in #alerts-critical

**2. Verify Cross-Tenant Monitoring**:
```bash
# Attempt cross-company access (should be blocked)
curl -X GET http://your-app.com/api/v2/bookings \
  -H "Authorization: Bearer COMPANY_1_TOKEN" \
  -H "X-Company-ID: 2"  # Wrong company
```

**Expected**:
- HTTP 403 Forbidden response
- Security log entry created
- Metric `app_tenant_violations_total` increments (if attempt bypasses policy)

**3. Test Webhook Signature Validation Logging**:
```bash
# Valid signature test
curl -X POST http://your-app.com/calcom/webhook \
  -H "X-Cal-Signature-256: valid_signature_here" \
  -d '{"triggerEvent": "BOOKING_CREATED"}'
```

**Expected**:
- HTTP 200 response
- No alert triggered
- Successful webhook logged

---

#### Performance Monitoring Validation (20 minutes)

**1. Generate Load to Test Response Time Alerts**:
```bash
# Use Apache Bench to generate load
ab -n 1000 -c 50 http://your-app.com/health
```

**Expected**:
- Grafana dashboard shows throughput spike
- Response time metrics updated
- No alerts (if performance good)

**2. Simulate Slow Query**:
```php
// Temporarily add to a controller for testing
DB::connection()->getPdo()->exec('SELECT SLEEP(2)');
```

**Expected**:
- Slow query log entry created
- Metric `app_company_scope_query_duration_seconds` shows spike
- Alert fires if p95 > 1 second threshold

**3. Test Database Connection Pool Monitoring**:
```bash
# Check current connection count
mysql -e "SHOW STATUS LIKE 'Threads_connected';"
```

**Expected**:
- Grafana dashboard shows connection count
- Alert fires if >80% utilization

---

#### Dashboard Validation (10 minutes)

**1. Security Dashboard**:
- Navigate to Grafana → Dashboards → Security Monitoring
- Verify all panels load without errors
- Check authentication failure graph shows data
- Verify cross-tenant violation counter displays

**2. Performance Dashboard**:
- Navigate to Grafana → Dashboards → Performance Monitoring
- Verify API response time graph populated
- Check database metrics displayed
- Verify CompanyScope performance panel shows data

**3. Business Metrics Dashboard**:
- Navigate to Grafana → Dashboards → Business Metrics
- Verify tenant count accurate
- Check webhook success rate calculated correctly
- Verify top tenants by API usage displayed

---

#### Alert Routing Validation (15 minutes)

**1. Test CRITICAL Alert Path**:
```bash
# Manually fire test alert
curl -X POST http://localhost:9093/api/v1/alerts \
  -H "Content-Type: application/json" \
  -d '[{
    "labels": {
      "alertname": "TestCriticalAlert",
      "severity": "critical"
    },
    "annotations": {
      "summary": "Test alert - ignore"
    }
  }]'
```

**Expected**:
- PagerDuty incident created within 30 seconds
- Phone call/SMS to on-call engineer
- Slack message in #alerts-critical with @channel

**2. Test HIGH Alert Path**:
- Same as above with `"severity": "high"`
- Expected: PagerDuty notification (no phone call), Slack #alerts-high

**3. Test Alert Acknowledgment**:
- Acknowledge alert in PagerDuty
- Verify alert marked as acknowledged in Alertmanager
- Verify no escalation occurs

**4. Test Alert Resolution**:
- Resolve condition (e.g., stop sending unsigned webhooks)
- Wait for alert resolve timeout (5 minutes)
- Verify PagerDuty incident auto-resolves
- Verify Slack resolution message sent

---

### 6.5 Effort Estimates

#### Initial Setup (Total: 5-7 days)

| Phase | Tasks | Effort | Dependencies |
|-------|-------|--------|--------------|
| **Phase 1**: Infrastructure Setup | Deploy monitoring stack (Prometheus, Grafana, Loki)<br>Configure Promtail log shipping<br>Setup Node Exporter | 2 days | Docker access, server access |
| **Phase 2**: Laravel Integration | Install Prometheus exporter<br>Create custom metrics collectors<br>Implement structured logging<br>Add security logging middleware | 2 days | Laravel codebase access |
| **Phase 3**: Dashboard Creation | Build security dashboard<br>Build performance dashboard<br>Build business metrics dashboard<br>Create unified ops dashboard | 1.5 days | Phase 1, 2 complete |
| **Phase 4**: Alerting Configuration | Configure Prometheus alert rules<br>Setup Alertmanager<br>Integrate PagerDuty<br>Configure Slack webhooks<br>Create runbooks | 1 day | Phase 1-3 complete |
| **Phase 5**: Testing & Validation | Validate metrics collection<br>Test alert routing<br>Verify dashboard accuracy<br>Load testing<br>Security scenario testing | 0.5 day | All phases complete |

---

#### Ongoing Maintenance (Monthly)

| Activity | Frequency | Effort/Month | Responsible |
|----------|-----------|--------------|-------------|
| **Dashboard Review** | Weekly | 2 hours | Engineering team |
| **Alert Tuning** | Bi-weekly | 1 hour | DevOps engineer |
| **Runbook Updates** | Monthly | 2 hours | On-call rotation |
| **Security Log Review** | Weekly | 1 hour | Security engineer |
| **Performance Optimization** | Monthly | 4 hours | Backend team |
| **Incident Retrospectives** | Per incident | 1-2 hours | Engineering leadership |

**Total Ongoing Effort**: ~12 hours/month (3 hours/week)

---

#### Cost Estimates (Annual)

| Service | Plan | Cost/Month | Cost/Year | Notes |
|---------|------|------------|-----------|-------|
| **PagerDuty** | Professional (3 users) | $63 | $756 | On-call rotation |
| **Sentry** | Team (100k events) | $26 | $312 | Error tracking |
| **Grafana Cloud** | (Optional - recommend self-hosted) | $0 | $0 | Free tier sufficient |
| **Infrastructure** | Monitoring server (4GB RAM, 2 CPU) | $20 | $240 | DigitalOcean droplet |
| **Log Storage** | 90-day retention (~50GB) | $5 | $60 | S3-compatible storage |
| **Backup** | Prometheus/Grafana backups | $5 | $60 | Automated backups |

**Total Annual Cost**: ~$1,428/year (~$119/month)

**Cost Optimization Options**:
- Use Grafana OnCall instead of PagerDuty: -$756/year
- Self-host Sentry: -$312/year
- Total with optimizations: ~$360/year

---

## 7. PRODUCTION DEPLOYMENT CHECKLIST

### Pre-Deployment Requirements

#### Environment Configuration
- [ ] Configure `SENTRY_LARAVEL_DSN` in production .env
- [ ] Verify `RETELL_WEBHOOK_SECRET` configured
- [ ] Verify `CALCOM_WEBHOOK_SECRET` configured
- [ ] Verify `STRIPE_WEBHOOK_SECRET` configured
- [ ] Configure Prometheus scrape authentication token
- [ ] Set `LOG_CHANNEL=stack` with security channel

#### Infrastructure Preparation
- [ ] Provision monitoring server (4GB RAM minimum)
- [ ] Configure firewall rules (Prometheus: 9090, Grafana: 3000)
- [ ] Setup backup strategy for Prometheus data
- [ ] Configure log rotation for Laravel logs (90-day retention)
- [ ] Verify database has indexes on `company_id` columns

#### Alerting Setup
- [ ] Create PagerDuty service and integration keys
- [ ] Configure on-call rotation schedule
- [ ] Create Slack channels (#alerts-critical, #alerts-high, #alerts-medium)
- [ ] Configure Slack webhook URLs
- [ ] Test alert routing end-to-end

#### Dashboard Preparation
- [ ] Import Grafana dashboards (security, performance, business)
- [ ] Configure dashboard permissions
- [ ] Set up dashboard TV mode for operations center (if applicable)
- [ ] Create dashboard shortcuts/bookmarks

---

### Deployment Execution

#### Step 1: Deploy Monitoring Stack (2 hours)
```bash
# On monitoring server
cd /opt/monitoring
docker-compose up -d

# Verify services
docker-compose ps
curl http://localhost:9090/-/healthy
curl http://localhost:3000/api/health
```

#### Step 2: Configure Laravel Application (1 hour)
```bash
# On application server
cd /var/www/api-gateway

# Install dependencies
composer require ensi/laravel-prometheus sentry/sentry-laravel

# Publish configurations
php artisan vendor:publish --provider="Ensi\LaravelPrometheus\ServiceProvider"
php artisan sentry:publish

# Update .env
echo "SENTRY_LARAVEL_DSN=YOUR_DSN" >> .env
echo "SENTRY_TRACES_SAMPLE_RATE=0.2" >> .env

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

#### Step 3: Deploy Promtail (30 minutes)
```bash
# Install Promtail
curl -O -L "https://github.com/grafana/loki/releases/download/v2.9.0/promtail-linux-amd64.zip"
unzip promtail-linux-amd64.zip
sudo mv promtail-linux-amd64 /usr/local/bin/promtail
sudo chmod +x /usr/local/bin/promtail

# Configure systemd service
sudo systemctl enable promtail
sudo systemctl start promtail
sudo systemctl status promtail
```

#### Step 4: Configure Alerting (1 hour)
```bash
# Update Alertmanager config with real PagerDuty/Slack credentials
vi /opt/monitoring/alertmanager/config.yml

# Restart Alertmanager
docker-compose restart alertmanager

# Verify configuration
curl http://localhost:9093/-/healthy
```

#### Step 5: Validation (30 minutes)
- Run all validation tests from Section 6.4
- Trigger test alerts to verify routing
- Check dashboard data population
- Verify log ingestion working

---

### Post-Deployment Monitoring (First 48 Hours)

#### Hour 0-4: Critical Monitoring Period
- [ ] Monitor #alerts-critical Slack channel continuously
- [ ] Watch Grafana security dashboard for anomalies
- [ ] Verify metrics collection working (check Prometheus targets)
- [ ] Check for any alert storms (>10 alerts/hour = investigate)

#### Hour 4-24: Active Monitoring Period
- [ ] Review alert frequency (tune thresholds if >5 false positives)
- [ ] Check cross-tenant violation counter (should remain 0)
- [ ] Verify webhook signature validation metrics accurate
- [ ] Monitor API performance baselines (establish p50/p95/p99)

#### Hour 24-48: Baseline Establishment
- [ ] Document normal traffic patterns (requests/sec baseline)
- [ ] Record typical error rates (establish acceptable ranges)
- [ ] Tune alert thresholds based on real traffic
- [ ] Schedule first monitoring review meeting

---

### Rollback Plan

#### Monitoring Stack Issues
```bash
# Stop monitoring stack (does not affect application)
cd /opt/monitoring
docker-compose down

# Application continues running normally
# No user impact
```

#### Application Performance Degradation
```bash
# Disable Prometheus metrics export
# Comment out metrics endpoint in routes/api.php
php artisan route:clear

# Disable Sentry if causing issues
# Set SENTRY_LARAVEL_DSN= (empty) in .env
php artisan config:clear
```

#### Alert Storm Mitigation
```bash
# Temporarily silence all alerts (emergency only)
curl -X POST http://localhost:9093/api/v1/silence \
  -d '{"matchers":[{"name":"severity","value":".*","isRegex":true}],"comment":"Emergency silence","createdBy":"oncall"}'

# Investigate root cause
# Re-enable alerts after fix
```

---

## 8. SUCCESS METRICS

### Week 1 Targets
- [ ] Zero undetected security incidents
- [ ] <5% false positive alert rate
- [ ] All CRITICAL alerts acknowledged within 5 minutes
- [ ] 100% dashboard uptime
- [ ] All runbooks tested at least once

### Month 1 Targets
- [ ] Mean Time to Detection (MTTD) <2 minutes for CRITICAL
- [ ] Mean Time to Response (MTTR) <15 minutes for CRITICAL
- [ ] <2% false positive alert rate (after tuning)
- [ ] Zero cross-tenant violations detected (confirms security working)
- [ ] Security log review completed 4x

### Month 3 Targets
- [ ] MTTD <1 minute for CRITICAL
- [ ] MTTR <10 minutes for CRITICAL
- [ ] External security audit passed
- [ ] All team members trained on dashboards and runbooks
- [ ] Automated capacity planning based on metrics

---

## 9. RECOMMENDED TOOLS SUMMARY

### Core Stack (Required)
| Tool | Purpose | Cost | Hosting |
|------|---------|------|---------|
| **Prometheus** | Metrics collection & storage | Free | Self-hosted |
| **Grafana** | Dashboards & visualization | Free | Self-hosted |
| **Loki** | Log aggregation | Free | Self-hosted |
| **Promtail** | Log shipping | Free | Self-hosted |
| **PagerDuty** | Incident management | $756/year | SaaS |
| **Sentry** | Error tracking | $312/year | SaaS |

### Optional Enhancements
| Tool | Purpose | Cost | Priority |
|------|---------|------|----------|
| **Grafana OnCall** | Alternative to PagerDuty | Free | Medium |
| **Elasticsearch** | Advanced log search | Free (self-hosted) | Low |
| **Uptime Robot** | External uptime monitoring | Free tier | High |
| **Cloudflare Analytics** | CDN-level insights | Included | Medium |

---

## 10. NEXT STEPS

### Immediate Actions (This Week)
1. **Provision monitoring infrastructure**: Spin up monitoring server, install Docker
2. **Deploy monitoring stack**: Run docker-compose, verify services healthy
3. **Configure PagerDuty**: Create service, set up on-call rotation
4. **Create Slack channels**: #alerts-critical, #alerts-high, #alerts-medium

### Week 2 Actions
1. **Install Laravel integrations**: Prometheus exporter, Sentry SDK
2. **Implement structured logging**: Security logging middleware
3. **Create custom metrics collectors**: Auth failures, tenant violations, scope performance
4. **Deploy Promtail**: Configure log shipping to Loki

### Week 3 Actions
1. **Build Grafana dashboards**: Security, performance, business metrics
2. **Configure alert rules**: Prometheus alerts, Alertmanager routing
3. **Write runbooks**: One runbook per CRITICAL alert type
4. **Validation testing**: End-to-end alert routing, dashboard accuracy

### Week 4 Actions
1. **Production deployment**: Deploy monitoring to production
2. **Team training**: Dashboard walkthrough, runbook review
3. **48-hour intensive monitoring**: Tune thresholds, fix false positives
4. **Document learnings**: Update runbooks with real incident data

---

## APPENDIX A: Key Metrics Catalog

### Security Metrics (18 total)
```
auth.webhook.signature_failures       - Failed webhook signature validations
auth.api.unauthorized_rate            - HTTP 401 responses per endpoint
auth.api.forbidden_rate               - HTTP 403 policy denials
auth.session.failed_logins            - Failed Sanctum authentication
auth.webhook.missing_signatures       - Unsigned webhook requests
tenant.cross_access_attempts          - Cross-company query attempts
tenant.scope_bypass_attempts          - CompanyScope bypass attempts
tenant.policy_violations              - Policy authorization failures
tenant.admin_scope_bypass             - Admin attempting super_admin actions
tenant.service_discovery_violations   - Cross-company service access
webhook.retell.signature_invalid      - Invalid Retell signatures
webhook.calcom.signature_invalid      - Invalid Cal.com signatures
webhook.stripe.signature_invalid      - Invalid Stripe signatures
webhook.rate_limit_exceeded           - Webhook rate limit violations
webhook.unconfigured_secret           - Missing webhook secret configs
security.sql_injection_attempts       - Suspicious SQL patterns in logs
security.xss_attempts                 - XSS patterns blocked by observer
security.timing_attack_pattern        - User enumeration timing patterns
security.brute_force_pattern          - Rapid repeated auth failures
```

### Performance Metrics (12 total)
```
scope.company.query_time_p95          - 95th percentile CompanyScope query time
scope.company.query_time_p99          - 99th percentile CompanyScope query time
scope.company.queries_per_second      - CompanyScope queries/sec
scope.company.cache_hit_rate          - Company data cache efficiency
scope.company.index_usage             - company_id index usage percentage
db.connection_pool.active             - Active database connections
db.connection_pool.waiting            - Queued connection requests
db.query.slow_log_rate                - Queries exceeding slow log threshold
db.deadlock_rate                      - Database deadlocks detected
api.response_time_p50                 - Median API response time
api.response_time_p95                 - 95th percentile response time
api.response_time_p99                 - 99th percentile response time
api.throughput                        - Requests per second
api.error_rate_5xx                    - Server error rate percentage
```

### Business Metrics (8 total)
```
tenant.active_companies               - Companies with activity in last 24h
tenant.api_calls_per_company          - API requests by tenant
webhook.retell.success_rate           - Retell webhook processing success
webhook.calcom.success_rate           - Cal.com webhook processing success
webhook.stripe.success_rate           - Stripe webhook processing success
webhook.delivery_latency_p95          - 95th percentile webhook processing time
tenant.error_rate_overall             - Overall error rate per company
tenant.growth_rate                    - New tenants per week
```

---

## APPENDIX B: Alert Threshold Reference

| Alert | Metric Expression | Threshold | Duration | Severity |
|-------|------------------|-----------|----------|----------|
| Cross-Tenant Access | `app_tenant_violations_total` | >0 | 0s | CRITICAL |
| Webhook Sig Failures | `rate(app_auth_failures_total{type="webhook"}[1m])` | >5/min | 1min | CRITICAL |
| API Error Rate | `rate(app_http_requests_total{status=~"5.."}[5m])` | >1% | 2min | HIGH |
| Slow Scope Queries | `histogram_quantile(0.95, app_company_scope_query_duration_seconds_bucket)` | >1.0s | 5min | MEDIUM |
| DB Connection High | `mysql_global_status_threads_connected / mysql_global_variables_max_connections` | >80% | 5min | HIGH |
| Auth Failure Spike | `rate(app_auth_failures_total[5m])` | >20/5min | 2min | HIGH |
| Webhook Delivery Slow | `histogram_quantile(0.95, app_webhook_processing_duration_seconds_bucket)` | >5s | 5min | MEDIUM |

---

## APPENDIX C: Dashboard Panel Specifications

### Security Dashboard - Panel Breakdown

**Panel 1: Alert Status (Stat)**
- Metric: `ALERTS{alertstate="firing"}`
- Visualization: Large number with severity color coding
- Thresholds: 0=green, 1-2=yellow, 3+=red

**Panel 2: Auth Failure Rate (Graph)**
- Metric: `rate(app_auth_failures_total[5m])`
- Visualization: Line graph with alert threshold line
- Time range: Last 1 hour

**Panel 3: Cross-Tenant Violations (Counter)**
- Metric: `app_tenant_violations_total`
- Visualization: Stat panel with sparkline
- Thresholds: 0=green, >0=critical red

**Panel 4: Webhook Signature Validation (Pie Chart)**
- Metrics: `app_webhook_signatures_total{result="success"}` vs `result="failure"`
- Visualization: Donut chart
- Success target: >99%

**Panel 5: Top Attack Sources (Table)**
- Metric: `topk(10, sum by (ip_address) (app_auth_failures_total))`
- Columns: IP Address, Failure Count, Last Seen
- Actions: Block IP button (if implemented)

---

**CLASSIFICATION**: INTERNAL - DevOps/Engineering
**DOCUMENT VERSION**: 1.0
**LAST UPDATED**: 2025-10-02
**NEXT REVIEW**: 2025-10-09 (1 week post-deployment)
