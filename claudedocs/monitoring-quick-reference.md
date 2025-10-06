# Production Monitoring Quick Reference
## Real-Time Monitoring Commands for New Components

**Date**: 2025-10-01
**Purpose**: Quick copy-paste commands for monitoring production health

---

## 🎯 ONE-COMMAND HEALTH CHECK

```bash
# Complete system health snapshot
echo "=== SYSTEM HEALTH ===" && \
curl -s http://localhost/api/health | jq . && \
echo "" && \
echo "=== CIRCUIT BREAKERS ===" && \
redis-cli KEYS "circuit_breaker:*:state" | while read key; do echo "$key: $(redis-cli GET $key)"; done && \
echo "" && \
echo "=== RATE LIMITERS ===" && \
redis-cli GET "calcom_api_rate_limit:$(date +'%Y-%m-%d-%H-%M')" && \
echo "" && \
echo "=== RECENT ERRORS ===" && \
tail -20 storage/logs/laravel-$(date +%Y-%m-%d).log | grep -i error | tail -5 && \
echo "" && \
echo "=== CACHE STATS ===" && \
redis-cli INFO stats | grep -E "keyspace_hits|keyspace_misses"
```

---

## 📊 CONTINUOUS MONITORING DASHBOARDS

### Dashboard 1: Error Watch (Critical)
**Purpose**: Catch errors as they happen
**Terminal**: Keep open in split screen

```bash
# Watch for any errors, exceptions, or critical issues
tail -f storage/logs/laravel.log | grep -i --color=always "error\|exception\|critical\|fatal"
```

**What to look for**:
- 🔴 Stack traces (Exception in...)
- 🔴 "CRITICAL" level messages
- 🔴 Database connection errors
- 🔴 Timeout errors

---

### Dashboard 2: Circuit Breaker Monitor
**Purpose**: Watch for Cal.com API issues
**Update Interval**: Every 10 seconds

```bash
# Monitor circuit breaker state changes
watch -n 10 -c 'echo "=== CIRCUIT BREAKER STATUS ==="; \
redis-cli KEYS "circuit_breaker:*:state" | while read key; do \
  state=$(redis-cli GET "$key"); \
  failures=$(redis-cli GET "${key/:state/:failures}"); \
  echo "$key: $state (failures: $failures)"; \
done; \
echo ""; \
echo "=== RECENT CB EVENTS ==="; \
tail -10 storage/logs/laravel.log | grep -i "circuit breaker"'
```

**What to look for**:
- 🟢 State: "closed" (normal)
- 🟡 State: "half_open" (testing recovery)
- 🔴 State: "open" (service down)
- 🔴 Failures: >3 (approaching threshold)

---

### Dashboard 3: Rate Limiter Monitor
**Purpose**: Watch API request throttling
**Update Interval**: Every 5 seconds

```bash
# Monitor rate limit usage
watch -n 5 'echo "=== RATE LIMIT STATUS ==="; \
current_minute=$(date +"%Y-%m-%d-%H-%M"); \
count=$(redis-cli GET "calcom_api_rate_limit:$current_minute" 2>/dev/null || echo "0"); \
remaining=$((60 - count)); \
echo "Current minute: $current_minute"; \
echo "Requests used: $count / 60"; \
echo "Remaining: $remaining"; \
echo ""; \
if [ "$count" -gt 45 ]; then \
  echo "⚠️  WARNING: High rate limit usage!"; \
elif [ "$count" -gt 55 ]; then \
  echo "🔴 CRITICAL: Rate limit nearly exhausted!"; \
fi; \
echo ""; \
echo "=== RATE LIMIT LOG ==="; \
tail -5 storage/logs/calcom.log | grep -i "rate limit"'
```

**What to look for**:
- 🟢 Usage: 0-40 requests (normal)
- 🟡 Usage: 41-55 requests (elevated)
- 🔴 Usage: 56-60 requests (critical)
- 🔴 "Rate limit reached" warnings

---

### Dashboard 4: PII Exposure Monitor (GDPR)
**Purpose**: Detect log sanitization failures
**Frequency**: Every 5 minutes

```bash
# Check for PII exposure (run periodically, not continuously)
watch -n 300 'echo "=== PII EXPOSURE CHECK ==="; \
log_file="storage/logs/laravel-$(date +%Y-%m-%d).log"; \
if [ -f "$log_file" ]; then \
  emails=$(grep -cE "[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" "$log_file" 2>/dev/null | grep -v "REDACTED" || echo "0"); \
  tokens=$(grep -c "Bearer [A-Za-z0-9]" "$log_file" 2>/dev/null || echo "0"); \
  echo "Exposed emails: $emails"; \
  echo "Exposed tokens: $tokens"; \
  if [ "$emails" -gt 0 ] || [ "$tokens" -gt 0 ]; then \
    echo ""; \
    echo "🚨 PII EXPOSURE DETECTED!"; \
    echo "This is a GDPR compliance violation."; \
    echo "Review logs immediately!"; \
  else \
    echo ""; \
    echo "✅ No PII exposure detected"; \
  fi; \
else \
  echo "Log file not found"; \
fi'
```

**What to look for**:
- 🟢 Exposed emails: 0 (compliant)
- 🔴 Exposed emails: >0 (GDPR violation)
- 🔴 Exposed tokens: >0 (security issue)

---

### Dashboard 5: Performance Monitor
**Purpose**: Track response times and slow queries
**Update Interval**: Every 30 seconds

```bash
# Monitor performance metrics
watch -n 30 'echo "=== RESPONSE TIME ==="; \
time=$(curl -o /dev/null -s -w "%{time_total}" http://localhost/api/health); \
echo "Health endpoint: ${time}s"; \
if (( $(echo "$time < 0.5" | bc -l) )); then \
  echo "✅ Excellent"; \
elif (( $(echo "$time < 1.0" | bc -l) )); then \
  echo "✅ Good"; \
elif (( $(echo "$time < 2.0" | bc -l) )); then \
  echo "⚠️  Elevated"; \
else \
  echo "🔴 Too slow"; \
fi; \
echo ""; \
echo "=== SLOW QUERIES ==="; \
tail -20 storage/logs/laravel.log | grep -i "slow\|timeout" | tail -5; \
echo ""; \
echo "=== CACHE HIT RATIO ==="; \
hits=$(redis-cli INFO stats | grep keyspace_hits | cut -d: -f2 | tr -d "\r"); \
misses=$(redis-cli INFO stats | grep keyspace_misses | cut -d: -f2 | tr -d "\r"); \
total=$((hits + misses)); \
if [ "$total" -gt 0 ]; then \
  ratio=$((hits * 100 / total)); \
  echo "Hit ratio: ${ratio}% ($hits hits, $misses misses)"; \
else \
  echo "No cache activity"; \
fi'
```

**What to look for**:
- 🟢 Response time: <0.5s (excellent)
- 🟡 Response time: 0.5-1s (good)
- 🔴 Response time: >2s (too slow)
- 🟢 Cache hit ratio: >70% (good)
- 🔴 Cache hit ratio: <50% (poor)

---

### Dashboard 6: User Activity Monitor
**Purpose**: Watch real user interactions
**Terminal**: Keep open to see live traffic

```bash
# Monitor user activity (webhooks, bookings, API calls)
tail -f storage/logs/laravel.log | grep -i --color=always "webhook\|booking\|retell\|calcom"
```

**What to look for**:
- 🟢 Regular webhook processing
- 🟢 Successful booking confirmations
- 🔴 Webhook failures
- 🔴 Booking errors

---

## 🔍 DIAGNOSTIC ONE-LINERS

### Quick Health Checks
```bash
# Application responsive?
curl -s http://localhost/api/health | jq -r .status

# Any errors in last 5 minutes?
tail -100 storage/logs/laravel.log | grep -c ERROR

# Circuit breaker healthy?
redis-cli GET circuit_breaker:calcom_api:state

# Rate limit status?
redis-cli GET "calcom_api_rate_limit:$(date +'%Y-%m-%d-%H-%M')"

# Redis accessible?
redis-cli PING

# Services running?
sudo systemctl is-active php8.3-fpm nginx redis-server
```

### PII Exposure Quick Check
```bash
# Check for exposed emails in logs (CRITICAL)
grep -E "[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" \
  storage/logs/laravel-$(date +%Y-%m-%d).log | \
  grep -v REDACTED | \
  wc -l

# Expected: 0 (zero emails exposed)
# If >0: IMMEDIATE ROLLBACK REQUIRED
```

### Circuit Breaker Quick Check
```bash
# All circuit breaker states
redis-cli --scan --pattern "circuit_breaker:*:state" | \
while read key; do echo "$key: $(redis-cli GET $key)"; done

# Expected: All "closed" or "not_set"
```

### Rate Limiter Quick Check
```bash
# Current minute's request count
php artisan tinker --execute="echo (new \App\Services\CalcomApiRateLimiter())->getRemainingRequests();"

# Expected: Number between 0-60
```

---

## 📈 METRIC COLLECTION

### Collect Hourly Metrics
```bash
#!/bin/bash
# Save to: scripts/collect-metrics.sh
# Run via cron: */60 * * * * /var/www/api-gateway/scripts/collect-metrics.sh

TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")
LOG_FILE="storage/logs/metrics-$(date +%Y-%m-%d).log"

echo "[$TIMESTAMP] Metrics Collection" >> $LOG_FILE

# Error count
ERROR_COUNT=$(grep -c ERROR storage/logs/laravel-$(date +%Y-%m-%d).log 2>/dev/null || echo "0")
echo "  Errors: $ERROR_COUNT" >> $LOG_FILE

# Circuit breaker states
CB_OPEN=$(redis-cli --scan --pattern "circuit_breaker:*:state" | xargs redis-cli MGET | grep -c "open" || echo "0")
echo "  Circuit breakers open: $CB_OPEN" >> $LOG_FILE

# Cache hit ratio
HITS=$(redis-cli INFO stats | grep keyspace_hits | cut -d: -f2 | tr -d '\r')
MISSES=$(redis-cli INFO stats | grep keyspace_misses | cut -d: -f2 | tr -d '\r')
TOTAL=$((HITS + MISSES))
if [ "$TOTAL" -gt 0 ]; then
  RATIO=$((HITS * 100 / TOTAL))
  echo "  Cache hit ratio: ${RATIO}%" >> $LOG_FILE
fi

# Response time
RESPONSE_TIME=$(curl -o /dev/null -s -w '%{time_total}' http://localhost/api/health)
echo "  Response time: ${RESPONSE_TIME}s" >> $LOG_FILE

echo "" >> $LOG_FILE
```

---

## 🚨 ALERT THRESHOLDS

### Critical Alerts (Immediate Action)
```bash
# Set up alerts for these conditions:

# 1. Circuit breaker opens
redis-cli GET circuit_breaker:calcom_api:state | grep -q "open" && \
  echo "🚨 CRITICAL: Circuit breaker is OPEN!"

# 2. PII exposure detected
[ "$(grep -cE '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}' storage/logs/laravel-$(date +%Y-%m-%d).log | grep -v REDACTED)" -gt 0 ] && \
  echo "🚨 CRITICAL: PII exposure detected in logs!"

# 3. Error rate spike
[ "$(grep -c ERROR storage/logs/laravel-$(date +%Y-%m-%d).log)" -gt 100 ] && \
  echo "🚨 CRITICAL: High error count!"

# 4. Response time degradation
time=$(curl -o /dev/null -s -w '%{time_total}' http://localhost/api/health)
(( $(echo "$time > 2.0" | bc -l) )) && \
  echo "🚨 CRITICAL: Response time too slow: ${time}s"
```

### Warning Alerts (Monitor Closely)
```bash
# 1. Rate limit high usage
count=$(redis-cli GET "calcom_api_rate_limit:$(date +'%Y-%m-%d-%H-%M')" || echo "0")
[ "$count" -gt 50 ] && \
  echo "⚠️  WARNING: Rate limit usage high: $count/60"

# 2. Cache hit ratio low
hits=$(redis-cli INFO stats | grep keyspace_hits | cut -d: -f2 | tr -d '\r')
misses=$(redis-cli INFO stats | grep keyspace_misses | cut -d: -f2 | tr -d '\r')
total=$((hits + misses))
[ "$total" -gt 0 ] && [ "$((hits * 100 / total))" -lt 70 ] && \
  echo "⚠️  WARNING: Low cache hit ratio"

# 3. Circuit breaker high failures
failures=$(redis-cli GET circuit_breaker:calcom_api:failures || echo "0")
[ "$failures" -gt 3 ] && \
  echo "⚠️  WARNING: Circuit breaker approaching threshold: $failures/5"
```

---

## 📋 MONITORING CHECKLIST

### Every 5 Minutes
- [ ] Check Dashboard 1 (Error Watch) for new errors
- [ ] Verify no PII exposure alerts
- [ ] Check circuit breaker states

### Every 15 Minutes
- [ ] Review rate limiter usage
- [ ] Check cache hit ratio
- [ ] Review performance metrics

### Every Hour
- [ ] Collect and log metrics
- [ ] Review error patterns
- [ ] Check for slow queries
- [ ] Verify service health

### Daily
- [ ] Generate daily summary report
- [ ] Review accumulated metrics
- [ ] Analyze error trends
- [ ] Update monitoring thresholds if needed

---

## 🔗 QUICK LINKS

- **Full Verification Strategy**: `claudedocs/production-verification-strategy.md`
- **Emergency Rollback**: `claudedocs/emergency-rollback-procedure.md`
- **Run Automated Tests**: `./scripts/verify-production-deployment.sh`

---

## 💡 TIPS

1. **Use tmux/screen**: Run multiple dashboards in split panes
   ```bash
   tmux new-session -s monitoring
   # Ctrl+B then " to split horizontally
   # Ctrl+B then % to split vertically
   ```

2. **Color Coding**: Use grep --color=always for better visibility

3. **Save Output**: Redirect to files for later analysis
   ```bash
   tail -f storage/logs/laravel.log > /tmp/monitoring-$(date +%Y%m%d-%H%M).log
   ```

4. **Remote Monitoring**: SSH with port forwarding if monitoring remotely
   ```bash
   ssh -L 6379:localhost:6379 user@production-server
   ```

5. **Alerting**: Set up email/SMS alerts for critical conditions
   ```bash
   # Add to crontab for critical checks
   */5 * * * * /var/www/api-gateway/scripts/alert-if-critical.sh
   ```

---

**Remember**: Continuous monitoring is key to catching issues before users do!
