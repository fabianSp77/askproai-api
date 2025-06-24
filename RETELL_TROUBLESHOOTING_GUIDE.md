# Retell Integration Troubleshooting Guide

## Common Issues & Solutions

### 1. "No appointment data found in any expected location"

**Symptom**: Webhook processes but no appointment is created

**Debug Steps**:
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log | grep -i retell

# Check cache
php artisan tinker
>>> Cache::get('retell:appointment:YOUR_CALL_ID')
```

**Possible Causes**:
- Custom function not called during call
- Cache expired (1 hour TTL)
- Wrong call_id format

**Solution**:
- Ensure Retell agent has `collect_appointment` function configured
- Check agent prompts include appointment collection
- Verify webhook arrives within 1 hour of call

### 2. "No company context found for model"

**Symptom**: Tenant scope errors

**Debug Steps**:
```bash
php debug-phone-branch.php
```

**Solution**:
- Ensure phone number exists in `phone_numbers` table
- Branch must be active
- Use `withoutGlobalScope(\App\Scopes\TenantScope::class)` for webhook processing

### 3. Webhook Not Processing

**Symptom**: Calls complete but no webhook received

**Check**:
```bash
# Verify webhook URL in Retell dashboard
https://your-domain.com/api/retell/webhook

# Test webhook manually
curl -X POST https://your-domain.com/api/retell/webhook \
  -H "Content-Type: application/json" \
  -d '{"event":"call_ended","call":{"call_id":"test"}}'
```

**Common Issues**:
- Wrong webhook URL in Retell
- SSL certificate issues
- Rate limiting

### 4. Appointment Not Created

**Debug Query**:
```sql
-- Check recent calls
SELECT * FROM calls 
WHERE created_at > NOW() - INTERVAL 1 HOUR 
ORDER BY created_at DESC;

-- Check webhook events
SELECT * FROM webhook_events 
WHERE provider = 'retell' 
AND created_at > NOW() - INTERVAL 1 HOUR;
```

**Verify**:
- Service exists and is active
- Customer phone number is valid
- Branch has available time slots

### 5. Custom Function Not Working

**Test Directly**:
```php
php test-retell-custom-function.php
```

**Common Issues**:
- Wrong parameter names (must be German: datum, uhrzeit, etc.)
- Date parsing issues (supports: heute, morgen, DD.MM.YYYY)
- Phone number format issues

## Debug Commands

### Test Phone Resolution
```bash
php artisan tinker
>>> $resolver = app(\App\Services\PhoneNumberResolver::class);
>>> $resolver->resolve('+49 30 837 93 369');
```

### Test Custom Function
```bash
php artisan tinker
>>> $server = app(\App\Services\MCP\RetellCustomFunctionMCPServer::class);
>>> $server->collect_appointment([
...     'call_id' => 'test123',
...     'datum' => 'morgen',
...     'uhrzeit' => '14:00',
...     'name' => 'Test',
...     'dienstleistung' => 'Beratung'
... ]);
```

### Clear Cache Issues
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Monitoring Queries

### Recent Calls
```sql
SELECT 
    c.id,
    c.retell_call_id,
    c.from_number,
    c.status,
    c.appointment_id,
    a.starts_at as appointment_time
FROM calls c
LEFT JOIN appointments a ON a.id = c.appointment_id
WHERE c.created_at > NOW() - INTERVAL 24 HOUR
ORDER BY c.created_at DESC;
```

### Webhook Processing Status
```sql
SELECT 
    provider,
    event_type,
    status,
    COUNT(*) as count
FROM webhook_events
WHERE created_at > NOW() - INTERVAL 24 HOUR
GROUP BY provider, event_type, status;
```

## Configuration Checklist

### Retell Dashboard
- [ ] Webhook URL configured
- [ ] Custom functions added
- [ ] Agent prompt includes appointment booking
- [ ] Test mode disabled for production

### Database
- [ ] phone_numbers table has entries
- [ ] branches are active
- [ ] services are configured
- [ ] working_hours set

### Environment
- [ ] RETELL_TOKEN set
- [ ] RETELL_WEBHOOK_SECRET set
- [ ] Cache driver configured (Redis recommended)
- [ ] Queue workers running

## Emergency Fixes

### Reset Stuck Appointment
```sql
-- Find stuck appointments
SELECT * FROM appointments 
WHERE status = 'scheduled' 
AND starts_at < NOW();

-- Clean up test data
DELETE FROM calls WHERE retell_call_id LIKE 'test_%';
DELETE FROM appointments WHERE notes LIKE '%Test%';
```

### Force Reprocess Webhook
```php
php artisan tinker
>>> $webhookEvent = \App\Models\WebhookEvent::find(ID);
>>> $processor = app(\App\Services\WebhookProcessor::class);
>>> $processor->retry($webhookEvent->id);
```

## Contact Support

If issues persist:
1. Collect debug information
2. Check Retell status page
3. Review recent deployments
4. Contact technical support with:
   - Call ID
   - Timestamp
   - Error messages
   - Laravel logs