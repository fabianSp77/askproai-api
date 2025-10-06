# Deployment Checklist - Phase 1 Implementation

**Date:** 2025-10-01
**Version:** Phase 1 (Production-Ready)
**Status:** ✅ Ready for Staging → Production

---

## 📋 PRE-DEPLOYMENT VERIFICATION

### ✅ Code Quality (COMPLETE)
- [x] All PHP files syntax-checked (no errors)
- [x] Composer autoload regenerated (optimized)
- [x] All new classes discoverable
- [x] No PSR-4 violations in production code
- [x] Git commit ready (all changes tracked)

### ⏳ Testing Requirements (PENDING)

#### Unit Tests
- [ ] Run existing test suite: `php artisan test`
- [ ] Verify no regressions in existing tests
- [ ] Add tests for new components (optional for v1)

#### Integration Tests
- [ ] Test Cal.com API integration with real Event Type 2563193
- [ ] Verify cache key isolation (Company 15 vs Company 20)
- [ ] Test business hours adjustment (08:00 → 09:00, 19:00 → next day)
- [ ] Verify Cal.com error handling (simulate API down)
- [ ] Test circuit breaker behavior (5 failures → open)

#### Security Tests
- [ ] Verify log sanitization (no PII in logs)
- [ ] Test rate limiter (50 calls/call threshold)
- [ ] Verify input validation (XSS attempts blocked)
- [ ] Test multi-tenant isolation (no cache collisions)

---

## 🚀 STAGING DEPLOYMENT

### Step 1: Environment Preparation
```bash
# 1. Backup current state
cd /var/www/api-gateway
git status
git diff

# 2. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 3. Optimize for staging
COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize
php artisan config:cache
php artisan route:cache
```

### Step 2: Verification Tests

**Test 1: Multi-Tenant Cache Isolation**
```bash
# Simulate Company 15 request
curl -X POST https://staging.askproai.de/api/retell/check-availability \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test-company-15",
    "args": {
      "service": "Haarschnitt",
      "date": "2025-10-02",
      "time": "10:00"
    }
  }'

# Check Redis for cache keys
redis-cli keys "askpro_cache_*cal_slots_15_*"
redis-cli keys "askpro_cache_*cal_slots_20_*"

# Verify different cache keys for different companies
```

**Test 2: Log Sanitization**
```bash
# Trigger webhook with PII
curl -X POST https://staging.askproai.de/api/webhooks/retell \
  -H "Authorization: Bearer test-token-12345" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "phone": "+491234567890",
    "name": "Test User"
  }'

# Check logs - should NOT contain:
# - test@example.com
# - +491234567890
# - Bearer test-token-12345

tail -f storage/logs/laravel.log | grep -E "EMAIL_REDACTED|PHONE_REDACTED|REDACTED"
```

**Test 3: Rate Limiting**
```bash
# Send 60 rapid requests with same call_id
for i in {1..60}; do
  curl -X POST https://staging.askproai.de/api/webhooks/retell/function \
    -d "call_id=rate-test-001&function_name=test" &
done

# Expect: HTTP 429 after ~50 requests
# Check logs for rate limit blocks
```

**Test 4: Business Hours Adjustment**
```bash
# Request at 08:00 (before opening)
curl -X POST https://staging.askproai.de/api/retell/check-availability \
  -d '{
    "call_id": "hours-test",
    "args": {
      "date": "2025-10-02",
      "time": "08:00",
      "service": "Haarschnitt"
    }
  }'

# Expect: Auto-adjusted to 09:00 in logs
# Check: grep "Auto-adjusted request time" storage/logs/laravel.log
```

**Test 5: Circuit Breaker**
```bash
# Method 1: Simulate Cal.com downtime (edit .env temporarily)
# Set invalid API key or URL
# CALCOM_API_KEY=invalid_key

# Make 6 requests (should trigger circuit breaker)
for i in {1..6}; do
  curl https://staging.askproai.de/api/retell/check-availability \
    -d '{"call_id":"cb-test","args":{"service":"Test","date":"2025-10-02","time":"10:00"}}'
done

# Method 2: Check circuit breaker status
php artisan tinker
>>> $service = app(\App\Services\CalcomService::class);
>>> $service->getCircuitBreakerStatus();
// Should show state: "open" after 5 failures

# Restore .env after test
```

### Step 3: Monitoring Setup

**Enable Debug Logging** (staging only):
```bash
# .env
LOG_LEVEL=debug
APP_DEBUG=true
```

**Watch Logs in Real-Time:**
```bash
# Terminal 1: General logs
tail -f storage/logs/laravel.log

# Terminal 2: Cal.com API logs
tail -f storage/logs/calcom.log

# Terminal 3: Redis monitor
redis-cli monitor | grep "cal_slots"
```

**Key Metrics to Track:**
- Circuit breaker state changes
- Rate limit violations
- Cache hit/miss ratio
- Cal.com API response times
- PII redaction effectiveness

---

## 🏭 PRODUCTION DEPLOYMENT

### Pre-Production Checklist
- [ ] All staging tests passed
- [ ] No critical errors in staging logs (48h)
- [ ] Circuit breaker tested successfully
- [ ] Multi-tenant isolation verified
- [ ] Log sanitization confirmed
- [ ] Stakeholder approval obtained

### Deployment Window
**Recommended:** Off-peak hours (2-4 AM CET)
**Duration:** 30 minutes
**Rollback Plan:** Git revert + cache clear

### Step 1: Pre-Deployment Backup
```bash
# 1. Database backup
mysqldump -u askproai_user -p askproai_db > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Code backup
cd /var/www
tar -czf api-gateway-backup-$(date +%Y%m%d_%H%M%S).tar.gz api-gateway/

# 3. Redis snapshot
redis-cli SAVE
cp /var/lib/redis/dump.rdb /var/lib/redis/dump.rdb.backup
```

### Step 2: Deploy Code
```bash
cd /var/www/api-gateway

# 1. Pull changes (if using git)
# git pull origin main

# 2. Install dependencies
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader

# 3. Clear and cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Restart services
php artisan queue:restart
supervisorctl restart all
```

### Step 3: Immediate Verification (5 minutes)
```bash
# 1. Health check
curl https://api.askproai.de/api/health/detailed

# 2. Check circuit breaker initial state
php artisan tinker --execute="var_dump(app(\App\Services\CalcomService::class)->getCircuitBreakerStatus());"

# 3. Monitor first 10 requests
tail -f storage/logs/laravel.log | head -50

# 4. Verify Redis cache working
redis-cli ping
redis-cli dbsize
```

### Step 4: Intensive Monitoring (2 hours)
**Terminal Setup:**
```bash
# Terminal 1: Error log watch
tail -f storage/logs/laravel.log | grep -E "ERROR|CRITICAL|Circuit|Rate limit"

# Terminal 2: Cal.com API monitoring
tail -f storage/logs/calcom.log | grep -E "Cal\.com|circuit|exception"

# Terminal 3: System metrics
watch -n 5 'php artisan tinker --execute="
  echo \"Redis: \" . app(\"cache\")->getRedis()->info(\"stats\");
  echo \"\\nCircuit: \" . json_encode(app(\App\Services\CalcomService::class)->getCircuitBreakerStatus());
"'
```

**Alert Triggers:**
- Circuit breaker opens (CRITICAL)
- >10 rate limit violations/minute (WARNING)
- Cal.com API error rate >5% (WARNING)
- Any cross-tenant data leakage (CRITICAL)
- PII in logs (CRITICAL)

### Step 5: Gradual Traffic Validation
**Hour 1-2:** Monitor baseline traffic
- Track all error types
- Verify circuit breaker stays CLOSED
- Check rate limiter effectiveness
- Confirm no PII in logs

**Hour 2-24:** Extended monitoring
- Circuit breaker metrics
- Cache hit ratio (target: >80%)
- Response time percentiles
- Multi-tenant isolation

**Day 2-7:** Production validation
- Zero cross-tenant incidents
- Circuit breaker auto-recovery
- Rate limit abuse patterns
- Business hours edge case handling

---

## 🔥 ROLLBACK PROCEDURE

### Trigger Conditions
- Cross-tenant data leakage detected
- Circuit breaker stuck in OPEN state
- Rate limiter blocking legitimate traffic
- PII leaking in logs
- Critical system errors

### Rollback Steps (5 minutes)
```bash
# 1. Stop traffic (optional - if critical)
# Edit nginx config to return 503

# 2. Restore code
cd /var/www/api-gateway
git log --oneline -10  # Find previous commit
git reset --hard <previous-commit-hash>

# 3. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 4. Regenerate autoload
COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize

# 5. Restart services
php artisan queue:restart
supervisorctl restart all

# 6. Verify rollback
curl https://api.askproai.de/api/health
tail -f storage/logs/laravel.log
```

### Post-Rollback
1. Document the failure reason
2. Create bug report with logs
3. Test fix in staging
4. Schedule new deployment

---

## 📊 SUCCESS CRITERIA

### Week 1 Metrics

**Security (CRITICAL):**
- ✅ Zero cross-tenant data leakage incidents
- ✅ Zero PII exposed in logs
- ✅ Zero successful injection attacks
- ✅ No unauthorized rate limit bypasses

**Reliability:**
- ✅ Cal.com API error rate <5%
- ✅ Circuit breaker auto-recovery working
- ✅ Response time <2s (95th percentile)
- ✅ Cache hit ratio >80%

**Business:**
- ✅ User booking success rate >60%
- ✅ Zero critical incidents
- ✅ No customer complaints about privacy
- ✅ Improved conversion from edge case handling

### Monitoring Dashboard

**Key Metrics to Track:**
```php
// Circuit Breaker Status
$calcom = app(\App\Services\CalcomService::class);
$status = $calcom->getCircuitBreakerStatus();

// Expected output:
[
  'service' => 'calcom_api',
  'state' => 'closed',  // ← Should be 'closed' most of the time
  'failure_count' => 0,
  'failure_threshold' => 5,
  'seconds_until_retry' => null
]

// Cache Performance
$cacheStats = Redis::info('stats');
$hitRate = $cacheStats['keyspace_hits'] / ($cacheStats['keyspace_hits'] + $cacheStats['keyspace_misses']) * 100;
// Target: >80%

// Rate Limiter
// Check: storage/logs/laravel.log for "Rate limit exceeded"
// Target: <10 violations/hour for legitimate traffic
```

---

## 🛠️ TROUBLESHOOTING GUIDE

### Issue: Circuit Breaker Stuck OPEN
**Symptoms:** All Cal.com requests failing immediately
**Cause:** 5+ consecutive Cal.com API failures
**Fix:**
```php
php artisan tinker
>>> $service = app(\App\Services\CalcomService::class);
>>> $service->getCircuitBreakerStatus(); // Check state
>>> $service->resetCircuitBreaker(); // Manual reset
```

### Issue: Rate Limiter Blocking Legitimate Traffic
**Symptoms:** HTTP 429 errors for valid calls
**Cause:** Single call_id making >50 requests
**Fix:**
```bash
# Check which call_id is affected
redis-cli keys "retell_call_*" | xargs redis-cli del

# Or specific call_id
redis-cli del "retell_call_total:CALL_ID_HERE"
redis-cli del "retell_call_minute:CALL_ID_HERE"
```

### Issue: PII Still Appearing in Logs
**Symptoms:** Emails/phones visible in logs
**Cause:** LogSanitizer not applied to all log statements
**Fix:**
```bash
# Find unsanitized log statements
grep -r "Log::info.*\$request->all()" app/
grep -r "Log::debug.*\$request->headers" app/

# Apply sanitization to found statements
```

### Issue: Cache Key Collision
**Symptoms:** Company A seeing Company B's data
**Cause:** Tenant context not set before findAlternatives()
**Fix:**
```bash
# Check cache keys in Redis
redis-cli keys "*cal_slots*" | head -20

# Should see:
# cal_slots_15_0_2563193_... (Company 15)
# cal_slots_20_0_2563193_... (Company 20)

# If missing company IDs, check setTenantContext() calls
```

---

## ✅ DEPLOYMENT SIGN-OFF

**Pre-Production:**
- [ ] All code changes reviewed
- [ ] Staging tests passed (100%)
- [ ] Security verification complete
- [ ] Performance benchmarks acceptable
- [ ] Rollback plan tested

**Production Deployment:**
- [ ] Off-peak deployment window confirmed
- [ ] Backup completed successfully
- [ ] Deployment executed without errors
- [ ] Immediate verification passed
- [ ] 2-hour monitoring completed

**Post-Deployment:**
- [ ] Week 1 metrics within targets
- [ ] No critical incidents
- [ ] Stakeholders informed
- [ ] Documentation updated

**Sign-Off:**
- Developer: _________________ Date: _______
- Tech Lead: _________________ Date: _______
- Operations: ________________ Date: _______

---

**Prepared By:** Claude Code
**Version:** Phase 1 Production-Ready
**Last Updated:** 2025-10-01
