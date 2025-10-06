# Production Verification - Quick Start Card
**Print this or keep it open in a terminal**

---

## 🚀 STEP 1: RUN AUTOMATED TESTS (3 min)
```bash
cd /var/www/api-gateway
./scripts/verify-production-deployment.sh
```
**Expected**: All tests PASS or WARN (no FAIL)
**If ANY FAIL**: Stop and review failures before proceeding

---

## 🔍 STEP 2: MANUAL SPOT CHECKS (5 min)

### Test 1: Health Check
```bash
curl http://localhost/api/health | jq .
```
**Expected**: `{"status":"healthy",...}`

### Test 2: Circuit Breaker
```bash
php artisan tinker
$b = new \App\Services\CircuitBreaker('test');
print_r($b->getStatus());
exit
```
**Expected**: `[state] => closed`, `[failure_count] => 0`

### Test 3: PII Check (CRITICAL)
```bash
grep -E "[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" \
  storage/logs/laravel-$(date +%Y-%m-%d).log | \
  grep -v REDACTED | wc -l
```
**Expected**: `0` (zero)
**If >0**: 🚨 IMMEDIATE ROLLBACK - GDPR VIOLATION

### Test 4: Recent Errors
```bash
tail -50 storage/logs/laravel.log | grep -i "error\|critical"
```
**Expected**: None or minimal old errors
**If many recent**: Investigate before proceeding

---

## 📊 STEP 3: START MONITORING (Keep Open)

### Terminal 1: Error Watch
```bash
tail -f storage/logs/laravel.log | grep -i --color=always "error\|critical\|exception"
```

### Terminal 2: Circuit Breaker
```bash
watch -n 10 'redis-cli GET circuit_breaker:calcom_api:state; \
redis-cli GET circuit_breaker:calcom_api:failures'
```
**Watch for**: State should be "closed" or empty, failures should be 0-2

### Terminal 3: Rate Limiter
```bash
watch -n 5 'redis-cli GET "calcom_api_rate_limit:$(date +"%Y-%m-%d-%H-%M")"'
```
**Watch for**: Count should be 0-60, resets each minute

---

## ⏱️ STEP 4: MONITOR FOR 10 MINUTES

**Minute 0-2**: Watch all terminals, verify no immediate errors
**Minute 2-5**: Check PII exposure again, verify circuit breakers stable
**Minute 5-10**: Watch for patterns, check user activity

---

## ✅ SUCCESS INDICATORS

- ✅ Health endpoint returns 200
- ✅ Circuit breakers show "closed" state
- ✅ Rate limiter counting requests (if any traffic)
- ✅ No error spikes in logs
- ✅ Zero PII in logs
- ✅ Response times <500ms

---

## 🚨 ROLLBACK TRIGGERS (DO NOT IGNORE)

**Immediate Rollback If**:
1. PII/secrets visible in logs (emails, tokens, API keys)
2. Circuit breaker stuck "open" for >5 minutes
3. Application crashes (HTTP 500, fatal errors)
4. User bookings failing (>10% failure rate)
5. Error rate spikes (>50 errors in 5 minutes)

---

## ⚡ EMERGENCY ROLLBACK (Copy-Paste)

```bash
# Find previous commit
git log --oneline -5

# Rollback (replace abc1234 with actual commit)
git checkout abc1234

# Clear caches
php artisan cache:clear && \
php artisan config:clear && \
php artisan route:clear

# Clear Redis
redis-cli FLUSHDB

# Restart services
sudo systemctl restart php8.3-fpm nginx

# Verify
curl http://localhost/api/health
tail -20 storage/logs/laravel.log
```

**Time**: 5 minutes
**If successful**: Monitor for 10 more minutes to confirm stability

---

## 📋 10-MINUTE CHECKLIST

**Minute 0**: ✅ Run automated script
**Minute 1**: ✅ Manual spot checks (health, circuit breaker, PII)
**Minute 2**: ✅ Start 3 monitoring terminals
**Minute 3**: ✅ Verify no immediate errors
**Minute 4**: ✅ Check circuit breaker stable
**Minute 5**: ✅ Verify rate limiter working
**Minute 6**: ✅ Re-check PII exposure
**Minute 7**: ✅ Review error patterns
**Minute 8**: ✅ Check user activity logs
**Minute 9**: ✅ Verify performance (response time)
**Minute 10**: ✅ Final decision: Continue or Rollback

---

## 🎯 DECISION MATRIX

### ✅ CONTINUE (All Clear)
- All automated tests PASS
- Zero PII in logs
- Circuit breakers closed
- No error spikes
- Users not complaining

**Action**: Continue monitoring for 1 hour

### ⚠️ INVESTIGATE (Warnings)
- 1-2 automated tests WARN
- Elevated error count
- Circuit breaker flapping
- Performance degraded

**Action**: Monitor closely for 30 minutes, prepare rollback

### 🚨 ROLLBACK (Critical Issues)
- Any automated test FAIL
- PII visible in logs
- Circuit breaker stuck open
- Application crashes
- User failures

**Action**: Execute emergency rollback immediately

---

## 📊 ONE-COMMAND HEALTH SNAPSHOT

```bash
echo "=== HEALTH ===" && curl -s http://localhost/api/health | jq -r .status && \
echo "=== CB STATE ===" && redis-cli GET circuit_breaker:calcom_api:state && \
echo "=== RATE LIMIT ===" && redis-cli GET "calcom_api_rate_limit:$(date +'%Y-%m-%d-%H-%M')" && \
echo "=== ERRORS ===" && tail -20 storage/logs/laravel.log | grep -c ERROR && \
echo "=== PII CHECK ===" && grep -cE "[a-zA-Z0-9._%+-]+@" storage/logs/laravel-$(date +%Y-%m-%d).log | grep -v REDACTED
```

**Run every 5 minutes during monitoring period**

---

## 📞 EMERGENCY CONTACTS

**DevOps Lead**: [Your Contact]
**Backend Lead**: [Your Contact]
**On-Call Engineer**: [Your Contact]

---

## 📚 DETAILED DOCUMENTATION

- **Full Strategy**: `claudedocs/production-verification-strategy.md`
- **Rollback Guide**: `claudedocs/emergency-rollback-procedure.md`
- **Monitoring Commands**: `claudedocs/monitoring-quick-reference.md`
- **Summary**: `claudedocs/PRODUCTION-TESTING-SUMMARY.md`

---

## 💡 REMEMBER

1. **Safety First**: When in doubt, rollback
2. **No Synthetic Tests**: Only observe natural traffic
3. **PII is Critical**: Zero tolerance for exposure
4. **Quick Decisions**: Don't wait if something is wrong
5. **Document Everything**: Note what you observe

---

**Good luck! You've got this! 🚀**

**Total Time**: 18 minutes active + 1 hour passive monitoring
**Rollback Time**: 5 minutes if needed
**Risk**: LOW (non-destructive tests, quick rollback available)
