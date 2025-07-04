# Booking Orchestrator Context Fix Documentation

## Problem Summary

The `RetellWebhookMCPController` had a TODO comment disabling the booking orchestrator due to a context resolution issue. This meant that booking logic was being handled directly in the `WebhookMCPServer` instead of using the dedicated `MCPBookingOrchestrator`.

## Root Cause

The booking orchestrator requires a company/tenant context to be established before processing bookings. However, webhooks arrive without tenant context - they only contain phone numbers. The context resolution was not happening before calling the booking orchestrator.

## Solution Implemented

### 1. Controller Changes (`RetellWebhookMCPController.php`)

**Before:**
```php
// TODO: Re-enable booking orchestrator after fixing context resolution
// if ($event === 'call_ended' && $this->hasBookingData($payload)) {
//     return $this->processBookingThroughMCP($payload, $correlationId);
// }
```

**After:**
```php
if ($event === 'call_ended' && $this->hasBookingData($payload)) {
    $phoneNumber = $payload['call']['to_number'] ?? $payload['call']['to'] ?? null;
    
    if ($phoneNumber) {
        // Resolve context from phone number first
        $context = $this->contextResolver->resolveFromPhone($phoneNumber);
        
        if ($context['success']) {
            // Set tenant context before calling booking orchestrator
            $this->contextResolver->setTenantContext($context['company']['id']);
            
            try {
                // Now we can safely call the booking orchestrator
                $bookingResult = $this->processBookingThroughMCP($payload, $correlationId);
                return array_merge($result, $bookingResult);
            } finally {
                // Always clear tenant context after processing
                $this->contextResolver->clearTenantContext();
            }
        }
    }
}
```

### 2. Booking Orchestrator Changes (`MCPBookingOrchestrator.php`)

Added checks to use existing context if already set by the controller:

```php
// Check if we already have context (set by controller)
$currentContext = $this->contextResolver->getCurrentContext();

if ($currentContext && $currentContext['success']) {
    $context = $currentContext;
    Log::info('MCP BookingOrchestrator: Using existing context', [
        'company_id' => $context['company']['id'],
        'correlation_id' => $correlationId
    ]);
} else {
    // Resolve context from phone number if not already set
    $context = $this->contextResolver->resolveFromPhone($phoneNumber);
    // ... continue with context resolution
}
```

## Benefits of This Fix

1. **Proper Architecture**: Booking logic is now handled by the dedicated `MCPBookingOrchestrator` instead of being mixed in the webhook server.

2. **Better Separation of Concerns**: 
   - `WebhookMCPServer` handles webhook validation and deduplication
   - `MCPBookingOrchestrator` handles booking business logic

3. **Improved Error Handling**: The booking orchestrator has dedicated error handling and transaction management.

4. **Enhanced Logging**: Better tracking of booking flow with correlation IDs.

5. **Context Safety**: Proper establishment and cleanup of tenant context prevents data leakage between tenants.

## Testing

Use the provided test script to verify the fix:

```bash
php test-booking-orchestrator-fix.php
```

This script will:
1. Test context resolution from phone number
2. Process a test webhook through the controller
3. Verify that the booking orchestrator is being used
4. Check logs for confirmation

## Migration Notes

No database changes are required. This is a code-only fix that re-enables existing functionality.

## Monitoring

After deployment, monitor for:
- Successful booking creations through the orchestrator
- Any context resolution errors in logs
- Performance impact (should be minimal)

Look for these log entries:
- `"MCP Retell: Processing booking through orchestrator"`
- `"MCP BookingOrchestrator: Using existing context"`
- `"MCP Retell: Booking processed successfully"`

## Rollback Plan

If issues occur, the fix can be rolled back by:
1. Commenting out the booking orchestrator call again
2. The system will fall back to using `WebhookMCPServer` for booking logic

## Related Files

- `/app/Http/Controllers/RetellWebhookMCPController.php`
- `/app/Services/MCP/MCPBookingOrchestrator.php`
- `/app/Services/MCP/MCPContextResolver.php`
- `/app/Services/MCP/WebhookMCPServer.php`