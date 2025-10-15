# Emergency Rollback Procedure
## Quick Reference Card - Production Incident Response

**Last Updated**: 2025-10-01
**Status**: Production Environment
**Purpose**: Emergency rollback for failed deployment

---

## 🚨 IMMEDIATE ROLLBACK TRIGGERS

Execute rollback immediately if ANY of these occur:

1. **PII/Secrets Visible in Logs** (GDPR violation)
   - Email addresses not redacted
   - Bearer tokens visible in plain text
   - API keys exposed

2. **Circuit Breaker Stuck Open**
   - Cal.com API inaccessible
   - Users cannot book appointments
   - Service degraded for >5 minutes

3. **Application Crashes**
   - HTTP 500 errors
   - Fatal PHP errors
   - Database connection failures
   - Memory exhaustion

4. **User-Facing Failures**
   - Booking failure rate >10%
   - Webhook processing failures
   - Payment processing errors
   - Data corruption detected

5. **Rate Limiting Failure**
   - Cal.com API rate limit exceeded
   - Rate limiter not functioning
   - Request flooding detected

---

## ⚡ EMERGENCY ROLLBACK (5 Minutes)

### Step 1: Identify Previous Commit (30 seconds)
```bash
cd /var/www/api-gateway

# View recent commits
git log --oneline -10

# Find the commit BEFORE deployment
# Example output:
# abc1234 (HEAD) Add new components
# def5678 Previous stable version  <-- ROLLBACK TO THIS
```

### Step 2: Execute Rollback (60 seconds)
```bash
# Checkout previous commit
git checkout def5678  # Replace with actual commit hash

# Or if you tagged the previous version:
git checkout production-stable-2025-09-30
```

### Step 3: Clear All Caches (30 seconds)
```bash
# Clear application caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Clear OPcache
php artisan optimize:clear
```

### Step 4: Clear Redis State (30 seconds)
```bash
# OPTION A: Clear all Redis (safest)
redis-cli FLUSHDB

# OPTION B: Clear only circuit breaker state (selective)
redis-cli DEL $(redis-cli KEYS "circuit_breaker:*")
redis-cli DEL $(redis-cli KEYS "calcom_api_rate_limit:*")
```

### Step 5: Restart Services (60 seconds)
```bash
# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Restart Nginx (if needed)
sudo systemctl restart nginx

# Restart Queue Workers (if running)
php artisan queue:restart
```

### Step 6: Verify Rollback (90 seconds)
```bash
# Test health endpoint
curl http://localhost/api/health

# Expected output:
# {"status":"healthy","timestamp":"2025-10-01T..."}

# Check for immediate errors
tail -20 storage/logs/laravel.log

# Verify services are responding
curl -I http://localhost/api/v2/test
# Expected: HTTP/1.1 200 OK
```

### Step 7: Monitor Stability (60 seconds)
```bash
# Watch logs for errors
tail -f storage/logs/laravel.log | grep -i "error\|critical\|exception"

# Monitor for 2 minutes to confirm stability
# Press Ctrl+C when stable
```

---

## 📋 POST-ROLLBACK CHECKLIST

### Immediate Verification (5 minutes)
- [ ] Application returns HTTP 200 for health endpoint
- [ ] No fatal errors in last 20 log lines
- [ ] Users can access the application
- [ ] Bookings are processing successfully
- [ ] Webhooks are functioning
- [ ] No circuit breakers stuck open

### Communication (10 minutes)
- [ ] Notify team of rollback in Slack/Discord
- [ ] Log incident in incident management system
- [ ] Update status page if applicable
- [ ] Prepare incident summary

### Root Cause Analysis (30 minutes)
- [ ] Document what triggered the rollback
- [ ] Capture relevant logs and error messages
- [ ] Identify which component caused the failure
- [ ] Create post-mortem action items

---

## 🔍 DIAGNOSTIC COMMANDS

### Quick System Health Check
```bash
# Application status
curl -s http://localhost/api/health | jq .

# Recent errors
tail -50 storage/logs/laravel-$(date +%Y-%m-%d).log | grep ERROR

# Circuit breaker states
redis-cli KEYS "circuit_breaker:*:state" | while read key; do
  echo "$key: $(redis-cli GET $key)"
done

# Rate limiter status
redis-cli KEYS "calcom_api_rate_limit:*" | while read key; do
  echo "$key: $(redis-cli GET $key)"
done

# PHP-FPM status
sudo systemctl status php8.3-fpm

# Nginx status
sudo systemctl status nginx

# Disk space
df -h /var/www

# Memory usage
free -h
```

### Check for PII Exposure
```bash
# Check today's logs for exposed emails
grep -E "[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" \
  storage/logs/laravel-$(date +%Y-%m-%d).log | \
  grep -v "REDACTED" | \
  head -10

# Check for exposed bearer tokens
grep "Bearer" storage/logs/laravel-$(date +%Y-%m-%d).log | \
  grep -v "REDACTED" | \
  head -10

# Check for exposed API keys (long hex strings)
grep -E "[a-f0-9]{32,}" storage/logs/laravel-$(date +%Y-%m-%d).log | \
  grep -v "REDACTED" | \
  head -10
```

---

## 🛡️ SAFE MODE OPERATION

If rollback is not possible, enable safe mode:

### Disable New Features
```bash
# Option 1: Environment variable (requires restart)
echo "FEATURE_CIRCUIT_BREAKER=false" >> .env
echo "FEATURE_LOG_SANITIZER=false" >> .env
php artisan config:clear

# Option 2: Feature flag in database
php artisan tinker
\DB::table('feature_flags')->where('name', 'circuit_breaker')->update(['enabled' => false]);
\DB::table('feature_flags')->where('name', 'log_sanitizer')->update(['enabled' => false]);
exit
```

### Bypass Circuit Breakers
```bash
# Reset all circuit breakers to closed state
redis-cli KEYS "circuit_breaker:*:state" | while read key; do
  redis-cli SET "$key" "closed"
done

# Clear failure counts
redis-cli DEL $(redis-cli KEYS "circuit_breaker:*:failures")
```

### Disable Rate Limiting
```bash
# Clear rate limit counters
redis-cli DEL $(redis-cli KEYS "calcom_api_rate_limit:*")

# Temporarily disable in code (requires code change)
# Edit: app/Services/CalcomApiRateLimiter.php
# Change: MAX_REQUESTS_PER_MINUTE = 1000 (very high)
```

---

## 📞 ESCALATION CONTACTS

### Internal Team
- **DevOps Lead**: [Contact Info]
- **Backend Lead**: [Contact Info]
- **On-Call Engineer**: [Contact Info]

### External Services
- **Cal.com Support**: support@cal.com
- **Redis Support**: [If applicable]
- **Hosting Provider**: [Provider contact]

---

## 📊 INCIDENT SEVERITY LEVELS

### P0 - Critical (Immediate Rollback)
- Complete service outage
- Data loss or corruption
- Security breach (PII exposure)
- Payment processing failure

**Action**: Immediate rollback, no questions asked

### P1 - High (Consider Rollback)
- Partial service degradation
- High error rate (>5%)
- Circuit breakers stuck open
- Performance degradation >50%

**Action**: Assess impact, rollback if not resolved in 5 minutes

### P2 - Medium (Monitor)
- Elevated error rate (<5%)
- Single component failure
- Performance degradation <50%
- Non-critical features affected

**Action**: Monitor closely, prepare rollback plan

### P3 - Low (Log and Fix)
- Minor bugs
- Cosmetic issues
- Non-critical warnings
- Performance degradation <10%

**Action**: Log issue, fix in next deployment

---

## 🔄 ROLLBACK ALTERNATIVES

If full rollback is not viable, consider:

### Partial Rollback
```bash
# Revert specific files
git checkout def5678 -- app/Services/CircuitBreaker.php
git checkout def5678 -- app/Helpers/LogSanitizer.php

# Clear caches
php artisan config:clear
php artisan cache:clear
```

### Feature Flags
```bash
# Disable specific features via config
php artisan config:set features.circuit_breaker false
php artisan config:set features.log_sanitizer false
```

### Traffic Shaping
```bash
# Reduce traffic to affected services
# (If load balancer available)
# Redirect traffic to backup server
```

---

## 📝 ROLLBACK LOG TEMPLATE

```
=== EMERGENCY ROLLBACK LOG ===
Date: [YYYY-MM-DD HH:MM:SS]
Trigger: [What caused the rollback?]
Severity: [P0/P1/P2/P3]
Rolled Back From: [commit hash]
Rolled Back To: [commit hash]
Services Restarted: [List services]
User Impact: [Describe impact]
Duration: [Outage duration]
Root Cause: [If known]
Action Items: [Next steps]
Executed By: [Your name]
```

---

## ✅ VERIFICATION AFTER ROLLBACK

### 1-Minute Check
```bash
# Health check
curl http://localhost/api/health

# Recent errors
tail -20 storage/logs/laravel.log | grep -i error

# Service status
sudo systemctl status php8.3-fpm nginx
```

### 5-Minute Check
```bash
# Monitor logs
tail -f storage/logs/laravel.log

# Check user activity
tail -f storage/logs/laravel.log | grep -i "webhook\|booking"

# Circuit breaker states
redis-cli KEYS "circuit_breaker:*" | wc -l
```

### 15-Minute Check
```bash
# Error rate comparison
BEFORE_ROLLBACK_ERRORS=50  # Note this before rollback
AFTER_ROLLBACK_ERRORS=$(grep -c ERROR storage/logs/laravel.log)
echo "Errors before: $BEFORE_ROLLBACK_ERRORS"
echo "Errors after: $AFTER_ROLLBACK_ERRORS"

# Success indicator: Errors should decrease or stop
```

---

## 🎯 SUCCESS CRITERIA

Rollback is successful when:
- ✅ Application returns HTTP 200 for health checks
- ✅ Error rate returns to normal (<5 errors/hour)
- ✅ Users report no issues
- ✅ All critical services operational
- ✅ No PII/secrets in logs
- ✅ Circuit breakers in closed state
- ✅ Performance metrics normal

---

## 🚀 SAFE REDEPLOYMENT CHECKLIST

Before attempting redeployment:

### Code Review
- [ ] Root cause identified and fixed
- [ ] Unit tests added for failure scenario
- [ ] Integration tests pass
- [ ] Security review completed

### Testing
- [ ] Tested in local environment
- [ ] Tested with production-like data
- [ ] Load testing completed
- [ ] Security scanning completed

### Monitoring
- [ ] Additional monitoring in place
- [ ] Alerting configured for failure conditions
- [ ] Rollback plan updated
- [ ] On-call engineer available

### Communication
- [ ] Team notified of redeployment
- [ ] Deployment window scheduled
- [ ] Stakeholders informed
- [ ] Post-mortem completed

---

## 📚 ADDITIONAL RESOURCES

- **Full Verification Strategy**: `claudedocs/production-verification-strategy.md`
- **Architecture Documentation**: `claudedocs/architecture.md`
- **Incident Playbook**: `claudedocs/incident-response.md`
- **Git History**: `git log --graph --oneline --all`

---

**Remember**: When in doubt, ROLLBACK. It's faster to rollback and fix than to debug in production.

**Mantra**: "Rollback first, ask questions later."
