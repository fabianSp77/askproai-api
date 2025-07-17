# Retell.ai Troubleshooting Guide

> **Quick Reference for Common Issues**  
> Last Updated: July 10, 2025

## 🚨 Critical Issues & Quick Fixes

### Issue: No Calls Appearing in Dashboard

**Symptoms:**
- Calls are made to the phone number
- Nothing shows up in the admin dashboard
- No entries in the calls table

**Quick Fix:**
```bash
# 1. Check if Horizon is running
php artisan horizon:status

# 2. If not running, start it
php artisan horizon

# 3. Force manual import
php manual-retell-import.php

# 4. Check logs for errors
tail -f storage/logs/laravel.log | grep -i retell
```

**Root Causes:**
- Horizon queue worker not running
- Webhook URL misconfigured in Retell.ai
- API key mismatch
- Phone number not mapped to company/branch

---

### Issue: Webhook Returns 500 Error

**Symptoms:**
- Retell.ai shows webhook delivery failures
- 500 errors in nginx logs
- Calls not being processed

**Quick Fix:**
```bash
# 1. Switch to simple webhook endpoint (no signature verification)
# In Retell.ai dashboard, change webhook URL to:
https://api.askproai.de/api/retell/webhook-simple

# 2. Clear all caches
php artisan optimize:clear

# 3. Test webhook manually
curl -X POST https://api.askproai.de/api/retell/webhook-simple \
  -H "Content-Type: application/json" \
  -d '{"event":"call_started","call":{"call_id":"test_123"}}'
```

**Common Causes:**
1. **Data structure mismatch** - Retell changed from flat to nested structure
2. **Timestamp format issues** - Mixed ISO 8601 and numeric formats
3. **Signature verification failing** - Wrong secret or format
4. **TenantScope blocking webhooks** - No company context

---

### Issue: Call Times Are Wrong

**Symptoms:**
- Call timestamps off by 1-2 hours
- Showing UTC instead of local time
- Appointment times mismatched

**Quick Fix:**
```bash
# 1. Check timezone setting
grep APP_TIMEZONE .env

# 2. Should be:
APP_TIMEZONE=Europe/Berlin

# 3. Clear config cache
php artisan config:clear

# 4. Verify in tinker
php artisan tinker
>>> Carbon\Carbon::now()->timezone
```

**Note:** Retell sends UTC timestamps. System auto-converts to Berlin time (+2 hours CEST).

---

### Issue: "Agent Not Found" Errors

**Symptoms:**
- 404 errors when trying to view/edit agents
- Test calls fail with "no agent configured"
- Agent dropdown empty

**Quick Fix:**
```bash
# 1. List all available agents
php artisan tinker
>>> $retell = new \App\Services\RetellV2Service();
>>> $agents = $retell->listAgents();
>>> dd($agents);

# 2. Update company with correct agent ID
>>> $company = \App\Models\Company::first();
>>> $company->retell_agent_id = 'agent_abc123...'; // Use ID from step 1
>>> $company->save();

# 3. Clear agent cache
php artisan cache:forget retell_agents_list
```

---

### Issue: Appointments Not Being Created

**Symptoms:**
- AI collects appointment data
- No appointment appears in system
- No Cal.com event created

**Quick Fix:**
```bash
# 1. Check if company has appointment booking enabled
php artisan tinker
>>> Company::first()->needsAppointmentBooking()
# Should return true

# 2. Check appointment data in call
>>> $call = Call::latest()->first();
>>> dd($call->webhook_data['call']['retell_llm_dynamic_variables'] ?? []);

# 3. Check for cached appointment data
>>> Cache::get('retell_appointment_data:' . $call->call_id);

# 4. Manually trigger appointment creation
php artisan call:process-appointment {call_id}
```

**Common Causes:**
- Custom function not returning data correctly
- Company doesn't have appointment booking enabled
- Cal.com integration issues
- Missing required fields (date, time, name)

---

### Issue: Custom Functions Not Working

**Symptoms:**
- AI says "I couldn't check availability"
- Functions timeout or return errors
- No function calls in logs

**Quick Fix:**
```bash
# 1. Test custom function directly
curl -X POST https://api.askproai.de/api/retell/check-availability \
  -H "Content-Type: application/json" \
  -d '{"date": "tomorrow", "call": {"call_id": "test"}}'

# 2. Check function logs
tail -f storage/logs/retell-functions-$(date +%Y-%m-%d).log

# 3. Verify function configuration in Retell
# Dashboard > Agent > Custom Functions
# Ensure URL matches exactly

# 4. Test with minimal data
curl -X POST https://api.askproai.de/api/retell/current-time-berlin
```

---

### Issue: High Call Costs

**Symptoms:**
- Costs exceeding €2-3 per call
- Budget depleting quickly
- Unexpected usage spikes

**Quick Fix:**
```bash
# 1. Analyze cost breakdown
mysql -u askproai_user -p askproai_db -e "
SELECT 
    DATE(created_at) as date,
    COUNT(*) as calls,
    AVG(cost) as avg_cost,
    MAX(cost) as max_cost,
    SUM(cost) as total_cost
FROM calls 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;"

# 2. Find expensive calls
mysql -u askproai_user -p askproai_db -e "
SELECT call_id, duration_sec, cost, from_number 
FROM calls 
WHERE cost > 3 
ORDER BY cost DESC 
LIMIT 10;"

# 3. Optimize agent prompt (shorter = cheaper)
# Remove unnecessary instructions
# Use concise language
# Limit response length
```

**Cost Reduction Tips:**
- Shorten agent prompt
- Set max conversation length
- Use cheaper voice model
- Implement call time limits
- Add pre-screening IVR

---

## 🔍 Diagnostic Commands

### Health Check Suite

```bash
# Complete system health check
php retell-health-check.php

# Check specific components
php artisan health:check retell
php artisan health:check database
php artisan health:check redis
php artisan health:check horizon

# Verify webhook endpoint
curl -I https://api.askproai.de/api/retell/webhook-simple
```

### Database Diagnostics

```sql
-- Check recent calls
SELECT call_id, from_number, to_number, call_status, 
       created_at, duration_sec, cost
FROM calls 
ORDER BY created_at DESC 
LIMIT 10;

-- Check webhook events
SELECT event_type, status, created_at, 
       JSON_EXTRACT(payload, '$.call.call_id') as call_id
FROM webhook_events 
WHERE provider = 'retell' 
ORDER BY created_at DESC 
LIMIT 10;

-- Phone number mappings
SELECT * FROM phone_numbers 
WHERE number LIKE '%3083793369%';

-- Failed jobs
SELECT * FROM failed_jobs 
WHERE payload LIKE '%Retell%' 
ORDER BY failed_at DESC;
```

### Real-time Monitoring

```bash
# Watch incoming webhooks
tail -f /var/log/nginx/access.log | grep "retell/webhook"

# Monitor Laravel logs
tail -f storage/logs/laravel.log | grep -E "(Retell|Call|Webhook)"

# Watch queue processing
php artisan horizon
# Then open https://api.askproai.de/horizon

# Monitor specific job types
php artisan queue:monitor webhooks --interval=5
```

---

## 🛠️ Advanced Troubleshooting

### Webhook Signature Verification Issues

```php
// Test signature generation
$apiKey = env('RETELL_WEBHOOK_SECRET');
$timestamp = time();
$payload = '{"test": "data"}';
$signature = "v={$timestamp},d=" . hash_hmac('sha256', $timestamp . $payload, $apiKey);

echo "Expected header: x-retell-signature: {$signature}\n";
```

### Manual Call Import

```php
// manual-import-single-call.php
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$retell = new \App\Services\RetellV2Service();
$call = $retell->getCall('YOUR_CALL_ID');

if ($call) {
    $handler = new \App\Services\Webhooks\RetellWebhookHandler();
    $handler->handleCallEnded($call);
    echo "Call imported successfully\n";
} else {
    echo "Call not found\n";
}
```

### Debug Phone Resolution

```php
// test-phone-resolution-detailed.php
$phoneNumber = '+493083793369';
$resolver = new \App\Services\PhoneNumberResolver();
$result = $resolver->resolveFromPhoneNumber($phoneNumber);

echo "Phone: {$phoneNumber}\n";
echo "Company ID: " . ($result['company_id'] ?? 'NOT FOUND') . "\n";
echo "Branch ID: " . ($result['branch_id'] ?? 'NOT FOUND') . "\n";
echo "Method: " . ($result['resolution_method'] ?? 'NONE') . "\n";
```

---

## 📊 Performance Optimization

### Slow Webhook Processing

```bash
# Enable query logging
mysql -u root -p
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;

# Check slow queries
tail -f /var/log/mysql/slow-query.log

# Add indexes if needed
CREATE INDEX idx_calls_created_at ON calls(created_at);
CREATE INDEX idx_calls_company_id ON calls(company_id);
CREATE INDEX idx_webhook_events_created_at ON webhook_events(created_at);
```

### Queue Optimization

```php
// config/horizon.php
'environments' => [
    'production' => [
        'webhooks' => [
            'connection' => 'redis',
            'queue' => 'webhooks',
            'balance' => 'auto',
            'processes' => 5,  // Increase for more throughput
            'tries' => 3,
            'timeout' => 60,
            'memory' => 256,
        ],
    ],
],
```

---

## 🚑 Emergency Procedures

### Complete System Reset

```bash
# 1. Stop all services
php artisan down
supervisorctl stop all

# 2. Clear everything
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
redis-cli FLUSHALL

# 3. Restart services
supervisorctl start all
php artisan up

# 4. Test webhook
php trigger-simple-webhook.php
```

### Switching to Bypass Mode

```bash
# 1. Update webhook URL in Retell.ai to:
https://api.askproai.de/api/retell/webhook-simple

# 2. Disable signature verification in .env
RETELL_VERIFY_SIGNATURE=false

# 3. Clear config
php artisan config:clear

# 4. Monitor logs closely
tail -f storage/logs/laravel.log
```

### Data Recovery

```bash
# 1. Find missing calls in Retell
php artisan tinker
>>> $retell = new \App\Services\RetellV2Service();
>>> $calls = $retell->listCalls(100);
>>> foreach ($calls['calls'] as $call) {
...     $exists = \App\Models\Call::where('call_id', $call['call_id'])->exists();
...     if (!$exists) echo "Missing: " . $call['call_id'] . "\n";
... }

# 2. Import missing calls
php import-missing-calls.php

# 3. Verify data integrity
php artisan calls:verify --fix
```

---

## 📞 Common Error Messages

| Error | Meaning | Solution |
|-------|---------|----------|
| "No company context" | TenantScope blocking | Use webhook-simple endpoint |
| "Invalid signature" | Webhook auth failed | Check API key matches |
| "Call to a member function company()" | Missing relationships | Check phone_numbers table |
| "Undefined array key 'call'" | Old webhook format | Update webhook handler |
| "Non-numeric value encountered" | Timestamp format issue | Update parseTimestamp method |
| "Target class does not exist" | Missing middleware | Run composer dump-autoload |
| "No query results for model" | Call/Company not found | Check data relationships |

---

## 📋 Pre-Deployment Checklist

Before deploying Retell-related changes:

- [ ] Test webhook with both flat and nested structure
- [ ] Verify timestamp parsing handles all formats
- [ ] Confirm TenantScope has webhook bypass
- [ ] Check Horizon configuration includes webhook queue
- [ ] Test all custom functions return valid JSON
- [ ] Verify error handling doesn't expose sensitive data
- [ ] Confirm logging includes correlation IDs
- [ ] Test with production-like data volume
- [ ] Check cost calculations are accurate
- [ ] Verify timezone conversions work correctly

---

**Emergency Contact**: For critical issues, check Slack #askproai-alerts or create urgent GitHub issue

**Last Updated**: July 10, 2025