# Cal.com V1 to V2 API Migration Guide

## Overview

This guide explains how to gradually migrate from Cal.com V1 API to V2 API in the AskProAI platform.

## Migration Benefits

### Performance Improvements
- **Faster Response Times**: V2 API is optimized for performance
- **Better Caching**: Built-in caching support with Redis
- **Circuit Breaker**: Automatic fault tolerance

### Feature Enhancements
- **Type-Safe DTOs**: All responses use typed data transfer objects
- **Better Error Handling**: Specific exception types for different errors
- **Improved Availability Checking**: More accurate slot availability

## Migration Strategy

### Phase 1: Testing (Current)
- Both V1 and V2 APIs are available
- V2 is disabled by default
- Use migration commands to test V2 endpoints

### Phase 2: Gradual Rollout
- Enable V2 for specific methods
- Monitor performance and errors
- Fallback to V1 on errors

### Phase 3: Full Migration
- V2 becomes default
- V1 kept as fallback
- Eventually deprecate V1

## Using the Migration Service

### 1. Check Current Status

```bash
php artisan calcom:migrate-to-v2 --status
```

### 2. Run Comparison Tests

```bash
php artisan calcom:migrate-to-v2 --test
```

This will:
- Compare response times between V1 and V2
- Verify data consistency
- Show performance improvements

### 3. Enable V2 for Specific Method

```bash
# Enable V2 for getEventTypes (1 hour trial)
php artisan calcom:migrate-to-v2 --method=getEventTypes

# Enable V2 for bookAppointment
php artisan calcom:migrate-to-v2 --method=bookAppointment
```

### 4. Enable V2 for All Methods

```bash
php artisan calcom:migrate-to-v2 --all
```

### 5. Rollback if Needed

```bash
# Rollback specific method
php artisan calcom:migrate-to-v2 --rollback --method=getEventTypes

# Rollback all methods
php artisan calcom:migrate-to-v2 --rollback
```

## Configuration

### Environment Variables

```env
# Enable V2 globally (default: false)
CALCOM_USE_V2_API=false

# Enable V2 for specific methods (comma-separated)
CALCOM_V2_ENABLED_METHODS=getEventTypes,getAvailableSlots

# Methods that MUST use V2 (no fallback)
CALCOM_V2_MANDATORY_METHODS=

# Performance settings
CALCOM_CACHE_TTL=300
CALCOM_CIRCUIT_BREAKER_ENABLED=true
CALCOM_CIRCUIT_BREAKER_THRESHOLD=5
CALCOM_CIRCUIT_BREAKER_TIMEOUT=60
```

### Code Usage

#### Using CalcomMigrationService

```php
use App\Services\CalcomMigrationService;

class BookingController
{
    protected CalcomMigrationService $calcom;
    
    public function __construct(CalcomMigrationService $calcom)
    {
        $this->calcom = $calcom;
    }
    
    public function getSlots(Request $request)
    {
        // Automatically uses V2 if enabled, falls back to V1
        $slots = $this->calcom->getAvailableSlots(
            $request->event_type_id,
            $request->start_date,
            $request->end_date,
            $request->timezone ?? 'Europe/Berlin'
        );
        
        return response()->json($slots);
    }
}
```

#### Direct V2 Usage

```php
use App\Services\CalcomV2Service;

class ModernBookingController
{
    protected CalcomV2Service $calcomV2;
    
    public function __construct(CalcomV2Service $calcomV2)
    {
        $this->calcomV2 = $calcomV2;
    }
    
    public function createBooking(Request $request)
    {
        $booking = $this->calcomV2->bookAppointment(
            $request->event_type_id,
            $request->start_time,
            [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'timeZone' => 'Europe/Berlin'
            ],
            $request->notes,
            ['source' => 'askproai']
        );
        
        return response()->json($booking);
    }
}
```

## Monitoring

### Logs

Monitor migration progress in logs:

```bash
tail -f storage/logs/laravel.log | grep -E "(Cal\.com V2|CalcomMigration)"
```

### Key Metrics to Track

1. **Response Times**: V2 should be faster
2. **Error Rates**: Should remain low
3. **Cache Hit Rates**: Should improve with V2
4. **Circuit Breaker**: Should rarely trigger

### Health Checks

```bash
# Check Cal.com API health
curl https://api.askproai.de/api/health/calcom

# Check specific V2 endpoint
php artisan calcom:test-v2-endpoint --method=getEventTypes
```

## Troubleshooting

### Common Issues

#### 1. Authentication Errors
- Ensure API key is valid for V2
- Check if team slug is configured

#### 2. Different Response Format
- CalcomMigrationService transforms V2 responses to V1 format
- Update code gradually to use V2 format directly

#### 3. Rate Limiting
- V2 has different rate limits
- Circuit breaker helps manage this

### Emergency Rollback

If critical issues occur:

```bash
# Immediate rollback
php artisan calcom:migrate-to-v2 --rollback --all

# Or set in .env
CALCOM_USE_V2_API=false
```

## Migration Checklist

- [ ] Run comparison tests
- [ ] Enable V2 for getEventTypes
- [ ] Monitor for 24 hours
- [ ] Enable V2 for getAvailableSlots
- [ ] Monitor for 24 hours
- [ ] Enable V2 for bookAppointment
- [ ] Monitor for 48 hours
- [ ] Enable V2 for remaining methods
- [ ] Update environment config
- [ ] Remove V1 fallback code
- [ ] Update documentation

## Best Practices

1. **Test in Staging First**: Always test migration in staging environment
2. **Monitor Closely**: Watch logs during initial rollout
3. **Gradual Rollout**: Enable one method at a time
4. **Keep Fallbacks**: Don't remove V1 code immediately
5. **Document Changes**: Update API documentation

## Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Run diagnostics: `php artisan calcom:migrate-to-v2 --status`
- Review Cal.com V2 docs: https://cal.com/docs/api-reference/v2

---

Last Updated: 2025-06-17