# Cal.com Integration Guide

## Overview

AskProAI integrates with Cal.com to provide automated appointment booking capabilities through AI-powered phone calls. This integration enables real-time availability checking, appointment creation, and calendar synchronization.

## Current Status

- ✅ **API Version**: V2 (fully migrated from V1)
- ✅ **Production Ready**: Yes
- ✅ **Multi-tenant Support**: Yes
- ✅ **Webhook Integration**: Active
- ✅ **Circuit Breaker**: Enabled
- ✅ **Response Caching**: Enabled

## Architecture

### Integration Points

```
┌─────────────────┐     ┌──────────────┐     ┌─────────────┐
│  Retell AI Call │────▶│  AskProAI    │────▶│   Cal.com   │
└─────────────────┘     │   Platform   │     │   API V2    │
                        └──────┬───────┘     └──────┬──────┘
                               │                     │
                        ┌──────▼───────┐     ┌──────▼──────┐
                        │  Appointment │◀────│   Webhook   │
                        │   Database   │     │   Events    │
                        └──────────────┘     └─────────────┘
```

### Service Layer

1. **CalcomV2Client**: Low-level API client with circuit breaker
2. **CalcomV2Service**: High-level business logic service
3. **CalcomEventTypeSyncService**: Event type synchronization
4. **CalcomWebhookHandler**: Webhook event processing
5. **CalcomMCPServer**: MCP interface for external tools

## Key Features

### 1. Event Type Management
- Automatic synchronization from Cal.com
- Multi-location support with branch mapping
- Custom field configuration
- Duration and pricing settings

### 2. Real-time Availability
- Timezone-aware slot checking
- Buffer time handling
- Concurrent booking prevention
- Team member availability

### 3. Booking Management
- Create appointments via API
- Automatic customer data mapping
- Metadata for tracking and analytics
- Reschedule and cancellation support

### 4. Webhook Processing
- Real-time booking updates
- Signature verification
- Asynchronous job processing
- Event type filtering

## Quick Start

### 1. Environment Setup

```bash
# Required environment variables
DEFAULT_CALCOM_API_KEY=cal_live_xxxxxxxxxxxxxx
DEFAULT_CALCOM_TEAM_SLUG=your-team-slug
CALCOM_WEBHOOK_SECRET=your-webhook-secret

# Optional V2-specific settings
CALCOM_V2_API_URL=https://api.cal.com/v2
CALCOM_V2_ORGANIZATION_ID=your-org-id
CALCOM_V2_RATE_LIMIT_ENABLED=true
CALCOM_V2_CIRCUIT_BREAKER_ENABLED=true
CALCOM_V2_CACHE_ENABLED=true
```

### 2. Initial Configuration

```bash
# Run migrations
php artisan migrate --force

# Sync event types
php artisan calcom:sync-event-types --all

# Test connection
php artisan calcom:test-connection
```

### 3. Basic Usage

```php
use App\Services\Calcom\CalcomV2Service;

// Initialize service
$calcom = app(CalcomV2Service::class);

// Check availability
$slots = $calcom->getAvailableSlots(
    eventTypeId: 2026361,
    startDate: now(),
    endDate: now()->addDays(7),
    timeZone: 'Europe/Berlin'
);

// Create booking
$booking = $calcom->createBookingFromAppointment($appointment);
```

## Configuration Details

### Company Settings

Each company can have their own Cal.com configuration:

```php
$company->update([
    'calcom_api_key' => 'cal_live_xxx',
    'calcom_team_id' => 12345,
    'calcom_organization_id' => 67890,
    'calcom_team_slug' => 'medical-center'
]);
```

### Branch Event Types

Map Cal.com event types to branches:

```php
$branch->calcomEventTypes()->create([
    'calcom_event_type_id' => 2026361,
    'title' => '30 Min Consultation',
    'slug' => '30min',
    'length' => 30,
    'price' => 50.00,
    'currency' => 'EUR',
    'is_active' => true
]);
```

### Staff Assignment

Link staff members to Cal.com users:

```php
$staff->update([
    'calcom_user_id' => 98765,
    'calcom_username' => 'dr.smith'
]);
```

## Webhook Configuration

### 1. Register Webhook URL

In Cal.com dashboard, add webhook endpoint:
```
https://api.askproai.de/api/webhooks/calcom
```

### 2. Select Events

Enable these webhook events:
- `BOOKING_CREATED`
- `BOOKING_RESCHEDULED`
- `BOOKING_CANCELLED`
- `BOOKING_CONFIRMED`
- `BOOKING_REJECTED`

### 3. Verify Signature

Webhooks are automatically verified using HMAC-SHA256:

```php
// Handled by VerifyCalcomSignature middleware
Route::post('/api/webhooks/calcom', [CalcomWebhookController::class, 'handle'])
    ->middleware(VerifyCalcomSignature::class);
```

## API Endpoints

### Health Check
```bash
GET /api/health/calcom

Response:
{
    "service": "cal.com",
    "status": "healthy",
    "api_version": "v2",
    "circuit_breaker": "closed",
    "cache_enabled": true
}
```

### Sync Status
```bash
GET /api/calcom/sync-status

Response:
{
    "last_sync": "2025-01-10T10:00:00Z",
    "event_types_count": 15,
    "pending_webhooks": 0,
    "sync_errors": []
}
```

## Common Operations

### Manual Event Type Sync
```bash
# Sync all companies
php artisan calcom:sync-event-types --all

# Sync specific company
php artisan calcom:sync-event-types --company=1

# Force resync (ignore cache)
php artisan calcom:sync-event-types --force
```

### Debug Commands
```bash
# Test API connection
php artisan calcom:test-connection

# Debug availability for event type
php artisan calcom:debug-availability 2026361

# List all event types
php artisan calcom:list-event-types
```

### Cache Management
```bash
# Warm up cache
php artisan calcom:cache-warmup

# Clear Cal.com cache
php artisan cache:clear --tags=calcom
```

## Performance Optimization

### 1. Caching Strategy
- Event types: 5 minutes TTL
- Available slots: 1 minute TTL
- User data: 10 minutes TTL

### 2. Circuit Breaker
- Failure threshold: 5 consecutive failures
- Recovery timeout: 60 seconds
- Half-open test requests: 2

### 3. Rate Limiting
- Default: 100 requests per minute
- Burst: 150 requests per minute
- Per-company limits configurable

## Monitoring

### Key Metrics
- API response times
- Circuit breaker state changes
- Cache hit/miss rates
- Webhook processing times
- Booking success rates

### Log Locations
- API requests: `storage/logs/calcom.log`
- Webhooks: `storage/logs/webhooks.log`
- Errors: `storage/logs/laravel.log`

### Alerting
- Circuit breaker opens
- API authentication failures
- Webhook signature mismatches
- High error rates (>5%)

## Troubleshooting

### Common Issues

#### 1. "Invalid API Key"
```bash
# Verify API key
php artisan tinker
>>> $company = Company::first();
>>> $company->calcom_api_key; // Should not be null
```

#### 2. "No Available Slots"
```bash
# Debug availability
php artisan calcom:debug-availability 2026361 --date=2025-01-15
```

#### 3. "Webhook Not Processing"
```bash
# Check webhook logs
tail -f storage/logs/webhooks.log

# Verify webhook secret
php artisan tinker
>>> config('services.calcom.webhook_secret');
```

#### 4. "Circuit Breaker Open"
```bash
# Check circuit breaker status
php artisan calcom:circuit-status

# Reset if needed
php artisan calcom:circuit-reset
```

## Security Considerations

1. **API Keys**: Stored encrypted in database
2. **Webhook Verification**: HMAC-SHA256 signature required
3. **Rate Limiting**: Prevents API abuse
4. **SSL/TLS**: All API calls use HTTPS
5. **Audit Logging**: All operations logged

## Best Practices

1. **Always use timezone-aware dates**
2. **Include metadata for tracking**
3. **Handle API errors gracefully**
4. **Monitor circuit breaker status**
5. **Regular event type synchronization**
6. **Test webhook handling thoroughly**

## Migration from V1

If upgrading from V1:
1. Run migration command: `php artisan calcom:migrate-to-v2`
2. Update environment variables
3. Test webhook endpoints
4. Monitor for v1 deprecation warnings

## Support & Resources

- **API Documentation**: [Cal.com V2 API Reference](./CALCOM_V2_API_REFERENCE.md)
- **Webhook Guide**: [Webhook Configuration](./CALCOM_WEBHOOK_GUIDE.md)
- **Troubleshooting**: [Common Issues](./CALCOM_TROUBLESHOOTING_GUIDE.md)
- **Cal.com Support**: support@cal.com
- **API Status**: https://status.cal.com