# Production Activation Checklist
**Quick Reference for Production Deployment**

---

## Pre-Activation (5 minutes)

### System Health Check
```bash
cd /var/www/api-gateway

# 1. Check overall health
php artisan health:detailed

# 2. Verify Redis
redis-cli ping
# Expected: PONG

# 3. Check PHP-FPM
ps aux | grep php-fpm | grep -v grep | wc -l
# Expected: > 2

# 4. Check disk space
df -h | grep -E "Use%|/var/www"
# Expected: < 80% used

# 5. Check recent errors
tail -20 storage/logs/laravel.log | grep -c ERROR
# Expected: 0
```

**GO/NO-GO:**
- ✅ All checks pass → Proceed to activation
- ❌ Any check fails → Investigate before proceeding

---

## Activation (3 minutes)

### Step 1: Clear Caches
```bash
cd /var/www/api-gateway

# Clear all Laravel caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
```

**Verify:**
```bash
# Verify middleware registration
php artisan route:list --path=v2 | grep -E "rate-limit|performance"
# Expected: See middleware names in output
```

### Step 2: Reload PHP-FPM
```bash
# Graceful reload (NO downtime)
sudo systemctl reload php8.3-fpm

# Verify success
sudo systemctl status php8.3-fpm --no-pager | head -5
# Expected: "active (running)"
```

### Step 3: Immediate Verification
```bash
# Test health endpoint
curl -s http://localhost/api/health | jq
# Expected: {"status":"healthy",...}

# Check rate limit headers are present
curl -s -I http://localhost/api/health | grep -E "RateLimit|Response-Time"
# Expected: X-RateLimit-Limit, X-RateLimit-Remaining, X-Response-Time
```

---

## Verification (10 minutes)

### Check 1: Logs (First 10 Requests)
```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log

# In another terminal, make test requests
for i in {1..10}; do
  curl -s http://localhost/api/health > /dev/null
  echo "Request $i sent"
  sleep 1
done
```

**Look for:**
- ✅ No PHP errors or exceptions
- ✅ Rate limit headers logged
- ✅ No sensitive data in logs
- ❌ Any ERROR or CRITICAL messages

### Check 2: Cache Keys
```bash
# Verify rate limit keys are being created
redis-cli KEYS "rate_limit:*" | wc -l
# Expected: > 0

# Verify circuit breaker state
redis-cli GET "circuit_breaker:calcom_api:state"
# Expected: "closed" or null (both OK)
```

### Check 3: Rate Limiting Works
```bash
# Test rate limiting (35 requests in 30 seconds)
for i in {1..35}; do
  STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/health)
  echo "Request $i: HTTP $STATUS"
  sleep 0.8
done
```

**Expected Results:**
- Requests 1-30: HTTP 200
- Requests 31+: HTTP 429 (rate limit exceeded)

### Check 4: Circuit Breaker Initial State
```bash
# Verify circuit breaker is ready
redis-cli HGETALL "circuit_breaker:calcom_api"
# Expected: state=closed, failure_count=0
```

---

## Monitoring (First 2 Hours)

### Open 4 Terminal Windows

**Terminal 1: Error Log**
```bash
tail -f storage/logs/laravel.log | grep -E "ERROR|CRITICAL|emergency"
```

**Terminal 2: Rate Limiting**
```bash
tail -f storage/logs/laravel.log | grep "Rate limit"
```

**Terminal 3: Circuit Breaker**
```bash
tail -f storage/logs/laravel.log | grep "Circuit breaker"
```

**Terminal 4: Health Checks Every 15 Minutes**
```bash
watch -n 900 'curl -s http://localhost/api/health | jq && echo "---" && redis-cli GET "circuit_breaker:calcom_api:state"'
```

### Alert Thresholds
| Condition | Action |
|-----------|--------|
| > 5 ERRORs in 5 minutes | Investigate immediately |
| > 50 rate limit violations/hour | Consider raising limits |
| Circuit breaker OPENS | Check Cal.com API status |
| Response time > 1000ms | Check Redis connection |

---

## Emergency Rollback (60 seconds)

### If Critical Issues Occur

```bash
# 1. Comment out rate limiting middleware
sed -i "s/'api.rate-limit',/\/\/ 'api.rate-limit', \/\/ ROLLBACK/" routes/api.php

# 2. Clear caches
php artisan config:clear && php artisan route:clear

# 3. Reload PHP-FPM
sudo systemctl reload php8.3-fpm

# 4. Verify rollback
curl http://localhost/api/health
```

**Rollback Verification:**
```bash
# Rate limiting should NOT be active
curl -I http://localhost/api/health | grep RateLimit
# Expected: No output (headers missing)
```

---

## Success Criteria

### Immediate (First Hour)
- [ ] No 5xx errors introduced
- [ ] Rate limiting returns 429 for excessive requests
- [ ] Log files contain no sensitive data
- [ ] Circuit breaker state = "closed"
- [ ] Response times < 500ms

### First 24 Hours
- [ ] Error rate unchanged or improved
- [ ] No legitimate users blocked by rate limits
- [ ] Circuit breaker remains closed
- [ ] Redis memory usage stable

---

## Known Issues to Fix After Activation

### HIGH PRIORITY: CollectAppointmentRequest Integration
**File:** `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`

**Current:** Validation not active
**Fix:** Add import and type hint

```php
// Add to imports (line ~14)
use App\Http\Requests\CollectAppointmentRequest;

// Update method signature (find collectAppointment method)
public function collectAppointment(CollectAppointmentRequest $request)
{
    $validated = $request->getAppointmentData();
    // ... rest of method
}
```

**Deploy fix:**
```bash
# After editing file
php artisan config:clear
sudo systemctl reload php8.3-fpm
```

---

## Quick Reference Commands

### Health Check
```bash
php artisan health:detailed | jq
```

### Rate Limit Status
```bash
redis-cli KEYS "rate_limit:*" | wc -l
```

### Circuit Breaker Status
```bash
redis-cli GET "circuit_breaker:calcom_api:state"
```

### Recent Errors
```bash
tail -100 storage/logs/laravel.log | grep ERROR
```

### Active Rate Limits
```bash
redis-cli --scan --pattern "rate_limit:*" | while read key; do
  echo "$key: $(redis-cli GET "$key")"
done
```

### Reset All Rate Limits (Admin Only)
```bash
redis-cli KEYS "rate_limit:*" | xargs redis-cli DEL
```

---

## Support Information

### Log Files
- **Application:** `/var/www/api-gateway/storage/logs/laravel.log`
- **Cal.com:** `/var/www/api-gateway/storage/logs/calcom.log`
- **PHP-FPM:** `sudo journalctl -u php8.3-fpm --since "1 hour ago"`

### Cache Files
- **Config:** `/var/www/api-gateway/bootstrap/cache/config.php`
- **Routes:** `/var/www/api-gateway/bootstrap/cache/routes-*.php`
- **Services:** `/var/www/api-gateway/bootstrap/cache/services.php`

### Key Configuration Files
- **Routes:** `/var/www/api-gateway/routes/api.php`
- **Middleware:** `/var/www/api-gateway/app/Http/Kernel.php`
- **Rate Limits:** `/var/www/api-gateway/app/Http/Middleware/RateLimitMiddleware.php`
- **Circuit Breaker:** `/var/www/api-gateway/app/Services/CircuitBreaker.php`

---

## Timeline

| Task | Duration |
|------|----------|
| Pre-activation checks | 5 minutes |
| Cache clearing | 1 minute |
| PHP-FPM reload | 30 seconds |
| Immediate verification | 5 minutes |
| Initial monitoring | 2 hours |
| **TOTAL** | **~2 hours 10 minutes** |

**Downtime:** 0 minutes (graceful reload)
