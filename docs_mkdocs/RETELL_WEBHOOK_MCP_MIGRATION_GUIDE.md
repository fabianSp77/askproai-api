# Retell Webhook MCP Migration Guide

## Overview

This guide documents the migration of the RetellWebhookController to use the MCP (Modular Component Pattern) architecture. The new implementation provides better modularity, error handling, and tenant isolation.

## Key Changes

### 1. Architecture Migration

**Old Architecture:**
- Direct service calls to WebhookProcessor, CalcomV2Service
- Manual tenant resolution
- Mixed concerns (webhook processing, booking, availability)

**New MCP Architecture:**
- Uses MCPOrchestrator for service routing
- MCPContextResolver for company/branch resolution
- MCPBookingOrchestrator for booking logic
- Clear separation of concerns

### 2. Dependency Injection

**Old Controller:**
```php
public function __construct(
    WebhookProcessor $webhookProcessor, 
    ApiRateLimiter $rateLimiter
)
```

**New MCP Controller:**
```php
public function __construct(
    MCPOrchestrator $mcpOrchestrator,
    MCPContextResolver $contextResolver,
    MCPBookingOrchestrator $bookingOrchestrator,
    ApiRateLimiter $rateLimiter
)
```

### 3. Context Resolution

**Old Approach:**
```php
$toNumber = $callData['call_inbound']['to_number'] ?? null;
$company = Company::where('phone_number', $toNumber)->first();
if (!$company) {
    $company = Company::first(); // Fallback
}
```

**New MCP Approach:**
```php
$context = $this->contextResolver->resolveFromPhone($toNumber);
if (!$context['success']) {
    // Handle with proper error or fallback
}
$this->contextResolver->setTenantContext($context['company']['id']);
```

### 4. Service Calls

**Old Direct Service Calls:**
```php
$calcomService = new CalcomV2Service($company->calcom_api_key);
$availabilityService = new CalcomAvailabilityService($calcomService);
$isAvailable = $availabilityService->isTimeSlotAvailable(...);
```

**New MCP Service Calls:**
```php
$mcpRequest = new MCPRequest(
    service: 'calcom',
    operation: 'checkAvailability',
    params: [...],
    tenantId: $context['company']['id'],
    correlationId: $correlationId
);
$mcpResponse = $this->mcpOrchestrator->route($mcpRequest);
```

### 5. Error Handling

**Old Error Handling:**
```php
try {
    // Process webhook
} catch (\Exception $e) {
    Log::error('Failed to process webhook', [...]);
    // Return success to avoid retries
    return response()->json(['success' => true], 200);
}
```

**New MCP Error Handling:**
```php
try {
    $response = $this->processThroughMCP($request, $correlationId);
    return $this->successResponse($response, $correlationId);
} catch (\App\Exceptions\WebhookSignatureException $e) {
    return $this->errorResponse('Invalid signature', 401, $correlationId);
} catch (\Exception $e) {
    // Proper error logging with correlation ID
    // Still return success but with error details in dev
    return $this->successResponse([
        'processed' => false,
        'error' => app()->environment('local') ? $e->getMessage() : 'Internal error'
    ], $correlationId);
}
```

## Migration Steps

### 1. Update Routes

Add the new MCP routes to your API routes:

```php
// In routes/api.php
require __DIR__.'/api-mcp.php';
```

Or manually add:

```php
Route::prefix('mcp/retell')->middleware([
    'throttle:webhook',
    VerifyRetellSignature::class
])->group(function () {
    Route::post('/webhook', [RetellWebhookMCPController::class, 'processWebhook']);
});
```

### 2. Update Retell.ai Configuration

Update your webhook URL in Retell.ai dashboard:
- Old: `https://api.askproai.de/api/retell/webhook`
- New: `https://api.askproai.de/api/mcp/retell/webhook`

### 3. Enable Migration Mode (Optional)

For gradual migration, enable migration mode in config:

```php
// In config/features.php
'mcp_migration_mode' => true,
```

This will forward old webhook URLs to the new MCP controller.

### 4. Update Environment Variables

No new environment variables required. The MCP system uses existing configurations.

### 5. Test the Migration

Run these tests to verify the migration:

```bash
# Test webhook signature verification
curl -X POST https://api.askproai.de/api/mcp/retell/webhook \
  -H "x-retell-signature: YOUR_SIGNATURE" \
  -H "Content-Type: application/json" \
  -d @test-webhook-payload.json

# Check MCP health
php artisan mcp:health

# Monitor MCP metrics
php artisan mcp:monitor
```

## Benefits of MCP Migration

### 1. **Better Error Handling**
- Centralized error handling in MCPOrchestrator
- Proper error propagation with correlation IDs
- Circuit breaker pattern for external services

### 2. **Improved Tenant Isolation**
- Automatic tenant context resolution
- Secure multi-tenant operations
- No accidental data leakage

### 3. **Enhanced Monitoring**
- Built-in metrics collection
- Performance tracking per service
- Real-time health monitoring

### 4. **Easier Testing**
- Mock MCP services for unit tests
- Isolated component testing
- Better integration test support

### 5. **Scalability**
- Service-based architecture
- Easy to add new services
- Built-in rate limiting and quotas

## Rollback Plan

If issues arise, you can quickly rollback:

1. **Update Retell.ai webhook URL** back to old endpoint
2. **Disable migration mode** in config
3. **Monitor old endpoint** for any issues

The old controller remains functional and unchanged.

## Monitoring

Monitor the migration using:

```bash
# View MCP metrics
php artisan mcp:metrics

# Check webhook processing
tail -f storage/logs/laravel.log | grep "MCP Retell"

# View circuit breaker status
php artisan circuit-breaker:status
```

## Common Issues and Solutions

### Issue: "Service 'webhook' not found"
**Solution:** Ensure MCPServiceProvider is registered and WebhookMCPServer is properly instantiated.

### Issue: "Unable to resolve tenant ID"
**Solution:** Check that phone numbers are properly associated with branches in the phone_numbers table.

### Issue: "Circuit breaker open"
**Solution:** Check external service health (Cal.com, Retell.ai). Reset circuit breaker if needed:
```bash
php artisan circuit-breaker:reset calcom
```

## Future Enhancements

1. **Async Processing**: Move to fully async webhook processing
2. **Event Sourcing**: Store all webhook events for replay
3. **Advanced Analytics**: Track conversion rates, booking patterns
4. **A/B Testing**: Test different booking flows through MCP