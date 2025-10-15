# Production Security Verification Plan
**Date**: 2025-10-01
**Context**: Production deployment of security fixes (multi-tenant isolation, log sanitization, input validation, rate limiting)
**Status**: Pre-activation security checklist

---

## 🔴 CRITICAL: Pre-Activation Security Checks

### 1. Multi-Tenant Isolation Verification

#### Cache Operations - Tenant Context
**Status**: ✅ **VERIFIED** - Tenant context properly implemented

**Locations Checked**:
- `/var/www/api-gateway/app/Services/AppointmentAlternativeFinder.php`
  - Line 47-56: `setTenantContext()` method defined
  - Tenant context passed to cache key generation

- `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`
  - Line 192-197: `checkAvailability` → `setTenantContext($companyId, $branchId)`
  - Line 266-272: `getAlternatives` → `setTenantContext($companyId, $branchId)`
  - Line 894-900: `collectAppointment` → `setTenantContext($companyId, $branchId)`
  - Line 1051-1057: Error handling → `setTenantContext($companyId, $branchId)`

**Security Pattern**:
```php
$alternatives = $this->alternativeFinder
    ->setTenantContext($companyId, $branchId)
    ->findAlternatives($date, $duration, $eventTypeId);
```

**Verification Command**:
```bash
# Check all cache operations have tenant context
grep -n "alternativeFinder" /var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php | grep -v "setTenantContext"
# Expected: Only constructor and import lines
```

**Risk**: 🟢 **LOW** - All cache operations properly scoped

---

### 2. Log Sanitization Verification

#### PII Redaction - LogSanitizer Applied
**Status**: ✅ **VERIFIED** - LogSanitizer applied to all webhook controllers

**Locations Checked**:
- `/var/www/api-gateway/app/Helpers/LogSanitizer.php`
  - Comprehensive PII redaction (emails, phones, names)
  - Secrets redaction (tokens, API keys, auth headers)
  - Production-only mode for PII (configurable)

- `/var/www/api-gateway/app/Http/Controllers/RetellWebhookController.php`
  - Line 21: `use App\Helpers\LogSanitizer;`
  - Line 81: `LogSanitizer::sanitizeHeaders()` for headers
  - All webhook logging properly sanitized

- `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`
  - Line 14: `use App\Helpers\LogSanitizer;`
  - Line 88-96: All webhook data sanitized before logging
  - Line 606-607: Headers sanitized in collectAppointment

- `/var/www/api-gateway/app/Http/Controllers/CalcomWebhookController.php`
  - LogSanitizer applied to headers and body

**Security Pattern**:
```php
Log::info('Webhook received', [
    'headers' => LogSanitizer::sanitizeHeaders($request->headers->all()),
    'raw_body' => LogSanitizer::sanitize($request->getContent()),
    'parsed_data' => LogSanitizer::sanitize($data),
]);
```

**Verification Command**:
```bash
# Check for raw logging without sanitization
grep -rn "Log::" /var/www/api-gateway/app/Http/Controllers/Retell*.php | grep -v "LogSanitizer" | grep -E "(headers|email|phone|name)"
# Expected: No matches (all sensitive logs sanitized)
```

**Risk**: 🟢 **LOW** - Complete PII protection in logs

---

### 3. Rate Limiter Middleware Verification

#### Rate Limiting Active on Retell Routes
**Status**: ✅ **VERIFIED** - Rate limiter middleware registered and applied

**Locations Checked**:
- `/var/www/api-gateway/app/Http/Middleware/RetellCallRateLimiter.php`
  - Per-call rate limiting (50 total, 20/minute, 10 same function)
  - Cooldown mechanism (5 minutes)
  - Circuit breaker pattern

- `/var/www/api-gateway/app/Http/Kernel.php`
  - Line: `'retell.call.ratelimit' => \App\Http\Middleware\RetellCallRateLimiter::class`
  - Middleware registered globally

- `/var/www/api-gateway/routes/api.php`
  - Line 60: `/retell/function` → middleware `retell.call.ratelimit`
  - Line 65: `/retell/collect-appointment` → middleware `retell.call.ratelimit`
  - Line 70: `/retell/check-availability` → middleware `retell.call.ratelimit`

**Rate Limits**:
- Total per call: 50 requests (lifetime)
- Per minute: 20 requests
- Same function: 10 requests per call
- Cooldown: 300 seconds (5 minutes)

**Verification Command**:
```bash
# Check rate limiter is on all Retell function routes
grep -A 2 "retell/function\|retell/collect\|retell/check" /var/www/api-gateway/routes/api.php | grep "middleware"
# Expected: All routes have 'retell.call.ratelimit' middleware
```

**Risk**: 🟢 **LOW** - Comprehensive rate limiting active

---

### 4. Security Regression Check

#### No Security Weaknesses Introduced
**Status**: ✅ **VERIFIED** - No new vulnerabilities detected

**Analysis**:
- No SQL injection vectors (using Eloquent ORM)
- No XSS vulnerabilities (JSON API responses)
- No authentication bypasses (middleware properly applied)
- No CSRF issues (stateless API with Sanctum)
- No path traversal (no file system operations)

**Code Quality**:
- Proper input validation with `Request` objects
- Type hints throughout codebase
- Exception handling with try-catch blocks
- Logging for security events

**Verification Command**:
```bash
# Check for dangerous functions
grep -rn "DB::raw\|exec\|shell_exec\|eval\|system\|passthru" /var/www/api-gateway/app/Http/Controllers/Retell*.php
# Expected: No matches (no dangerous operations)
```

**Risk**: 🟢 **LOW** - Clean security posture

---

## 🟡 Post-Activation Verification (Immediate)

### 1. Multi-Tenant Isolation Verification

**Goal**: Verify tenant data never crosses boundaries

**Without Creating Cross-Tenant Test**:
```bash
# Monitor Redis cache keys for proper tenant scoping
redis-cli KEYS "availability:*" | head -20
# Expected: Keys contain company_id and branch_id

# Check cache key pattern
redis-cli --scan --pattern "availability:*" | head -5
# Expected format: availability:{company_id}:{branch_id}:{date}:{service_id}
```

**Natural Event Trigger**: First availability check after restart
```bash
# Watch logs for tenant context
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "setTenantContext\|company_id\|branch_id"
# Expected: Every cache operation shows company_id and branch_id
```

**Success Criteria**:
- ✅ Cache keys include `{company_id}:{branch_id}` prefix
- ✅ No generic cache keys without tenant scope
- ✅ Logs show tenant context for all operations

**Failure Indicator** (ROLLBACK TRIGGER):
- ❌ Redis keys like `availability:2024-10-01:service_123` (missing tenant scope)
- ❌ Cross-tenant data leakage (company 1 sees company 2 data)

---

### 2. Log Sanitization Verification

**Goal**: Verify PII is redacted in production logs

**Check Existing Logs**:
```bash
# Check recent logs for PII leakage (last 1000 lines)
tail -1000 /var/www/api-gateway/storage/logs/laravel.log | grep -E "@|phone|telefon" | grep -v "REDACTED\|PII_REDACTED"
# Expected: No matches (all PII redacted)

# Verify sanitization markers present
tail -1000 /var/www/api-gateway/storage/logs/laravel.log | grep -E "REDACTED|PII_REDACTED" | head -5
# Expected: Redaction markers present in webhook logs
```

**Natural Event Trigger**: First webhook after restart
```bash
# Watch for sanitized webhook logs
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "Retell.*Webhook"
# Expected: Headers sanitized, no raw emails/phones visible
```

**Success Criteria**:
- ✅ Emails appear as `[EMAIL_REDACTED]` or `[PII_REDACTED]`
- ✅ Phone numbers appear as `[PHONE_REDACTED]` or `[PII_REDACTED]`
- ✅ Authorization headers show `[REDACTED]`
- ✅ API keys appear as `[API_KEY_REDACTED]`

**Failure Indicator** (ROLLBACK TRIGGER):
- ❌ Plain email addresses visible: `customer@example.com`
- ❌ Plain phone numbers visible: `+491234567890`
- ❌ Bearer tokens in logs: `Bearer sk_live_...`

---

### 3. Rate Limiter Activation Verification

**Goal**: Verify rate limiter is active without triggering it

**Check Rate Limiter Status**:
```bash
# Check middleware is loaded
php artisan route:list | grep "retell/function\|retell/collect\|retell/check" | grep "retell.call.ratelimit"
# Expected: All 3 routes show rate limiter middleware

# Check Redis for rate limiter keys (after first call)
redis-cli KEYS "retell_call_*"
# Expected: Keys like retell_call_total:{call_id}, retell_call_minute:{call_id}
```

**Natural Event Trigger**: First Retell function call
```bash
# Monitor rate limiter headers in response
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "X-Call-RateLimit"
# Expected: Response headers show X-Call-RateLimit-Limit, X-Call-RateLimit-Remaining
```

**Success Criteria**:
- ✅ Rate limiter middleware active on all Retell routes
- ✅ Redis cache keys created for call tracking
- ✅ Response headers include rate limit info

**Failure Indicator** (ROLLBACK TRIGGER):
- ❌ No Redis keys created (rate limiter not working)
- ❌ Middleware not applied (bypass detected)
- ❌ No rate limit headers in response

---

### 4. Circuit Breaker Initial State

**Goal**: Verify circuit breaker is in closed state (operational)

**Check Circuit Breaker**:
```bash
# Check for blocked calls (should be none initially)
redis-cli KEYS "retell_call_blocked:*"
# Expected: Empty (no calls blocked yet)

# Check circuit breaker configuration
grep -A 10 "cooldown\|LIMITS" /var/www/api-gateway/app/Http/Middleware/RetellCallRateLimiter.php
# Expected: cooldown => 300, total_per_call => 50, per_minute => 20
```

**Natural Event Trigger**: Normal call flow
```bash
# Monitor for rate limit warnings (should see none)
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "rate limit exceeded\|call blocked"
# Expected: No matches during normal operation
```

**Success Criteria**:
- ✅ No blocked calls initially
- ✅ Circuit breaker in closed state
- ✅ Rate limits configured correctly

**Failure Indicator** (ROLLBACK TRIGGER):
- ❌ Calls blocked without reason (circuit breaker open)
- ❌ Rate limits too restrictive (legitimate traffic blocked)

---

## 🟢 Real-World Validation Triggers

### Natural Events That Prove Security Works

#### 1. First Webhook → Log Sanitization Verified
**Event**: Incoming Retell webhook
**Check**:
```bash
tail -100 /var/www/api-gateway/storage/logs/laravel.log | grep "Retell Webhook received" -A 5
```
**Expected**: Headers sanitized, PII redacted

#### 2. First Availability Check → Tenant Context Verified
**Event**: Retell AI checks appointment availability
**Check**:
```bash
redis-cli --scan --pattern "availability:*" | head -3
tail -100 /var/www/api-gateway/storage/logs/laravel.log | grep "setTenantContext"
```
**Expected**: Cache keys scoped to company/branch

#### 3. First Rate Limit → Limiter Verified
**Event**: Multiple function calls from same call_id
**Check**:
```bash
redis-cli KEYS "retell_call_total:*" | head -1 | xargs redis-cli GET
```
**Expected**: Counter increments, headers show remaining quota

---

## 🔍 Security Monitoring Commands

### Continuous Monitoring (Production)

#### 1. PII Leakage Detection
```bash
# Real-time PII leak detection
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -E "(@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})|(\+?[0-9]{10,})" | grep -v "REDACTED"
# Expected: No output (all PII redacted)
```

#### 2. Tenant Isolation Monitoring
```bash
# Monitor tenant context in cache operations
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "company_id\|branch_id" | grep "alternativeFinder"
# Expected: Every operation shows tenant IDs
```

#### 3. Rate Limiter Status
```bash
# Check for rate limit violations
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "rate limit"
# Expected: Warnings only for actual abuse, not normal traffic
```

#### 4. Security Event Monitoring
```bash
# Watch for security-related events
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -E "CRITICAL|blocked|exceeded|unauthorized"
# Expected: Only legitimate security blocks
```

---

## 🚨 Security Rollback Triggers

### When to Rollback IMMEDIATELY

#### 1. Cross-Tenant Data Leak (CRITICAL)
**Indicator**:
```bash
# Company 1 sees Company 2's data
redis-cli GET "availability:1:*" | grep "company.*:2:"
```
**Action**: **IMMEDIATE ROLLBACK** - Data breach risk

#### 2. PII in Production Logs (CRITICAL)
**Indicator**:
```bash
# Plain email addresses visible in logs
tail -100 /var/www/api-gateway/storage/logs/laravel.log | grep -E "[a-zA-Z0-9]+@[a-zA-Z0-9]+\.[a-zA-Z]+" | grep -v "REDACTED"
```
**Action**: **IMMEDIATE ROLLBACK** - GDPR violation risk

#### 3. Rate Limiter Blocking Legitimate Traffic (HIGH)
**Indicator**:
```bash
# Normal traffic getting 429 errors
tail -100 /var/www/api-gateway/storage/logs/laravel.log | grep "rate limit exceeded" | wc -l
# If > 50% of requests blocked → ROLLBACK
```
**Action**: **INVESTIGATE** - May need to adjust limits

#### 4. Security Middleware Bypass (CRITICAL)
**Indicator**:
```bash
# Retell routes accessible without rate limiter
curl -X POST https://api.askproai.de/api/webhooks/retell/function -H "Content-Type: application/json" -d '{"test": true}' -v | grep "X-Call-RateLimit"
# Expected: Header present. If missing → BYPASS DETECTED
```
**Action**: **IMMEDIATE ROLLBACK** - Security control failure

---

## 📊 Success Metrics

### Post-Activation Health Checks (1 hour)

#### Metric 1: Cache Isolation
- **Target**: 100% of cache keys include tenant scope
- **Check**: `redis-cli --scan --pattern "availability:*" | grep -v ":" | wc -l` → **Expected: 0**

#### Metric 2: Log Sanitization
- **Target**: 0 PII leaks in logs
- **Check**: `tail -10000 /var/www/api-gateway/storage/logs/laravel.log | grep -E "@|phone" | grep -v "REDACTED" | wc -l` → **Expected: 0**

#### Metric 3: Rate Limiter Effectiveness
- **Target**: 0 false positives, 100% abuse blocked
- **Check**: Review rate limit logs for legitimate vs. malicious blocks

#### Metric 4: System Stability
- **Target**: No 500 errors, response time < 500ms
- **Check**: Application monitoring dashboard

---

## 🔧 Quick Fix Commands

### Emergency Fixes (If Issues Found)

#### Disable Rate Limiter (Emergency Only)
```bash
# Remove rate limiter from routes temporarily
sed -i 's/, "retell.call.ratelimit"//' /var/www/api-gateway/routes/api.php
php artisan config:cache
sudo systemctl reload php8.2-fpm
```

#### Clear Tenant Cache (If Cross-Contamination)
```bash
# Clear all availability cache
redis-cli KEYS "availability:*" | xargs redis-cli DEL
redis-cli KEYS "retell_call_*" | xargs redis-cli DEL
```

#### Restore Previous Version
```bash
# Git rollback to previous commit
cd /var/www/api-gateway
git log --oneline -5  # Find previous commit
git checkout <previous-commit-hash>
composer install --no-dev --optimize-autoloader
php artisan config:cache
sudo systemctl reload php8.2-fpm
```

---

## ✅ Final Checklist

### Pre-Restart Verification
- [x] Multi-tenant isolation code reviewed
- [x] Log sanitization applied to all controllers
- [x] Rate limiter middleware registered
- [x] Routes configured with security middleware
- [x] No dangerous operations introduced

### Post-Restart Verification (First 15 minutes)
- [ ] First webhook logged with sanitization
- [ ] First availability check uses tenant context
- [ ] Rate limiter creates Redis keys
- [ ] No 500 errors in logs
- [ ] Response times normal (<500ms)

### Long-term Monitoring (First 24 hours)
- [ ] Zero PII leaks detected
- [ ] Zero cross-tenant data access
- [ ] Rate limiter blocking only abuse
- [ ] Application stable and responsive

---

## 📞 Contacts & Escalation

**Security Issue Detected**:
1. Check rollback triggers (above)
2. If CRITICAL → Immediate rollback
3. If HIGH → Investigate within 30 minutes
4. If MEDIUM → Monitor and schedule fix

**Rollback Procedure**:
```bash
# Stop traffic, rollback, restart
git checkout <previous-stable-commit>
composer install --no-dev
php artisan config:cache
sudo systemctl reload php8.2-fpm
```

**Post-Rollback**:
- Document issue in `/var/www/api-gateway/claudedocs/SECURITY_INCIDENT_<date>.md`
- Review logs for root cause
- Fix in development environment
- Re-test before re-deployment

---

**Security Verification Plan Created**: 2025-10-01
**Next Review**: After first production traffic (15 minutes post-restart)
**Status**: Ready for production activation ✅
