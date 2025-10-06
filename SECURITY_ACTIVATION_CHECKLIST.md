# Security Activation Checklist - Quick Reference
**Date**: 2025-10-01
**Purpose**: Production deployment security verification
**Status**: ✅ READY FOR ACTIVATION

---

## 🔴 BEFORE RESTART - Critical Checks

| Check | Command | Expected Result | Status |
|-------|---------|-----------------|--------|
| **Tenant Context** | `grep -c "setTenantContext" app/Http/Controllers/RetellFunctionCallHandler.php` | ≥ 4 usages | ✅ PASS |
| **Log Sanitization** | `grep -c "LogSanitizer" app/Http/Controllers/Retell*.php` | ≥ 6 usages | ✅ PASS |
| **Rate Limiter Routes** | `grep -c "retell.call.ratelimit" routes/api.php` | 3 routes | ✅ PASS |
| **Middleware Registered** | `grep -q "retell.call.ratelimit" app/Http/Kernel.php && echo "OK"` | OK | ✅ PASS |

**Quick Verification**:
```bash
./SECURITY_VERIFICATION_COMMANDS.sh pre
```

---

## 🟡 AFTER RESTART - Immediate Verification (15 minutes)

### 1. Multi-Tenant Isolation
**First Availability Check** → Verify tenant context in cache keys

```bash
# Check cache keys
redis-cli --scan --pattern "availability:*" | head -3
# Expected: availability:{company_id}:{branch_id}:{date}:{service}

# Monitor live
tail -f storage/logs/laravel.log | grep "setTenantContext"
```

**Success**: ✅ Cache keys contain `{company_id}:{branch_id}`
**Failure**: ❌ Generic keys without tenant → **ROLLBACK**

---

### 2. Log Sanitization
**First Webhook** → Verify PII redaction

```bash
# Check for PII leaks
tail -1000 storage/logs/laravel.log | grep -E "@|phone" | grep -v "REDACTED"
# Expected: No output

# Monitor live
./SECURITY_VERIFICATION_COMMANDS.sh pii
```

**Success**: ✅ All PII shows `[REDACTED]` or `[PII_REDACTED]`
**Failure**: ❌ Plain emails/phones visible → **ROLLBACK**

---

### 3. Rate Limiter
**First Function Call** → Verify rate limiter active

```bash
# Check Redis keys
redis-cli KEYS "retell_call_*"
# Expected: Keys like retell_call_total:{call_id}

# Check route middleware
php artisan route:list | grep "retell/function" | grep "retell.call.ratelimit"
```

**Success**: ✅ Rate limiter keys created, middleware active
**Failure**: ❌ No Redis keys → **INVESTIGATE**

---

### 4. System Health
**Overall Stability** → No errors, normal response times

```bash
# Check for errors
tail -100 storage/logs/laravel.log | grep ERROR
# Expected: No critical errors

# Check webhook processing
tail -100 storage/logs/laravel.log | grep "Webhook" | grep "sanitize"
```

**Success**: ✅ No 500 errors, webhooks processing normally
**Failure**: ❌ Errors or failures → **INVESTIGATE**

---

## 🔍 Real-Time Monitoring Commands

### Monitor Everything
```bash
# Interactive menu
./SECURITY_VERIFICATION_COMMANDS.sh

# Or specific monitors
./SECURITY_VERIFICATION_COMMANDS.sh pii       # PII leak detection
./SECURITY_VERIFICATION_COMMANDS.sh tenant    # Tenant context
./SECURITY_VERIFICATION_COMMANDS.sh rate      # Rate limiter
./SECURITY_VERIFICATION_COMMANDS.sh webhook   # Webhook activity
```

### Quick Status Check
```bash
./SECURITY_VERIFICATION_COMMANDS.sh status
```

---

## 🚨 ROLLBACK TRIGGERS

### Immediate Rollback (CRITICAL)

| Trigger | Detection | Action |
|---------|-----------|--------|
| **Cross-Tenant Data** | Redis keys without tenant scope | ⚠️ **ROLLBACK NOW** |
| **PII in Logs** | Plain emails/phones in logs | ⚠️ **ROLLBACK NOW** |
| **Middleware Bypass** | Routes accessible without rate limiter | ⚠️ **ROLLBACK NOW** |

### Investigate (HIGH)

| Trigger | Detection | Action |
|---------|-----------|--------|
| **Rate Limiter Blocking Legit Traffic** | >50% of requests getting 429 | 🔍 **INVESTIGATE** |
| **Repeated Errors** | >10 errors/minute | 🔍 **INVESTIGATE** |

---

## 🔧 Emergency Fixes

### Clear Tenant Cache (if contamination)
```bash
redis-cli KEYS "availability:*" | xargs redis-cli DEL
redis-cli KEYS "retell_call_*" | xargs redis-cli DEL
```

### Rollback to Previous Version
```bash
cd /var/www/api-gateway
git log --oneline -5  # Find previous commit
git checkout <previous-commit-hash>
composer install --no-dev
php artisan config:cache
sudo systemctl reload php8.2-fpm
```

### Disable Rate Limiter (Emergency Only)
```bash
# Temporarily remove from routes
sed -i 's/, "retell.call.ratelimit"//' routes/api.php
php artisan config:cache
sudo systemctl reload php8.2-fpm
```

---

## ✅ Success Criteria (First Hour)

### Tenant Isolation
- ✅ 100% of cache keys include `{company_id}:{branch_id}`
- ✅ No cross-tenant data access
- ✅ Logs show tenant context for all operations

### Log Sanitization
- ✅ Zero PII leaks in logs
- ✅ All sensitive data shows `[REDACTED]` markers
- ✅ Headers and bodies properly sanitized

### Rate Limiter
- ✅ Redis keys created for call tracking
- ✅ Response headers include `X-Call-RateLimit-*`
- ✅ Normal traffic not blocked

### System Stability
- ✅ Zero 500 errors
- ✅ Response times < 500ms
- ✅ Webhooks processing successfully

---

## 📊 Verification Timeline

**Minute 0** - Restart complete
- Run post-activation checks

**Minute 5** - First traffic
- Check tenant isolation
- Verify log sanitization
- Confirm rate limiter active

**Minute 15** - Traffic flowing
- Review security status report
- Check for any anomalies
- Validate success criteria

**Hour 1** - Stable operation
- Full security audit
- Document any issues
- Confirm all systems operational

---

## 📞 Quick Commands Reference

```bash
# Pre-restart verification
./SECURITY_VERIFICATION_COMMANDS.sh pre

# Post-restart verification
./SECURITY_VERIFICATION_COMMANDS.sh post

# Status report
./SECURITY_VERIFICATION_COMMANDS.sh status

# Live monitoring
./SECURITY_VERIFICATION_COMMANDS.sh        # Interactive menu
./SECURITY_VERIFICATION_COMMANDS.sh pii    # PII leak detection
./SECURITY_VERIFICATION_COMMANDS.sh tenant # Tenant context
./SECURITY_VERIFICATION_COMMANDS.sh rate   # Rate limiter

# Check specific security aspects
grep -c "setTenantContext" app/Http/Controllers/RetellFunctionCallHandler.php
tail -1000 storage/logs/laravel.log | grep -E "@" | grep -v "REDACTED"
redis-cli KEYS "retell_call_*"
php artisan route:list | grep retell
```

---

## 🎯 Production Activation Ready

**Security Fixes Deployed**:
- ✅ Multi-tenant cache isolation (VULN-003)
- ✅ Log sanitization (GDPR compliance)
- ✅ Rate limiter (DoS protection)
- ✅ Input validation throughout

**Verification Tools**:
- ✅ Interactive verification script
- ✅ Real-time monitoring commands
- ✅ Automated status reports
- ✅ Emergency rollback procedures

**Next Steps**:
1. Run `./SECURITY_VERIFICATION_COMMANDS.sh pre`
2. Restart application
3. Run `./SECURITY_VERIFICATION_COMMANDS.sh post`
4. Monitor for 15 minutes
5. Review success criteria

**Documentation**:
- Full plan: `/var/www/api-gateway/claudedocs/PRODUCTION_SECURITY_VERIFICATION_PLAN.md`
- This checklist: `/var/www/api-gateway/SECURITY_ACTIVATION_CHECKLIST.md`
- Verification script: `/var/www/api-gateway/SECURITY_VERIFICATION_COMMANDS.sh`

---

**Ready for Production** ✅
**Date**: 2025-10-01
**Status**: All security checks passed, monitoring in place
