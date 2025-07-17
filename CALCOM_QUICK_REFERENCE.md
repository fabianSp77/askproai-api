# Cal.com Quick Reference

## Essential Information

### API Endpoints
```
Production: https://api.cal.com/v2
API Version: 2024-08-13
Documentation: https://cal.com/docs/api-reference/v2
```

### Environment Variables
```bash
DEFAULT_CALCOM_API_KEY=cal_live_xxxxxxxxxxxxxx
DEFAULT_CALCOM_TEAM_SLUG=your-team-slug
CALCOM_WEBHOOK_SECRET=your-webhook-secret
CALCOM_V2_API_URL=https://api.cal.com/v2
```

### Webhook URL
```
https://api.askproai.de/api/webhooks/calcom
```

## Most Used Commands

### Daily Operations
```bash
# Check health
php artisan calcom:health-check

# Sync event types
php artisan calcom:sync-event-types --all

# Check circuit breaker
php artisan calcom:circuit-status

# View recent webhooks
tail -f storage/logs/webhooks.log | grep calcom
```

### Troubleshooting
```bash
# Test connection
php artisan calcom:test-connection

# Debug availability
php artisan calcom:debug-availability 2026361

# Reset circuit breaker
php artisan calcom:circuit-reset

# Force sync
php artisan calcom:sync-event-types --force
```

### Emergency
```bash
# Stop all operations
php artisan calcom:emergency-stop

# Enable fallback mode
php artisan calcom:fallback-mode

# Clear all caches
php artisan cache:clear --tags=calcom

# Recover failed bookings
php artisan calcom:recover-failed-bookings
```

## Common API Calls

### Check Availability
```php
$slots = $calcomService->getAvailableSlots(
    eventTypeId: 2026361,
    startDate: now(),
    endDate: now()->addDays(7),
    timeZone: 'Europe/Berlin'
);
```

### Create Booking
```php
$booking = $calcomService->createBookingFromAppointment($appointment);
```

### Get Event Types
```php
$eventTypes = $calcomService->getEventTypes();
```

## Database Queries

### Find Missing Bookings
```sql
SELECT * FROM appointments 
WHERE status = 'confirmed' 
  AND calcom_booking_id IS NULL
  AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY);
```

### Check Today's Bookings
```sql
SELECT 
    a.id,
    a.start_time,
    c.full_name as customer,
    ce.title as service,
    a.status
FROM appointments a
JOIN customers c ON a.customer_id = c.id
JOIN calcom_event_types ce ON a.calcom_event_type_id = ce.calcom_event_type_id
WHERE DATE(a.start_time) = CURDATE()
ORDER BY a.start_time;
```

### Event Type Usage
```sql
SELECT 
    ce.title,
    COUNT(a.id) as bookings,
    ce.is_active
FROM calcom_event_types ce
LEFT JOIN appointments a ON ce.calcom_event_type_id = a.calcom_event_type_id
WHERE ce.company_id = 1
GROUP BY ce.id
ORDER BY bookings DESC;
```

## Error Codes Quick Fix

| Error | Quick Fix |
|-------|-----------|
| 401 Unauthorized | Check API key: `php artisan calcom:test-auth` |
| 404 Event Type Not Found | Re-sync: `php artisan calcom:sync-event-types` |
| 422 Validation Error | Check required fields in request |
| 429 Rate Limited | Wait or check rate limits |
| 500 Server Error | Check Cal.com status page |

## Configuration Files

### Main Config
- `/config/calcom-v2.php` - V2 API configuration
- `/config/services.php` - Legacy configuration
- `/.env` - Environment variables

### Middleware
- `VerifyCalcomSignature` - Webhook verification
- `CalcomRateLimiter` - API rate limiting

### Service Classes
- `CalcomV2Service` - High-level service
- `CalcomV2Client` - Low-level API client
- `CalcomWebhookHandler` - Webhook processor

## Key Models

### CalcomEventType
```php
// Get active event types for branch
$eventTypes = $branch->calcomEventTypes()
    ->where('is_active', true)
    ->get();
```

### Appointment
```php
// Find appointments with Cal.com bookings
$appointments = Appointment::whereNotNull('calcom_booking_id')
    ->where('start_time', '>', now())
    ->get();
```

## Logs & Monitoring

### Log Files
```
/storage/logs/calcom.log         # API requests/responses
/storage/logs/webhooks.log       # Webhook events
/storage/logs/calcom-sync.log    # Sync operations
/storage/logs/laravel.log        # General errors
```

### Monitoring URLs
```
/admin/calcom-diagnostics        # Diagnostics dashboard
/admin/calcom-monitoring         # Real-time monitoring
/api/health/calcom              # Health check endpoint
```

## Circuit Breaker States

| State | Description | Action |
|-------|-------------|--------|
| CLOSED | Normal operation | None needed |
| OPEN | Too many failures | Wait or force reset |
| HALF_OPEN | Testing recovery | Limited requests |

## Cache Keys

```php
"calcom:event_types:{$companyId}"     # TTL: 5 min
"calcom:slots:{$eventTypeId}:{$date}" # TTL: 1 min
"calcom:user:{$userId}"               # TTL: 30 min
"calcom:circuit:state"                # Circuit breaker
```

## Webhook Events

### High Priority
- `BOOKING_CREATED`
- `BOOKING_RESCHEDULED`
- `BOOKING_CANCELLED`

### Medium Priority
- `BOOKING_CONFIRMED`
- `BOOKING_REJECTED`
- `BOOKING_REQUESTED`

## Performance Targets

- API Response: < 500ms
- Booking Success: > 99%
- Webhook Processing: < 5s
- Cache Hit Rate: > 80%
- Availability: > 99.9%

## Support Contacts

### Internal
- System Admin: admin@askproai.de
- Emergency: +49 30 12345678

### External
- Cal.com Support: enterprise-support@cal.com
- Cal.com Status: https://status.cal.com
- API Docs: https://cal.com/docs/api-reference/v2

## Quick Debug Checklist

- [ ] API key valid?
- [ ] Webhook URL correct?
- [ ] SSL certificate valid?
- [ ] Circuit breaker closed?
- [ ] Queue workers running?
- [ ] Cache enabled?
- [ ] Logs showing errors?
- [ ] Cal.com status page checked?

## Common Fixes

### "No slots available"
```bash
php artisan calcom:debug-availability <event-type-id>
# Check timezone, buffers, schedule
```

### "Webhook not received"
```bash
# Test endpoint
curl -X POST https://api.askproai.de/api/webhooks/calcom -d '{}'
# Check logs
tail -f storage/logs/webhooks.log
```

### "Booking failed"
```bash
# Check required fields
php artisan tinker
>>> $et = CalcomEventType::find(1);
>>> $et->v2_attributes['customInputs'];
```

## Migration Commands

### From V1 to V2
```bash
php artisan calcom:migrate-to-v2 --dry-run
php artisan calcom:migrate-to-v2
php artisan calcom:verify-migration
```

## Testing

### Manual Test Booking
```bash
php artisan calcom:test-booking \
  --event-type=2026361 \
  --date="2025-01-20" \
  --time="14:00"
```

### Webhook Test
```bash
php artisan calcom:test-webhook \
  --event=BOOKING_CREATED \
  --payload='{"calcomBookingId": 12345}'
```