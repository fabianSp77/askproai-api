# 🆘 Retell.ai Emergency Procedures

> ⚠️ **Critical Issues Quick Response Guide**  
> Last Updated: January 10, 2025 | Response Time Target: < 5 minutes

---

## 🚨 When to Use This Guide

Use these procedures when experiencing:
- ❌ No incoming calls processed
- ❌ Webhook failures (>10% error rate)
- ❌ Agent not responding or errors
- ❌ API connection timeouts
- ❌ Customer complaints about call quality

---

## 🔴 LEVEL 1: Complete System Down

### Symptoms
- No calls being answered
- Retell dashboard shows disconnected
- Multiple webhook failures

### Immediate Actions

```bash
# 1. Run emergency diagnostic
php retell-health-check.php

# 2. Check core services
php artisan horizon:status
systemctl status php8.3-fpm
systemctl status nginx

# 3. Restart if needed
php artisan horizon:terminate && php artisan horizon
```

### Manual Fallback
```bash
# Enable manual call forwarding
php artisan retell:enable-fallback --number="+49XXXXXXXXX"

# Import missed calls later
php import-retell-calls-manual.php --from="1 hour ago"
```

---

## 🟡 LEVEL 2: Partial Failures

### Issue: Webhooks Not Processing

#### Quick Diagnosis
```bash
# Check webhook logs
tail -f storage/logs/retell-webhooks.log | grep ERROR

# Verify signature
curl -X POST https://api.askproai.de/api/retell/webhook \
  -H "x-retell-signature: test" \
  -H "Content-Type: application/json" \
  -d '{"test": true}'
```

#### Fix Steps
1. **Clear webhook queue**
   ```bash
   php artisan queue:clear webhooks
   php artisan queue:retry all
   ```

2. **Re-register webhooks**
   ```bash
   php artisan retell:register-webhooks --force
   ```

3. **Verify in Retell Dashboard**
   - Login to https://app.retellai.com
   - Check webhook URL: `https://api.askproai.de/api/retell/webhook`
   - Ensure all events enabled

### Issue: Agent Malfunction

#### Quick Diagnosis
```bash
# Test agent directly
php artisan retell:test-agent {agent_id}

# Check function logs
tail -f storage/logs/retell-functions.log
```

#### Fix Steps
1. **Reset agent configuration**
   ```bash
   php artisan retell:reset-agent {agent_id}
   ```

2. **Reload custom functions**
   ```bash
   php artisan retell:reload-functions
   php artisan cache:forget "retell_functions_*"
   ```

3. **Emergency prompt override**
   ```php
   // Temporary safe prompt
   $agent->prompt = "Du bist ein Assistent. Bitte den Anrufer um Geduld und notiere seine Kontaktdaten.";
   $agent->save();
   ```

---

## 🟢 LEVEL 3: Performance Issues

### Issue: Slow Response Times

#### Quick Diagnosis
```bash
# Check queue sizes
php artisan horizon:metrics

# Database query performance
mysql -u root -p'V9LGz2tdR5gpDQz' -e "SHOW PROCESSLIST;"
```

#### Fix Steps
1. **Clear caches**
   ```bash
   php artisan cache:clear
   php artisan config:cache
   php artisan route:cache
   ```

2. **Optimize queues**
   ```bash
   php artisan horizon:pause
   php artisan horizon:continue
   ```

3. **Scale workers**
   ```bash
   # Increase webhook processors
   php artisan horizon:scale retell-webhooks=5
   ```

---

## 📊 Diagnostic Commands

### Complete System Check
```bash
#!/bin/bash
# save as: emergency-diagnostic.sh

echo "🔍 Starting Retell Emergency Diagnostic..."

# API Connection
echo -n "API Connection: "
php artisan retell:test-connection || echo "❌ FAILED"

# Webhook Status
echo -n "Webhook Endpoint: "
curl -s -o /dev/null -w "%{http_code}" https://api.askproai.de/api/retell/webhook

# Queue Status
echo -n "Queue Workers: "
php artisan horizon:status | grep "Status"

# Recent Errors
echo "Recent Errors:"
tail -n 20 storage/logs/retell-*.log | grep ERROR

# Call Statistics
echo "Last Hour Stats:"
php artisan retell:stats --period=1h
```

### Quick Recovery Script
```bash
#!/bin/bash
# save as: quick-recovery.sh

echo "🚀 Starting Quick Recovery..."

# 1. Clear all caches
php artisan optimize:clear

# 2. Restart services
php artisan horizon:terminate
sleep 5
php artisan horizon &

# 3. Test connection
php artisan retell:test

# 4. Process pending webhooks
php artisan queue:retry all

echo "✅ Recovery complete!"
```

---

## 📞 Manual Intervention

### Taking Over Active Calls
```bash
# List active calls
php artisan retell:list-active-calls

# Terminate specific call
php artisan retell:end-call {call_id}

# Send SMS to customer
php artisan customer:notify {phone} --message="Entschuldigung für die Unterbrechung. Bitte rufen Sie erneut an."
```

### Emergency Contact Script
```php
// emergency-notify-customers.php
$recentCalls = Call::where('created_at', '>', now()->subHour())
    ->whereNull('appointment_id')
    ->get();

foreach ($recentCalls as $call) {
    // Send emergency notification
    $customer = Customer::where('phone', $call->customer_phone)->first();
    if ($customer && $customer->email) {
        Mail::to($customer->email)->send(new EmergencyNotification());
    }
}
```

---

## 🔧 Configuration Overrides

### Temporary Settings
```php
// Force simple mode
Config::set('retell.simple_mode', true);
Config::set('retell.max_retry', 1);
Config::set('retell.timeout', 10);

// Disable complex features
Config::set('retell.features.booking', false);
Config::set('retell.features.customer_lookup', false);
```

### Fallback Agent
```env
# Emergency fallback agent
RETELL_FALLBACK_AGENT_ID=agent_emergency_123
RETELL_FALLBACK_PROMPT="Entschuldigung, wir haben technische Probleme. Bitte hinterlassen Sie Ihre Nummer."
```

---

## 📋 Recovery Checklist

After resolving issues:

- [ ] Verify all services running
- [ ] Process missed calls/webhooks
- [ ] Check appointment bookings
- [ ] Review error logs
- [ ] Update incident report
- [ ] Notify affected customers
- [ ] Document root cause
- [ ] Update monitoring alerts

---

## 🚨 Escalation Contacts

### Internal Team
1. **Level 1 Support**: Available 24/7
   - Slack: #retell-alerts
   - Phone: +49 XXX XXX 1111

2. **Level 2 Engineering**: Business hours
   - Email: tech-team@askproai.de
   - Phone: +49 XXX XXX 2222

3. **Level 3 Management**: Critical only
   - Direct: +49 XXX XXX 3333

### External Support
- **Retell.ai Support**: support@retellai.com
- **Retell.ai Status**: https://status.retellai.com
- **Emergency Hotline**: +1-XXX-XXX-XXXX

---

## 📊 Post-Incident Analysis

### Required Reports
1. **Incident Timeline** - What happened when
2. **Impact Assessment** - Calls affected, revenue impact
3. **Root Cause Analysis** - Why it happened
4. **Prevention Plan** - How to avoid recurrence

### Template
```markdown
## Incident Report - [Date]

**Duration**: XX:XX - XX:XX  
**Severity**: Critical/High/Medium  
**Impact**: X calls affected  

**Timeline**:
- XX:XX - Issue detected
- XX:XX - Emergency procedure initiated
- XX:XX - Service restored

**Root Cause**: [Detailed explanation]

**Action Items**:
1. [Prevention measure 1]
2. [Prevention measure 2]
```

---

**Quick Reference Card**
```
🔴 System Down: php retell-health-check.php
🟡 Webhooks Failed: php artisan retell:register-webhooks --force  
🟢 Slow Response: php artisan horizon:scale retell-webhooks=5
📞 Support: +49 XXX XXX 1111
```

---

**Document Type**: Emergency Response  
**Review Frequency**: Weekly  
**Last Drill**: [Date]  
**Next Drill**: [Date]