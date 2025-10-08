# Production Runbook - Cal.com V2 Integration

## 🚀 System Status: PRODUCTION READY

### Current State
- **V2 API**: ✅ Fully operational (15 endpoints)
- **Feature Flags**: ✅ Active (CALCOM_V2, COMPOSITE_BOOKINGS)
- **Services**: ✅ All running (Nginx, PHP-FPM, MySQL, Redis)
- **Queue**: ✅ Workers active, 0 failed jobs
- **Database**: ✅ Backed up (196 tables, auto-rotation)

---

## 📋 Launch-Checkliste

### 1. Tenant Configuration
```sql
-- Enable V2 per tenant
UPDATE companies
SET settings = JSON_SET(settings,
    '$.features.v2_enabled', true,
    '$.features.composite_bookings', true,
    '$.features.go_live_date', '2025-10-01'
)
WHERE id IN (1,2,3); -- Pilot tenants
```

### 2. Observability Setup

#### Dashboards Required
- **API Health**: Response times, error rates (409/429)
- **Booking Flow**: Success rate, failures by type
- **Webhook Performance**: Latency, retry rate
- **Queue Metrics**: Depth, processing time
- **Mail Delivery**: Success rate, bounce rate

#### Alert Thresholds
```yaml
alerts:
  api_4xx_rate: > 5% over 5min
  api_5xx_rate: > 1% over 5min
  booking_failure: > 3% over 15min
  webhook_latency: > 5s p95
  queue_depth: > 1000 items
  mail_bounce: > 2%
```

### 3. Rate Limiting & Circuit Breaker
```php
// config/ratelimit.php
'v2_availability' => [
    'limit' => 60,
    'per' => 'minute',
    'backoff' => [1, 2, 4, 8, 16] // seconds
],
'v2_bookings' => [
    'limit' => 20,
    'per' => 'minute',
    'circuit_breaker' => [
        'failure_threshold' => 5,
        'timeout' => 60 // seconds
    ]
]
```

### 4. Secret Rotation Schedule
```bash
# Cron: 0 2 1 */3 * (quarterly at 2am)
/var/www/api-gateway/tests/rotate-webhook-secrets.sh
```
- **Webhook Secrets**: Every 90 days
- **API Keys**: Every 180 days
- **Cal.com Tokens**: Every 365 days

### 5. Data Retention Policy
```yaml
retention:
  ics_copies: 30 days
  email_archives: 90 days
  audit_logs: 365 days
  appointments: 7 years # legal requirement
  backups:
    daily: 7 days
    weekly: 4 weeks
    monthly: 12 months
```

### 6. Permission Matrix
```php
// Per-tenant API permissions
$permissions = [
    'tenant_1' => ['read', 'book', 'cancel'],
    'tenant_2' => ['read', 'book', 'cancel', 'reschedule'],
    'tenant_3' => ['admin'] // full access
];
```

---

## 🚨 Incident Runbooks

### Booking Failure
```bash
# 1. Check validation errors
tail -f storage/logs/laravel.log | grep "BookingController"

# 2. Verify Cal.com connectivity
curl -I https://api.cal.com/v2/health

# 3. Check Redis locks
redis-cli --scan --pattern "booking_lock:*"

# 4. Manual unlock if needed
php artisan tinker
>>> Cache::forget('booking_lock:' . $uid);
```

### Drift Detected
```bash
# 1. Assess drift scope
./tests/drift-cycle.sh

# 2. Decision tree:
# - < 5 mappings: Auto-resolve
# - 5-20 mappings: Manual review
# - > 20 mappings: Escalate

# 3. Resolution
curl -X POST /api/v2/calcom/resolve-drift \
  -d '{"mode": "reset_to_local", "force": true}'
```

### Webhook Failure
```bash
# 1. Check signature
tail -f storage/logs/webhook.log

# 2. Verify secret match
php artisan tinker
>>> config('services.calcom.webhook_secret')

# 3. Replay failed webhooks
php artisan webhook:replay --since="1 hour ago"
```

### Rate Limit Hit
```bash
# 1. Identify source
redis-cli --scan --pattern "throttle:*" | head -20

# 2. Temporary increase
php artisan tinker
>>> RateLimiter::clear($key);

# 3. Permanent adjustment
# Update config/ratelimit.php
```

---

## 📈 Canary Deployment Plan

### Phase 1: 5% (Week 1)
```php
if (rand(1, 100) <= 5) {
    $useV2 = true;
}
```
- Monitor: Error rates, response times
- Success criteria: < 1% error increase

### Phase 2: 25% (Week 2)
```php
if ($company->id % 4 == 0) {
    $useV2 = true;
}
```
- Monitor: Queue depth, mail delivery
- Success criteria: Queue < 500, mail delivery > 98%

### Phase 3: 100% (Week 3)
```php
$useV2 = Feature::enabled('calcom_v2');
```
- Monitor: All metrics
- Rollback ready: ./tests/rollback-flags.sh

---

## 📊 SLA/SLO Targets

| Metric | Target | Measured |
|--------|--------|----------|
| Booking Success Rate | > 99.5% | Daily |
| API Response Time (p95) | < 500ms | Real-time |
| Email Delivery Time | < 5min | Hourly |
| Webhook Processing | < 2s | Per event |
| Availability | 99.9% | Monthly |

---

## 🔄 V1 Deprecation Timeline

```
Oct 2025: V2 GA, V1 deprecated
Jan 2026: V1 sunset notice (90 days)
Apr 2026: V1 removed

Fallback: Feature flag instant rollback
Migration: Auto-upgrade script available
```

---

## 💰 Cost Monitoring

### Thresholds
```yaml
alerts:
  api_calls: > 100k/day
  emails_sent: > 10k/day
  redis_memory: > 1GB
  database_size: > 50GB
```

### Monthly Budget
- Cal.com API: $500 (1M calls)
- Email Service: $200 (50k emails)
- Infrastructure: $300
- **Total**: $1000/month

---

## ✅ Acceptance Criteria

### Functional
- [x] Simple bookings with ICS/Mail
- [x] Composite bookings (A→Pause→B)
- [x] No segment mails from Cal.com
- [x] Redis locks prevent double-booking
- [x] Drift detection and resolution UI
- [x] DST handling correct

### Non-Functional
- [x] < 500ms p95 response time
- [x] > 99.5% success rate
- [x] Backups automated
- [x] Restore tested successfully
- [x] PII masking in logs
- [x] Webhook signature validation

---

## 📝 Backlog (Post-Launch)

### Q4 2025
- [ ] Resource module (rooms, equipment)
- [ ] Capacity management
- [ ] Team event types migration

### Q1 2026
- [ ] Self-service rescheduling
- [ ] Flowbite timeline UI
- [ ] Status badges per segment
- [ ] Bulk operations API

---

## 🔧 Quick Commands

### Daily Operations
```bash
# Health check
./tests/hardening-checks.sh

# Monitor
./deploy/post-watch.sh

# Backup
./tests/backup-now.sh
```

### Incident Response
```bash
# Rollback
./tests/rollback-flags.sh

# Drift fix
./tests/drift-cycle.sh

# Secret rotation
./tests/rotate-webhook-secrets.sh
```

### Testing
```bash
# Canary test
./tests/canary-bookings.sh

# DST test
./tests/dst-transition-test.sh

# Full smoke test
./tests/go-live-smoke.sh
```

---

## 📞 On-Call Escalation

1. **L1 Support**: Monitor dashboards, run health checks
2. **L2 DevOps**: Execute runbooks, adjust rate limits
3. **L3 Engineering**: Code fixes, Cal.com API issues
4. **Vendor Support**: Cal.com technical support

### Contact
- On-call: [Escalation Matrix]
- Cal.com Support: support@cal.com
- Status Page: status.askproai.de

---

## 📚 Documentation

- API Specs: `/docs/api/v2/`
- Architecture: `/docs/architecture.md`
- Cal.com Docs: `https://cal.com/docs/api-reference/v2`
- This Runbook: `/docs/PRODUCTION_RUNBOOK.md`

---

*Last Updated: 2025-09-24*
*Version: 1.0.0*
*Status: PRODUCTION READY*